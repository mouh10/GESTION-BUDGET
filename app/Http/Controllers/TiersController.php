<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Engagement;
use App\Models\MouvementTresorerie;
use App\Models\TitreRecette;
use App\Models\Tiers;
use App\Services\Numerotation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TiersController extends Controller
{
    public function index(Request $request)
    {
        $tiers = Tiers::with('compte')
            ->where('type', $request->query('type') === 'redevable' ? 'redevable' : 'fournisseur')
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('nom', 'like', $t)->orWhere('code', 'like', $t)->orWhere('telephone', 'like', $t));
            })
            ->orderBy('nom')
            ->paginate(par_page(25))->withQueryString();

        // Restes à régler calculés en une seule requête pour toute la page (au lieu d'une par ligne).
        $ids = $tiers->getCollection()->pluck('id');
        $restes = $request->query('type') === 'redevable'
            ? TitreRecette::whereIn('tiers_id', $ids)->whereIn('statut', ['emis', 'partiellement_recouvre'])
                ->groupBy('tiers_id')->selectRaw('tiers_id, SUM(montant - montant_recouvre) as r')->pluck('r', 'tiers_id')
            : \Illuminate\Support\Facades\DB::table('mandats as m')->join('liquidations as l', 'l.id', '=', 'm.liquidation_id')
                ->join('engagements as e', 'e.id', '=', 'l.engagement_id')->whereIn('e.tiers_id', $ids)
                ->whereIn('m.statut', ['emis', 'pris_en_charge'])->groupBy('e.tiers_id')
                ->selectRaw('e.tiers_id, SUM(m.montant) as r')->pluck('r', 'tiers_id');
        $tiers->getCollection()->each(fn ($t) => $t->reste_calcule = round((float) ($restes[$t->id] ?? 0), 2));

        return view('tiers.index', compact('tiers'));
    }

    public function create(Request $request)
    {
        $type = $request->query('type') === 'redevable' ? 'redevable' : 'fournisseur';

        return view('tiers.form', [
            'tiers' => new Tiers([
                'type' => $type,
                'actif' => true,
                'compte_id' => Compte::where('numero', config('gestion.comptes.'.($type === 'redevable' ? 'redevables' : 'fournisseurs')))->value('id'),
            ]),
            'comptes' => $this->comptesCollectifs(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->valider($request);
        if (empty($data['code'])) {
            $data['code'] = Numerotation::suivant($data['type'] === 'redevable' ? 'RDV-' : 'FRS-', 'tiers', 'code', 3);
        }
        $data['actif'] = true;

        $tiers = Tiers::create($data);

        return redirect()->route('tiers.show', $tiers)->with('succes', "{$tiers->nom} a été ajouté.");
    }

    public function show(Tiers $tiers)
    {
        $exercice = $this->exercice();

        return view('tiers.show', [
            'tiers' => $tiers->load('compte'),
            'engagements' => $tiers->estRedevable() ? collect() : Engagement::with('ligneCredit.nature')
                ->withSum(['liquidations as liquide' => fn ($q) => $q->where('statut', 'validee')], 'montant')
                ->where('tiers_id', $tiers->id)->orderByDesc('date')->limit(20)->get(),
            'titres' => $tiers->estRedevable() ? TitreRecette::with('prevision.nature')->where('tiers_id', $tiers->id)->orderByDesc('date')->limit(20)->get() : collect(),
            'mouvements' => MouvementTresorerie::with('compteTresorerie', 'mandat', 'titreRecette')
                ->where('tiers_id', $tiers->id)->orderByDesc('date')->limit(15)->get(),
            'solde' => $tiers->soldeComptable($exercice),
            'reste' => $tiers->resteARegler(),
            'exercice' => $exercice,
        ]);
    }

    public function edit(Tiers $tiers)
    {
        return view('tiers.form', ['tiers' => $tiers, 'comptes' => $this->comptesCollectifs()]);
    }

    public function update(Request $request, Tiers $tiers)
    {
        $data = $this->valider($request, $tiers);
        $data['actif'] = $request->boolean('actif');
        if (empty($data['code'])) {
            unset($data['code']);
        }

        if ((int) $data['compte_id'] !== $tiers->compte_id && $tiers->lignesEcriture()->exists()) {
            return back()->withInput()->with('erreur', 'Ce tiers a déjà des écritures : son compte collectif ne peut plus être changé.');
        }

        $tiers->update($data);

        return redirect()->route('tiers.show', $tiers)->with('succes', 'Fiche mise à jour.');
    }

    public function destroy(Tiers $tiers)
    {
        if ($tiers->engagements()->exists() || $tiers->titresRecette()->exists() || $tiers->marches()->exists() || $tiers->lignesEcriture()->exists()) {
            return back()->with('erreur', 'Ce tiers est utilisé (engagements, titres, marchés ou écritures) : désactivez-le plutôt.');
        }

        $tiers->delete();

        return redirect()->route('tiers.index')->with('succes', 'Tiers supprimé.');
    }

    protected function valider(Request $request, ?Tiers $tiers = null): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(Tiers::TYPES))],
            'code' => ['nullable', 'string', 'max:30', Rule::unique('tiers', 'code')->ignore($tiers?->id)],
            'nom' => ['required', 'string', 'max:255'],
            'ninea' => ['nullable', 'string', 'max:50'],
            'rib' => ['nullable', 'string', 'max:100'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'compte_id' => ['required', 'exists:comptes,id'],
        ]);
    }

    protected function comptesCollectifs()
    {
        return Compte::actifs()->where('classe', 4)->whereRaw('LENGTH(numero) >= 3')->orderBy('numero')->get();
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\CompteTresorerie;
use App\Models\Journal;
use App\Models\MouvementTresorerie;
use App\Models\Tiers;
use App\Services\Comptabilite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TresorerieController extends Controller
{
    public function __construct(protected Comptabilite $compta)
    {
    }

    public function index()
    {
        $comptes = CompteTresorerie::with('compte', 'journal')->orderByDesc('actif')->orderBy('nom')->get()
            ->map(fn ($c) => (object) ['compte' => $c, 'solde' => $c->solde()]);

        $derniers = MouvementTresorerie::with('compteTresorerie', 'compte', 'tiers')
            ->orderByDesc('date')->orderByDesc('id')->limit(15)->get();

        return view('tresorerie.index', [
            'comptes' => $comptes,
            'total' => round($comptes->where('compte.actif', true)->sum('solde'), 2),
            'derniers' => $derniers,
        ]);
    }

    public function create()
    {
        return view('tresorerie.form', $this->donneesFormulaire() + [
            'tresorerie' => new CompteTresorerie(['type' => 'banque', 'actif' => true, 'solde_initial' => 0]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(CompteTresorerie::TYPES))],
            'numero' => ['nullable', 'string', 'max:100'],
            'compte_id' => ['required', 'exists:comptes,id'],
            'journal_id' => ['required', 'exists:journaux,id'],
            'solde_initial' => ['nullable', 'numeric'],
            'date_ouverture' => ['required_with:solde_initial', 'nullable', 'date'],
            'contrepartie_id' => ['nullable', 'exists:comptes,id'],
        ]);

        $compte = Compte::find($data['compte_id']);
        if ($compte->classe !== 5) {
            return back()->withInput()->with('erreur', 'Le compte comptable doit appartenir à la classe 5 (trésorerie).');
        }

        $tresorerie = DB::transaction(function () use ($data) {
            $tresorerie = CompteTresorerie::create([
                'nom' => $data['nom'],
                'type' => $data['type'],
                'numero' => $data['numero'] ?? null,
                'compte_id' => $data['compte_id'],
                'journal_id' => $data['journal_id'],
                'solde_initial' => $data['solde_initial'] ?? 0,
                'actif' => true,
            ]);

            if ((float) $tresorerie->solde_initial != 0.0) {
                $contrepartie = $data['contrepartie_id'] ?? Compte::where('numero', config('gestion.comptes.capital'))->value('id');
                $this->compta->ouvertureTresorerie($tresorerie, (int) $contrepartie, $data['date_ouverture'] ?? now()->toDateString());
            }

            return $tresorerie;
        });

        return redirect()->route('tresorerie.show', $tresorerie)->with('succes', 'Compte de trésorerie créé.');
    }

    public function show(Request $request, CompteTresorerie $tresorerie)
    {
        $mouvements = $tresorerie->mouvements()->with('compte', 'tiers', 'mandat', 'titreRecette', 'ecriture')
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
            ->when($request->filled('du'), fn ($q) => $q->whereDate('date', '>=', $request->du))
            ->when($request->filled('au'), fn ($q) => $q->whereDate('date', '<=', $request->au))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(30)->withQueryString();

        return view('tresorerie.show', [
            'tresorerie' => $tresorerie->load('compte', 'journal'),
            'mouvements' => $mouvements,
            'solde' => $tresorerie->solde(),
            'entrees' => (float) $tresorerie->mouvements()->where('type', 'encaissement')->sum('montant'),
            'sorties' => (float) $tresorerie->mouvements()->where('type', 'decaissement')->sum('montant'),
        ]);
    }

    public function edit(CompteTresorerie $tresorerie)
    {
        return view('tresorerie.form', $this->donneesFormulaire() + ['tresorerie' => $tresorerie]);
    }

    public function update(Request $request, CompteTresorerie $tresorerie)
    {
        $data = $request->validate([
            'nom' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(array_keys(CompteTresorerie::TYPES))],
            'numero' => ['nullable', 'string', 'max:100'],
            'journal_id' => ['required', 'exists:journaux,id'],
        ]);
        $data['actif'] = $request->boolean('actif');

        $tresorerie->update($data);

        return redirect()->route('tresorerie.show', $tresorerie)->with('succes', 'Compte de trésorerie mis à jour.');
    }

    public function nouveauMouvement(Request $request, CompteTresorerie $tresorerie)
    {
        return view('tresorerie.mouvement', [
            'tresorerie' => $tresorerie,
            'type' => $request->query('type') === 'encaissement' ? 'encaissement' : 'decaissement',
            'comptes' => Compte::saisissables()->where('id', '!=', $tresorerie->compte_id)->get(),
            'tiers' => Tiers::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function enregistrerMouvement(Request $request, CompteTresorerie $tresorerie)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(MouvementTresorerie::TYPES))],
            'date' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'gt:0'],
            'libelle' => ['required', 'string', 'max:255'],
            'mode' => ['required', Rule::in(array_keys(MouvementTresorerie::MODES))],
            'reference' => ['nullable', 'string', 'max:100'],
            'compte_id' => ['required', 'exists:comptes,id'],
            'tiers_id' => ['nullable', 'exists:tiers,id'],
        ]);

        $this->compta->enregistrerMouvement($tresorerie, $data);

        return redirect()->route('tresorerie.show', $tresorerie)->with('succes', MouvementTresorerie::TYPES[$data['type']].' de '.fcfa($data['montant']).' enregistré.');
    }

    public function annulerMouvement(MouvementTresorerie $mouvement)
    {
        $tresorerie = $mouvement->compte_tresorerie_id;
        $this->compta->annulerMouvement($mouvement);

        return redirect()->route('tresorerie.show', $tresorerie)->with('succes', 'Mouvement annulé (écriture contre-passée).');
    }

    public function virement()
    {
        return view('tresorerie.virement', [
            'tresoreries' => CompteTresorerie::where('actif', true)->orderBy('nom')->get()
                ->map(fn ($c) => (object) ['compte' => $c, 'solde' => $c->solde()]),
        ]);
    }

    public function enregistrerVirement(Request $request)
    {
        $data = $request->validate([
            'source_id' => ['required', 'exists:comptes_tresorerie,id'],
            'destination_id' => ['required', 'exists:comptes_tresorerie,id', 'different:source_id'],
            'date' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'gt:0'],
            'libelle' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
        ], ['destination_id.different' => 'Choisissez deux comptes différents.']);

        $this->compta->virement(
            CompteTresorerie::findOrFail($data['source_id']),
            CompteTresorerie::findOrFail($data['destination_id']),
            $data
        );

        return redirect()->route('tresorerie.index')->with('succes', 'Virement de '.fcfa($data['montant']).' enregistré.');
    }

    protected function donneesFormulaire(): array
    {
        return [
            'comptes' => Compte::saisissables()->where('classe', 5)->get(),
            'journaux' => Journal::whereIn('type', ['banque', 'caisse'])->orderBy('code')->get(),
            'contreparties' => Compte::saisissables()->whereIn('classe', [1, 4])->get(),
        ];
    }
}

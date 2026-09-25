<?php

namespace App\Http\Controllers;

use App\Models\Engagement;
use App\Models\LigneCredit;
use App\Models\Liquidation;
use App\Models\Marche;
use App\Models\Programme;
use App\Models\Service;
use App\Models\Tiers;
use App\Services\Credits;
use App\Services\Depenses;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EngagementController extends Controller
{
    public function __construct(protected Depenses $depenses, protected Credits $credits)
    {
    }

    public function index(Request $request)
    {
        $exercice = $this->exercice();

        $engagements = Engagement::with('tiers', 'ligneCredit.action.programme', 'ligneCredit.service', 'ligneCredit.nature')
            ->withSum(['liquidations as liquide' => fn ($q) => $q->where('statut', 'validee')], 'montant')
            ->where('exercice_id', $exercice->id)
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('programme_id'), fn ($q) => $q->whereHas('ligneCredit.action', fn ($a) => $a->where('programme_id', $request->programme_id)))
            ->when($request->filled('service_id'), fn ($q) => $q->whereHas('ligneCredit', fn ($l) => $l->where('service_id', $request->service_id)))
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('numero', 'like', $t)->orWhere('objet', 'like', $t)->orWhereHas('tiers', fn ($x) => $x->where('nom', 'like', $t)));
            })
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        $compteurs = Engagement::where('exercice_id', $exercice->id)->selectRaw('statut, COUNT(*) as n, SUM(montant) as total')->groupBy('statut')->get()->keyBy('statut');

        return view('engagements.index', [
            'engagements' => $engagements,
            'compteurs' => $compteurs,
            'programmes' => Programme::orderBy('code')->get(),
            'services' => Service::orderBy('code')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $exercice = $this->exercice();
        $marche = $request->filled('marche_id') ? Marche::find($request->marche_id) : null;

        return view('engagements.form', $this->donnees() + [
            'engagement' => new Engagement([
                'date' => $exercice->contient(now()) ? now() : $exercice->date_fin,
                'type' => $marche ? 'marche' : 'bon_commande',
                'ligne_credit_id' => $request->query('ligne_credit_id', $marche?->ligne_credit_id),
                'marche_id' => $marche?->id,
                'tiers_id' => $marche?->tiers_id,
                'objet' => $marche?->objet,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $engagement = $this->depenses->enregistrerEngagement($this->valider($request));

        if ($request->boolean('soumettre')) {
            $this->depenses->soumettre($engagement);

            return redirect()->route('engagements.show', $engagement)->with('succes', "Engagement {$engagement->numero} transmis au contrôle financier.");
        }

        return redirect()->route('engagements.show', $engagement)->with('succes', "Engagement {$engagement->numero} enregistré en brouillon.");
    }

    public function show(Engagement $engagement)
    {
        $engagement->load('tiers', 'marche', 'viseur', 'user', 'ligneCredit.action.programme', 'ligneCredit.service', 'ligneCredit.nature', 'liquidations.mandats');

        return view('engagements.show', [
            'engagement' => $engagement,
            's' => $this->credits->pourLigne($engagement->ligneCredit),
        ]);
    }

    public function imprimer(Engagement $engagement)
    {
        $engagement->load('tiers', 'marche', 'viseur', 'ligneCredit.action.programme', 'ligneCredit.service', 'ligneCredit.nature');

        return view('engagements.imprimer', compact('engagement'));
    }

    public function edit(Engagement $engagement)
    {
        if (! $engagement->estModifiable()) {
            return redirect()->route('engagements.show', $engagement)->with('erreur', 'Seul un engagement en brouillon ou rejeté peut être modifié.');
        }

        return view('engagements.form', $this->donnees() + ['engagement' => $engagement]);
    }

    public function update(Request $request, Engagement $engagement)
    {
        $this->depenses->enregistrerEngagement($this->valider($request), $engagement);

        if ($request->boolean('soumettre')) {
            $this->depenses->soumettre($engagement->fresh());

            return redirect()->route('engagements.show', $engagement)->with('succes', 'Engagement transmis au contrôle financier.');
        }

        return redirect()->route('engagements.show', $engagement)->with('succes', 'Engagement mis à jour.');
    }

    public function destroy(Engagement $engagement)
    {
        if ($engagement->statut !== 'brouillon') {
            return back()->with('erreur', 'Seul un brouillon peut être supprimé. Utilisez « Annuler ».');
        }
        $engagement->delete();

        return redirect()->route('engagements.index')->with('succes', 'Brouillon supprimé.');
    }

    public function soumettre(Engagement $engagement)
    {
        $this->depenses->soumettre($engagement);

        return back()->with('succes', 'Engagement transmis au contrôle financier.');
    }

    public function viser(Engagement $engagement)
    {
        $this->depenses->viser($engagement);

        return back()->with('succes', "Visa apposé sur l'engagement {$engagement->numero}.");
    }

    public function rejeter(Request $request, Engagement $engagement)
    {
        $request->validate(['motif' => ['required', 'string', 'max:2000']], ['motif.required' => 'Indiquez le motif du rejet.']);
        $this->depenses->rejeter($engagement, $request->motif);

        return back()->with('succes', "Engagement {$engagement->numero} rejeté et renvoyé à l'ordonnateur.");
    }

    public function annuler(Engagement $engagement)
    {
        $this->depenses->annulerEngagement($engagement);

        return back()->with('succes', 'Engagement annulé : les crédits sont libérés.');
    }

    public function liquider(Request $request, Engagement $engagement)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'gt:0'],
            'reference_facture' => ['nullable', 'string', 'max:100'],
            'date_service_fait' => ['nullable', 'date'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'mandater' => ['nullable', 'boolean'],
        ]);

        $liquidation = $this->depenses->liquider($engagement, $data);
        $message = "Liquidation {$liquidation->numero} enregistrée.";

        if ($request->boolean('mandater')) {
            $mandat = $this->depenses->emettreMandat($liquidation, $data['date']);
            $message .= " Mandat {$mandat->numero} émis et transmis au comptable.";
        }

        return back()->with('succes', $message);
    }

    public function annulerLiquidation(Liquidation $liquidation)
    {
        $this->depenses->annulerLiquidation($liquidation);

        return back()->with('succes', 'Liquidation annulée.');
    }

    protected function valider(Request $request): array
    {
        return $request->validate([
            'ligne_credit_id' => ['required', 'exists:lignes_credit,id'],
            'tiers_id' => ['required', Rule::exists('tiers', 'id')->where('type', 'fournisseur')],
            'marche_id' => ['nullable', 'exists:marches,id'],
            'date' => ['required', 'date'],
            'type' => ['required', Rule::in(array_keys(Engagement::TYPES))],
            'objet' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'gt:0'],
        ], ['tiers_id.required' => 'Choisissez le bénéficiaire.']);
    }

    protected function donnees(): array
    {
        $exercice = $this->exercice();
        $situation = $this->credits->situation($exercice);

        return [
            'situation' => $situation,
            'beneficiaires' => Tiers::where('type', 'fournisseur')->where('actif', true)->orderBy('nom')->get(),
            'marches' => Marche::with('tiers')->where('exercice_id', $exercice->id)->where('statut', 'en_cours')->orderBy('numero')->get(),
        ];
    }
}

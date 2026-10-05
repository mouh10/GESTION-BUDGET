<?php

namespace App\Http\Controllers;

use App\Models\CompteTresorerie;
use App\Models\MouvementTresorerie;
use App\Models\PrevisionRecette;
use App\Models\Tiers;
use App\Models\TitreRecette;
use App\Services\Recettes;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TitreRecetteController extends Controller
{
    public function __construct(protected Recettes $recettes)
    {
    }

    public function index(Request $request)
    {
        $exercice = $this->exercice();
        $titres = TitreRecette::with('tiers', 'prevision.nature')
            ->where('exercice_id', $exercice->id)
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('numero', 'like', $t)->orWhere('objet', 'like', $t)->orWhereHas('tiers', fn ($x) => $x->where('nom', 'like', $t)));
            })
            ->orderByDesc('date')->orderByDesc('id')->paginate(par_page(25))->withQueryString();

        $totaux = TitreRecette::where('exercice_id', $exercice->id)->where('statut', '!=', 'annule')
            ->selectRaw('COALESCE(SUM(montant),0) as emis, COALESCE(SUM(montant_recouvre),0) as recouvre')->first();

        return view('titres.index', compact('titres', 'totaux'));
    }

    public function create(Request $request)
    {
        $exercice = $this->exercice();

        return view('titres.form', [
            'titre' => new TitreRecette(['date' => $exercice->contient(now()) ? now() : $exercice->date_fin, 'prevision_recette_id' => $request->query('prevision_recette_id')]),
            'previsions' => PrevisionRecette::with('nature', 'service')->where('exercice_id', $exercice->id)->get(),
            'redevables' => Tiers::where('type', 'redevable')->where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'prevision_recette_id' => ['required', new \App\Rules\Accessible(\App\Models\PrevisionRecette::class)],
            'tiers_id' => ['required', Rule::exists('tiers', 'id')->where('type', 'redevable')],
            'date' => ['required', 'date'],
            'date_echeance' => ['nullable', 'date', 'after_or_equal:date'],
            'objet' => ['required', 'string', 'max:255'],
            'montant' => ['required', 'numeric', 'gt:0'],
        ], ['tiers_id.required' => 'Choisissez le redevable.']);

        $titre = $this->recettes->emettre($data);

        return redirect()->route('titres.show', $titre)->with('succes', "Titre de recette {$titre->numero} émis et pris en charge.");
    }

    public function show(TitreRecette $titre)
    {
        $titre->load('tiers', 'prevision.nature', 'prevision.service', 'ecriture', 'recouvrements.compteTresorerie');

        return view('titres.show', compact('titre'));
    }

    public function imprimer(TitreRecette $titre)
    {
        $titre->load('tiers', 'exercice', 'prevision.nature.compte', 'prevision.service', 'ecriture', 'recouvrements.compteTresorerie');

        return view('titres.imprimer', compact('titre'));
    }

    public function annuler(TitreRecette $titre)
    {
        $this->recettes->annuler($titre);

        return back()->with('succes', "Titre {$titre->numero} annulé.");
    }

    public function recouvrement(TitreRecette $titre)
    {
        if (! $titre->peutEtreRecouvre()) {
            return redirect()->route('titres.show', $titre)->with('erreur', 'Ce titre ne peut plus être recouvré.');
        }

        return view('titres.recouvrement', [
            'titre' => $titre->load('tiers'),
            'tresoreries' => CompteTresorerie::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function recouvrer(Request $request, TitreRecette $titre)
    {
        $data = $request->validate([
            'compte_tresorerie_id' => ['required', 'exists:comptes_tresorerie,id'],
            'date' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'gt:0'],
            'mode' => ['required', Rule::in(array_keys(MouvementTresorerie::MODES))],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $this->recettes->recouvrer($titre, CompteTresorerie::findOrFail($data['compte_tresorerie_id']), $data);

        return redirect()->route('titres.show', $titre)->with('succes', 'Recouvrement de '.fcfa($data['montant']).' enregistré.');
    }
}

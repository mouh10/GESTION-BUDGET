<?php

namespace App\Http\Controllers;

use App\Models\LigneCredit;
use App\Models\Marche;
use App\Models\Tiers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MarcheController extends Controller
{
    public function index(Request $request)
    {
        $exercice = $this->exercice();
        $marches = Marche::with('tiers')->withSum(['engagements as engage' => fn ($q) => $q->whereIn('statut', ['soumis', 'vise'])], 'montant')
            ->where('exercice_id', $exercice->id)
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('numero', 'like', '%'.$request->q.'%')->orWhere('objet', 'like', '%'.$request->q.'%')))
            ->orderByDesc('date_signature')->get();

        return view('marches.index', compact('marches'));
    }

    public function create()
    {
        return view('marches.form', $this->donnees() + ['marche' => new Marche(['statut' => 'en_cours', 'type' => 'fournitures', 'mode_passation' => 'aoo', 'date_signature' => now()])]);
    }

    public function store(Request $request)
    {
        $exercice = $this->exercice();
        $marche = Marche::create($this->valider($request) + ['exercice_id' => $exercice->id]);

        return redirect()->route('marches.show', $marche)->with('succes', 'Marché enregistré.');
    }

    public function show(Marche $marche)
    {
        $marche->load('tiers', 'ligneCredit.action.programme', 'ligneCredit.nature', 'ligneCredit.service', 'engagements.liquidations.mandats');
        $engage = $marche->montantEngage();
        $paye = $marche->engagements->sum(fn ($e) => $e->montantPaye());

        return view('marches.show', compact('marche', 'engage', 'paye'));
    }

    public function edit(Marche $marche)
    {
        return view('marches.form', $this->donnees() + ['marche' => $marche]);
    }

    public function update(Request $request, Marche $marche)
    {
        $data = $this->valider($request, $marche);
        if ((float) $data['montant'] < $marche->montantEngage()) {
            return back()->withInput()->with('erreur', 'Le montant ne peut pas être inférieur au montant déjà engagé ('.fcfa($marche->montantEngage()).').');
        }
        $marche->update($data);

        return redirect()->route('marches.show', $marche)->with('succes', 'Marché mis à jour.');
    }

    protected function valider(Request $request, ?Marche $marche = null): array
    {
        return $request->validate([
            'numero' => ['required', 'string', 'max:50', Rule::unique('marches', 'numero')->ignore($marche?->id)],
            'objet' => ['required', 'string', 'max:255'],
            'tiers_id' => ['required', Rule::exists('tiers', 'id')->where('type', 'fournisseur')],
            'type' => ['required', Rule::in(array_keys(Marche::TYPES))],
            'mode_passation' => ['required', Rule::in(array_keys(Marche::MODES))],
            'montant' => ['required', 'numeric', 'gt:0'],
            'date_signature' => ['nullable', 'date'],
            'date_fin' => ['nullable', 'date', 'after_or_equal:date_signature'],
            'statut' => ['required', Rule::in(array_keys(Marche::STATUTS))],
            'ligne_credit_id' => ['nullable', new \App\Rules\Accessible(\App\Models\LigneCredit::class)],
        ]);
    }

    protected function donnees(): array
    {
        $exercice = $this->exercice();

        return [
            'fournisseurs' => Tiers::where('type', 'fournisseur')->where('actif', true)->orderBy('nom')->get(),
            'lignes' => LigneCredit::with('action.programme', 'service', 'nature')->where('exercice_id', $exercice->id)->get()
                ->sortBy(fn ($l) => $l->imputation()),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Ecriture;
use App\Models\Journal;
use App\Models\Tiers;
use App\Services\Comptabilite;
use Illuminate\Http\Request;

class EcritureController extends Controller
{
    public function __construct(protected Comptabilite $compta)
    {
    }

    public function index(Request $request)
    {
        $exercice = $this->exercice();

        $ecritures = Ecriture::with(['journal', 'lignes'])
            ->where('exercice_id', $exercice->id)
            ->when($request->filled('journal_id'), fn ($q) => $q->where('journal_id', $request->journal_id))
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('du'), fn ($q) => $q->whereDate('date', '>=', $request->du))
            ->when($request->filled('au'), fn ($q) => $q->whereDate('date', '<=', $request->au))
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('libelle', 'like', $t)->orWhere('numero_piece', 'like', $t)->orWhere('reference', 'like', $t));
            })
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(par_page(25))
            ->withQueryString();

        return view('ecritures.index', [
            'ecritures' => $ecritures,
            'journaux' => Journal::orderBy('code')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $exercice = $this->exercice();
        $date = $exercice->contient(now()) ? now() : $exercice->date_fin;

        return view('ecritures.form', $this->donneesFormulaire() + [
            'ecriture' => new Ecriture([
                'date' => $date,
                'journal_id' => $request->query('journal_id', Journal::where('code', 'OD')->value('id')),
            ]),
            'lignes' => [],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validerRequete($request);

        $ecriture = $this->compta->enregistrer($data, $request->input('lignes', []), $request->boolean('valider'));

        return redirect()->route('ecritures.show', $ecriture)
            ->with('succes', "Écriture {$ecriture->numero_piece} enregistrée".($ecriture->estValidee() ? ' et validée.' : ' en brouillon.'));
    }

    public function show(Ecriture $ecriture)
    {
        $ecriture->load('lignes.compte', 'lignes.tiers', 'journal', 'exercice', 'user');

        return view('ecritures.show', compact('ecriture'));
    }

    public function edit(Ecriture $ecriture)
    {
        if ($ecriture->estValidee() || $ecriture->estAutomatique()) {
            return redirect()->route('ecritures.show', $ecriture)->with('erreur', 'Cette écriture ne peut plus être modifiée.');
        }

        $ecriture->load('lignes');

        return view('ecritures.form', $this->donneesFormulaire() + [
            'ecriture' => $ecriture,
            'lignes' => $ecriture->lignes->toArray(),
        ]);
    }

    public function update(Request $request, Ecriture $ecriture)
    {
        $data = $this->validerRequete($request);

        $this->compta->modifier($ecriture, $data, $request->input('lignes', []), $request->boolean('valider'));

        return redirect()->route('ecritures.show', $ecriture)->with('succes', 'Écriture mise à jour.');
    }

    public function destroy(Ecriture $ecriture)
    {
        $this->compta->supprimer($ecriture);

        return redirect()->route('ecritures.index')->with('succes', 'Écriture supprimée.');
    }

    public function valider(Ecriture $ecriture)
    {
        $this->compta->valider($ecriture);

        return back()->with('succes', "Écriture {$ecriture->numero_piece} validée.");
    }

    public function contrePasser(Ecriture $ecriture)
    {
        if (! $ecriture->estValidee()) {
            return back()->with('erreur', 'Seule une écriture validée peut être contre-passée. Un brouillon peut simplement être supprimé.');
        }
        if ($ecriture->estAutomatique()) {
            return back()->with('erreur', "Cette écriture est automatique (mandat, titre, trésorerie) : annulez l'opération depuis son origine.");
        }

        $inverse = $this->compta->contrePasser($ecriture);

        return redirect()->route('ecritures.show', $inverse)->with('succes', "Contre-passation {$inverse->numero_piece} enregistrée.");
    }

    protected function validerRequete(Request $request): array
    {
        return $request->validate([
            'journal_id' => ['required', 'exists:journaux,id'],
            'date' => ['required', 'date'],
            'libelle' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'lignes' => ['required', 'array', 'min:2'],
            'lignes.*.compte_id' => ['nullable', 'exists:comptes,id'],
            'lignes.*.tiers_id' => ['nullable', 'exists:tiers,id'],
            'lignes.*.libelle' => ['nullable', 'string', 'max:255'],
            'lignes.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lignes.*.credit' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    protected function donneesFormulaire(): array
    {
        return [
            'journaux' => Journal::orderBy('code')->get(),
            'comptes' => Compte::saisissables()->get(['id', 'numero', 'libelle']),
            'tiers' => Tiers::orderBy('nom')->get(['id', 'nom', 'type']),
        ];
    }
}

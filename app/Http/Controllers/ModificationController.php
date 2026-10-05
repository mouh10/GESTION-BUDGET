<?php

namespace App\Http\Controllers;

use App\Models\LigneCredit;
use App\Models\Modification;
use App\Services\Credits;
use App\Services\Numerotation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** Actes de modification budgétaire. */
class ModificationController extends Controller
{
    public function __construct(protected Credits $credits)
    {
    }

    public function index(Request $request)
    {
        $exercice = $this->exercice();

        return view('modifications.index', [
            'modifications' => Modification::with('lignes')->withCount('lignes')
                ->where('exercice_id', $exercice->id)
                ->when($request->filled('type'), fn ($q) => $q->where('type', $request->type))
                ->orderByDesc('date')->orderByDesc('id')->get(),
        ]);
    }

    public function create()
    {
        $exercice = $this->exercice();

        return view('modifications.form', [
            'modification' => new Modification(['date' => $exercice->contient(now()) ? now() : $exercice->date_fin, 'type' => 'virement']),
            'lignes' => [],
            'situation' => $this->credits->situation($exercice),
        ]);
    }

    public function store(Request $request)
    {
        $exercice = $this->exercice();
        abort_if($exercice->cloture, 403, 'Exercice clôturé.');
        $data = $this->valider($request, $exercice);

        $modification = DB::transaction(function () use ($data, $exercice) {
            $modification = Modification::create(collect($data)->except('lignes')->all() + [
                'exercice_id' => $exercice->id,
                'numero' => Numerotation::suivant('MB-'.$exercice->annee().'-', 'modifications', 'numero'),
                'statut' => 'brouillon',
                'user_id' => auth()->id(),
            ]);
            $modification->lignes()->createMany($data['lignes']);

            return $modification;
        });

        return redirect()->route('modifications.show', $modification)->with('succes', "Acte {$modification->numero} enregistré en brouillon. Il doit être approuvé pour prendre effet.");
    }

    public function show(Modification $modification)
    {
        $modification->load('lignes.ligneCredit.action.programme', 'lignes.ligneCredit.service', 'lignes.ligneCredit.nature', 'user');
        $situation = $this->credits->situation($modification->exercice, ['ids' => $modification->lignes->pluck('ligne_credit_id')->all()])
            ->keyBy(fn ($r) => $r->ligne->id);

        return view('modifications.show', compact('modification', 'situation'));
    }

    public function imprimer(Modification $modification)
    {
        $modification->load('lignes.ligneCredit.action.programme', 'lignes.ligneCredit.service', 'lignes.ligneCredit.nature', 'user', 'exercice');

        return view('modifications.imprimer', compact('modification'));
    }

    public function edit(Modification $modification)
    {
        if ($modification->estApprouvee()) {
            return redirect()->route('modifications.show', $modification)->with('erreur', 'Un acte approuvé ne peut plus être modifié.');
        }

        return view('modifications.form', [
            'modification' => $modification,
            'lignes' => $modification->lignes->map(fn ($l) => ['ligne_credit_id' => $l->ligne_credit_id, 'ae' => (float) $l->ae, 'cp' => (float) $l->cp])->all(),
            'situation' => $this->credits->situation($modification->exercice),
        ]);
    }

    public function update(Request $request, Modification $modification)
    {
        abort_if($modification->estApprouvee(), 403, 'Acte déjà approuvé.');
        $data = $this->valider($request, $modification->exercice);

        DB::transaction(function () use ($modification, $data) {
            $modification->update(collect($data)->except('lignes')->all());
            $modification->lignes()->delete();
            $modification->lignes()->createMany($data['lignes']);
        });

        return redirect()->route('modifications.show', $modification)->with('succes', 'Acte mis à jour.');
    }

    public function destroy(Modification $modification)
    {
        if ($modification->estApprouvee()) {
            return back()->with('erreur', 'Un acte approuvé ne peut pas être supprimé. Passez un acte inverse.');
        }
        $modification->delete();

        return redirect()->route('modifications.index')->with('succes', 'Acte supprimé.');
    }

    public function approuver(Modification $modification)
    {
        $this->credits->approuver($modification);

        return back()->with('succes', "Acte {$modification->numero} approuvé : les crédits sont mis à jour.");
    }

    protected function valider(Request $request, $exercice): array
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(Modification::TYPES))],
            'date' => ['required', 'date'],
            'reference_acte' => ['nullable', 'string', 'max:150'],
            'motif' => ['nullable', 'string', 'max:2000'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.ligne_credit_id' => ['required', new \App\Rules\Accessible(\App\Models\LigneCredit::class, fn ($q) => $q->where('exercice_id', $exercice->id))],
            'lignes.*.ae' => ['nullable', 'numeric'],
            'lignes.*.cp' => ['nullable', 'numeric'],
        ], ['lignes.required' => 'Ajoutez au moins une ligne de crédits.']);

        if (! $exercice->contient($data['date'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['date' => "La date doit être comprise dans l'exercice."]);
        }

        $data['lignes'] = collect($data['lignes'])->map(function ($l) use ($data) {
            $ae = round((float) ($l['ae'] ?? 0), 2);
            $cp = round((float) ($l['cp'] ?? 0), 2);
            // Hors investissement, AE et CP évoluent ensemble.
            $ligne = LigneCredit::with('nature')->find($l['ligne_credit_id']);
            if (! in_array($ligne->nature->titre, \App\Models\Nature::TITRES_AE_DISTINCTES, true)) {
                $ae = $cp;
            }

            return ['ligne_credit_id' => (int) $l['ligne_credit_id'], 'ae' => $ae, 'cp' => $cp];
        })->filter(fn ($l) => $l['ae'] != 0 || $l['cp'] != 0)->values()->all();

        if (empty($data['lignes'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['lignes' => 'Saisissez au moins un montant.']);
        }

        return $data;
    }
}

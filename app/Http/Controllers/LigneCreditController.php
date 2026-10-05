<?php

namespace App\Http\Controllers;

use App\Models\Action;
use App\Models\Engagement;
use App\Models\LigneCredit;
use App\Models\ModificationLigne;
use App\Models\Nature;
use App\Models\Programme;
use App\Models\Service;
use App\Services\Credits;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Budget des dépenses : lignes de crédits (AE / CP). */
class LigneCreditController extends Controller
{
    public function __construct(protected Credits $credits)
    {
    }

    public function index(Request $request)
    {
        $exercice = $this->exercice();
        $filtres = $request->only(['programme_id', 'service_id', 'titre', 'source']);
        $situation = $this->credits->situation($exercice, $filtres);

        return view('credits.index', [
            'exercice' => $exercice,
            'groupes' => $this->credits->regrouper($situation, 'programme'),
            'totaux' => $this->credits->totaux($situation),
            'programmes' => Programme::orderBy('code')->get(),
            'services' => Service::orderBy('code')->get(),
        ]);
    }

    public function create(Request $request)
    {
        $exercice = $this->exercice();

        return view('credits.form', $this->donnees() + [
            'ligne' => new LigneCredit(['exercice_id' => $exercice->id, 'source' => 'etat', 'action_id' => $request->query('action_id')]),
            'exercice' => $exercice,
            'modifiable' => true,
        ]);
    }

    public function store(Request $request)
    {
        $exercice = $this->exercice();
        abort_if($exercice->cloture, 403, 'Exercice clôturé.');

        $data = $this->valider($request, $exercice->id);
        $ligne = LigneCredit::create($data + ['exercice_id' => $exercice->id]);

        return redirect()->route('credits.index')->with('succes', 'Ligne de crédits '.$ligne->imputation().' créée.');
    }

    public function show(LigneCredit $credit)
    {
        $credit->load('action.programme', 'service', 'nature', 'exercice');

        return view('credits.show', [
            'ligne' => $credit,
            's' => $this->credits->pourLigne($credit),
            'engagements' => Engagement::with('tiers')->where('ligne_credit_id', $credit->id)->orderByDesc('date')->orderByDesc('id')->paginate(par_page(20)),
            'modifications' => ModificationLigne::with('modification')->where('ligne_credit_id', $credit->id)->orderByDesc('id')->get(),
        ]);
    }

    public function edit(LigneCredit $credit)
    {
        return view('credits.form', $this->donnees() + [
            'ligne' => $credit,
            'exercice' => $credit->exercice,
            'modifiable' => ! $credit->estUtilisee(),
        ]);
    }

    public function update(Request $request, LigneCredit $credit)
    {
        abort_if($credit->exercice->cloture, 403, 'Exercice clôturé.');

        if ($credit->estUtilisee()) {
            // Après exécution, seuls le libellé peut changer ; les montants évoluent par acte de modification.
            $credit->update($request->validate(['libelle' => ['nullable', 'string', 'max:255']]));

            return redirect()->route('credits.show', $credit)->with('succes', 'Libellé mis à jour. Les montants se modifient par un acte de modification budgétaire.');
        }

        $credit->update($this->valider($request, $credit->exercice_id, $credit));

        return redirect()->route('credits.show', $credit)->with('succes', 'Ligne de crédits mise à jour.');
    }

    public function destroy(LigneCredit $credit)
    {
        if ($credit->estUtilisee()) {
            return back()->with('erreur', 'Cette ligne a déjà été exécutée ou modifiée : elle ne peut pas être supprimée.');
        }
        $credit->delete();

        return redirect()->route('credits.index')->with('succes', 'Ligne supprimée.');
    }

    protected function valider(Request $request, int $exerciceId, ?LigneCredit $ligne = null): array
    {
        $data = $request->validate([
            'action_id' => ['required', 'exists:actions,id'],
            'service_id' => ['required', 'exists:services,id', \Illuminate\Validation\Rule::in(\App\Models\Scopes\ParService::servicesAutorises() ?? \App\Models\Service::pluck('id')->all())],
            'nature_id' => ['required', Rule::exists('natures', 'id')->where('type', 'depense')],
            'source' => ['required', Rule::in(array_keys(LigneCredit::SOURCES))],
            'libelle' => ['nullable', 'string', 'max:255'],
            'ae_initiale' => ['nullable', 'numeric', 'min:0'],
            'cp_initial' => ['required', 'numeric', 'min:0'],
        ]);

        $nature = Nature::find($data['nature_id']);
        if (! in_array($nature->titre, Nature::TITRES_AE_DISTINCTES, true) || ($data['ae_initiale'] ?? null) === null) {
            $data['ae_initiale'] = $data['cp_initial']; // hors investissement : AE = CP
        }

        $doublon = LigneCredit::where('exercice_id', $exerciceId)
            ->where('action_id', $data['action_id'])->where('service_id', $data['service_id'])
            ->where('nature_id', $data['nature_id'])->where('source', $data['source'])
            ->when($ligne, fn ($q) => $q->where('id', '!=', $ligne->id))->exists();
        if ($doublon) {
            throw \Illuminate\Validation\ValidationException::withMessages(['nature_id' => 'Cette imputation existe déjà pour cet exercice.']);
        }

        return $data;
    }

    protected function donnees(): array
    {
        return [
            'actions' => Action::with('programme')->get()->sortBy(fn ($a) => $a->programme->code.$a->code),
            'services' => Service::where('actif', true)->when(\App\Models\Scopes\ParService::servicesAutorises(), fn ($q, $ids) => $q->whereIn('id', $ids))->orderBy('code')->get(),
            'natures' => Nature::depenses()->where('actif', true)->orderBy('titre')->orderBy('code')->get(),
        ];
    }
}

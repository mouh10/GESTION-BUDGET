<?php

namespace App\Http\Controllers;

use App\Models\Exercice;
use App\Services\Comptabilite;
use Illuminate\Http\Request;

class ExerciceController extends Controller
{
    public function index()
    {
        return view('exercices.index', [
            'exercices' => Exercice::withCount('ecritures')->orderByDesc('date_debut')->get(),
        ]);
    }

    public function create()
    {
        $dernier = Exercice::orderByDesc('date_fin')->first();
        $debut = $dernier ? $dernier->date_fin->copy()->addDay() : now()->startOfYear();

        return view('exercices.form', ['exercice' => new Exercice([
            'libelle' => 'Exercice '.$debut->format('Y'),
            'date_debut' => $debut,
            'date_fin' => $debut->copy()->addYear()->subDay(),
        ])]);
    }

    public function store(Request $request)
    {
        $data = $this->valider($request);
        $exercice = Exercice::create($data);

        return redirect()->route('exercices.index')->with('succes', "L'exercice {$exercice->libelle} a été créé.");
    }

    public function edit(Exercice $exercice)
    {
        return view('exercices.form', compact('exercice'));
    }

    public function update(Request $request, Exercice $exercice)
    {
        abort_if($exercice->cloture, 403, 'Un exercice clôturé ne peut plus être modifié.');

        $data = $this->valider($request, $exercice);

        $horsPeriode = $exercice->ecritures()
            ->where(fn ($q) => $q->whereDate('date', '<', $data['date_debut'])->orWhereDate('date', '>', $data['date_fin']))
            ->exists();
        if ($horsPeriode) {
            return back()->withInput()->with('erreur', 'Des écritures existent en dehors de la nouvelle période.');
        }

        $exercice->update($data);

        return redirect()->route('exercices.index')->with('succes', 'Exercice mis à jour.');
    }

    public function destroy(Exercice $exercice)
    {
        if ($exercice->ecritures()->exists() || $exercice->lignesCredit()->exists() || $exercice->engagements()->exists()) {
            return back()->with('erreur', 'Cet exercice contient des écritures, des crédits ou des engagements : il ne peut pas être supprimé.');
        }

        $exercice->delete();
        session()->forget('exercice_id');

        return redirect()->route('exercices.index')->with('succes', 'Exercice supprimé.');
    }

    public function cloturer(Request $request, Exercice $exercice, Comptabilite $compta)
    {
        $ecriture = $compta->cloturer($exercice, $request->boolean('a_nouveaux', true));

        $message = "L'exercice {$exercice->libelle} est clôturé.";
        if ($ecriture) {
            $message .= " Les à-nouveaux ont été générés ({$ecriture->numero_piece}).";
        }

        return redirect()->route('exercices.index')->with('succes', $message);
    }

    public function selectionner(Request $request)
    {
        $request->validate(['exercice_id' => ['required', 'exists:exercices,id']]);
        session(['exercice_id' => (int) $request->exercice_id]);

        return back();
    }

    protected function valider(Request $request, ?Exercice $exercice = null): array
    {
        $data = $request->validate([
            'libelle' => ['required', 'string', 'max:100'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['required', 'date', 'after:date_debut'],
        ]);

        $chevauche = Exercice::when($exercice, fn ($q) => $q->where('id', '!=', $exercice->id))
            ->whereDate('date_debut', '<=', $data['date_fin'])
            ->whereDate('date_fin', '>=', $data['date_debut'])
            ->exists();

        if ($chevauche) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'date_debut' => 'Cette période chevauche un exercice existant.',
            ]);
        }

        return $data;
    }
}

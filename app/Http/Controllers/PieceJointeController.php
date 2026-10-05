<?php

namespace App\Http\Controllers;

use App\Models\PieceJointe;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Pièces justificatives numérisées (factures, PV de réception, ordres de mission, actes…).
 * Les fichiers sont stockés hors du dossier public et ne sont servis qu'aux utilisateurs
 * autorisés à voir l'objet auquel ils sont rattachés.
 */
class PieceJointeController extends Controller
{
    public function store(Request $request, string $type, int $id)
    {
        abort_unless(isset(PieceJointe::OBJETS[$type]), 404);
        abort_unless($request->user()->aRole('ordonnateur', 'controleur', 'comptable'), 403);

        $objet = PieceJointe::OBJETS[$type]::findOrFail($id);   // restrictions par service appliquées

        $data = $request->validate([
            'fichiers' => ['required', 'array', 'max:10'],
            'fichiers.*' => ['file', 'max:'.PieceJointe::TAILLE_MAX_KO, 'extensions:'.implode(',', PieceJointe::EXTENSIONS)],
            'categorie' => ['required', Rule::in(array_keys(PieceJointe::CATEGORIES))],
        ], [
            'fichiers.required' => 'Choisissez au moins un fichier.',
            'fichiers.*.max' => 'Chaque fichier doit faire moins de '.(PieceJointe::TAILLE_MAX_KO / 1024).' Mo.',
            'fichiers.*.extensions' => 'Formats acceptés : '.implode(', ', PieceJointe::EXTENSIONS).'.',
        ]);

        foreach ($data['fichiers'] as $fichier) {
            $dossier = 'pieces/'.$type.'/'.$objet->getKey();
            $chemin = $fichier->storeAs($dossier, Str::uuid().'.'.strtolower($fichier->getClientOriginalExtension()), 'local');

            $piece = PieceJointe::create([
                'objet_type' => $objet->getMorphClass(),
                'objet_id' => $objet->getKey(),
                'categorie' => $data['categorie'],
                'nom' => Str::limit($fichier->getClientOriginalName(), 250, ''),
                'chemin' => $chemin,
                'mime' => $fichier->getClientMimeType(),
                'taille' => $fichier->getSize(),
                'user_id' => $request->user()->id,
            ]);
            Audit::enregistrer('piece_jointe', $objet, 'Ajout de la pièce « '.$piece->nom.' » ('.PieceJointe::CATEGORIES[$piece->categorie].')');
        }

        return back()->with('succes', count($data['fichiers']) > 1 ? count($data['fichiers']).' pièces jointes ajoutées.' : 'Pièce jointe ajoutée.');
    }

    public function telecharger(Request $request, PieceJointe $piece)
    {
        abort_unless($piece->objet, 404);   // objet hors du périmètre de l'utilisateur
        abort_unless(Storage::disk('local')->exists($piece->chemin), 404, 'Fichier introuvable sur le serveur.');

        $disposition = $request->boolean('telecharger') ? 'attachment' : 'inline';

        return Storage::disk('local')->response($piece->chemin, $piece->nom, [
            'Content-Type' => $piece->mime ?: 'application/octet-stream',
        ], $disposition);
    }

    public function destroy(Request $request, PieceJointe $piece)
    {
        $objet = $piece->objet;
        abort_unless($objet, 404);
        abort_unless($request->user()->estAdmin() || $piece->user_id === $request->user()->id, 403, 'Seul l’auteur de la pièce ou l’administrateur peut la retirer.');

        Storage::disk('local')->delete($piece->chemin);
        $piece->delete();
        Audit::enregistrer('piece_jointe', $objet, 'Retrait de la pièce « '.$piece->nom.' »');

        return back()->with('succes', 'Pièce jointe retirée.');
    }
}

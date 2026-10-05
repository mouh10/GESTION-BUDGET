<?php

namespace App\Http\Controllers;

use App\Services\Sauvegarde;

class SauvegardeController extends Controller
{
    public function __construct(protected Sauvegarde $sauvegarde)
    {
    }

    public function index()
    {
        return view('sauvegardes.index', [
            'sauvegardes' => $this->sauvegarde->lister(),
            'dossier' => $this->sauvegarde->dossier(),
            'service' => $this->sauvegarde,
        ]);
    }

    public function store()
    {
        $r = $this->sauvegarde->lancer();

        return back()->with('succes', 'Sauvegarde réalisée : '.basename($r['fichier']).' ('.$this->sauvegarde->tailleLisible($r['taille']).').'
            .($r['supprimees'] ? " {$r['supprimees']} ancienne(s) sauvegarde(s) supprimée(s)." : ''));
    }

    public function telecharger(string $fichier)
    {
        $chemin = $this->sauvegarde->chemin($fichier);
        abort_unless($chemin, 404);
        \App\Support\Audit::enregistrer('sauvegarde', null, 'Téléchargement de la sauvegarde '.$fichier);

        return response()->download($chemin);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Compte;
use App\Models\Ecriture;
use App\Models\Engagement;
use App\Models\Mandat;
use App\Models\Marche;
use App\Models\TitreRecette;
use App\Models\Tiers;
use Illuminate\Http\Request;

class RechercheController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $resultats = ['engagements' => collect(), 'mandats' => collect(), 'titres' => collect(), 'marches' => collect(), 'tiers' => collect(), 'comptes' => collect(), 'ecritures' => collect()];

        if (mb_strlen($q) >= 2) {
            $t = '%'.$q.'%';
            $resultats['engagements'] = Engagement::with('tiers')
                ->where(fn ($w) => $w->where('numero', 'like', $t)->orWhere('objet', 'like', $t)
                    ->orWhereHas('tiers', fn ($x) => $x->where('nom', 'like', $t)))
                ->orderByDesc('date')->limit(10)->get();
            $resultats['mandats'] = Mandat::with('liquidation.engagement.tiers')->where('numero', 'like', $t)->orderByDesc('date')->limit(10)->get();
            $resultats['titres'] = TitreRecette::with('tiers')
                ->where(fn ($w) => $w->where('numero', 'like', $t)->orWhere('objet', 'like', $t)->orWhereHas('tiers', fn ($x) => $x->where('nom', 'like', $t)))
                ->orderByDesc('date')->limit(10)->get();
            $resultats['marches'] = Marche::with('tiers')->where(fn ($w) => $w->where('numero', 'like', $t)->orWhere('objet', 'like', $t))->limit(10)->get();
            $resultats['tiers'] = Tiers::where(fn ($w) => $w->where('nom', 'like', $t)->orWhere('code', 'like', $t)
                ->orWhere('telephone', 'like', $t)->orWhere('ninea', 'like', $t))
                ->orderBy('nom')->limit(10)->get();
            $resultats['comptes'] = Compte::where(fn ($w) => $w->where('numero', 'like', $q.'%')->orWhere('libelle', 'like', $t))
                ->orderBy('numero')->limit(10)->get();
            $resultats['ecritures'] = Ecriture::with('journal')
                ->where(fn ($w) => $w->where('numero_piece', 'like', $t)->orWhere('libelle', 'like', $t)->orWhere('reference', 'like', $t))
                ->orderByDesc('date')->limit(10)->get();
        }

        return view('recherche', [
            'q' => $q,
            'resultats' => $resultats,
            'total' => collect($resultats)->sum(fn ($r) => $r->count()),
        ]);
    }
}

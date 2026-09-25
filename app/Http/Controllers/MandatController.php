<?php

namespace App\Http\Controllers;

use App\Models\CompteTresorerie;
use App\Models\Liquidation;
use App\Models\Mandat;
use App\Models\MouvementTresorerie;
use App\Services\Depenses;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MandatController extends Controller
{
    public function __construct(protected Depenses $depenses)
    {
    }

    public function index(Request $request)
    {
        $exercice = $this->exercice();
        $base = Mandat::whereHas('liquidation.engagement', fn ($q) => $q->where('exercice_id', $exercice->id));

        $mandats = (clone $base)->with('liquidation.engagement.tiers', 'liquidation.engagement.ligneCredit.action.programme', 'liquidation.engagement.ligneCredit.nature')
            ->when($request->filled('statut'), fn ($q) => $q->where('statut', $request->statut))
            ->when($request->filled('q'), function ($q) use ($request) {
                $t = '%'.$request->q.'%';
                $q->where(fn ($w) => $w->where('numero', 'like', $t)
                    ->orWhereHas('liquidation.engagement', fn ($e) => $e->where('objet', 'like', $t)->orWhere('numero', 'like', $t)
                        ->orWhereHas('tiers', fn ($x) => $x->where('nom', 'like', $t))));
            })
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('mandats.index', [
            'mandats' => $mandats,
            'compteurs' => (clone $base)->selectRaw('statut, COUNT(*) as n, SUM(montant) as total')->groupBy('statut')->get()->keyBy('statut'),
        ]);
    }

    public function emettre(Request $request, Liquidation $liquidation)
    {
        $mandat = $this->depenses->emettreMandat($liquidation, $request->input('date'));

        return back()->with('succes', "Mandat {$mandat->numero} émis et transmis au comptable.");
    }

    public function show(Mandat $mandat)
    {
        $mandat->load('liquidation.engagement.tiers', 'liquidation.engagement.ligneCredit.action.programme', 'liquidation.engagement.ligneCredit.service',
            'liquidation.engagement.ligneCredit.nature.compte', 'ecriture', 'paiement.compteTresorerie', 'paiement.ecriture');

        return view('mandats.show', compact('mandat'));
    }

    public function imprimer(Mandat $mandat)
    {
        $mandat->load('liquidation.engagement.tiers', 'liquidation.engagement.ligneCredit.action.programme', 'liquidation.engagement.ligneCredit.service', 'liquidation.engagement.ligneCredit.nature');

        return view('mandats.imprimer', compact('mandat'));
    }

    public function prendreEnCharge(Mandat $mandat)
    {
        $this->depenses->prendreEnCharge($mandat);

        return back()->with('succes', "Mandat {$mandat->numero} pris en charge et comptabilisé.");
    }

    public function rejeter(Request $request, Mandat $mandat)
    {
        $request->validate(['motif' => ['required', 'string', 'max:2000']], ['motif.required' => 'Indiquez le motif du rejet.']);
        $this->depenses->rejeterMandat($mandat, $request->motif);

        return back()->with('succes', "Mandat {$mandat->numero} rejeté et renvoyé à l'ordonnateur.");
    }

    public function paiement(Mandat $mandat)
    {
        if ($mandat->statut !== 'pris_en_charge') {
            return redirect()->route('mandats.show', $mandat)->with('erreur', 'Seul un mandat pris en charge peut être payé.');
        }

        return view('mandats.paiement', [
            'mandat' => $mandat->load('liquidation.engagement.tiers'),
            'tresoreries' => CompteTresorerie::where('actif', true)->orderBy('nom')->get(),
        ]);
    }

    public function payer(Request $request, Mandat $mandat)
    {
        $data = $request->validate([
            'compte_tresorerie_id' => ['required', 'exists:comptes_tresorerie,id'],
            'date' => ['required', 'date'],
            'mode' => ['required', Rule::in(array_keys(MouvementTresorerie::MODES))],
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $this->depenses->payer($mandat, CompteTresorerie::findOrFail($data['compte_tresorerie_id']), $data);

        return redirect()->route('mandats.show', $mandat)->with('succes', "Mandat {$mandat->numero} payé.");
    }
}

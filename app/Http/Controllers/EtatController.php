<?php

namespace App\Http\Controllers;

use App\Models\Ecriture;
use App\Models\Journal;
use App\Services\Etats;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EtatController extends Controller
{
    public function __construct(protected Etats $etats)
    {
    }

    protected function periode(Request $request): array
    {
        $request->validate(['du' => ['nullable', 'date'], 'au' => ['nullable', 'date']]);

        return [$request->input('du'), $request->input('au')];
    }

    public function journal(Request $request)
    {
        $exercice = $this->exercice();
        [$du, $au] = $this->periode($request);

        $ecritures = Ecriture::with(['lignes.compte', 'lignes.tiers', 'journal'])
            ->where('exercice_id', $exercice->id)
            ->where('statut', 'validee')
            ->when($request->filled('journal_id'), fn ($q) => $q->where('journal_id', $request->journal_id))
            ->when($du, fn ($q) => $q->whereDate('date', '>=', $du))
            ->when($au, fn ($q) => $q->whereDate('date', '<=', $au))
            ->orderBy('date')->orderBy('id')
            ->paginate(par_page(30))->withQueryString();

        return view('etats.journal', [
            'exercice' => $exercice,
            'ecritures' => $ecritures,
            'journaux' => Journal::orderBy('code')->get(),
        ]);
    }

    public function grandLivre(Request $request)
    {
        $exercice = $this->exercice();
        [$du, $au] = $this->periode($request);
        $compteDu = $request->input('compte_du');
        $compteAu = $request->input('compte_au');

        $grandLivre = $this->etats->grandLivre($exercice, $du, $au, $compteDu, $compteAu);

        if ($request->query('export') === 'csv') {
            return $this->csv('grand-livre-'.$exercice->annee().'.csv', function ($out) use ($grandLivre) {
                fputcsv($out, ['Compte', 'Intitulé', 'Date', 'Pièce', 'Journal', 'Libellé', 'Débit', 'Crédit', 'Solde'], ';');
                foreach ($grandLivre as $g) {
                    if ($g->report_debit || $g->report_credit) {
                        fputcsv($out, [$g->compte->numero, $g->compte->libelle, '', '', '', 'Report', $this->nb($g->report_debit), $this->nb($g->report_credit), $this->nb($g->report_debit - $g->report_credit)], ';');
                    }
                    foreach ($g->mouvements as $m) {
                        fputcsv($out, [$g->compte->numero, $g->compte->libelle, date_fr($m->date), $m->numero_piece, $m->journal, $m->libelle ?: $m->ecriture_libelle, $this->nb($m->debit), $this->nb($m->credit), $this->nb($m->solde)], ';');
                    }
                }
            });
        }

        return view('etats.grand-livre', compact('exercice', 'grandLivre'));
    }

    public function balance(Request $request)
    {
        $exercice = $this->exercice();
        [$du, $au] = $this->periode($request);
        $classe = $request->filled('classe') ? (int) $request->classe : null;

        $balance = $this->etats->balance($exercice, $du, $au, $classe);

        if ($request->query('export') === 'csv') {
            return $this->csv('balance-'.$exercice->annee().'.csv', function ($out) use ($balance) {
                fputcsv($out, ['Compte', 'Intitulé', 'Mouvements débit', 'Mouvements crédit', 'Solde débiteur', 'Solde créditeur'], ';');
                foreach ($balance['lignes'] as $l) {
                    fputcsv($out, [$l->numero, $l->libelle, $this->nb($l->debit), $this->nb($l->credit), $this->nb($l->solde_debiteur), $this->nb($l->solde_crediteur)], ';');
                }
                $t = $balance['totaux'];
                fputcsv($out, ['TOTAL', '', $this->nb($t['debit']), $this->nb($t['credit']), $this->nb($t['solde_debiteur']), $this->nb($t['solde_crediteur'])], ';');
            });
        }

        return view('etats.balance', compact('exercice', 'balance'));
    }

    public function resultat(Request $request)
    {
        $exercice = $this->exercice();
        [$du, $au] = $this->periode($request);

        return view('etats.resultat', [
            'exercice' => $exercice,
            'cr' => $this->etats->compteDeResultat($exercice, $du, $au),
        ]);
    }

    public function bilan(Request $request)
    {
        $exercice = $this->exercice();
        [, $au] = $this->periode($request);

        return view('etats.bilan', [
            'exercice' => $exercice,
            'bilan' => $this->etats->bilan($exercice, $au),
        ]);
    }

    protected function nb($valeur): string
    {
        return number_format((float) $valeur, 2, ',', '');
    }

    protected function csv(string $nom, callable $ecrire): StreamedResponse
    {
        return response()->streamDownload(function () use ($ecrire) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            $ecrire($out);
            fclose($out);
        }, $nom, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}

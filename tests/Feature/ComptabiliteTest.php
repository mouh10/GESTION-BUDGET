<?php

namespace Tests\Feature;

use App\Exceptions\GestionException;
use App\Models\Exercice;
use App\Models\Journal;
use App\Services\Comptabilite;
use App\Services\Etats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Moteur de comptabilité générale. */
class ComptabiliteTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_une_ecriture_desequilibree_est_refusee(): void
    {
        $this->expectException(GestionException::class);
        $this->expectExceptionMessage("n'est pas équilibrée");

        app(Comptabilite::class)->enregistrer(
            ['journal_id' => Journal::parCode('OD')->id, 'date' => $this->aujourdhui(), 'libelle' => 'Test'],
            [['compte_id' => $this->compte('622'), 'debit' => 1000], ['compte_id' => $this->compte('532'), 'credit' => 900]]
        );
    }

    public function test_numerotation_par_journal(): void
    {
        $compta = app(Comptabilite::class);
        $l = [['compte_id' => $this->compte('622'), 'debit' => 1000], ['compte_id' => $this->compte('532'), 'credit' => 1000]];
        $e1 = $compta->enregistrer(['journal_id' => Journal::parCode('OD')->id, 'date' => $this->aujourdhui(), 'libelle' => 'A'], $l);
        $e2 = $compta->enregistrer(['journal_id' => Journal::parCode('OD')->id, 'date' => $this->aujourdhui(), 'libelle' => 'B'], $l);

        $annee = Exercice::courant()->annee();
        $this->assertSame("OD-{$annee}-0001", $e1->numero_piece);
        $this->assertSame("OD-{$annee}-0002", $e2->numero_piece);
    }

    public function test_aucune_ecriture_dans_un_exercice_cloture(): void
    {
        Exercice::courant()->update(['cloture' => true]);
        $this->expectException(GestionException::class);

        app(Comptabilite::class)->enregistrer(
            ['journal_id' => Journal::parCode('OD')->id, 'date' => $this->aujourdhui(), 'libelle' => 'Test'],
            [['compte_id' => $this->compte('622'), 'debit' => 10], ['compte_id' => $this->compte('532'), 'credit' => 10]]
        );
    }

    public function test_virement_interne_equilibre(): void
    {
        $tresor = $this->tresor(500000);
        $regie = \App\Models\CompteTresorerie::create(['nom' => 'Régie', 'type' => 'regie', 'compte_id' => $this->compte('581'), 'journal_id' => Journal::parCode('RG')->id]);

        app(Comptabilite::class)->virement($tresor, $regie, ['date' => $this->aujourdhui(), 'montant' => 100000]);

        $this->assertEquals(400000, $tresor->solde());
        $this->assertEquals(100000, $regie->solde());
        $this->assertEquals(0, app(Etats::class)->soldes(Exercice::courant())->keyBy('numero')['585']->solde);
    }

    public function test_cloture_genere_les_a_nouveaux(): void
    {
        $compta = app(Comptabilite::class);
        $exercice = Exercice::courant();
        $suivant = Exercice::create(['libelle' => 'Suivant', 'date_debut' => $exercice->date_fin->copy()->addDay()->toDateString(), 'date_fin' => $exercice->date_fin->copy()->addYear()->toDateString()]);
        $tresor = $this->tresor(1000000);
        $compta->ouvertureTresorerie($tresor, $this->compte('121'), $exercice->date_debut->toDateString());
        $compta->enregistrerMouvement($tresor, ['date' => $exercice->date_debut->toDateString(), 'type' => 'decaissement', 'montant' => 300000, 'libelle' => 'Loyer', 'compte_id' => $this->compte('622')]);

        $an = $compta->cloturer($exercice);

        $this->assertTrue($exercice->fresh()->cloture);
        $this->assertEquals($suivant->id, $an->exercice_id);
        $lignes = $an->lignes->keyBy(fn ($l) => $l->compte->numero);
        $this->assertEquals(700000, $lignes['532']->debit);
        $this->assertEquals(300000, $lignes['139']->debit);
    }
}

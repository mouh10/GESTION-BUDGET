<?php

namespace Tests\Feature;

use App\Exceptions\GestionException;
use App\Models\Exercice;
use App\Models\Marche;
use App\Models\Modification;
use App\Services\Credits;
use App\Services\Depenses;
use App\Services\Etats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Chaîne de la dépense et contrôle des crédits. */
class ChaineDepenseTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function engager($ligne, $tiers, float $montant, ?Marche $marche = null)
    {
        return app(Depenses::class)->enregistrerEngagement([
            'ligne_credit_id' => $ligne->id, 'tiers_id' => $tiers->id, 'marche_id' => $marche?->id,
            'date' => $this->aujourdhui(), 'type' => 'bon_commande', 'objet' => 'Test', 'montant' => $montant,
        ]);
    }

    public function test_chaine_complete_et_ecritures(): void
    {
        $d = app(Depenses::class);
        $ligne = $this->ligne('6055', 1000000);
        $f = $this->fournisseur();
        $tresor = $this->tresor(5000000);

        $e = $this->engager($ligne, $f, 400000);
        $d->soumettre($e);
        $d->viser($e);
        $liq = $d->liquider($e->fresh(), ['date' => $this->aujourdhui(), 'montant' => 250000]);
        $mandat = $d->emettreMandat($liq, $this->aujourdhui());

        $s = app(Credits::class)->pourLigne($ligne);
        $this->assertEquals(600000, $s->ae_disponible);
        $this->assertEquals(750000, $s->cp_disponible);
        $this->assertEquals(250000, $s->liquide);

        $d->prendreEnCharge($mandat);
        $lignes = $mandat->fresh()->ecriture->lignes->keyBy(fn ($l) => $l->compte->numero);
        $this->assertEquals(250000, $lignes['6055']->debit);
        $this->assertEquals(250000, $lignes['4011']->credit);

        $d->payer($mandat->fresh(), $tresor, ['date' => $this->aujourdhui(), 'mode' => 'virement']);
        $this->assertSame('paye', $mandat->fresh()->statut);
        $this->assertEquals(4750000, $tresor->solde());
        $this->assertEquals(0, $f->soldeComptable());
        $this->assertEquals(250000, app(Credits::class)->pourLigne($ligne)->paye);

        $b = app(Etats::class)->balance(Exercice::courant());
        $this->assertEquals($b['totaux']['debit'], $b['totaux']['credit']);
    }

    public function test_engagement_refuse_si_ae_insuffisantes(): void
    {
        $ligne = $this->ligne('6055', 100000);
        $e = $this->engager($ligne, $this->fournisseur(), 150000);

        $this->expectException(GestionException::class);
        $this->expectExceptionMessage('Crédits insuffisants');
        app(Depenses::class)->soumettre($e);
    }

    public function test_rejet_libere_les_credits(): void
    {
        $d = app(Depenses::class);
        $ligne = $this->ligne('6055', 100000);
        $e = $this->engager($ligne, $this->fournisseur(), 100000);
        $d->soumettre($e);
        $this->assertEquals(0, app(Credits::class)->pourLigne($ligne)->ae_disponible);

        $d->rejeter($e, 'Pièces manquantes');
        $this->assertSame('rejete', $e->fresh()->statut);
        $this->assertEquals(100000, app(Credits::class)->pourLigne($ligne)->ae_disponible);
    }

    public function test_liquidation_limitee_au_reste_et_visa_obligatoire(): void
    {
        $d = app(Depenses::class);
        $e = $this->engager($this->ligne('6055', 500000), $this->fournisseur(), 200000);
        $d->soumettre($e);

        try {
            $d->liquider($e->fresh(), ['date' => $this->aujourdhui(), 'montant' => 100000]);
            $this->fail('Une liquidation sans visa aurait dû être refusée.');
        } catch (GestionException $x) {
            $this->assertStringContainsString('visé', $x->getMessage());
        }

        $d->viser($e);
        $d->liquider($e->fresh(), ['date' => $this->aujourdhui(), 'montant' => 150000]);
        $this->expectException(GestionException::class);
        $d->liquider($e->fresh(), ['date' => $this->aujourdhui(), 'montant' => 60000]);
    }

    public function test_mandat_refuse_si_cp_insuffisants(): void
    {
        $d = app(Depenses::class);
        $ligne = $this->ligne('2442', 100000, 500000); // investissement : AE 500 000, CP 100 000
        $e = $this->engager($ligne, $this->fournisseur(), 300000);
        $d->soumettre($e);
        $d->viser($e);
        $liq = $d->liquider($e->fresh(), ['date' => $this->aujourdhui(), 'montant' => 300000]);

        $this->expectException(GestionException::class);
        $this->expectExceptionMessage('CP disponibles insuffisants');
        $d->emettreMandat($liq, $this->aujourdhui());
    }

    public function test_marche_plafonne_les_engagements(): void
    {
        $d = app(Depenses::class);
        $ligne = $this->ligne('6055', 5000000);
        $f = $this->fournisseur();
        $marche = Marche::create(['exercice_id' => Exercice::courant()->id, 'numero' => 'M-1', 'objet' => 'Test', 'tiers_id' => $f->id, 'type' => 'fournitures', 'mode_passation' => 'aoo', 'montant' => 1000000]);

        $d->soumettre($this->engager($ligne, $f, 800000, $marche));
        $this->expectException(GestionException::class);
        $d->soumettre($this->engager($ligne, $f, 300000, $marche));
    }

    public function test_rejet_du_mandat_puis_reemission(): void
    {
        $d = app(Depenses::class);
        $e = $this->engager($this->ligne('6055', 500000), $this->fournisseur(), 100000);
        $d->soumettre($e);
        $d->viser($e);
        $liq = $d->liquider($e->fresh(), ['date' => $this->aujourdhui(), 'montant' => 100000]);
        $m1 = $d->emettreMandat($liq, $this->aujourdhui());
        $d->rejeterMandat($m1, 'RIB erroné');

        $m2 = $d->emettreMandat($liq->fresh(), $this->aujourdhui());
        $this->assertSame('emis', $m2->statut);
        $this->assertNotSame($m1->numero, $m2->numero);
    }

    public function test_virement_de_credits(): void
    {
        $a = $this->ligne('6055', 1000000);
        $b = $this->ligne('6381', 1000000);
        $m = Modification::create(['exercice_id' => Exercice::courant()->id, 'numero' => 'MB-1', 'date' => $this->aujourdhui(), 'type' => 'virement']);
        $m->lignes()->createMany([['ligne_credit_id' => $a->id, 'ae' => -300000, 'cp' => -300000], ['ligne_credit_id' => $b->id, 'ae' => 300000, 'cp' => 300000]]);

        app(Credits::class)->approuver($m);

        $this->assertEquals(700000, app(Credits::class)->pourLigne($a)->cp_revise);
        $this->assertEquals(1300000, app(Credits::class)->pourLigne($b)->cp_revise);
    }

    public function test_virement_desequilibre_ou_superieur_au_disponible_refuse(): void
    {
        $credits = app(Credits::class);
        $a = $this->ligne('6055', 100000);
        $b = $this->ligne('6381', 100000);

        $m = Modification::create(['exercice_id' => Exercice::courant()->id, 'numero' => 'MB-1', 'date' => $this->aujourdhui(), 'type' => 'virement']);
        $m->lignes()->createMany([['ligne_credit_id' => $a->id, 'ae' => -50000, 'cp' => -50000], ['ligne_credit_id' => $b->id, 'ae' => 40000, 'cp' => 40000]]);
        try {
            $credits->approuver($m);
            $this->fail('Virement déséquilibré accepté.');
        } catch (GestionException) {
        }

        $m2 = Modification::create(['exercice_id' => Exercice::courant()->id, 'numero' => 'MB-2', 'date' => $this->aujourdhui(), 'type' => 'virement']);
        $m2->lignes()->createMany([['ligne_credit_id' => $a->id, 'ae' => -150000, 'cp' => -150000], ['ligne_credit_id' => $b->id, 'ae' => 150000, 'cp' => 150000]]);
        $this->expectException(GestionException::class);
        $credits->approuver($m2);
    }

    public function test_gel_reduit_le_disponible(): void
    {
        $credits = app(Credits::class);
        $a = $this->ligne('6055', 1000000);
        $m = Modification::create(['exercice_id' => Exercice::courant()->id, 'numero' => 'MB-1', 'date' => $this->aujourdhui(), 'type' => 'gel']);
        $m->lignes()->create(['ligne_credit_id' => $a->id, 'ae' => 200000, 'cp' => 200000]);
        $credits->approuver($m);

        $s = $credits->pourLigne($a);
        $this->assertEquals(1000000, $s->cp_revise);
        $this->assertEquals(800000, $s->cp_disponible);
    }
}

<?php

namespace Tests\Feature;

use App\Exceptions\GestionException;
use App\Models\Exercice;
use App\Services\Recettes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecettesTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_emission_et_recouvrement(): void
    {
        $r = app(Recettes::class);
        $p = $this->prevision('7064', 1000000);
        $redevable = $this->redevable();
        $tresor = $this->tresor();

        $titre = $r->emettre(['prevision_recette_id' => $p->id, 'tiers_id' => $redevable->id, 'date' => $this->aujourdhui(), 'objet' => 'Redevance', 'montant' => 300000]);
        $lignes = $titre->ecriture->lignes->keyBy(fn ($l) => $l->compte->numero);
        $this->assertEquals(300000, $lignes['4111']->debit);
        $this->assertEquals(300000, $lignes['7064']->credit);

        $r->recouvrer($titre, $tresor, ['date' => $this->aujourdhui(), 'montant' => 100000, 'mode' => 'especes']);
        $this->assertSame('partiellement_recouvre', $titre->fresh()->statut);
        $r->recouvrer($titre->fresh(), $tresor, ['date' => $this->aujourdhui(), 'montant' => 200000, 'mode' => 'especes']);
        $this->assertSame('recouvre', $titre->fresh()->statut);
        $this->assertEquals(300000, $tresor->solde());
        $this->assertEquals(0, $redevable->soldeComptable());

        $s = $r->situation(Exercice::courant())->first();
        $this->assertEquals(300000, $s->recouvre);
        $this->assertEquals(30.0, $s->taux_recouvrement);
    }

    public function test_recouvrement_superieur_au_reste_refuse(): void
    {
        $r = app(Recettes::class);
        $titre = $r->emettre(['prevision_recette_id' => $this->prevision()->id, 'tiers_id' => $this->redevable()->id, 'date' => $this->aujourdhui(), 'objet' => 'X', 'montant' => 1000]);

        $this->expectException(GestionException::class);
        $r->recouvrer($titre, $this->tresor(), ['date' => $this->aujourdhui(), 'montant' => 2000]);
    }
}

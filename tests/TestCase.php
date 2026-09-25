<?php

namespace Tests;

use App\Models\Action;
use App\Models\Compte;
use App\Models\CompteTresorerie;
use App\Models\Exercice;
use App\Models\Journal;
use App\Models\LigneCredit;
use App\Models\Nature;
use App\Models\PrevisionRecette;
use App\Models\Programme;
use App\Models\Service;
use App\Models\Tiers;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function compte(string $numero): int
    {
        return Compte::where('numero', $numero)->value('id');
    }

    protected function connecter(string $role = 'ordonnateur'): User
    {
        $user = User::factory()->create(['role' => $role]);
        $this->actingAs($user);

        return $user;
    }

    protected function tresor(float $soldeInitial = 0): CompteTresorerie
    {
        return CompteTresorerie::create([
            'nom' => 'Trésor test', 'type' => 'tresor', 'compte_id' => $this->compte('532'),
            'journal_id' => Journal::parCode('TR')->id, 'solde_initial' => $soldeInitial,
        ]);
    }

    protected function fournisseur(): Tiers
    {
        return Tiers::create(['type' => 'fournisseur', 'code' => 'FRS-'.uniqid(), 'nom' => 'Fournisseur test', 'compte_id' => $this->compte('4011')]);
    }

    protected function redevable(): Tiers
    {
        return Tiers::create(['type' => 'redevable', 'code' => 'RDV-'.uniqid(), 'nom' => 'Redevable test', 'compte_id' => $this->compte('4111')]);
    }

    /** Ligne de crédits de test : nature (code), AE et CP. */
    protected function ligne(string $nature = '6055', float $cp = 1000000, ?float $ae = null, ?Programme $programme = null): LigneCredit
    {
        $programme ??= Programme::firstOrCreate(['code' => '1001'], ['libelle' => 'Programme test']);
        $action = Action::firstOrCreate(['programme_id' => $programme->id, 'code' => '01'], ['libelle' => 'Action test']);
        $service = Service::firstOrCreate(['code' => 'DAGE'], ['libelle' => 'Service test']);

        return LigneCredit::create([
            'exercice_id' => Exercice::courant()->id,
            'action_id' => $action->id,
            'service_id' => $service->id,
            'nature_id' => Nature::where('code', $nature)->value('id'),
            'source' => 'etat',
            'ae_initiale' => $ae ?? $cp,
            'cp_initial' => $cp,
        ]);
    }

    protected function prevision(string $nature = '7064', float $montant = 1000000): PrevisionRecette
    {
        return PrevisionRecette::create(['exercice_id' => Exercice::courant()->id, 'nature_id' => Nature::where('code', $nature)->value('id'), 'montant_prevu' => $montant]);
    }

    protected function aujourdhui(): string
    {
        $ex = Exercice::courant();

        return $ex->contient(now()) ? now()->toDateString() : $ex->date_debut->toDateString();
    }
}

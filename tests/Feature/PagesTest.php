<?php

namespace Tests\Feature;

use App\Models\Engagement;
use App\Models\Mandat;
use App\Models\Modification;
use App\Models\TitreRecette;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        putenv('GESTION_DEMO=true');
        $_ENV['GESTION_DEMO'] = $_SERVER['GESTION_DEMO'] = 'true';
        $this->seed();
    }

    protected function tearDown(): void
    {
        putenv('GESTION_DEMO=false');
        $_ENV['GESTION_DEMO'] = $_SERVER['GESTION_DEMO'] = 'false';
        parent::tearDown();
    }

    public function test_un_invite_est_redirige_vers_la_connexion(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Se connecter');
    }

    public function test_connexion(): void
    {
        $this->post('/login', ['email' => 'admin@gestion.test', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticated();
    }

    public function test_toutes_les_pages_s_affichent(): void
    {
        $this->connecter('admin');
        $e = Engagement::first();
        $m = Mandat::first();
        $t = TitreRecette::first();
        $mod = Modification::first();

        $pages = [
            '/', '/credits', '/credits/create', '/credits/1', '/credits/1/edit', '/previsions', '/previsions/create',
            '/modifications', '/modifications/create', "/modifications/{$mod->id}", '/engagements', '/engagements/create',
            "/engagements/{$e->id}", "/engagements/{$e->id}/imprimer", '/mandats', "/mandats/{$m->id}", "/mandats/{$m->id}/imprimer",
            '/marches', '/marches/create', '/marches/1', '/tiers', '/tiers?type=redevable', '/tiers/create', '/tiers/1',
            '/titres', '/titres/create', "/titres/{$t->id}", '/tresorerie', '/tresorerie/1', '/tresorerie/virement',
            '/execution/depenses', '/execution/depenses?par=titre', '/execution/depenses?par=ligne', '/execution/depenses?export=csv', '/execution/recettes',
            '/programmes', '/services', '/natures', '/natures?type=recette', '/comptes', '/journaux', '/exercices', '/utilisateurs',
            '/ecritures', '/etats/balance', '/etats/bilan', '/etats/grand-livre', '/etats/journal', '/etats/compte-de-resultat', '/recherche?q=EJ',
        ];

        foreach ($pages as $page) {
            $this->get($page)->assertOk();
        }
    }

    public function test_parcours_complet_par_formulaires_selon_les_roles(): void
    {
        $ligne = \App\Models\LigneCredit::whereHas('nature', fn ($q) => $q->where('code', '6055'))->first();
        $fournisseur = \App\Models\Tiers::where('code', 'FRS-007')->first();
        $date = $this->aujourdhui();

        // Ordonnateur : engage et soumet
        $this->connecter('ordonnateur');
        $this->post('/engagements', ['ligne_credit_id' => $ligne->id, 'tiers_id' => $fournisseur->id, 'date' => $date, 'type' => 'bon_commande', 'objet' => 'Ramettes de papier', 'montant' => 250000, 'soumettre' => 1])->assertRedirect();
        $e = Engagement::where('objet', 'Ramettes de papier')->first();
        $this->assertSame('soumis', $e->statut);
        $this->post("/engagements/{$e->id}/viser")->assertForbidden();

        // Contrôleur : vise
        $this->connecter('controleur');
        $this->post("/engagements/{$e->id}/viser")->assertRedirect();
        $this->assertSame('vise', $e->fresh()->statut);

        // Ordonnateur : liquide et mandate
        $this->connecter('ordonnateur');
        $this->post("/engagements/{$e->id}/liquidations", ['date' => $date, 'montant' => 250000, 'reference_facture' => 'F-001', 'mandater' => 1])->assertRedirect();
        $mandat = $e->fresh()->liquidations->first()->mandats->first();
        $this->assertSame('emis', $mandat->statut);

        // Comptable : prend en charge et paie
        $this->connecter('comptable');
        $this->post("/mandats/{$mandat->id}/prendre-en-charge")->assertRedirect();
        $this->post("/mandats/{$mandat->id}/paiement", ['compte_tresorerie_id' => 1, 'date' => $date, 'mode' => 'virement'])->assertRedirect();
        $this->assertSame('paye', $mandat->fresh()->statut);
    }

    public function test_droits_par_role(): void
    {
        $this->connecter('lecteur');
        $this->get('/credits')->assertOk();
        $this->get('/engagements/create')->assertForbidden();
        $this->get('/utilisateurs')->assertForbidden();

        $this->connecter('controleur');
        $this->get('/engagements/create')->assertForbidden();
        $this->get('/ecritures/create')->assertForbidden();

        $this->connecter('comptable');
        $this->get('/ecritures/create')->assertOk();
        $this->get('/engagements/create')->assertForbidden();
        $this->post('/modifications/1/approuver')->assertForbidden();

        $this->connecter('ordonnateur');
        $this->get('/engagements/create')->assertOk();
        $this->get('/ecritures/create')->assertForbidden();
    }

    public function test_ecriture_desequilibree_revient_au_formulaire(): void
    {
        $this->connecter('comptable');
        $this->from('/ecritures/create')->post('/ecritures', [
            'journal_id' => \App\Models\Journal::parCode('OD')->id, 'date' => $this->aujourdhui(), 'libelle' => 'Faux',
            'lignes' => [['compte_id' => $this->compte('6055'), 'debit' => '25000'], ['compte_id' => $this->compte('532'), 'credit' => '20000']],
        ])->assertRedirect('/ecritures/create')->assertSessionHas('erreur');
    }
}

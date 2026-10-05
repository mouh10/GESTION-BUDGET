<?php

namespace Tests\Feature;

use App\Models\Engagement;
use App\Models\JournalAudit;
use App\Models\LigneCredit;
use App\Models\PieceJointe;
use App\Models\Service;
use App\Models\Tiers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Journal d'audit, pièces jointes, restriction par service, mot de passe provisoire, sauvegardes. */
class SecuriteTest extends TestCase
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

    public function test_les_actions_sont_journalisees_avec_leur_auteur(): void
    {
        $this->assertSame(0, JournalAudit::count(), 'Le chargement initial ne doit pas remplir le journal.');

        $this->post('/login', ['email' => 'ordonnateur@gestion.test', 'password' => 'mauvais']);
        $this->assertTrue(JournalAudit::where('action', 'echec_connexion')->exists());

        $this->post('/login', ['email' => 'ordonnateur@gestion.test', 'password' => 'password'])->assertRedirect('/');
        $ordo = User::where('email', 'ordonnateur@gestion.test')->first();
        $this->assertTrue(JournalAudit::where('action', 'connexion')->where('user_id', $ordo->id)->exists());

        $ligne = LigneCredit::whereHas('nature', fn ($q) => $q->where('code', '6055'))->first();
        $this->post('/engagements', ['ligne_credit_id' => $ligne->id, 'tiers_id' => Tiers::where('code', 'FRS-007')->value('id'), 'date' => $this->aujourdhui(),
            'type' => 'bon_commande', 'objet' => 'Cartouches d’encre', 'montant' => 150000, 'soumettre' => 1])->assertRedirect();
        $e = Engagement::where('objet', 'Cartouches d’encre')->first();

        $this->assertTrue(JournalAudit::where('sujet_type', 'engagement')->where('sujet_id', $e->id)->where('action', 'creation')->where('user_id', $ordo->id)->exists());
        $statut = JournalAudit::where('sujet_type', 'engagement')->where('sujet_id', $e->id)->where('action', 'statut')->first();
        $this->assertNotNull($statut);
        $this->assertStringContainsString('Soumis au visa', $statut->description);

        $this->get("/engagements/{$e->id}")->assertOk()->assertSee('Historique')->assertSee('Soumis au visa');
        $this->get('/audit')->assertForbidden();

        $this->connecter('admin');
        $this->get('/audit')->assertOk()->assertSee($e->numero);
        $this->get('/audit?action=connexion&user_id='.$ordo->id)->assertOk()->assertSee($ordo->name);
    }

    public function test_pieces_jointes(): void
    {
        Storage::fake('local');
        $e = Engagement::first();

        $this->connecter('lecteur');
        $this->post("/pieces/engagement/{$e->id}", ['categorie' => 'facture', 'fichiers' => [UploadedFile::fake()->create('facture.pdf', 120, 'application/pdf')]])->assertForbidden();

        $auteur = $this->connecter('ordonnateur');
        $this->post("/pieces/engagement/{$e->id}", ['categorie' => 'facture', 'fichiers' => [UploadedFile::fake()->create('facture.pdf', 120, 'application/pdf')]])->assertRedirect();
        $this->post("/pieces/engagement/{$e->id}", ['categorie' => 'facture', 'fichiers' => [UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors('fichiers.0');

        $piece = PieceJointe::firstOrFail();
        $this->assertSame('facture.pdf', $piece->nom);
        Storage::disk('local')->assertExists($piece->chemin);
        $this->assertTrue(JournalAudit::where('action', 'piece_jointe')->where('sujet_id', $e->id)->exists());

        $this->get("/engagements/{$e->id}")->assertOk()->assertSee('facture.pdf');
        $this->get("/pieces/{$piece->id}")->assertOk();

        $this->connecter('comptable');
        $this->delete("/pieces/{$piece->id}")->assertForbidden();

        $this->actingAs($auteur);
        $this->delete("/pieces/{$piece->id}")->assertRedirect();
        $this->assertSame(0, PieceJointe::count());
        Storage::disk('local')->assertMissing($piece->chemin);
    }

    public function test_un_gestionnaire_ne_voit_que_son_service(): void
    {
        $drh = User::where('email', 'drh@gestion.test')->firstOrFail();
        $idDrh = Service::where('code', 'DRH')->value('id');
        $this->actingAs($drh);

        $a = Engagement::withoutGlobalScopes()->whereHas('ligneCredit', fn ($q) => $q->withoutGlobalScopes()->where('service_id', $idDrh))->first();
        $autre = Engagement::withoutGlobalScopes()->whereHas('ligneCredit', fn ($q) => $q->withoutGlobalScopes()->where('service_id', '!=', $idDrh))->first();

        $this->assertTrue(LigneCredit::all()->every(fn ($l) => $l->service_id === $idDrh));
        $this->get('/engagements')->assertOk()->assertSee($a->numero)->assertDontSee($autre->numero);
        $this->get("/engagements/{$a->id}")->assertOk();
        $this->get("/engagements/{$autre->id}")->assertNotFound();
        $this->get('/')->assertOk()->assertSee('Périmètre : DRH');
        foreach (['/credits', '/mandats', '/titres', '/modifications', '/marches', '/execution/depenses', '/execution/recettes', '/previsions'] as $page) {
            $this->get($page)->assertOk();
        }

        // Tentative d'engagement sur une ligne d'un autre service
        $ligneAutre = LigneCredit::withoutGlobalScopes()->where('service_id', '!=', $idDrh)->first();
        $this->post('/engagements', ['ligne_credit_id' => $ligneAutre->id, 'tiers_id' => Tiers::first()->id, 'date' => $this->aujourdhui(),
            'type' => 'bon_commande', 'objet' => 'Hors périmètre', 'montant' => 1000])->assertSessionHasErrors('ligne_credit_id');
        $this->assertFalse(Engagement::withoutGlobalScopes()->where('objet', 'Hors périmètre')->exists());

        // L'administrateur voit tout
        $this->connecter('admin');
        $this->get('/engagements')->assertSee($autre->numero);
    }

    public function test_mot_de_passe_provisoire_a_changer(): void
    {
        $u = User::factory()->create(['role' => 'ordonnateur', 'password' => 'Provisoire1', 'doit_changer_mdp' => true]);

        $this->post('/login', ['email' => $u->email, 'password' => 'Provisoire1'])->assertRedirect('/mon-compte/mot-de-passe');
        $this->get('/engagements')->assertRedirect('/mon-compte/mot-de-passe');

        $this->put('/mon-compte/mot-de-passe', ['actuel' => 'faux', 'password' => 'Nouveau2026', 'password_confirmation' => 'Nouveau2026'])->assertSessionHasErrors('actuel');
        $this->put('/mon-compte/mot-de-passe', ['actuel' => 'Provisoire1', 'password' => 'Nouveau2026', 'password_confirmation' => 'Nouveau2026'])->assertRedirect('/');

        $this->assertFalse($u->fresh()->doit_changer_mdp);
        $this->get('/engagements')->assertOk();
    }

    public function test_sauvegardes_reservees_a_l_administrateur(): void
    {
        $this->connecter('comptable');
        $this->get('/sauvegardes')->assertForbidden();

        $this->connecter('admin');
        $this->get('/sauvegardes')->assertOk()->assertSee('Sauvegarder maintenant');
        $this->get('/sauvegardes/../../.env')->assertNotFound();
    }
}

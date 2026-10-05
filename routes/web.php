<?php

use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EcritureController;
use App\Http\Controllers\EngagementController;
use App\Http\Controllers\EtatBudgetaireController;
use App\Http\Controllers\EtatController;
use App\Http\Controllers\ExerciceController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LigneCreditController;
use App\Http\Controllers\MandatController;
use App\Http\Controllers\MarcheController;
use App\Http\Controllers\ModificationController;
use App\Http\Controllers\NatureController;
use App\Http\Controllers\PieceJointeController;
use App\Http\Controllers\PrevisionRecetteController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\RechercheController;
use App\Http\Controllers\SauvegardeController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TiersController;
use App\Http\Controllers\TitreRecetteController;
use App\Http\Controllers\TresorerieController;
use App\Http\Controllers\UtilisateurController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentification
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'formulaire'])->name('login');
    Route::post('/login', [AuthController::class, 'connexion'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'deconnexion'])->name('logout');
    Route::get('/mon-compte/mot-de-passe', [AuthController::class, 'motDePasse'])->name('mot-de-passe.edit');
    Route::put('/mon-compte/mot-de-passe', [AuthController::class, 'changerMotDePasse'])->name('mot-de-passe.update');

    // Pièces jointes (contrôle des droits dans le contrôleur)
    Route::post('/pieces/{type}/{id}', [PieceJointeController::class, 'store'])->name('pieces.store')->whereNumber('id');
    Route::get('/pieces/{piece}', [PieceJointeController::class, 'telecharger'])->name('pieces.telecharger');
    Route::delete('/pieces/{piece}', [PieceJointeController::class, 'destroy'])->name('pieces.destroy');

    Route::get('/', DashboardController::class)->name('dashboard');
    Route::get('/recherche', RechercheController::class)->name('recherche');
    Route::post('/exercice-courant', [ExerciceController::class, 'selectionner'])->name('exercices.selectionner');

    /*
    | Les routes d'action sont déclarées avant les routes de consultation
    | pour que « /create » ne soit pas capturé par « /{id} ».
    | Le rôle « admin » a accès à tout (voir VerifierRole).
    */

    // Administration : paramètres, exercices, utilisateurs, approbation des modifications
    Route::middleware('role:admin')->group(function () {
        Route::get('audit', [AuditController::class, 'index'])->name('audit.index');
        Route::get('sauvegardes', [SauvegardeController::class, 'index'])->name('sauvegardes.index');
        Route::post('sauvegardes', [SauvegardeController::class, 'store'])->name('sauvegardes.store');
        Route::get('sauvegardes/{fichier}', [SauvegardeController::class, 'telecharger'])->name('sauvegardes.telecharger')->where('fichier', '[A-Za-z0-9._-]+');
        Route::resource('utilisateurs', UtilisateurController::class)->except(['show'])->parameters(['utilisateurs' => 'utilisateur']);
        Route::resource('exercices', ExerciceController::class)->except(['index', 'show'])->parameters(['exercices' => 'exercice']);
        Route::post('exercices/{exercice}/cloturer', [ExerciceController::class, 'cloturer'])->name('exercices.cloturer');

        Route::resource('programmes', ProgrammeController::class)->except(['index', 'show'])->parameters(['programmes' => 'programme']);
        Route::post('programmes/{programme}/actions', [ProgrammeController::class, 'ajouterAction'])->name('programmes.actions.store');
        Route::put('actions/{action}', [ProgrammeController::class, 'modifierAction'])->name('actions.update');
        Route::delete('actions/{action}', [ProgrammeController::class, 'supprimerAction'])->name('actions.destroy');
        Route::resource('services', ServiceController::class)->except(['index', 'show'])->parameters(['services' => 'service']);
        Route::resource('natures', NatureController::class)->except(['index', 'show'])->parameters(['natures' => 'nature']);

        Route::post('modifications/{modification}/approuver', [ModificationController::class, 'approuver'])->name('modifications.approuver');
    });

    // Ordonnateur : budget, engagements, liquidations, mandats, titres de recette, marchés, fournisseurs
    Route::middleware('role:ordonnateur')->group(function () {
        Route::resource('credits', LigneCreditController::class)->except(['index', 'show'])->parameters(['credits' => 'credit']);
        Route::resource('previsions', PrevisionRecetteController::class)->except(['index', 'show'])->parameters(['previsions' => 'prevision']);
        Route::resource('modifications', ModificationController::class)->except(['index', 'show'])->parameters(['modifications' => 'modification']);

        Route::resource('engagements', EngagementController::class)->except(['index', 'show'])->parameters(['engagements' => 'engagement']);
        Route::post('engagements/{engagement}/soumettre', [EngagementController::class, 'soumettre'])->name('engagements.soumettre');
        Route::post('engagements/{engagement}/annuler', [EngagementController::class, 'annuler'])->name('engagements.annuler');
        Route::post('engagements/{engagement}/liquidations', [EngagementController::class, 'liquider'])->name('engagements.liquider');
        Route::post('liquidations/{liquidation}/annuler', [EngagementController::class, 'annulerLiquidation'])->name('liquidations.annuler');
        Route::post('liquidations/{liquidation}/mandat', [MandatController::class, 'emettre'])->name('mandats.emettre');

        Route::resource('marches', MarcheController::class)->except(['index', 'show', 'destroy'])->parameters(['marches' => 'marche']);
        Route::resource('tiers', TiersController::class)->except(['index', 'show'])->parameters(['tiers' => 'tiers']);

        Route::get('titres/create', [TitreRecetteController::class, 'create'])->name('titres.create');
        Route::post('titres', [TitreRecetteController::class, 'store'])->name('titres.store');
        Route::post('titres/{titre}/annuler', [TitreRecetteController::class, 'annuler'])->name('titres.annuler');
    });

    // Contrôleur financier : visa des engagements
    Route::middleware('role:controleur')->group(function () {
        Route::post('engagements/{engagement}/viser', [EngagementController::class, 'viser'])->name('engagements.viser');
        Route::post('engagements/{engagement}/rejeter', [EngagementController::class, 'rejeter'])->name('engagements.rejeter');
    });

    // Comptable public : prise en charge, paiement, recouvrement, trésorerie, comptabilité
    Route::middleware('role:comptable')->group(function () {
        Route::post('mandats/{mandat}/prendre-en-charge', [MandatController::class, 'prendreEnCharge'])->name('mandats.prendre-en-charge');
        Route::post('mandats/{mandat}/rejeter', [MandatController::class, 'rejeter'])->name('mandats.rejeter');
        Route::get('mandats/{mandat}/paiement', [MandatController::class, 'paiement'])->name('mandats.paiement');
        Route::post('mandats/{mandat}/paiement', [MandatController::class, 'payer'])->name('mandats.payer');

        Route::get('titres/{titre}/recouvrement', [TitreRecetteController::class, 'recouvrement'])->name('titres.recouvrement');
        Route::post('titres/{titre}/recouvrement', [TitreRecetteController::class, 'recouvrer'])->name('titres.recouvrer');

        Route::get('tresorerie/virement', [TresorerieController::class, 'virement'])->name('tresorerie.virement');
        Route::post('tresorerie/virement', [TresorerieController::class, 'enregistrerVirement'])->name('tresorerie.virement.store');
        Route::resource('tresorerie', TresorerieController::class)->except(['index', 'show', 'destroy'])->parameters(['tresorerie' => 'tresorerie']);
        Route::get('tresorerie/{tresorerie}/mouvements/create', [TresorerieController::class, 'nouveauMouvement'])->name('tresorerie.mouvements.create');
        Route::post('tresorerie/{tresorerie}/mouvements', [TresorerieController::class, 'enregistrerMouvement'])->name('tresorerie.mouvements.store');
        Route::delete('mouvements/{mouvement}', [TresorerieController::class, 'annulerMouvement'])->name('mouvements.destroy');

        Route::resource('comptes', CompteController::class)->except(['index', 'show'])->parameters(['comptes' => 'compte']);
        Route::resource('journaux', JournalController::class)->except(['index', 'show', 'destroy'])->parameters(['journaux' => 'journal']);
        Route::resource('ecritures', EcritureController::class)->except(['index', 'show'])->parameters(['ecritures' => 'ecriture']);
        Route::post('ecritures/{ecriture}/valider', [EcritureController::class, 'valider'])->name('ecritures.valider');
        Route::post('ecritures/{ecriture}/contre-passer', [EcritureController::class, 'contrePasser'])->name('ecritures.contre-passer');
    });

    /*
    | Consultation (tous les utilisateurs connectés)
    */
    Route::get('credits', [LigneCreditController::class, 'index'])->name('credits.index');
    Route::get('credits/{credit}', [LigneCreditController::class, 'show'])->name('credits.show');
    Route::get('previsions', [PrevisionRecetteController::class, 'index'])->name('previsions.index');
    Route::get('modifications', [ModificationController::class, 'index'])->name('modifications.index');
    Route::get('modifications/{modification}', [ModificationController::class, 'show'])->name('modifications.show');
    Route::get('modifications/{modification}/imprimer', [ModificationController::class, 'imprimer'])->name('modifications.imprimer');

    Route::get('engagements', [EngagementController::class, 'index'])->name('engagements.index');
    Route::get('engagements/{engagement}', [EngagementController::class, 'show'])->name('engagements.show');
    Route::get('engagements/{engagement}/imprimer', [EngagementController::class, 'imprimer'])->name('engagements.imprimer');
    Route::get('mandats', [MandatController::class, 'index'])->name('mandats.index');
    Route::get('mandats/{mandat}', [MandatController::class, 'show'])->name('mandats.show');
    Route::get('mandats/{mandat}/imprimer', [MandatController::class, 'imprimer'])->name('mandats.imprimer');
    Route::get('marches', [MarcheController::class, 'index'])->name('marches.index');
    Route::get('marches/{marche}', [MarcheController::class, 'show'])->name('marches.show');
    Route::get('tiers', [TiersController::class, 'index'])->name('tiers.index');
    Route::get('tiers/{tiers}', [TiersController::class, 'show'])->name('tiers.show');

    Route::get('titres', [TitreRecetteController::class, 'index'])->name('titres.index');
    Route::get('titres/{titre}', [TitreRecetteController::class, 'show'])->name('titres.show');
    Route::get('titres/{titre}/imprimer', [TitreRecetteController::class, 'imprimer'])->name('titres.imprimer');

    Route::get('tresorerie', [TresorerieController::class, 'index'])->name('tresorerie.index');
    Route::get('tresorerie/{tresorerie}', [TresorerieController::class, 'show'])->name('tresorerie.show');

    Route::get('programmes', [ProgrammeController::class, 'index'])->name('programmes.index');
    Route::get('services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('natures', [NatureController::class, 'index'])->name('natures.index');
    Route::get('exercices', [ExerciceController::class, 'index'])->name('exercices.index');
    Route::get('comptes', [CompteController::class, 'index'])->name('comptes.index');
    Route::get('journaux', [JournalController::class, 'index'])->name('journaux.index');
    Route::get('ecritures', [EcritureController::class, 'index'])->name('ecritures.index');
    Route::get('ecritures/{ecriture}', [EcritureController::class, 'show'])->name('ecritures.show');

    Route::prefix('execution')->name('execution.')->controller(EtatBudgetaireController::class)->group(function () {
        Route::get('depenses', 'depenses')->name('depenses');
        Route::get('recettes', 'recettes')->name('recettes');
    });

    Route::prefix('etats')->name('etats.')->controller(EtatController::class)->group(function () {
        Route::get('journal', 'journal')->name('journal');
        Route::get('grand-livre', 'grandLivre')->name('grand-livre');
        Route::get('balance', 'balance')->name('balance');
        Route::get('compte-de-resultat', 'resultat')->name('resultat');
        Route::get('bilan', 'bilan')->name('bilan');
    });
});

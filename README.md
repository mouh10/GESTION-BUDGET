# Gestion — budget de l’État

Application web de **gestion budgétaire publique** pour un ministère ou un service de l’État : budget-programme en AE/CP, chaîne complète de la dépense (engagement → visa du contrôle financier → liquidation → mandat → prise en charge → paiement), recettes, modifications budgétaires, marchés, trésorerie et comptabilité générale.

Développée avec **Laravel 12 (PHP 8.2+)**, Blade et Tailwind CSS.

## Fonctionnalités

| Module | Contenu |
|---|---|
| **Nomenclature** | Programmes et actions (classification programmatique), services gestionnaires (administrative), natures économiques par titre (1 Dette, 2 Personnel, 3 Biens et services, 4 Transferts courants, 5 Investissements, 6 Transferts en capital) et catégories de recettes, chacune reliée à son compte d’imputation comptable |
| **Crédits** | Lignes programme.action × service × nature × source de financement (État, emprunt, don), AE et CP (distincts pour les titres 5 et 6), situation en temps réel : dotation initiale, modifications, réserve, engagé, liquidé, ordonnancé, payé, disponible |
| **Modifications budgétaires** | Virements (même programme), transferts (entre programmes), ouvertures (LFR), annulations, gels et dégels. Brouillon puis approbation, avec contrôle d’équilibre et de disponibilité |
| **Dépenses** | Engagement avec contrôle des AE disponibles, visa ou rejet motivé du contrôleur financier, liquidation (service fait) dans la limite du reste à liquider, mandat avec contrôle des CP disponibles, prise en charge ou rejet par le comptable, paiement. Bon d’engagement et mandat imprimables |
| **Marchés** | Marchés et contrats (type, mode de passation, titulaire, montant). Les engagements rattachés ne peuvent pas dépasser le montant du marché |
| **Recettes** | Prévisions, émission de titres (prise en charge comptable), recouvrements partiels ou totaux, annulation |
| **Trésorerie** | Compte au Trésor, banques, régies, virements internes, soldes |
| **États d’exécution** | Situation des dépenses par programme, action, titre, service, source ou ligne (export CSV, impression, situation à une date), situation des recettes |
| **Comptabilité générale** | Écritures automatiques et manuelles en partie double, livre journal, grand livre, balance, bilan, compte de résultat, clôture avec à-nouveaux |
| **Tableau de bord** | Crédits ouverts, taux d’engagement, d’ordonnancement et de paiement, dossiers à traiter selon le rôle, exécution par programme et par titre, recettes, lignes en tension |

### Rôles

| Rôle | Peut faire |
|---|---|
| **Administrateur** | Tout, plus : paramètres (nomenclature, exercices, utilisateurs) et approbation des actes de modification |
| **Ordonnateur** | Crédits, prévisions, projets de modification, engagements, liquidations, mandats, titres de recette, marchés, fournisseurs |
| **Contrôleur financier** | Visa ou rejet des engagements |
| **Comptable public** | Prise en charge ou rejet des mandats, paiements, recouvrements, trésorerie, comptabilité |
| **Lecteur** | Consultation de tout |

La cloche en haut de l’écran affiche à chacun ce qui l’attend : engagements à viser, mandats à prendre en charge ou à payer, rejets à corriger, actes à approuver.

### Écritures générées automatiquement

| Opération | Débit | Crédit |
|---|---|---|
| Prise en charge d’un mandat | Compte de la nature (6x ou 2x) | Compte du bénéficiaire (4011, 422, 431…) |
| Paiement d’un mandat | Compte du bénéficiaire | Trésorerie (532, 521, 581…) |
| Émission d’un titre de recette | Redevable (4111) | Compte de la nature (70x, 71x, 75x) |
| Recouvrement | Trésorerie | Redevable |

### Règles de contrôle

- **AE disponibles** = AE révisées − AE gelées − engagements soumis ou visés
- **CP disponibles** = CP révisés − CP gelés − mandats émis, pris en charge ou payés
- Hors investissement (titres 1 à 4), AE = CP.
- Un rejet (engagement ou mandat) libère immédiatement les crédits.

## Installation

### Prérequis

- **PHP 8.2 ou plus** avec les extensions `pdo_sqlite` (ou `pdo_mysql`), `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo` et, de préférence, `intl`
- **Composer**
- Node.js n’est pas nécessaire : les styles sont déjà compilés dans `public/build`.

Sous Windows : [Laragon](https://laragon.org) ou [XAMPP](https://www.apachefriends.org).

### Commandes

```bash
composer install
cp .env.example .env              # Windows : copy .env.example .env
php artisan key:generate
php -r "touch('database/database.sqlite');"
php artisan migrate --seed
php artisan serve
```

Ouvrez ensuite **http://localhost:8000**.

Si vous mettez à jour une installation précédente (version « ventes et factures »), la structure de la base a changé : lancez `php artisan migrate:fresh --seed` (cela efface les anciennes données).

### Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | admin@gestion.test | password |
| Ordonnateur | ordonnateur@gestion.test | password |
| Contrôleur financier | controleur@gestion.test | password |
| Comptable public | comptable@gestion.test | password |
| Lecteur | lecteur@gestion.test | password |

Les données de démonstration décrivent un ministère fictif (3 programmes, 4 services, 22 lignes de crédits, marchés, engagements à tous les stades, recettes et modifications budgétaires). Pour démarrer à vide : `GESTION_DEMO=false` dans `.env`, puis `php artisan migrate:fresh --seed`.

**Changez les mots de passe** avant toute utilisation réelle.

## Mise en place pour votre ministère

1. **Paramètres › Exercices** : vérifiez la gestion en cours.
2. **Paramètres › Programmes et actions** et **Services gestionnaires** : saisissez la structure de votre budget-programme.
3. **Paramètres › Nomenclature économique** : complétez les natures selon la nomenclature officielle et vérifiez les imputations comptables.
4. **Budget › Crédits** : saisissez les dotations de la loi de finances initiale (AE et CP).
5. **Budget › Prévisions de recettes**.
6. **Trésorerie** : créez le compte au Trésor et, le cas échéant, les régies.
7. Créez les utilisateurs avec leur rôle.

Nom affiché, structure et tutelle se règlent dans `.env` (`APP_NAME`, `GESTION_ENTREPRISE_NOM`, `GESTION_ENTREPRISE_TUTELLE`). Le seuil d’alerte des lignes en tension (90 % par défaut) se règle dans `config/gestion.php`.

## Structure du code

```
app/Services/
├── Credits.php        Situation des crédits (AE/CP), regroupements, approbation des modifications
├── Depenses.php       Chaîne de la dépense : engagement, visa, liquidation, mandat, prise en charge, paiement
├── Recettes.php       Titres de recette, recouvrements, situation des recettes
├── Comptabilite.php   Moteur de comptabilité générale (partie double, trésorerie, clôture)
├── Etats.php          Balance, grand livre, bilan, compte de résultat
└── Numerotation.php   EJ-2026-0001, LQ-, MD-, TR-, MB-…
app/Models/            Programme, Action, Service, Nature, LigneCredit, Modification, Marche, Engagement,
                       Liquidation, Mandat, PrevisionRecette, TitreRecette, Tiers, CompteTresorerie…
database/seeders/      Plan comptable, nomenclature économique, démonstration
tests/Feature/         Chaîne de la dépense, recettes, comptabilité, pages et droits par rôle
```

## Tests

```bash
php artisan test
```

## Limites connues

- La nomenclature économique fournie est une base de travail inspirée du cadre UEMOA : adaptez codes et libellés à la nomenclature budgétaire officielle en vigueur.
- Le plan comptable part du SYSCOHADA révisé, complété de comptes propres à l’administration. Il peut être adapté au plan comptable de l’État (PCE).
- Les restes à payer et les AE non consommées ne sont pas reportés automatiquement sur la gestion suivante.
- Pas de gestion de la paie ni des stocks.

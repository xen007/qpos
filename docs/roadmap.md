# QPOS — Roadmap de référence

**Version : 4.2 — consolidée**

**Base du texte : 30/09/2026 ; préparation dans le dépôt : 01/10/2026.**

**Socle : Laravel 12.69.3, PHP 8.2.12, XAMPP, Tailwind 4, React 19.**

Ce document pilote le chantier et contient ses décisions, sa cartographie et son journal. Les anciennes roadmaps et la carte de référence sont conservées dans `archives/`. Les rapports techniques cités restent datés : leur contenu historique ne remplace pas l'état constaté ici.

## 1. Objectif et périmètre

QPOS devient une application professionnelle pour une entreprise possédant plusieurs boutiques : alimentation, quincaillerie, boutique généraliste ou commerce nécessitant des lots et péremptions.

- Catalogue partagé, unités multiples et conditionnements.
- Stocks séparés par boutique ; lots, péremption et journal des mouvements.
- Achats, imports, transferts et inventaires.
- Ventes, paiements multiples, dettes clients/fournisseurs et avoirs.
- Retours, échanges, annulations et remboursements traçables.
- Caisse, dépenses, comptage et écarts.
- Tarifs, promotions, scanner USB, tickets et étiquettes.
- Dashboard, marges, valorisation du stock et rapports exportables.
- Synthèse quotidienne automatique, notifications et sauvegardes restaurables.
- Français par défaut, anglais au choix, responsive, accessibilité et thèmes clair/sombre.

Périmètre : une entreprise et plusieurs boutiques. Les prescriptions et autres fonctions spécialisées de pharmacie ne sont pas incluses implicitement.

## 2. État de départ

### Historique

- S0 : fondation Git.
- S1 : sécurisation des uploads.
- S2 : premières traductions FR/EN et thème sombre.
- S3 : pages Tailwind, Form Requests et Policies.

### État Git constaté le 01/10/2026, avant préparation documentaire

- Branche `main` ; aucun écart affiché avec la référence locale `origin/main`.
- Dernier commit : `e675093` — migration Laravel 10 vers 12.69.3.
- Les cinq fichiers de migration sont commités ; aucune modification de code en attente.
- Seul `docs/roadmap-V3.md` était non suivi. Il est préservé dans les archives de cette préparation.
- Aucun fetch réseau réalisé pour ce constat : l'état distant effectif sera vérifié pendant la Phase 0.

### Migration et vérifications

Laravel 12 a été installé et des vérifications techniques ont réussi précédemment : syntaxe PHP, démarrage, routes, compilation des vues/configuration, exigences de plateforme et audit Composer sans avis connu au moment du contrôle. Des ouvertures HTTP ont été vérifiées ; les interactions navigateur et opérations métier ne sont pas entièrement validées.

Les relevés historiques de 142 routes totales et 108 routes protégées par permission ont des périmètres différents. Ils ne constituent pas une garantie globale de sécurité.

Les statuts sont distingués : installé, vérifié, commité, fusionné, poussé. Le hash de préparation documentaire est fourni dans le bilan Git ; aucun hash de son propre commit n'est inscrit artificiellement dans ce fichier.

## 3. Méthode de travail

### 3.1 Une passe principale par groupe de fichiers

Chaque groupe possède une phase principale réunissant ses besoins métier, sécurité, performance, traductions, interface et accessibilité. Les adaptations de dépendances et corrections de régression sont ciblées et consignées.

Avant un lot : consulter la cartographie, identifier les fichiers et dépendances directes, confirmer les règles et critères de fin, expliquer les modifications, puis réaliser un ensemble fonctionnel cohérent. Aucun réaudit général entre les lots. Les lectures de `vendor/` et `node_modules/` restent exceptionnelles et ciblées.

### 3.2 Dépendances et fichiers partagés

Une structure nécessaire est disponible dans le lot courant ou avant. L'ordre migrations, données de référence, modèles, services, validation/autorisation, contrôleurs, routes et interfaces guide les travaux sans imposer d'étape cassée.

Les conventions des routes, traductions, modèles et composants communs sont fixées tôt. Les compléments sont regroupés par lot ; une fonctionnalité ne reste pas sans permission ou traduction en attendant une intervention globale.

### 3.3 Commits et limites d'intervention

- Un commit correspond à un changement cohérent et vérifiable, sans séparation obligatoire par couche.
- Le propriétaire effectue normalement les commits ; une délégation explicite autorise l'assistant pour le lot concerné. La préparation documentaire du 01/10/2026 est déléguée ; cette autorisation ne vaut pas automatiquement pour les phases suivantes.
- Vérifier la syntaxe avec `php -l` après toute modification PHP.
- Aucun nouveau test automatisé sans accord explicite.
- Aucun changement hors périmètre sans signalement et accord sur son extension.
- Ne pas modifier `.env`, `vendor/` ou `node_modules/` manuellement. Une opération Composer autorisée peut actualiser les dépendances installées.
- Runtime, Dockerfiles et dépendances ne changent que dans un lot explicitement consacré à ces sujets.
- Les commandes terminal utilisent des caractères ASCII ; les textes et traductions peuvent utiliser les accents.

### 3.4 Documentation et contrôles

Cette roadmap est la référence de pilotage. Chaque lot consigne résultat, fichiers, vérifications, limites et prochaine action. Les références techniques sont [routes-permissions.md](routes-permissions.md), [plugins-front-par-page.md](plugins-front-par-page.md) et [LARAVEL13-COMPAT.md](LARAVEL13-COMPAT.md). Les deux premières cartographies et le rapport de compatibilité seront actualisés uniquement sur les périmètres modifiés ; le rapport Laravel 13 décrit encore un état antérieur et sera recalculé lors du lot runtime.

### 3.5 Registre des décisions

| ID | Statut | Décision ou question | Conséquences / phase |
|---|---|---|---|
| D01 | Validée | Une entreprise, plusieurs boutiques, catalogue partagé | Isolation des données et droits, phases 1–2 |
| D02 | À décider | Réserver le stock au panier ? Proposition initiale : non | Checkout, phase 1 |
| D03 | À décider | Caisses physiques partagées entre caissiers ? | Sessions et responsabilités, phase 1 |
| D04 | À décider | Méthode de valorisation des stocks | Coûts historiques et marges, phase 1 |
| D05 | À décider | RPO : perte maximale de données acceptable | Fréquence des sauvegardes, phase 1 |
| D06 | À décider | RTO : délai maximal de remise en service | Restauration, phase 1 |
| D07 | À décider | Carte : terminal externe enregistré ou intégration réelle ? | Contrat décidé en phase 1, mise en œuvre en phase 4 |
| D08 | À décider | Droits globaux ou propres à chaque boutique ? | Autorisations, phase 1 |
| D09 | À décider | Priorité des tarifs et cumul des promotions | Moteur de prix, phase 1 |
| D10 | À décider | Taxes incluses/ajoutées, précision et arrondis | Calculs et documents, phase 1 |
| D11 | Validée | Français par défaut, anglais au choix, préférence conservée | Blade et React |
| D12 | Validée | Soft Modern : bleu pétrole, vert sauge, Inter ; clair/sombre | Composants communs, phase 1 |

Chaque décision ajoute sa date, sa raison et ses conséquences lorsqu'elle est tranchée. Statuts : proposée, à décider, validée, remplacée. Une proposition n'est pas une règle approuvée.

### 3.6 Retour arrière et échéances

Avant toute opération sensible : identifier le code en place, disposer d'une sauvegarde adaptée et définir la récupération. Après le lot : consigner les vérifications et le point de reprise.

Le retour du code suppose sa compatibilité avec la base. La restauration peut supprimer des opérations postérieures à la sauvegarde. Une vente ou un paiement effectué peut exiger une correction compensatrice. Un commit Git ne restaure ni la base ni les fichiers importés. Une ancienne sauvegarde n'est jamais restaurée sur les données courantes sans traiter les opérations intervenues depuis.

Les échéances runtime sont indépendantes de l'avancement visuel ; la Phase 8 peut s'intercaler entre deux lots. Sauvegarde et restauration sont vérifiées avant une conversion importante.

## 4. Règles métier communes

### 4.1 Boutiques et autorisations

Un utilisateur peut être affecté à plusieurs boutiques. Ventes, achats, sessions et mouvements appartiennent à une boutique explicite. L'administrateur choisit celle de son opération. Lectures, écritures, exports et accès directs sont contrôlés côté serveur. Panier et checkout conservent leur contexte même avec plusieurs onglets.

### 4.2 Unités et données existantes

Une unité de stock de référence par produit ; facteurs de conditionnement strictement positifs ; quantités fractionnaires selon les produits. Prix et codes-barres peuvent dépendre du conditionnement. Unité, facteur, prix, coût et remises utiles sont conservés dans l'historique.

Adapter `units`, `products.unit_id` et `order_products`, sans les recréer arbitrairement. Préserver les unités et quantités ; isoler les cas ambigus. Aucun lot, coût ou date inconnu n'est inventé pour masquer une lacune.

### 4.3 Stock et lots

Le journal trace réception, vente, retour, transfert, perte, inventaire et correction. `StockService` assure transactions, concurrence, traçabilité des lots et distinction entre disponible, transit et impropre à la vente.

Les soldes utilisés pour accélérer les lectures sont mis à jour dans la même transaction que les mouvements et restent réconciliables. FEFO est la règle cible pour les périssables ; la valorisation financière est distincte. La réservation au panier reste une décision à prendre.

### 4.4 Tarifs et promotions

Définir avant le moteur : priorités client/quantité/conditionnement/boutique, promotions cumulables ou exclusives, permissions des remises manuelles, taxes, précision et arrondis. Les montants sont recalculés côté serveur et les conditions appliquées restent attachées à la vente. Les promotions couvrent pourcentage, montant fixe, offres de quantité et lots promotionnels selon les règles validées.

### 4.5 Paiements, dettes et avoirs

Une vente peut recevoir plusieurs paiements ; mixte signifie plusieurs lignes. Distinguer montant dû, sommes reçues, montants affectés, monnaie rendue, dette restante, avoir utilisé et remboursements. Une dette n'est pas un encaissement. Ne pas conserver de données sensibles de carte ; les références autorisées d'un prestataire peuvent servir à la réconciliation.

### 4.6 Retours et annulations

Les retours référencent `order_products` et les allocations de stock. La quantité ne dépasse pas le solde retournable ; les prix/remises historiques servent au calcul. Une vente partiellement réglée exige de distinguer réduction de dette et remboursement. Avoir : solde et historique d'utilisation. Échange : retour, nouvelle vente et différence à régler.

Les produits endommagés/périmés ne reviennent pas au stock vendable. Documents originaux et traces sont conservés ; annulations et corrections produisent les mouvements et documents appropriés.

### 4.7 Caisse, dépenses et synthèse quotidienne

Une vente est liée à une session de caisse. Un règlement/remboursement ultérieur est lié à la session pendant laquelle il intervient. Seules les espèces affectent le comptage physique attendu. Les dépenses précisent leur moyen de règlement. Clôture : comptage humain et écart explicite.

La synthèse quotidienne est automatique ; elle ne remplace pas la clôture physique. Fuseau, sessions ouvertes, opérations tardives, verrouillage éventuel et reprise d'envoi sont définis. Le scheduler et les workers nécessitent un environnement actif.

### 4.8 Indicateurs et valorisation

Séparer ventes nettes, coût des marchandises vendues (CMV), marge brute, charges, résultat de gestion et trésorerie. Le CMV se rattache aux ventes, pas à l'ensemble des achats de la période. La méthode et les informations historiques sont décidées avant les nouvelles opérations. Référence : [IAS 2 — Stocks](https://www.ifrs.org/issued-standards/list-of-standards/ias-2-inventories/). Les rapports de gestion ne constituent pas une intégration comptable réglementaire.

## 5. Phases et critères de fin

### Phase 0 — Clôturer Laravel 12

Objectif : vérifier le fonctionnement existant et disposer d'un point de récupération.

1. Confirmer Git, commit, versions installées et intégration effective. La migration étant déjà commitée sur `main`, ne pas répéter sa fusion.
2. Comparer PHP Apache/CLI, extensions et accès MySQL.
3. Sauvegarder base et fichiers nécessaires hors public et hors Git, puis vérifier une restauration isolée.
4. Rejouer les contrôles techniques pertinents : PHP, Composer, routes, vues/configuration. Préserver l'état initial des caches.
5. Vérifier navigateur et JavaScript : connexion, dashboard, POS, achats, catalogue, clients/fournisseurs, rapports, administration, paramètres, FR/EN et thèmes.
6. Sur une copie dédiée, vérifier une vente existante, paiement, stock, achat, document généré et import représentatif.
7. Consigner résultats, limites et défauts ; actualiser le journal.

Aucune modification de code métier dans cette phase. Les défauts sont décrits et classés ; une correction exige un périmètre distinct. Critère : environnement utilisable, sauvegarde restaurable, contrôles essentiels réussis, aucun blocage critique de migration laissé sans résolution.

### Phase 1 — Règles, composants communs et fondations

Finaliser les décisions et scénarios ; définir schéma cible, conversion et contrats ; compléter les chemins de cartographie au premier lot concerné. Créer la structure minimale boutiques/affectations nécessaire aux autorisations. Préparer audit métier sans secrets, sauvegardes régulières et contrats de notifications/traitements différés.

Stabiliser sidebar, navigation, composants, états de chargement/vide/erreur/succès, FR/EN, thèmes et accessibilité. Palette : `#1E5F74` et `#88B04B`, avec variantes accessibles ; Inter ; Sonner et Lucide. Conserver Blade et les îles React. Toute dépendance ajoutée répond à un besoin justifié.

Critère : décisions structurantes prises, composants utilisables, protections communes opérationnelles et transition définie. Les phases suivantes reçoivent directement leur finition visuelle.

### Phase 2 — Catalogue et boutiques

Produits, catégories, marques, unités, conditionnements, conversions, codes-barres, tarifs et moteur de promotions. Administration boutiques/affectations et sélecteur. Validation, images, recherches bornées, pagination et contrôle de consultation produit.

Conversion : structure, données de référence, conversion contrôlée, rapprochement. Fournisseurs restent propriétaires phase 3 ; clients phase 4 ; leurs contrats sont fixés avant usage.

Critère : catalogue exploitable, accès contrôlés, conversions expliquées, données préservées. Les capacités nouvelles sont exposées quand les consommateurs concernés les prennent en charge.

### Phase 3 — Approvisionnement et stock

Ordre interne : journal/soldes/lots et `StockService` ; reprise des stocks par mouvements d'ouverture ; raccordement des consommateurs existants, dont POS ; fournisseurs/achats et `PurchaseService` ; imports/transferts/inventaires.

Réception par boutique et conditionnement ; achats, modifications et annulations contrôlées ; dettes fournisseurs/échéances/règlements ; imports validés avec traitement des doublons et compte rendu ; lots/péremption/alertes/FEFO ; expédition, réception totale/partielle, reliquats et annulations ; inventaire tenant compte des ventes pendant comptage.

Le POS existant utilise la même gestion de stock avant activation des nouveaux flux. L'adaptation minimale est planifiée ; elle ne constitue pas une deuxième refonte visuelle du POS.

Critère : stocks réconciliables, achats et POS cohérents, lots traçables, transferts et inventaires vérifiés.

### Phase 4 — Vente complète et caisse

Clients, client de passage protégé, dettes/échéances/avoirs ; `SaleService` ; POS tactile et clavier ; conditionnements/tarifs/promotions ; scanner USB ; paiements multiples et monnaie rendue ; caisses/comptage/écarts ; dépenses ; retours/échanges/remboursements/annulations ; historique/règlements/factures/tickets 80 mm/étiquettes selon formats retenus.

Préparer sessions/paiements avant branchement du checkout. Isolation des paniers, transactions et verrous, idempotence et récupération du résultat après perte réseau. Une réponse perdue après validation ne doit pas entraîner une deuxième vente.

Critère : vente traçable, stock correct, encaissements cohérents, retours contrôlés, interface FR/EN finalisée.

### Phase 5 — Dashboard et rapports

Dashboard utile avec filtres et graphiques. Rapports vendeur, boutique, période, catégorie, produit, marge, stock valorisé, péremption et écarts caisse ; dépenses/résultat de gestion. Écran, Excel et PDF selon le périmètre validé, avec les mêmes définitions et restrictions d'accès. Pagination, agrégats et volumes mesurés.

Synthèse quotidienne PDF/email : installer transport, planification et traitement différé au plus tard ici. Gérer sessions ouvertes, fuseau, opérations tardives et reprises sans duplications.

Critère : chiffres rapprochés des opérations, exports cohérents, performances mesurées et synthèse vérifiée.

### Phase 6 — Administration complète

Utilisateurs/rôles/permissions, dernier administrateur protégé, correction noms/identifiants des permissions ; paramètres, devises, URL, écritures de configuration et caches ; connexion/déconnexion/récupération/profil/Google si retenu ; limites de tentatives/sessions/FR-EN ; consultation audit ; méthodes mortes recensées.

Les protections indispensables sont livrées dans les lots qui en dépendent. Un défaut bloquant d'autorisation n'attend pas cette phase. Critère : comptes fiables, autorisations cohérentes, paramètres exploitables.

### Phase 7 — Exploitation et intégrations

Centre de notifications/badges/temps réel ; email puis SMS/WhatsApp selon comptes, budget et prestataires ; canaux autorisés par utilisateur/boutique ; scheduler/workers/reprises/surveillance. Externaliser les sauvegardes vers la destination choisie ; documenter protection et restauration.

Retirer AdminLTE, Bootstrap et Font Awesome après disparition de leurs derniers consommateurs. Vérifier sessions/cookies/CORS, échappement, logs, bundles/caches et politique de rétention des exports/imports/logs. Documenter installation, exploitation, déploiement et reprise.

Critère : automatisations vérifiées, restauration réalisable, procédures utilisables par un autre intervenant.

### Phase 8 — Runtime et Laravel 13

Échéances indépendantes : remplacer PHP 8.2 avant le **31/12/2026** et Laravel 12 avant le **24/02/2027**. Maintenir les correctifs pendant la transition ; PHP 8.2.12 n'est pas considéré à jour du seul fait que la branche 8.2 reste supportée. Sources : [PHP](https://www.php.net/supported-versions.php), [Laravel](https://laravel.com/docs/12.x/releases).

Choisir le runtime maintenu, avec ou sans Docker ; préparer un environnement séparé et aligner CLI/web/extensions ; recalculer Composer et les incompatibilités Yajra ; planifier Intervention Image vers une version maintenue ; migrer Laravel ; rejouer les parcours et la restauration ; documenter déploiement et retour arrière. Aucune montée majeure de package sans incompatibilité ou besoin identifié.

Critère : runtime supporté, packages compatibles, parcours vérifiés. Cette phase peut s'intercaler entre deux lots avant leurs échéances.

## 6. Critères communs de clôture

- Scénario principal et cas d'erreur vérifiés.
- Autorisations et refus vérifiés, dont boutique.
- FR/EN complets, thèmes, responsive et clavier contrôlés.
- Syntaxe PHP et compilation front selon le périmètre.
- Performances observées sur un volume représentatif : N+1, pagination, recherches bornées, index justifiés, agrégats et bundles.
- Régressions recherchées sur les dépendances directes ; résultat et limites explicités.
- Fichiers, décisions, vérifications et prochaine action consignés.

## 7. Scénarios critiques

À vérifier dès disponibilité, puis à automatiser uniquement avec accord :

1. Deux caissiers tentent la dernière unité.
2. Checkout soumis deux fois.
3. Vente enregistrée mais réponse réseau perdue.
4. Retour partiel d'une vente remisée et partiellement réglée.
5. Transfert reçu partiellement.
6. Vente pendant inventaire.
7. Accès direct/export/opération d'une boutique non autorisée.
8. Conditionnement et plusieurs lots dans une même opération.

## 8. Cartographie de reprise

Les groupes ci-dessous reprennent les responsabilités utiles de l'ancienne carte. Les chemins précis et décisions déjà constatées restent consultables dans [l'archive de la carte](archives/REFERENCE-MAP-2026-09-29.md). Les noms des services à créer indiquent des responsabilités prévues, pas des fichiers existants.

| Groupe | Phase principale | Contrat / dépendances |
|---|---|---|
| Layout, styles, shell, composants, thème, i18n et Vite | 1 | Conventions puis compléments ciblés par parcours |
| Boutiques, affectations, autorisations communes | 1–2 | Structures/protections en 1 ; CRUD complet en 2 |
| ProductController, UnitController, BrandController, CategoryController, requêtes et vues catalogue | 2 | Contrat d'import défini pour phase 3 |
| Product, unités, conditionnements, prix | 2 | Contrats partagés par stock, achats et ventes |
| SupplierController, PurchaseController, Purchase/PurchaseItem, ProductsImport, React achat | 3 | Fournisseurs, approvisionnement, imports ; fournisseur interne protégé |
| StockService, lots, mouvements, transferts, inventaires | 3 | Raccordement préalable de tous les consommateurs |
| CustomerController, CartController, OrderController, PosCart, Order/OrderProduct/OrderTransaction, React POS | 4 | Clients, cycle vente complet, historique, règlement et factures |
| DashboardController, ReportController, DateRange, Chart.js, vues et exports | 5 | Périodes, fuseau et indicateurs communs ; profil partagé à coordonner avec phase 6 |
| UserManagementController, RoleController, PermissionController, policies et seeder permissions | 6 | Protections antérieures conservées, dernier Admin et rôles attribués |
| WebsiteSettingController, CurrencyController, Helper, config/system.php | 6 | Paramètres, URL Linux, devise par défaut unique, secrets et caches |
| AuthController, GoogleController, LanguageController, middleware et profil | 6 | Sessions, suspension, OTP/récupération, contrôle des tentatives |
| Routes, PermissionRoutes, catalogues FR/EN | Partagés | Compléments livrés avec chaque fonctionnalité |
| Notifications et automatisations | 5–7 | Email quotidien en 5 ; autres canaux en 7 |
| Composer et runtime | 0 puis 8 | Maintenance autorisée et migration dédiée |

Défauts déjà relevés à suivre : consultation produit sans contrôle adapté (phase 2), trois méthodes mortes (phases 3/6), noms/IDs des permissions (phase 6 ou avant si bloquant), écriture de configuration en clair et site_url/APP_URL (phase 6), icônes et layouts historiques (parcours puis retrait phase 7). Un relevé statique n'est pas un test fonctionnel réalisé.

## 9. Hors périmètre actuel

Offline/synchronisation, plusieurs entreprises indépendantes, prescriptions, affichage client secondaire, scanner caméra sans extension validée, change multi-devises, comptabilité externe, fidélité/commissions/objectifs, mobile natif/e-commerce/API publique, pointage/planning RH.

## 10. Sauvegardes et estimations

### 10.1 Sauvegardes

Politique initiale : **7 quotidiennes, 4 hebdomadaires, 12 mensuelles**, sous réserve de capacité et besoins de conservation. Il s'agit de points conservés, pas d'une garantie sur la perte maximale.

RPO et RTO sont décidés en phase 1 ; aucune valeur d'exemple n'est approuvée implicitement. La fréquence répond au RPO. La politique commence avec les premières sauvegardes régulières ; l'externalisation est complétée en phase 7. Une sauvegarde avant conversion reste nécessaire indépendamment du calendrier.

### 10.2 Estimations provisoires

| Phase | Jours de travail |
|---|---:|
| 0 — Clôture Laravel 12 | 1 |
| 1 — Règles et fondations | 5–7 |
| 2 — Catalogue et boutiques | 7–10 |
| 3 — Approvisionnement et stock | 10–14 |
| 4 — Vente et caisse | 12–16 |
| 5 — Dashboard et rapports | 7–10 |
| 6 — Administration | 5–7 |
| 7 — Exploitation | 7–10 |
| **Total indicatif** | **54–75** |
| 8 — Runtime/Laravel 13 | Estimation séparée, sous échéances |

Enveloppe indicative de 12–16 semaines à réviser après phase 1 selon disponibilité, données, matériel et intégrations. Les échéances de support priment sur les finitions non bloquantes.

## 11. Journal de reprise

| Date | État / action | Prochaine action |
|---|---|---|
| 30/09/2026 | Version 4.2 consolidée dans la conversation : phases regroupées, décisions internes, récupération avant opérations, rétention/RPO/RTO | Préparation documentaire |
| 01/10/2026 | Préparation de cette référence ; migration commitée sur main à e675093 ; anciens documents archivés et liens de redirection conservés ; commit documentaire demandé par le propriétaire | Présenter Phase 0 puis attendre son lancement |

**Phase 0 : préparée, non exécutée.** Comparaison Apache/CLI, nouvelle sauvegarde/restauration, vérifications navigateur et essais sur copie restent à réaliser. Les vérifications anciennes ne sont pas présentées comme des résultats de cette phase.

## 12. Fiche d'exécution et Git — Phase 0

### Ordre de travail

1. Relever Git et versions en un seul passage ciblé ; confirmer URL locale et configuration Apache/CLI sans afficher les secrets.
2. Identifier outils MySQL, base source et fichiers à préserver ; créer sauvegarde datée et copie isolée. Documenter les destinations et contrôler la restauration avant essais d'écriture.
3. Sur l'environnement approprié, vérifier PHP, Composer et Laravel ; compiler les vues/configuration et restaurer l'état de cache initial.
4. Parcours navigateur : connexion/logout, dashboard, POS, achat, produits, clients/fournisseurs, rapports, utilisateurs/rôles, paramètres ; contrôle JS, FR/EN et thèmes.
5. Copie isolée : vente/paiement/stock, achat, document et import. Aucun essai métier d'écriture sur la base courante.
6. Pour chaque contrôle : attendu, observé, réussi/échoué/non vérifiable, cause et action. Un blocage d'accès ou de configuration est signalé dès constat.
7. Ajouter ici le bilan et les défauts ; présenter les changements. Le propriétaire réalise les commits, sauf nouvelle délégation explicite.

### Contrôle Git avant intervention

```powershell
git status --short --branch
git branch --show-current
git log --oneline -5
git diff --stat
```

La migration est déjà commitée sur `main`. Ne pas répéter une fusion ni supposer que la branche n'a pas changé. Vérifier l'état distant pendant Phase 0 ; un push est une action distincte à autoriser.

Avant commit : examiner le diff, ajouter seulement les fichiers du lot et vérifier l'index. Les noms de fichiers et messages sont adaptés au changement réel. Aucun `git add .`, reset ou écrasement de travail existant pour préparer un lot.

La préparation documentaire a reçu une délégation de commit. La Phase 0 reste soumise à validation de son lancement ; ses sauvegardes et vérifications ne sont pas exécutées par la seule création de ce document.

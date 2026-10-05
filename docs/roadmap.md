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
| D02 | Validée — 01/10/2026 | Pas de réservation du stock au panier | Le panier ne modifie pas les soldes ; contrôle et verrouillage transactionnel au checkout. Évite les réservations expirées et convient au fonctionnement retenu. Phase 4 |
| D03 | Validée — 01/10/2026 | Session de caisse individuelle par caissier | Chaque vente et opération de caisse est attribuable à une session et à son responsable ; prévoir une passation lors d'un changement de caissier. Phase 4 |
| D04 | Validée — 01/10/2026 | FEFO pour les produits périssables, FIFO sinon | Prioriser l'expiration pour réduire les pertes ; utiliser l'ancienneté des réceptions pour les autres produits. Exige traçabilité des lots/coûts et allocations explicites. Phases 2–5 |
| D05 | Validée — 01/10/2026 | RPO de 24 heures | Perte maximale retenue : une journée ; sauvegarde quotidienne en fin de journée, cohérente avec l'arrêt nocturne du serveur. Vérifier le succès et la restaurabilité. Phase 7 |
| D06 | Validée — 01/10/2026 | RTO de 8 heures | Cible de remise en service le jour même, sans présumer de haute disponibilité ; nécessite des procédures et exercices de restauration chronométrés. Phase 7 |
| D07 | Validée — 01/10/2026 | Paiement par carte sur terminal externe | QPOS enregistre le résultat et la référence autorisée, sans traiter ni conserver de données sensibles de carte ; réconciliation externe/manuelle. Phase 4 |
| D08 | Validée — 01/10/2026 | Rôle global avec portée explicite par boutique | Les rôles définissent les capacités et l'affectation détermine le périmètre des données ; les contrôles serveur couvrent lectures, écritures, exports et accès directs. Phases 1–2 |
| D09 | Validée — 01/10/2026 | Priorité tarifaire déterministe et une seule promotion appliquée | Évite les cumuls ambigus ; recalcul serveur et conservation des conditions appliquées dans l'historique de vente. Phase 4 |
| D10 | Validée — 01/10/2026 | Prix TTC (taxes incluses) | Le prix annoncé correspond au total dû ; conserver taux, base et montant de taxe ainsi que les règles de précision/arrondi sur les opérations. Phases 1 et 4 |
| D11 | Validée | Français par défaut, anglais au choix, préférence conservée | Blade et React |
| D12 | Validée | Soft Modern : bleu pétrole, vert sauge, Inter ; clair/sombre | Composants communs, phase 1 |
| D13 | Validée — 01/10/2026 | Fuseau métier `Africa/Douala` (E04) | Aligner le fuseau applicatif sur l'activité métier ; l'utiliser pour journées, rapports et clôtures, avec stockage cohérent des instants. Phase 1 |
| D14 | À robustifier en Phase 1 | B1 — 419 intermittent au login/logout ; ce passage a réussi avec `localhost` cohérent | Tester `SESSION_DOMAIN=localhost`, aligner `APP_URL`/`system.site_url` (E01), puis vérifier onglets et hôtes séparés avant de décider le réglage |
| D15 | Corrigée, à revalider navigateur | B2 — agrégation dashboard, coût par vente et liste inventaire | Phase 0 ; SQL agrégé, `withSum`, pagination serveur et unité préchargée |
| D16 | Implémentée — à revalider par le propriétaire | Résidus visuels dans sidebar/header ; erreur console `copyright.js` sur élément absent pendant les parcours testés | Phase 1, shell et scripts partagés |
| D17 | Implémentée — à revalider par le propriétaire | Couleurs du thème nulles/non renseignées | Phase 1, clair/sombre et paramètres de thème |
| D18 | Composant et retours CRUD implémentés — à revalider | Boutons de retour manquants/incohérents | Phase 1, navigation et composants |
| D19 | À corriger | Texte anglais restant au pied de facture | Phase 1, traductions et impression |
| D20 | Socle commun implémenté — validation visuelle attendue | Finitions UI/UX et responsive | Phase 1, composants et parcours |
| D21 | Validée — 02/10/2026 | Marque et catégorie facultatives ; nom et prix obligatoires, y compris à l'import. Les autres renseignements sont facultatifs, avec valeurs par défaut possibles | Phase 2 ; conserver les anciennes valeurs nulles ; aucun référentiel fictif |
| D22 | Validée — 02/10/2026 | Prix de vente TTC et coût de référence d'achat distincts par conditionnement ; coût réel conservé sur chaque réception ; mise à jour manuelle du coût de référence réservée à l'administrateur | Phases 2–4 ; aucun écrasement automatique du coût de référence par une réception |
| D23 | Validée — 02/10/2026 | Marque et fournisseur sont deux référentiels distincts ; leur lien est facultatif | Phases 2–3 ; aucun lien obligatoire ni fournisseur déduit de la marque |
| D24 | Validée — 02/10/2026 | Tarifs : client + boutique > client global > boutique > global ; dans une portée, seuil de quantité décroissant, priorité décroissante, ID croissant | Phase 2 ; calcul serveur déterministe et instantané des conditions pour la Phase 4 |
| D40 | À corriger | Ancienne D24 de Phase 0 : sélecteurs du formulaire d'achat ; Tempus Dominus 4 signale Moment.js absent sur la page testée | Phase 3, fournisseur/produits/unités et dépendances front |
| D25 | Validée — 02/10/2026 | Promotions : priorité décroissante, remise la plus avantageuse puis ID croissant ; une promotion par ligne. Prix/coûts et calculs en DECIMAL(20,6), remises HALF_UP à six décimales | Phase 2 ; arrondi FINAL des paiements/factures selon devise en Phase 4 (FCFA : zéro décimale) |
| D41 | À corriger | Ancienne D25 de Phase 0 : quantité initiale à zéro dans le formulaire d'achat | Phase 3 ; imposer une quantité positive avant enregistrement |
| D42 | Validée — 05/10/2026 | Interface multi-boutique détectée dynamiquement selon le nombre de boutiques actives de l'entreprise | Une boutique active : interface simple, sélecteur masqué ; deux boutiques actives ou plus : interface complète avec sélecteur. Ne confère aucun droit supplémentaire et ne remplace pas le contrôle d'accès serveur. Phase 3 |
| D26 | À clarifier | Libellé et sens du prix d'achat dans les parcours produit/achat | Phase 3 ; distinguer coût de réception et référence produit |
| D27 | Clarifié | Menu et bouton de liste mènent au même formulaire ; ce n'est pas un doublon métier | Phase 3 ; deux raccourcis conservés au besoin UX |
| D28 | À corriger | Lecture du scanner USB/code-barres au POS | Phase 4, saisie et correspondance produit/conditionnement |
| D29 | À corriger | Recherche de produits au POS | Phase 4, résultats réactifs et utiles |
| D30 | À corriger | Recherche multicritère | Phase 4, champs et priorité à décider |
| D31 | À corriger / à confirmer | Facture de vente répartie sur deux pages à l'impression ; le parcours actuel rend une page HTML imprimable, sans génération PDF vérifiée | Phase 4, gabarit, impression et décision sur le besoin PDF |
| D32 | À corriger — priorité haute | Transactions depuis un client affichent la liste générique des ventes ; filtre client perdu | Phase 4 ; corriger la route/requête AJAX et vérifier l'isolation par client |
| D33 | À investiguer | Certaines ventes affichent 0 article | Phase 4 ; comparer la vente à ses lignes `order_products` |
| D34 | Limite documentée | Reçu de règlement distinct de la facture de vente ; une réimpression utilise le solde actuel | Phase 4 ; décider s'il faut figer le solde à la date du paiement |
| D35 | À investiguer | Facture de règlement semblant afficher deux ventes | Phase 4 ; obtenir les IDs des ventes et l'URL du document |
| D36 | Compris | `products.purchase_price` est un coût de référence ; un coût réel différent reste sur chaque achat | Phases 2–3 ; achat actuel ne met pas automatiquement à jour la référence produit |
| D37 | À vérifier | Effet d'un changement du prix courant sur statistiques et marges historiques | Phase 5 ; contrôler chaque rapport et ses sources |
| D38 | Validée — 01/10/2026 | Sauvegarde quotidienne en fin de journée ; RPO de 24 heures | Le serveur étant éteint la nuit, cette fréquence borne la perte visée à une journée ; surveiller la réussite et vérifier la restaurabilité. Phase 7 |
| D39 | Validée — 01/10/2026 | Aucune réservation panier ; contrôle et verrouillage du stock au checkout | Le panier ne bloque pas le stock ; revérifier la disponibilité et verrouiller les lignes concernées dans la transaction de vente pour prévenir la survente concurrente. Phase 4 |

Les décisions D02–D10, D13, D38 et D39 ont été validées le 01/10/2026 ; leurs raisons et conséquences sont précisées dans le registre. Les décisions ajoutées ultérieurement suivent le même format. Statuts : proposée, à décider, validée, remplacée. Une proposition n'est pas une règle approuvée.

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

Les soldes utilisés pour accélérer les lectures sont mis à jour dans la même transaction que les mouvements et restent réconciliables. Pour les périssables, les sorties suivent FEFO (expiration la plus proche) ; pour les autres produits, elles suivent FIFO (réception la plus ancienne). Les lots/réceptions portent les quantités et coûts nécessaires à la traçabilité et à la valorisation ; les sorties sont allouées explicitement. Les données historiques manquantes restent signalées, jamais inventées. Aucun stock n'est réservé au panier ; disponibilité revérifiée et verrouillée au checkout dans la transaction de vente.

### 4.4 Tarifs et promotions

La priorité tarifaire est déterministe ; une seule promotion s'applique à une ligne, selon les règles validées. Les remises manuelles restent contrôlées par permission. Les prix sont TTC : le total affiché au client inclut les taxes. Le taux, la base, le montant de taxe, la précision et les arrondis sont définis et conservés avec l'opération. Tous les montants sont recalculés côté serveur et les conditions appliquées restent attachées à la vente. Les promotions couvrent pourcentage, montant fixe, offres de quantité et lots promotionnels selon les règles validées.

### 4.5 Paiements, dettes et avoirs

Une vente peut recevoir plusieurs paiements ; mixte signifie plusieurs lignes. Distinguer montant dû, sommes reçues, montants affectés, monnaie rendue, dette restante, avoir utilisé et remboursements. Une dette n'est pas un encaissement. Ne pas conserver de données sensibles de carte ; les références autorisées d'un prestataire peuvent servir à la réconciliation.

### 4.6 Retours et annulations

Les retours référencent `order_products` et les allocations de stock. La quantité ne dépasse pas le solde retournable ; les prix/remises historiques servent au calcul. Une vente partiellement réglée exige de distinguer réduction de dette et remboursement. Avoir : solde et historique d'utilisation. Échange : retour, nouvelle vente et différence à régler.

Les produits endommagés/périmés ne reviennent pas au stock vendable. Documents originaux et traces sont conservés ; annulations et corrections produisent les mouvements et documents appropriés.

### 4.7 Caisse, dépenses et synthèse quotidienne

Une vente est liée à la session individuelle du caissier responsable. Un règlement/remboursement ultérieur est lié à la session pendant laquelle il intervient. Seules les espèces affectent le comptage physique attendu. Les dépenses précisent leur moyen de règlement. Clôture : comptage humain et écart explicite ; lors d'un changement de caissier, la passation doit laisser une responsabilité et un comptage traçables.

La synthèse quotidienne est automatique ; elle ne remplace pas la clôture physique. `Africa/Douala` est le fuseau métier pour les journées, horaires, rapports et clôtures. Sessions ouvertes, opérations tardives, verrouillage éventuel et reprise d'envoi sont définis. Le scheduler et les workers nécessitent un environnement actif.

### 4.8 Indicateurs et valorisation

Séparer ventes nettes, coût des marchandises vendues (CMV), marge brute, charges, résultat de gestion et trésorerie. Le CMV se rattache aux ventes, pas à l'ensemble des achats de la période ; les sorties valorisent les périssables selon FEFO et les autres produits selon FIFO. Les coûts/allocations historiques sont conservés ; les données manquantes restent explicites. Référence : [IAS 2 — Stocks](https://www.ifrs.org/issued-standards/list-of-standards/ias-2-inventories/). Les rapports de gestion ne constituent pas une intégration comptable réglementaire.

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

Phase 0 reste une vérification, sauf correction explicitement autorisée par le propriétaire. Les défauts sont décrits et classés. Critère : environnement utilisable, sauvegarde restaurable, contrôles essentiels réussis, aucun blocage critique laissé sans résolution.

#### Défauts relevés Phase 0

Les observations viennent du test navigateur du propriétaire et d'une lecture ciblée du code. Les cas dépendant d'une vente précise restent à confirmer. Seuls B1 et B2 sont traités dans cette reprise ; les autres points sont affectés à leur phase dans le registre D16–D37.

**B1 — Erreur 419 intermittente au login/logout ; robustification Phase 1 (D14)**

- Attendu : cookie de session et jeton CSRF conservés entre l'affichage du formulaire et son POST.
- Observé : l'erreur avait été intermittente sur login et logout ; le dernier passage a réussi avec `localhost` cohérent. `APP_KEY` est valide ; driver `file`, durée 120 minutes ; domaine cookie non défini ; `SameSite=lax`, chemin `/`. Le middleware CSRF est dans `web`, les deux formulaires incluent `@csrf`.
- Impact : le mélange `localhost`/`127.0.0.1` peut empêcher l'envoi du cookie de session. Les sessions fichier partagées entre onglets peuvent également rencontrer des requêtes obsolètes après régénération au login/logout. L'incohérence de chemin `qpos`/`QPOS` (E01) peut produire des liens différents, mais le chemin de cookie `/` signifie que la casse seule n'explique pas le 419.
- Comportement Phase 1 (02/10/2026) : `APP_URL` et `system.site_url` utilisent l'URL canonique minuscule `http://localhost/qpos/public`. `SESSION_DOMAIN` reste vide : Laravel émet un cookie host-only, afin de ne pas partager le cookie entre `localhost` et `127.0.0.1` (origines et sessions distinctes). Utiliser le même hôte pendant un parcours login/logout ; après une déconnexion dans un onglet, recharger les autres onglets avant de soumettre leurs anciens formulaires CSRF. CSRF reste actif ; aucun changement aux routes ni au flux d'authentification.

**B2 — Lenteur générale**

- Attendu : dashboard agrégé en base et listes bornées, sans requête par ligne.
- Observé : dashboard chargeait toutes les ventes avant d'agréger en PHP ; `Order::total_item` sommait les lignes pour chaque vente malgré `withSum` dans la liste ; inventaire matérialisait tous les produits actifs puis consultait l'unité pour chacun. Manifest/assets présents ; bundle dashboard environ 204 Ko. Aucun cache de configuration/route n'était présent, mais cela n'explique pas seul la lenteur.
- Correction ciblée : totaux dashboard calculés par un agrégat SQL ; l'accessor lit désormais l'agrégat `item_quantity_sum` déjà chargé ; DataTables inventaire reçoit une requête paginable avec relation `unit` préchargée.
- Vérification : `php -l` réussi sur les trois fichiers modifiés. Le gain visuel et la durée de réponse restent à mesurer au navigateur sur données représentatives.

**Éléments consignés pour les phases suivantes et réponses fondées sur le code :**

- **Phase 3 — Rollback de la migration fournisseurs dangereux (constaté le 01/10/2026 pendant le sous-lot 2 de Phase 1)** : `2024_10_16_123030_create_suppliers_table.php::up()` crée `suppliers`, mais `down()` cible `customers`. Attendu : annuler uniquement la création de la table fournisseurs. Impact : un rollback peut tenter de supprimer la table clients au lieu de la table fournisseurs, avec risque de perte de données ou d'échec sur les contraintes. Correction à traiter dans le lot fournisseurs de Phase 3 ; ne pas utiliser un rollback général des migrations historiques comme procédure de récupération avant correction. Aucun code corrigé dans ce sous-lot documentaire.
- **Phase 1, D16–D20** : résidus sidebar/header, couleurs nulles, boutons retour, pied de facture anglais, finitions UI/UX responsive. Attendu : navigation et composants cohérents, traductions FR/EN, clair/sombre et responsive. Impact : présentation incomplète ; pas de correction dans cette tranche.
- **Phase 2, D21–D23 — validées le 02/10/2026** : nom et prix obligatoires, autres renseignements facultatifs ; prix TTC et coût de référence distincts par conditionnement, coût réel conservé à la réception et mise à jour manuelle de la référence par l'administrateur ; marque et fournisseur distincts avec lien facultatif. D24–D25 valident les priorités détaillées et les calculs décimaux à six décimales ; l'arrondi final des paiements/factures selon devise reste en Phase 4.
- **Phase 3, D40–D41 et D26–D27** : sélecteurs achat, quantité initiale zéro, prix d'achat ambigu. Le menu fournit Purchase List et Purchase Create ; Add New dans la liste ouvre aussi `backend.admin.purchase.create`. Ce sont deux raccourcis vers le même formulaire, pas deux opérations distinctes (D27 clarifié).
- **Phase 4, D28–D35** : scanner code-barres, recherche produits et multicritère, facture sur deux pages, transactions depuis client, ventes à 0 article, reçu d'encaissement, facture semblant montrer deux ventes. Les IDs des ventes sont nécessaires pour distinguer anomalie d'écran et données réelles.
- **Transactions depuis Clients (D32, priorité haute)** : `CustomerController::orders()` passe les ventes filtrées du client à la vue générique, mais le tableau appelle `backend.admin.orders.index` en AJAX, qui retourne toutes les ventes. Le filtre client est perdu ; défaut de code confirmé à corriger en phase 4.
- **Reçu d'encaissement (D34)** : preuve d'un règlement de dette ; distinct de la facture de vente complète et du ticket POS. Il correspond à une transaction. Le gabarit relit le solde courant ; réimprimer après d'autres paiements peut donc afficher un solde actualisé, pas celui au moment du règlement. Ajouter une décision en phase 4 sur la conservation d'un instantané du solde.
- **Facture d'encaissement avec deux ventes (D35)** : le contrôleur ouvre un `OrderTransaction`, puis sa relation `order` ; ce chemin vise une vente. Deux ventes sur un document ne sont pas attendues. Enquêter en phase 4 avec les IDs et l'URL/page exacte.
- **Vente affichant 0 article (D33)** : la liste calcule `SUM(order_products.quantity)`. Zéro signifie qu'aucune quantité liée n'est trouvée. Le checkout actuel refuse un panier vide ; investiguer en phase 4 avec l'ID et ses lignes.
- **Prix d'achat (D26, D36)** : `products.purchase_price` est une valeur de référence saisie avec la fiche produit. Chaque achat conserve son propre `purchase_items.purchase_price` et ne met pas automatiquement à jour le produit. Le checkout copie le coût de référence dans la ligne de vente. Réponse acceptée ; prix d'achat par réception conservé.
- **Changement de prix / statistiques (D37)** : les lignes `order_products` conservent prix de vente, coût, remise et total au checkout ; une modification ultérieure du produit ne réécrit pas ces instantanés. En phase 5, vérifier rapport par rapport que l'historique lit ces lignes et non les prix courants.

**Résultat des opérations sur copie (étape 5)** : vente et paiement complets, décrémentation de stock, achat/réception et import CSV réussis sur `qpos_test`. La facture et le ticket sont des pages HTML imprimables ; aucune génération PDF n'a été trouvée sur ces parcours. Les variations de compteurs et le nettoyage de la copie sont détaillés dans [le rapport de Phase 0](phase0-report.md). `qpos_test` et le dossier de l'application isolée ont été supprimés ; l'empreinte du `.env` courant est restée identique.

### Phase 1 — Règles, composants communs et fondations

Finaliser les décisions et scénarios ; définir schéma cible, conversion et contrats ; compléter les chemins de cartographie au premier lot concerné. Créer la structure minimale boutiques/affectations nécessaire aux autorisations. Préparer audit métier sans secrets, sauvegardes régulières et contrats de notifications/traitements différés.

Stabiliser sidebar, navigation, composants, états de chargement/vide/erreur/succès, FR/EN, thèmes et accessibilité. Palette : `#1E5F74` et `#88B04B`, avec variantes accessibles ; Inter ; Sonner et Lucide. Conserver Blade et les îles React. Toute dépendance ajoutée répond à un besoin justifié.

Critère : décisions structurantes prises, composants utilisables, protections communes opérationnelles et transition définie. Les phases suivantes reçoivent directement leur finition visuelle.

### Phase 2 — Catalogue et boutiques

Produits, catégories, marques, unités, conditionnements, conversions, codes-barres, tarifs et moteur de promotions. Administration boutiques/affectations et sélecteur. Validation, images, recherches bornées, pagination et contrôle de consultation produit.

Conversion : structure, données de référence, conversion contrôlée, rapprochement. Fournisseurs restent propriétaires phase 3 ; clients phase 4 ; leurs contrats sont fixés avant usage.

Critère : catalogue exploitable, accès contrôlés, conversions expliquées, données préservées. Les capacités nouvelles sont exposées quand les consommateurs concernés les prennent en charge.

**État au 05/10/2026 :** sous-lots 2.1–2.5 implémentés ; conversion sur copie, rejeu sans doublons et application locale réussis, historique préservé. Six migrations appliquées, application rouverte. 51 références, 51 codes-barres et 50 promotions ; aucune règle `allows_fractional` courante ne manque, tandis que les 51 anomalies historiques restent conservées comme audit. Aucune affectation utilisateur automatique. Phase 2 validée et commitée par le propriétaire en `9862796`. Bilan détaillé et inventaire : [phase2-report.md](phase2-report.md). Aucun travail Phase 3 lancé avant la présente reprise.

### Phase 3 — Approvisionnement et stock

Ordre interne : journal/soldes/lots et `StockService` ; reprise des stocks par mouvements d'ouverture ; raccordement des consommateurs existants, dont POS ; fournisseurs/achats et `PurchaseService` ; imports/transferts/inventaires.

Réception par boutique et conditionnement ; achats, modifications et annulations contrôlées ; dettes fournisseurs/échéances/règlements ; imports validés avec traitement des doublons et compte rendu ; lots/péremption/alertes/FEFO ; expédition, réception totale/partielle, reliquats et annulations ; inventaire tenant compte des ventes pendant comptage.

Le POS existant utilise la même gestion de stock avant activation des nouveaux flux. L'adaptation minimale est planifiée ; elle ne constitue pas une deuxième refonte visuelle du POS.

Critère : stocks réconciliables, achats et POS cohérents, lots traçables, transferts et inventaires vérifiés.

### Phase 4 — Vente complète et caisse

Clients, client de passage protégé, dettes/échéances/avoirs ; `SaleService` ; POS tactile et clavier ; conditionnements/tarifs/promotions ; scanner USB ; paiements multiples et monnaie rendue ; caisses/comptage/écarts ; dépenses ; retours/échanges/remboursements/annulations ; historique/règlements/factures/tickets 80 mm/étiquettes selon formats retenus.

Pour le scan d'un code de conditionnement, adapter `pos_carts` pour conserver `product_unit_id` et une quantité décimale, puis intégrer cette unité au panier et au checkout dans `CartController`, `OrderController` et `Pos.jsx`. En Phase 2, le scan d'un conditionnement autre que l'unité de référence est reconnu puis refusé avec un message explicite ; aucun conditionnement n'est ajouté au panier.

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

Fréquence validée (D05/D38) : **une sauvegarde quotidienne en fin de journée**, le serveur étant éteint la nuit ; RPO retenu : 24 heures. Politique de conservation initiale : **7 quotidiennes, 4 hebdomadaires, 12 mensuelles**, sous réserve de capacité et besoins de conservation. Ces points conservés ne changent pas la fréquence de sauvegarde ni ne garantissent à eux seuls l'atteinte du RPO. Le RTO cible est de 8 heures (D06).

La fréquence quotidienne répond au RPO validé ; son exécution et son succès doivent être surveillés, et la restauration exercée par rapport au RTO. La politique commence avec les premières sauvegardes régulières ; l'externalisation est complétée en phase 7. Une sauvegarde avant conversion reste nécessaire indépendamment du calendrier.

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

**Point de reprise actuel — 02/10/2026 :** Phase 1, sous-lot 4, refonte UI transversale des ecrans existants implementée avec une revue finale unique autorisée par le propriétaire. Un seul shell Tailwind, y compris POS/achat React ; Inter, composants, espacements, contrôles mobiles, palettes et icones Lucide communs. Les sept étapes visuelles ne remplacent pas les phases métier 2–8. Bilan : [refonte-ui-complete.md](refonte-ui-complete.md), contrats : [ui-composants.md](ui-composants.md). Compilation de 89 vues et rendu des composants réussis ; builds Vite réussis ; HTTP CSS/cache, récupération et 404 contrôlés. La revue navigateur a été autorisée, mais inspection/capture expirent : rendu visuel authentifié non validé. Un GET login a dépassé 30 secondes ; diagnostic PHP/environnement encore à traiter. Aucune écriture métier ou migration, aucun commit de cette finition. Prochaine action : revue globale du propriétaire et commit du groupe, puis reprise des travaux métier et défauts restants selon la roadmap.

| Date | État / action | Prochaine action |
|---|---|---|
| 30/09/2026 | Version 4.2 consolidée dans la conversation : phases regroupées, décisions internes, récupération avant opérations, rétention/RPO/RTO | Préparation documentaire |
| 01/10/2026 | Préparation de cette référence ; migration commitée sur main à e675093 ; anciens documents archivés et liens de redirection conservés ; commit documentaire demandé par le propriétaire | Présenter Phase 0 puis attendre son lancement |
| 01/10/2026 | Phase 0 étapes 1–6 terminées ; sauvegarde restaurable vérifiée, contrôles techniques réussis, navigateur utilisateur et opérations métier sur copie consignés. B1 a passé une fois sur `localhost` ; D14 reste à robustifier. Vente, achat, import et stock réussis ; PDF non démontré. Aucun commit/push | Traiter les défauts selon les phases |
| 01/10/2026 | Sous-lot 3 — migrations de boutiques/affectations, modèle, Policy et middleware de portée préparés ; MAIN idempotent sans affectation utilisateur ; tests isolés SQLite réussis. Aucune migration ni affectation exécutée sur les données courantes | Présenter la liste des utilisateurs avant toute affectation en base |
| 01/10/2026 | Sous-lot 4, Lot A — tokens Soft Modern clair/sombre, Inter 4.1 locale avec licence, utilitaires Tailwind, shell et navigation harmonisés ; thème valide, résistant au stockage bloqué et synchronisé entre onglets. Modifications : `qpos-tokens.css`, `app.css`, `theme.js`, master Tailwind, sidebar, topbar, nav-item et assets Vite. Syntaxe PHP/JS et compilation Blade vérifiées ; build Vite réussi. Contrastes principaux calculés : 7,13:1 clair et 5,05:1 sombre pour texte blanc sur marque. Aucun test automatisé ajouté/exécuté, aucune migration ni écriture métier. Contrôle visuel authentifié encore en attente : navigateur sur le login | Connexion du propriétaire pour contrôle visuel clair/sombre et mobile, puis un seul commit du Lot A par le propriétaire ; composants communs à présenter ensuite |
| 01/10/2026 | Extension du Lot A demandée : diagnostic ciblé dans [diagnostic-lenteur.md](diagnostic-lenteur.md) et trois maquettes HTML temporaires A/B/C en clair/sombre. Diagnostic en lecture seule : démarrage PHP coûteux/variable, double vérification au POST login, agrégat withSum supprimé par select dans la liste ventes (N+1 confirmé sur deux ventes), rapports non bornés/index dates manquants, assets frontend historiques. Corrections proposées uniquement ; aucun nouveau ID de décision. Maquettes contrôlées visuellement ; les modifications UI précédentes restent en attente dans le même lot | Choix de palette par le propriétaire, puis refonte des quatre vues d'authentification et de la 404. welcome.blade.php absent ; clarifier le besoin d'accueil avant de changer la redirection racine. Supprimer les trois maquettes après validation ; un seul commit final par le propriétaire |

**Phase 0 : étapes 1–6 exécutées.** Le rapport [phase0-report.md](phase0-report.md) distingue les résultats, limites et points non vérifiés. Les essais d'écriture ont été confinés à `qpos_test`, désormais supprimée.

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

La préparation documentaire a reçu une délégation de commit. Le propriétaire a autorisé la Phase 0 ; ses étapes 1–6 sont exécutées et documentées. Aucun commit ni push de cette phase n'a été effectué.

### Complément de finition — chargement CSS et navigation

À la suite du retour sur la lenteur perçue et les sidebars variables, le CSS des pages publiques a été isolé dans `resources/css/auth.css` (sources Tailwind limitées aux vues publiques) ; le login GET charge cette entrée et non le CSS back-office. Build mesuré : 41,08 Ko brut / 8,06 Ko gzip, contre 54,58 Ko / 10,37 Ko pour la feuille back-office. Un cache immutable d'un an ne s'applique qu'aux assets Vite hachés de `public/build`; confirmé en réponse HEAD HTTP. Les assets hachés étant versionnés dans leur nom, chaque nouveau build reçoit une URL différente. Aucun changement de base ou de configuration Apache globale.

Les deux pages encore sous AdminLTE (POS et création d'achat) gardent leur layout fonctionnel ; leur navigation a été rapprochée du shell Tailwind par les tokens de palette et l'espacement/hauteur des entrées. Compression HTTP non activée (mod_deflate absent) et coût variable du HTML Laravel/PHP restent hors de cette correction. Build Vite, GET login 200 avec auth.css seule, réponse CSS 200/cache confirmé, et `git diff --check` réussis. L'inspection visuelle de l'utilisateur reste le contrôle final ; aucun commit de cette finition n'est fait.


**Mise à jour runtime — 02/10/2026 :** `C:\xampp\php\php.ini` modifié pour charger OPcache, activer `opcache.enable=1` et neutraliser le JIT (`opcache.jit=off`), avec sauvegarde `C:\xampp\php\php.ini.codex-backup-20261002`. Apache confirme le cache actif ; login mesuré entre 1,5 et 2,8 s. `.env` local : `APP_DEBUG=false`. Le disque système est un HDD SATA WDC ; limite matérielle constatée pour les accès froids. Defender est actif, ses exclusions existantes sont illisibles sans privilèges administrateur. Exclusions candidates, non appliquées : `vendor/`, `bootstrap/cache`, `storage/framework/views` dans le projet QPOS, sans exclusion large de `C:\xampp\`.

**État UI — 02/10/2026 :** le sélecteur de palette est présent sous Paramètres > Style, protégé par `style_settings`, et enregistre une palette globale dans `config/system.php`. Valeur actuelle : Indigo. Les maquettes restent en test par le propriétaire. Les vues login, oubli, reset/nouveau mot de passe et 404 utilisent le layout public Soft Modern ; reset/nouveau mot de passe conservent leurs redirections actuelles sans session. Le sélecteur FR/EN non sélectionné utilise la couleur de marque en mode clair, côté public et shell Tailwind. Compilation Blade, build Vite, GET login/oubli à 200, redirections reset/nouveau à 302 et 404 à 404 vérifiés. L’inspection visuelle automatisée clair/sombre est indisponible dans cette session ; revue du propriétaire à faire. Aucun commit de ce groupe n’a été créé.

**Sous-lot 5 — URL, sessions et scripts (02/10/2026) :** `APP_URL` local était déjà `http://localhost/qpos/public` ; `system.site_url` et `.env.example` ont été alignés sur la casse minuscule du dépôt. Le serveur Windows redirige l’ancien préfixe `/QPOS/public` vers `/qpos/public` par 308 ; le dépôt `.htaccess` ne force pas cette casse sous Linux, où l’URL configurée doit correspondre exactement au répertoire servi. Le cookie Laravel reste host-only (`SESSION_DOMAIN` vide) ; `localhost` et `127.0.0.1` sont des hôtes distincts et ne partagent pas leur session. Le parcours multi-onglets doit recharger les formulaires après logout pour obtenir un jeton CSRF correspondant à la session courante. Aucune modification de route, contrôleur ou flux d’authentification. `copyright.js` n’était référencé par aucune vue et a été retiré. Aucun layout/vue ne charge Tempus Dominus ; le seul partial DateRangePicker, actuellement non inclus, importe Moment avant son propre plugin. Les assets tiers inutilisés ne sont pas supprimés. Essais HTTP : chemin canonique 200, ancien chemin Windows 308, hôte IP 200 avec cookies séparés, GET login/oubli simultanés 200, POST préférence FR/EN accepté avec retour 302 puis locale conservée. Login/logout authentifiés, revue console navigateur, parcours visuel thème clair/sombre et exécution Linux restent non vérifiés dans cet environnement.

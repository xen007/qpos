# QPOS — Diagnostic ciblé de lenteur

Date : 01/10/2026. Périmètre : sous-lot 4, Lot A, diagnostic demandé avant le choix de palette. Les optimisations serveur, SQL et d'authentification restent proposées. La simplification des assets publics a ensuite été réalisée dans la refonte autorisée du même lot. Un seul commit final sera réalisé par le propriétaire après sa revue.

## Mesures et limites

Lectures du code, requêtes HTTP GET, lecture de configuration effective, `SHOW INDEX`, `SELECT 1` et `EXPLAIN` sur MariaDB. Aucun POST métier, migration, changement de configuration/cache ou écriture SQL. Aucun nouveau test automatisé.

| Observation ponctuelle | Résultat |
|---|---|
| GET login, deux lectures via IPv4 local avec Host localhost | Réception des en-têtes en 10 685 puis 9 593 ms ; réponse complète en 10 756 puis 9 644 ms |
| CSS tokens, deux lectures | 101 puis 33 ms, 2 662 octets |
| CSS frontend historique, deux lectures | 84 puis 59 ms, 63 146 octets |
| Manifest Vite, deux lectures | 46 puis 56 ms, 3 109 octets |
| Observation ultérieure de Debugbar sur GET login | Laravel : 1,68 s ; Booting : 1,01 s ; Application : 0,67 s ; 0 requête SQL |
| CLI PHP, un démarrage Laravel | Autoload : 276 ms ; bootstrap : 2 091 ms |
| Connexion MariaDB, une observation CLI | 138 ms ; trois SELECT 1 : 3,62 / 1,08 / 1,97 ms |
| Lecture/sérialisation de deux ventes existantes, sans écriture | 2 requêtes de chargement, puis 4 au total après sérialisation ; alias item_quantity_sum absent : une somme SQL supplémentaire par vente |
| Configuration effective | debug actif ; caches configuration/routes absents ; cache et sessions fichiers ; 8 fichiers de session |
| OPcache | Extension absente en CLI ; zend_extension=opcache commentée dans le php.ini XAMPP. Activation effective côté Apache à confirmer avant intervention |
| Assets | Aucun public/hot ; manifest et build disponibles. Pas de compression ni Cache-Control observés sur les fichiers statiques mesurés |
| Vite | POS et achats importés dynamiquement dans app.jsx ; bundle React partagé environ 220 Ko brut / 69 Ko gzip au build. Ces poids concernent les écrans consommateurs, pas le GET login |

Ces observations ne constituent pas un benchmark représentatif. Elles montrent une forte variabilité et un écart entre PHP dynamique et fichiers statiques. Les mesures CLI, Laravel/Debugbar et HTTP couvrent des intervalles différents et ne doivent pas être soustraites pour attribuer un temps précis. Un essai initial avec localhost a expiré à la connexion ; les lectures suivantes ont utilisé 127.0.0.1 avec Host localhost. La résolution/boucle réseau locale reste donc un point à confirmer, sans modifier APP_URL ni les cookies.

## Cinq causes ou facteurs prioritaires

Les observations ci-dessous précèdent la refonte frontend du même Lot A.
Le facteur 5 décrit donc les anciennes vues d'authentification.

| Priorité | Constat et niveau de preuve | Correction proposée |
|---|---|---|
| 1 — Démarrage PHP et instrumentation | Lent même sans SQL sur GET login. Bootstrap coûteux mesuré ; debug/Debugbar actifs ; caches Laravel absents ; OPcache absent en CLI. Leur contribution individuelle à la lenteur HTTP n'est pas encore isolée. | Comparer sur une copie de configuration avec Debugbar désactivée ; confirmer puis activer OPcache côté Apache dans un lot environnement autorisé. Préparer les caches configuration/routes/vues pour une exploitation stabilisée, après vérification de compatibilité. Mesurer le temps serveur avant/après ; si la variabilité persiste, examiner CPU, I/O et analyse antivirus des fichiers PHP, sans exclusion automatique. |
| 2 — Double travail au POST login | AuthController appelle Auth::validate, relit le user par email, puis Auth::attempt. Les deux appels d'authentification vérifient le hash du mot de passe. Constat dans le code ; aucun POST ni gain chronométré ici. Cela n'explique pas le GET login. | Regrouper l'authentification autour d'une seule vérification du mot de passe, en conservant suspension, remember_me, régénération de session, messages et CSRF. Préparer cette correction fonctionnelle séparément de la refonte visuelle. |
| 3 — N+1 de ventes et agrégat supprimé | OrderController::index appelle withSum puis select('orders.*'). La requête compilée observée est seulement SELECT orders.* FROM orders : item_quantity_sum est perdu. Order::total_item retombe alors sur une somme par vente lors de sa lecture/sérialisation ; la colonne item utilise une valeur de repli 0. Le rapport de ventes lit aussi total_item sans charger sa somme. | Placer select avant withSum, ou conserver correctement la projection ajoutée. Charger la somme dans le rapport avant l'affichage. Confirmer le nombre d'articles et le nombre de requêtes sur une copie de données. Ce défaut est un candidat pour D33, sans conclure que toutes les ventes à zéro en proviennent. |
| 4 — Rapports en mémoire et index manquants | Les deux rapports de ventes font get() sur toute la période et somment les collections PHP. SHOW INDEX confirme l'absence d'index orders.created_at et de products(status, created_at). EXPLAIN : scan ALL pour le filtre de date ; ALL + filesort pour les produits actifs triés. Estimations actuelles : 1 vente, 50 produits, donc effet faible aujourd'hui. | Calculer les totaux en SQL et paginer la liste du rapport. Étudier orders(created_at), products(status, created_at), puis pos_carts(user_id, product_id) selon les requêtes et la future contrainte métier. Valider sur un volume représentatif avant migration ; ne pas créer d'index redondant sur les FK ou les colonnes déjà uniques. Le LIKE %terme% du POS nécessite une stratégie de recherche propre ; un index simple sur name ne suffit pas. |
| 5 — Assets frontend et chargement | Le login charge Bootstrap, une CSS généraliste, une police Google distante et plusieurs scripts peu utiles à l'authentification. copyright.js cible un élément absent, défaut déjà connu. Les fichiers statiques mesurés ne sont ni compressés ni assortis d'une politique explicite de cache. Le login ne charge aucun bundle Vite backend. | Pendant la refonte frontend approuvée après choix de palette : Inter locale, styles communs ciblés et scripts utiles uniquement. Proposer ensuite compression et cache long pour les assets Vite à noms hashés ; revalidation/versionnement pour les fichiers non hashés. Garder les réponses HTML authentifiées privées. Aucun remplacement de Vite nécessaire. |

## Fichiers qui fondent le diagnostic

- Authentification : [AuthController.php](../app/Http/Controllers/AuthController.php), lignes 39–56.
- Agrégat de liste : [OrderController.php](../app/Http/Controllers/Backend/Pos/OrderController.php), lignes 25–28.
- Accessor : [Order.php](../app/Models/Order.php), total_item et somme de repli.
- Rapports : [ReportController.php](../app/Http/Controllers/Backend/Report/ReportController.php), lignes 23 et 50 ; [sale-report.blade.php](../resources/views/backend/reports/sale-report.blade.php), ligne 61.
- Recherche POS : [CartController.php](../app/Http/Controllers/Backend/Pos/CartController.php), lignes 46–53.
- Assets : [login.blade.php](../resources/views/frontend/authentication/login.blade.php), public/assets/css/style.min.css, public/.htaccess et public/build/manifest.json.

## Cache, sessions et suites

Le faible nombre de sessions et le profil GET sans SQL ne montrent pas de problème massif de session ni de N+1 sur le login. Les sessions fichiers ne justifient pas à elles seules une migration Redis. L'absence de caches est un facteur possible, pas une preuve qu'un cache résoudra les attentes de 10 secondes. Ne pas lancer optimize:clear ni modifier .env comme correction empirique. Le 419 intermittent reste un défaut distinct à investiguer.

Ordre proposé : isoler le coût PHP/instrumentation ; corriger l'agrégat de ventes ; regrouper les assets dans la refonte frontend ; traiter pagination et index dans leurs lots métier. Les optimisations serveur et d'authentification nécessitent un périmètre explicitement validé.

## Maquettes temporaires et périmètre frontend

public/maquette-A.html, public/maquette-B.html et public/maquette-C.html comparent les palettes demandées, avec le même contenu fictif en clair et sombre. Elles ont été supprimées lors de la préparation du commit explicitement autorisé par le propriétaire et sont exclues du commit.

Le propriétaire a autorisé les trois palettes dans les paramètres du site. Les quatre vues d'authentification et la 404 partagent maintenant un layout Soft Modern : Inter locale, tokens et trois entrées Vite (CSS, thème, interactions). Aucun Bootstrap ni script historique sur ces vues ; les composants métier React ne sont pas chargés. Aucun welcome.blade.php n'existe ; après délégation du choix d'accueil, la redirection directe vers login est conservée pour cet outil de caisse.

Contrôles du lot : build Vite réussi ; syntaxe PHP/JS et compilation des dix vues touchées réussies ; GET login et forget-password 200 avec le layout partagé ; URL inexistante 404 avec le layout ; reset et new-password redirigent vers forget-password sans session de réinitialisation, conformément aux gardes existantes. Aucun POST de connexion, réinitialisation ou sauvegarde des paramètres exécuté. Le rendu visuel final reste à contrôler : le navigateur intégré a expiré pendant navigation, inspection et capture. Les anciennes maquettes ont été contrôlées visuellement, mais ne prouvent pas le rendu des nouvelles vues.

Ces contrôles HTTP ne constituent pas une mesure de gain comparable aux observations initiales. La lenteur PHP reste à traiter dans le périmètre proposé plus haut.

## Mise à jour — diagnostic avant choix de palette (02/10/2026)

### Requêtes SQL, sessions et permissions

La mesure Debugbar déjà relevée sur GET login indiquait zéro requête SQL. Le contrôleur ne lit l'utilisateur qu'après soumission du formulaire ; la page login invitée n'exécute donc pas de requête de chargement du profil ou de permissions. `session.driver` et `cache.default` sont tous deux configurés sur `file`, ce qui ne fait pas appel à MariaDB au démarrage. Les lectures SQL user/permissions sont attendues sur une session authentifiée et doivent être mesurées sur une page concernée avant toute modification ; aucune suppression ou mise en cache manuelle n'est appliquée sans mesure.

### Réglage debug et packages de diagnostic

Initialement `APP_DEBUG=true`. `fruitcake/laravel-debugbar` et `spatie/laravel-ignition` sont installés comme dépendances de développement ; `laravel/telescope` est absent. Avec debug activé en environnement `local`, Debugbar pouvait s'attacher et injecter sa barre/outillage dans les réponses HTML. Après passage de `.env` à `APP_DEBUG=false` et `php artisan config:clear`, GET `/login` a répondu HTTP 200 en 6 272 ms (7 815 octets, contre 27 916 octets avec debug actif lors de la mesure précédente). La baisse est compatible avec une surcharge significative de Debugbar, sans prouver qu'elle soit la seule cause. Debugbar est désormais neutralisée par `APP_DEBUG=false` ; Ignition reste disponible pour le traitement des erreurs. Le réglage effectif `app.debug=false` a été vérifié.

`php artisan config:cache` avait réussi ; les pilotes effectifs sont `file` pour le cache et la session. Les requêtes invitées ne vont pas lire user/permissions et n'ont pas d'appel SQL mesuré. Aucun changement SQL/session n'est donc justifié pour le login.

### Vite et assets

Le shell ne charge la feuille métier qu'une fois ; les fichiers Vite sont hachés, et une règle de cache immutable limitée à `public/build/assets/` est vérifiée en HTTP. La feuille publique d'authentification pèse 9,86 Ko, contre 54,58 Ko pour l'ancienne feuille frontend généraliste. Le GET HTML reste lent alors que le navigateur télécharge ses CSS/JS après réception de la réponse : ce poids ne peut expliquer le temps d'attente avant le premier octet. Aucun réglage global de compression Apache n'est entrepris dans ce lot.

### Mesures séquentielles demandées

| Étape | Commande/réglage | Temps de l'étape | GET login après l'étape |
|---|---|---:|---:|
| a | `.env APP_DEBUG=false`, `config:clear` | — | 6 272 ms |
| c | `php artisan package:discover --ansi` | 4 202 ms | 4 064 ms |
| d | `php artisan optimize` | 29 944 ms, dont ~21 s pour les vues | 10 054 ms |
| e | `composer dump-autoload -o` | 385 443 ms, 9 528 classes optimisées | 16 908 ms |

f) `php -m` ne montre pas Xdebug en CLI ; l'état du module Apache n'a pas été vérifié. `package:discover`, `optimize` et Composer réussissent, mais les lectures HTTP après chaque opération ne démontrent aucun gain stable. L'autoloader Composer et les caches ne corrigent pas les attentes de plusieurs secondes. La génération optimisée de l'autoloader a pris 6 min 25 s, anomalie compatible avec un coût I/O/environnement à examiner, sans exclusion antivirus automatique. La Debugbar neutralisée est la correction la mieux étayée : la première réponse après `APP_DEBUG=false` passe de 27,9 à 7,8 Ko et le délai de 33,2 à 6,3 s. Les lectures suivantes à 4,1, 10,1 et 16,9 s montrent une forte variabilité ; la dernière mesure HTTP 200 est 16,9 s. Aucun changement au schéma, aux dépendances suivies ou aux données.

## Mise à jour — Apache, stockage et palette (02/10/2026)

### OPcache et nouvelle mesure login

Une sonde PHP temporaire, limitée aux requêtes loopback et supprimée après lecture, confirme que l'Apache actif est `apache2handler`, charge `C:\xampp\php\php.ini`, et avait OPcache désactivé ; Xdebug était absent côté Apache comme en CLI. Le DLL `C:\xampp\php\ext\php_opcache.dll` existe. Dans `php.ini`, les directives de chargement OPcache et `opcache.enable=1` étaient commentées. Une copie de sauvegarde a été créée à `C:\xampp\php\php.ini.codex-backup-20261002`, puis les deux directives ont été activées.

Au premier redémarrage, Apache a journalisé répétitivement `VirtualProtect() failed [87]`. Le runtime révélait `opcache.jit=tracing` avec un tampon JIT à zéro. Le [bug PHP 79751](https://bugs.php.net/bug.php?id=79751) décrit ce symptôme Windows et le contournement `opcache.jit=off`. Ce réglage ciblé a été ajouté, puis Apache XAMPP a été redémarré. La sonde confirme maintenant OPcache actif, JIT désactivé, 767 scripts en cache, Xdebug absent. Aucune nouvelle erreur `VirtualProtect` n'apparaît dans le journal après ce redémarrage.

GET login répond maintenant HTTP 200 en 1 479 ms et 2 589 ms avec OPcache activé, puis 2 802 ms après la désactivation JIT. La dernière lecture, après échauffement du cache, est 2 426 ms. Avant OPcache, les mesures successives variaient de 4 à 33 secondes ; la baisse est nette, mais ces essais n'étaient pas un benchmark contrôlé. `APP_DEBUG=false` reste la correction qui empêche Debugbar de s'attacher au login. Aucun SQL n'est ajouté au login invité.

### Defender et disque

Windows Defender est actif. La lecture de `Get-MpPreference.ExclusionPath` est refusée sans administrateur ; aucune exclusion existante n'a pu être confirmée et aucune n'a été ajoutée. Le disque système identifié est un WDC WD5000LPLX, SATA HDD, état `Healthy`. La lecture d'un fichier PHP Laravel de 47 Ko a pris 428 ms lors de la première mesure, 574 ms lors d'un autre accès initial, puis 15–16 ms sur les accès chauds. Ce différentiel et les 385 s de `composer dump-autoload -o` rendent les accès froids sur HDD, potentiellement aggravés par l'analyse temps réel Defender, plausibles ; ils ne prouvent pas à eux seuls la cause exacte du temps Composer. Si l'administrateur choisit de tester des exclusions, candidates limitées à QPOS : `C:\xampp\htdocs\qpos\vendor`, `C:\xampp\htdocs\qpos\bootstrap\cache` et `C:\xampp\htdocs\qpos\storage\framework\views`. Ne pas exclure tout `C:\xampp\`, les uploads, ni les dossiers publics. Comparer les mêmes commandes avant/après et retirer l'exclusion si le gain est négligeable.

### État du sélecteur de palette

Le sélecteur existe déjà sous Paramètres du site → Style, dans la vue générale. La section et sa route POST sont réservées à `style_settings`. Trois choix radio sont validés contre `petrol` (#1E5F74), `teal` (#0F766E) et `indigo` (#4338CA), tous avec l'accent #88B04B. Le choix est enregistré de façon persistante dans `config/system.php` (`site_palette`) et la configuration est rechargée ; c'est une préférence globale au site, pas une préférence par utilisateur/session. Le thème clair/sombre reste une préférence du navigateur dans `localStorage` (`qpos-theme`).

Le shell expose `data-palette` depuis `SitePalette::current()`. Les tokens font varier marque, surfaces de navigation/sidebar, header, contrôles, boutons, tableaux et focus selon la palette et le thème. Le réglage actuellement enregistré est `indigo`. Le code de l'affichage, de l'autorisation, de la validation, de la persistance et des tokens a été vérifié ; aucune soumission réelle du formulaire ni revue visuelle authentifiée n'a été faite dans cette vérification.

## Complément — shell et assets Vite (01/10/2026)

Le contrôle des layouts confirme que deux écrans métier conservent `backend.master` / AdminLTE : le POS (`backend/cart/index`) et la création d'achat (`backend/purchase/create`). Les autres écrans migrés utilisent `backend.master-tailwind`. La sidebar AdminLTE garde donc sa structure native, mais ses couleurs, l'état actif, l'espacement et la hauteur des entrées sont maintenant raccordés au shell Tailwind. Les deux pages métier ne sont pas migrées dans ce lot.

La page d'authentification utilisait aussi `resources/css/app.css`, construit à partir de toutes les vues et scripts du back-office. Une entrée CSS Vite dédiée limite maintenant les sources Tailwind aux vues publiques et composants associés. Mesure du build après séparation : `auth.css` 41,08 Ko brut / 8,06 Ko gzip ; `app.css` back-office inchangé à 54,58 Ko / 10,37 Ko gzip. Le HEAD HTTP sur auth.css confirmait auparavant 41 087 octets sans compression, ni Cache-Control, uniquement ETag. `mod_headers` est chargé sous Apache, tandis que `mod_deflate` et `mod_expires` ne le sont pas.

Un `.htaccess` sous `public/build/` fixe donc un cache immutable d'un an seulement aux assets Vite dont le nom contient un hash de 8 caractères. Les réponses HTML et assets historiques ne sont pas concernés. La compression HTTP demeure désactivée par la configuration Apache et requiert un lot environnement séparé.

Le build Vite a réussi après séparation. Aucun benchmark avant/après du temps de rendu navigateur n'a été possible dans cette vérification ; la lenteur observée du GET HTML dynamique (1,68 s Debugbar dans une lecture précédente, jusqu'à environ 10 s lors d'autres GET) relève principalement du démarrage PHP et demeure distincte du poids CSS.


## Mise à jour — refonte intégrée du 02/10/2026

Le complément précédent décrivait encore deux consommateurs AdminLTE. Ils sont maintenant migrés au shell commun : leurs plugins globaux historiques ne sont plus chargés. Le CSS public limite réellement les sources Tailwind avec `source(none)` et inclut ses règles mobiles : environ 9,9 Ko brut, contre 54,6 Ko pour l’ancienne feuille généraliste. Le CSS métier est chargé une fois dans le shell, sans réimport depuis le montage React. Le calendrier React de l’achat est remplacé par une date native ; le chunk achat propre passe d’environ 173,4 à 9,7 Ko (dépendances partagées React exclues de ces chiffres).

Le cache long des fichiers Vite hachés est installé dans `public/.htaccess`, sous une règle limitée à `build/assets`, afin de survivre aux builds. HEAD CSS public : 200 et cache immutable ; tokens non hachés : 200 sans cache immutable. GET récupération : 200 ; URL inconnue : 404, sans CSS legacy et avec HTML privé. Un GET login a dépassé 30 secondes pendant les contrôles ; une nouvelle lecture après le build répond en HTTP 200 en 9 526 ms, avec CSS publique et HTML privé. La lenteur serveur reste une priorité à isoler dans son lot autorisé. Aucun gain de temps serveur n’est déduit de la réduction des assets. Voir [le bilan complet](refonte-ui-complete.md).


## Mise à jour finale du diagnostic Apache — 02/10/2026

`php.ini` Apache était celui de `C:\xampp\php\php.ini`; OPcache n'était pas chargé, Xdebug était absent côté Apache. OPcache a été activé et JIT désactivé après l'erreur Windows `VirtualProtect() failed [87]` causée par le JIT `tracing` avec tampon 0. Le backup est `C:\xampp\php\php.ini.codex-backup-20261002`. Après redémarrage, Apache confirme OPcache actif, 767 scripts en cache, JIT désactivé, Xdebug absent ; aucune nouvelle erreur VirtualProtect dans son journal. Les deux dernières mesures de login sont 2 802 ms et 2 426 ms, HTTP 200.

Le disque WDC WD5000LPLX est un HDD SATA en état Healthy. Lecture d'un fichier PHP de 47 Ko : 428–574 ms aux accès initiaux, puis 15–16 ms quand le contenu est chaud. Cela rend les scans de fichiers froids sur HDD plausibles comme contribution aux 385 s de Composer. Defender est actif ; l'accès administrateur requis a empêché la lecture de sa liste d'exclusions. Aucune exclusion n'a été ajoutée. Candidates limitées : `C:\xampp\htdocs\qpos\vendor`, `C:\xampp\htdocs\qpos\bootstrap\cache`, `C:\xampp\htdocs\qpos\storage\framework\views`.

Le sélecteur de palette existe. Les trois valeurs et tokens clair/sombre sont reliés ; la palette est partagée au niveau du site dans `config/system.php` (valeur actuelle `indigo`). La préférence clair/sombre reste dans `localStorage`. La visibilité et la persistance sont conditionnées par `style_settings`. Le contrôle interactif authentifié n'a pas été soumis.
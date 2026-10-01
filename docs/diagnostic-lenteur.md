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

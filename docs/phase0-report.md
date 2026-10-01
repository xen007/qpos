# QPOS — Rapport de Phase 0

Date : 01/10/2026. Référence de pilotage : [roadmap.md](roadmap.md), version 4.2.

Ce rapport contient les résultats d'exécution. La roadmap conserve les règles et l'ordre du chantier.

## Avancement

| Étape | Statut |
|---|---|
| 1 — Environnement | Terminée ; observations ci-dessous |
| 2 — Sauvegarde et restauration isolée | Terminée ; restauration et nettoyage vérifiés |
| 3 — Contrôles techniques complets | Terminée ; contrôles réussis, caches initiaux restaurés |
| 4 — Navigateur | Effectuée par le propriétaire ; B2 validé, B1 intermittent à robustifier en Phase 1 |
| 5 — Opérations métier sur copie | Terminée ; essais réalisés sur `qpos_test`, puis copie et base supprimées |
| 6 — Bilan consolidé | Terminée ; résultats, écarts et limites consolidés ci-dessous |

Les étapes 1 à 3 ont été exécutées successivement après validation du propriétaire. Le propriétaire a ensuite effectué le contrôle navigateur et signalé B1/B2. Aucun commit ni push effectué pour cette phase.

## 1. Environnement vérifié

| Contrôle | Attendu | Observé | Résultat / limite |
|---|---|---|---|
| Git | Migration intégrée ; travail existant préservé | `main...origin/main`, sans écart indiqué par les références locales au début du contrôle | Conforme ; aucun fetch réseau effectué |
| Laravel installé | Laravel 12 | 12.69.3 via la configuration et le framework installés | Conforme ; contrôles Artisan complets à l'étape 3 |
| PHP CLI | PHP 8.2 compatible avec Laravel 12 | 8.2.12, ZTS x64 ; `C:\xampp\php\php.ini` | Compatibilité de version confirmée |
| PHP servi par Apache | Même version et même configuration de base que la CLI | 8.2.12 ; Apache 2.0 Handler ; `C:\xampp\php\php.ini` | Confirmé par HTTP sur le diagnostic XAMPP existant |
| Apache | Serveur local accessible | Apache 2.4.58 Win64, port HTTP 80 ; réponse HTTP 200 | Accessible ; pas d'audit exhaustif de correctifs Apache dans cette étape |
| Extensions requises | Extensions déclarées dans les dépendances installées disponibles | 22 extensions contrôlées, présentes dans CLI et dans le PHP servi par Apache | Conforme ; le contrôle complet des contraintes de plateforme appartient à l'étape 3 |
| Accès base | Connexion effective à la base configurée | Base `qpos`, serveur 10.4.32-MariaDB, port 3306 | SELECT réussis dans une transaction READ ONLY, annulée ensuite |
| Moteur des tables | Moteur transactionnel pour les opérations de stock | 24 tables InnoDB | Compatible avec transactions/verrous ; scénario de concurrence non exécuté ici |
| URL applicative | Le chemin atteint le projet attendu | `http://localhost/qpos/public` ; favicon HTTP 200 identique au fichier local | Résolution HTTP confirmée ; aucun parcours métier HTTP exécuté |
| État de configuration | Identifier les réglages avant les contrôles suivants | Configuration non mise en cache ; sessions `file`, cache `file`, queue `sync` | État relevé, sans modification |

Extensions requises contrôlées : ctype, curl, dom, fileinfo, filter, gd, hash, iconv, json, libxml, mbstring, openssl, pcre, phar, session, simplexml, tokenizer, xml, xmlreader, xmlwriter, zip et zlib. PDO et pdo_mysql sont présents en CLI ; la connexion effective a été vérifiée avec PDO.

## Écarts signalés — attendu, observé, impact

### E01 — URL de configuration différente par la casse

- Attendu : une URL canonique cohérente entre `app.url` et `system.site_url`.
- Observé : `app.url = http://localhost/qpos/public`, `system.site_url = http://localhost/QPOS/public`.
- Impact : incohérence des liens générés ; risque de chemins introuvables sur un serveur sensible à la casse. L'accès local Windows testé fonctionne.
- Suite : alignement à traiter dans le lot paramètres/déploiement. Aucune correction effectuée.
- Classement : non bloquant pour une sauvegarde ou la poursuite de la vérification locale.

### E02 — MariaDB en fin de maintenance

- Attendu : environnement cible de production avec serveur de base maintenu.
- Observé : MariaDB Community 10.4.32 ; maintenance de la branche 10.4 terminée le 18/06/2024 selon la [politique MariaDB](https://mariadb.org/about/#maintenance-policy).
- Impact : absence de maintenance de cette branche ; la mise à niveau du runtime doit inclure la base de données et pas seulement PHP/Laravel. Ce constat ne prouve pas une incompatibilité fonctionnelle ni une vulnérabilité particulière de QPOS.
- Suite : enregistrer ce besoin pour la préparation de l'environnement cible et vérifier la compatibilité sur une copie avant migration.
- Classement : non bloquant pour les vérifications et la sauvegarde locale ; à traiter avant une mise en production professionnelle.

### E03 — Version corrective PHP ancienne

- Attendu : runtime avec correctifs maintenus.
- Observé : PHP 8.2.12, compilation du 24/10/2023. La branche 8.2 reçoit des correctifs de sécurité jusqu'au 31/12/2026 selon [PHP](https://www.php.net/supported-versions.php).
- Impact : compatibilité Laravel confirmée, mais le support de la branche n'implique pas que le binaire installé intègre les correctifs ultérieurs. L'audit Composer ne couvre pas ces correctifs PHP.
- Suite : intégrer le contrôle et la mise à niveau corrective au lot runtime, avec comparaison Apache/CLI et vérifications de non-régression. Aucun runtime changé ici.
- Classement : non bloquant pour la sauvegarde locale ; maintenance nécessaire pour l'environnement cible.

## Respect du périmètre

- Aucun contrôleur, vue, JavaScript, configuration ou `.env` modifié.
- Configuration Laravel chargée sans amorcer les providers ni traiter de requête applicative.
- Requêtes SQL limitées aux SELECT de version et métadonnées, dans une transaction en lecture seule.
- HTTP limité au diagnostic PHP XAMPP existant et au favicon statique du projet.
- Aucun mot de passe, clé ou identifiant de connexion affiché dans le rapport.
- La sonde opérationnelle temporaire a été retirée. Aucun code de test automatisé ajouté au projet.

## 2. Sauvegarde et restauration vérifiées

Destination : `C:\qpos-backups\2026-10-01-phase0\`, hors dépôt et hors répertoire public. Les droits du dossier daté sont limités à `XENDER\zinde` et au compte Système ; cette restriction est appliquée avant copie de `.env` et des fichiers sensibles.

| Élément | Résultat |
|---|---|
| Export SQL | `database\qpos.sql`, 61 795 octets |
| Méthode | `mysqldump` 10.4.32, `--single-transaction`, sans verrouillage des tables source |
| Précontrôle | 24 tables InnoDB ; aucun événement, routine, trigger ou vue à traiter séparément |
| Fichiers sauvegardés | `.env`, `storage/` et les logos téléversés de `public/assets/images/logo/` |
| Volume de fichiers | 192 fichiers, 2 109 257 octets ; répertoires vides préservés |
| Base de restauration | `qpos_phase0_restore_20261001_16437a3e`, créée uniquement pour la vérification |
| Connexion restaurée | Réussie via PDO |
| Comparaison SQL | Les 24 tables et tous leurs nombres de lignes correspondent à la source |
| Contrôle de la source | Les nombres de lignes relevés avant et après l'opération sont identiques ; aucune commande d'écriture envoyée à `qpos` |
| Fichiers restaurés | `restore-check\`, dossier isolé et non servi par Apache |
| Comparaison des fichiers | Les 192 empreintes SHA-256 restaurées correspondent au manifeste ; `.env` inclus, sans affichage de son contenu |
| Suppression de la base temporaire | Réussie et vérifiée par une deuxième requête de métadonnées |
| Identifiants client temporaires | Fichier d'options supprimé ; identifiants absents de la ligne de commande |
| Livrables conservés | SQL, copie des fichiers, `manifest.json`, `restore-report.json`, diagnostics privés et dossier de vérification des fichiers |

Exemples de nombres de lignes identiques :

| Table | Source et restauration |
|---|---:|
| users | 3 |
| products | 51 |
| orders | 1 |
| order_products | 2 |
| purchases | 11 |
| purchase_items | 27 |
| customers | 11 |
| units | 6 |

Empreinte SHA-256 du SQL : `44d4422fc71b94f0d220eb59120e8d6e4d69639b7ea486f993a4409940f68d87`.

L'import a progressé lentement sur ce poste ; un contrôle a observé 22 tables en création sur 24, sans erreur dans les diagnostics. Il s'est terminé avec succès. Aucun blocage de restauration constaté. Les comparaisons SQL portent sur la liste des tables présentes et leurs nombres de lignes, pas sur une validation fonctionnelle de toutes les données métier.

La copie de `.env` est fidèle à la source et contient donc sa configuration originale. Le dossier `restore-check` reste un dossier de fichiers inerte : aucune application n'y a été lancée. Avant les essais métier futurs, une nouvelle base dédiée et une configuration de copie pointant vers elle seront nécessaires.

### E04 — Fuseau horaire applicatif à confirmer

- Attendu : un fuseau métier explicitement choisi avant les rapports et clôtures.
- Observé : `config/app.php` définit `Asia/Dhaka` (UTC+6) ; le rapport JSON de restauration est horodaté avec ce décalage. Le contexte utilisateur est `Africa/Douala` (UTC+1).
- Impact : horaires, limites de journée et rapports potentiellement décalés si Douala est le fuseau métier attendu. Le décalage explicite permet d'interpréter l'instant de sauvegarde ; il ne remet pas en cause la restauration.
- Suite : décision à confirmer en Phase 1. Aucun fuseau modifié ici.
- Classement : observation non bloquante, sans présumer du fuseau métier souhaité.

## 3. Contrôles techniques

Exécutés avec `C:\xampp\php\php.exe` (PHP 8.2.12) et le PHAR Composer installé. Chaque commande ci-dessous s'est terminée avec un code de sortie 0.

| Contrôle | Attendu | Observé | Résultat / limite |
|---|---|---|---|
| `composer validate --no-interaction` | Manifestes cohérents | `./composer.json is valid` | Conforme |
| `composer audit --no-interaction` | Aucun avis de vulnérabilité sur les dépendances verrouillées | `No security vulnerability advisories found.` | Conforme ; avertissement de cache utilisateur E05 ci-dessous |
| `php artisan --version` | Laravel 12 | `Laravel Framework 12.69.3` | Conforme |
| `php artisan route:list --json` | 142 routes | Tableau JSON valide contenant 142 routes | Conforme ; ne vérifie pas l'exécution HTTP ni tous les refus d'accès |
| `php artisan view:cache` | Compilation Blade réussie | `Blade templates cached successfully` | Conforme ; ne vérifie pas le rendu ni les données à l'exécution |
| `php artisan config:cache` | Configuration sérialisable | `Configuration cached successfully` | Conforme |
| `php artisan event:cache` | Cache des événements généré | `Events cached successfully` | Conforme |
| `php -l` sur `app/` | Aucun défaut de syntaxe PHP | 87 fichiers contrôlés, 87 réussites, 0 échec | Conforme ; pas une validation fonctionnelle |

### État des caches préservé

- Avant contrôle : `bootstrap/cache/` contenait `.gitignore`, `packages.php` et `services.php` ; aucun cache de configuration, d'événements ou de routes. `storage/framework/views/` contenait `.gitignore` et 17 vues PHP compilées.
- Une copie temporaire de ces fichiers a été préparée avant les commandes de génération.
- Après contrôle : `event:clear`, `config:clear` et `view:clear` ont réussi. Les fichiers initiaux ont ensuite été restitués avec leurs contenus et dates de modification ; les empreintes SHA-256 ont été comparées et correspondent.
- Les caches de configuration et d'événements créés pour le contrôle sont absents à nouveau. Les 17 vues compilées préexistantes sont conservées, conformément à la préservation de l'état initial.
- Aucun `cache:clear` ou `optimize:clear` exécuté : le cache applicatif général n'a pas été purgé.

### E05 — Cache utilisateur Composer non accessible en écriture

- Attendu : Composer peut utiliser son cache local de métadonnées.
- Observé : avertissement sur `C:/Users/zinde/AppData/Local/Composer/repo/https---repo.packagist.org/`, non accessible en écriture dans le contexte d'exécution. Composer poursuit sans ce cache et termine l'audit avec le code 0.
- Impact : métadonnées à télécharger à nouveau ; contrôle potentiellement plus lent. Aucun échec d'audit constaté. Ce résultat ne démontre pas un problème de droits pour la session Windows habituelle du propriétaire.
- Suite : aucune modification des droits ni de la configuration Composer ; observation non bloquante.

### Périmètre de l'étape 3

- Aucun fichier source PHP, contrôleur, vue, JavaScript, configuration ou `.env` modifié ; aucune commande d'écriture métier ou migration SQL exécutée.
- Aucun navigateur ni scénario métier exécuté. Les providers du projet et le démarrage console ont été examinés de façon ciblée avant les commandes Artisan.
- E04 inscrit comme D13 dans le registre de `roadmap.md`, à décider en Phase 1 ; `Asia/Dhaka` reste inchangé.
- Le script opérationnel, sa copie de caches et ses résultats temporaires ont été retirés. Aucun test automatisé ajouté au projet ; aucun commit ni push effectué.

## 4. Retour navigateur et corrections ciblées B1/B2

Le propriétaire a signalé une erreur 419 au login/logout et une lenteur générale.

| Point | Diagnostic | Action / résultat |
|---|---|---|
| B1 — 419 login/logout | Erreur auparavant intermittente sur les deux actions ; le passage actuel réussit avec `localhost` cohérent. Indice utilisateur : navigation parfois entre `localhost` et `127.0.0.1`. | À robustifier en Phase 1 : vérifier cookie session/CSRF, tester `SESSION_DOMAIN=localhost`, aligner `APP_URL`/`system.site_url` (E01), tester un onglet et plusieurs hôtes. Aucun changement appliqué maintenant. |
| B2 — lenteur | Dashboard chargeait toutes les ventes ; accessor des ventes sommait les lignes par ordre ; rapport inventaire chargeait tous les produits et consultait leur unité séparément. Les assets sont présents. | Agrégat SQL dans le dashboard, usage de `withSum` dans l'accessor d'Order, requête DataTables paginable et `unit` eager loaded dans l'inventaire. `php -l` réussi sur les trois fichiers ; propriétaire confirme la correction. |

Questions fonctionnelles, points différés et réponses sur transactions client, reçus, ventes sans articles, prix d'achat et prix historiques sont consignés sous « Défauts relevés Phase 0 » dans `roadmap.md`.

## 5. Opérations métier sur copie dédiée

### Isolement et nettoyage

- Copie restaurée depuis `database/qpos.sql` dans `qpos_test` : 24 tables ; nombres de lignes initiaux identiques à la sauvegarde (dont 51 produits, 1 vente, 11 achats).
- Application lancée depuis `C:\qpos-backups\2026-10-01-phase0\test-app`, avec son `.env` isolé, `APP_URL=http://127.0.0.1:8091`, base `qpos_test` et cookie de session dédié.
- Un compte de test temporaire a été créé uniquement dans `qpos_test` pour les opérations authentifiées.
- Après les essais, serveur arrêté, `qpos_test` supprimée puis absence vérifiée ; `qpos` toujours présente.
- Dossier `test-app`, compte et fichier CSV temporaires supprimés. La sauvegarde et `restore-check` sont conservés.
- Empreinte SHA-256 du `.env` local avant et après : identique (`f74e38d21062ebc08a7a1340e369c64bd8b81e5512caaeffaf36ba9674a06603`). Aucune écriture envoyée à la base courante.

### Résultats — attendu, observé, impact

| Parcours | Attendu | Observé | Résultat / impact |
|---|---|---|---|
| Vente complète | Enregistrer une vente d'un produit, son paiement et ses lignes dans la copie | Vente n°2 : Donna Ferry, quantité 1, total/payé 827, reste dû 0 ; `orders` passe de 1 à 2 et `order_products` de 2 à 3 | Réussi sur `qpos_test` |
| Stock de vente | Décrémenter le produit vendu | Stock affiché 17 avant vente, 16 après vente | Réussi |
| Achat | Enregistrer un achat reçu et augmenter le stock | Achat d'une unité de Donna Ferry au prix d'achat 20 ; le nombre d'achats passe de 11 à 12, et le stock de 16 à 17 | Réussi sur la copie |
| Facture / ticket | Ouvrir un document cohérent pour la vente | Facture n°2 et ticket n°2 affichent le produit, total 827, payé 827 et dû 0 | Pages HTML imprimables affichées. Aucun PDF n'est généré par le parcours vérifié : la route `admin/orders/invoice/{id}` rend une vue Blade et le ticket POS rend aussi une vue. Le critère « PDF » n'est donc pas validé par l'application actuelle. |
| Import produit | Importer un fichier valide et créer produit, achat et quantités | CSV synthétique accepté ; `products` passe de 51 à 52, `purchases` de 12 à 13 et `purchase_items` de 28 à 29 ; nouveau produit de stock 2 | Réussi ; effets confinés à `qpos_test`, puis base supprimée |

Après les deux parcours augmentant le stock, Donna Ferry revient à 17 : vente -1 puis achat +1. À la fin des essais, les compteurs temporaires étaient `orders=2`, `order_products=3`, `products=52`, `purchases=13`, `purchase_items=29`.

### Défauts observés pendant le parcours de copie

- **Attendu :** scripts front sans erreurs de console. **Observé :** `copyright.js` tente d'affecter `innerHTML` à un élément absent ; Tempus Dominus Bootstrap 4 signale l'absence de Moment.js sur le formulaire d'achat. **Impact :** erreurs JavaScript sur les pages testées ; à classer pour Phase 1/7, sans correction dans cette étape.
- **Attendu :** export PDF identifiable. **Observé :** aucune réponse PDF ni action d'export PDF sur les factures inspectées, seulement des pages avec bouton « Imprimer ». **Impact :** production PDF à valider après définition ou mise en place du parcours correspondant.

Les observations techniques s'ajoutent aux défauts existants sans modifier la roadmap au-delà des éléments demandés. Aucun code métier ou configuration de la copie source n'a été changé pour ces essais.

## 6. Bilan consolidé

Étapes 1 à 6 exécutées. Environnement, sauvegarde/restauration et contrôles techniques sont documentés ; tests navigateur du propriétaire intégrés ; opérations métier validées sur la copie isolée puis nettoyées. B1 reste intermittent et est à robustifier en Phase 1. B2 est corrigé selon le propriétaire. Le parcours PDF reste non démontré : seules des vues HTML imprimables ont été trouvées. Aucun commit ni push effectué.

## Point de reprise

Phase 0 peut être clôturée sous réserve de traiter ses limites documentées : B1 en Phase 1, vérifier les erreurs JavaScript constatées et décider comment satisfaire le besoin de PDF. Les points métier déjà classés et les réponses aux sept questions sont consignés dans `roadmap.md` (D14–D37). La base `qpos` n'a pas été utilisée pour les écritures d'essai ; `qpos_test` et le dossier de l'application de copie ont été supprimés. Aucun commit ni push effectué.

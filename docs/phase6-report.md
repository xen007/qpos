# Phase 6 — administration complète

Date : 12/10/2026. Base `main`, HEAD `2bf0e2a`. Aucun commit créé ; le propriétaire réalise l'unique commit final. Le bilan Phase 5 local est préservé.

## État des sous-lots

| Lot | État | Livraison |
|---|---|---|
| 6.A | OK | Cartographie, sauvegarde restaurée, inventaire des écarts. |
| 6.B | OK | Catégorie, statut et marque ; filtres combinés, recherche, tri et pagination côté serveur ; portée boutique. |
| 6.C | OK | Noms/IDs reproduit puis corrigé ; rôles cumulables, retrait effectif, cible locale, dernier Admin sous verrou ; liste utilisateurs filtrable par rôle/date et actions selon droits. |
| 6.D | OK | Paramètres en base validés, rechargés au démarrage, configuration PHP et secrets préservés ; XAF initial, BDT historique protégé ; caches devise. |
| 6.E | OK | Trois comptes, déconnexion, profil/photo/mot de passe, récupération simulée, CSRF et limites de tentatives distinctes ; Google retiré, données historiques conservées. |
| 6.F | OK | Audit Phase 6 atomique, filtré, paginé, consultable en FR/EN avec portée boutique ; événements append-only ; méthodes recensées avant suppression. |
| 6.G | OK | 146 cas de régression OK avec preuves nouvelles, contrôles POS/navigateur/PDF et intégrité source ; bilan prêt pour commit propriétaire. |

## Défauts confirmés et corrections

- Le formulaire de permissions envoyait des noms, sa validation exigeait des IDs : IDs validés, distincts et limités au guard `web` ; synchronisation par modèles Permission.
- Suspension/suppression permettaient des opérations hors du périmètre de gestion : même contrôle de cible que l'édition, refus des autres boutiques et des comptes privilégiés ; définitions de rôles/permissions réservées à l'Admin global.
- La protection du dernier Admin n'était pas commune à toutes les mutations : verrou partagé, vérification de l'Admin global actif restant ; deux processus concurrents ont conservé un administrateur.
- Des permissions natives étaient absentes après le seed de démarrage (`sale_discount`, prix, dépenses, synthèses…) : chaîne des seeders complétée et migration additive. `sale_create` seul ne donne plus automatiquement `cash_session_manage` ; Jean reste vendeur, Marie caissière.
- Recherche par rôle et tri de colonnes calculées utilisateurs défaillants : requêtes SQL explicites, pagination plafonnée à 100, colonnes autorisées, actions calculées par utilisateur.
- Suppression d'un compte ayant uniquement un historique de caisse : erreur 500 reproduite, remplacée par refus 422 invitant à suspendre ; contrainte FK et historique conservés.
- Réécriture de `config/system.php` à chaque paramètre : stockage whitelist dans `system_settings`, transaction avec audit, validation par onglet, aucune écriture `.env` en HTTP.
- Limites de connexion/récupération partageant un compteur : compteurs distincts par action, GET non comptabilisés ; POST abusifs 429, CSRF absent/invalide 419.
- Image avec en-tête valide mais contenu corrompu : 500 reproduit, validation du décodage et dimensions avant traitement ; image refusée 422. Nettoyage des nouveaux fichiers si transaction échoue.
- Seed devise vide : XAF absent et défaut BDT proposé ; XAF devient le seul défaut d'une installation vide, le second seed préserve les devises existantes.

## Audit et paramètres

Deux migrations additives : `2026_10_12_140000_create_administration_audit_and_settings` et `2026_10_12_143000_register_native_administration_permissions`.

`audit_logs` conserve acteur nullable, boutique nullable, action, objet/ID, avant/après whitelist, résultat, motif, référence de suivi et dates UTC. Dates de consultation en Africa/Douala. Les suppressions de comptes n'effacent pas l'identité numérique de leurs événements. Aucun mot de passe, hash, token, secret SMTP ou donnée bancaire n'est journalisé. IP/UA non collectés par défaut conformément aux contrats. Les mutations réussies Phase 6 partagent leur transaction avec l'événement ; aucun faux succès après rollback. Les refus ne sont pas présentés comme des événements réussis ; pas d'instrumentation rétrospective des phases précédentes.

`audit_view` autorise la consultation ; l'Admin global voit les événements globaux et toutes les boutiques. Un auditeur local voit seulement ses affectations actives, aucune administration globale. Accès direct hors portée 404, filtre boutique interdit 403. Interface sans mutation du journal. L'immutabilité applicative ne constitue pas un stockage externe inviolable face à un administrateur SQL.

`system_settings` surcharge les valeurs de référence de `config/system.php`. Les modifications ne dépendent plus d'un fichier PHP inscriptible. Les données sensibles et adresses de réception restent exclues du détail d'audit ; les clés modifiées sont conservées. Paramètres communs modifiables par Admin global avec les permissions de l'onglet. Aucun taux ni conversion d'historique ajouté.

## Vérifications et preuves

Essais d'écriture uniquement sur `qpos_test_phase6_20261012`, port 8123, cookie `qpos_phase6_session`, stockage/cache/logs privés et transport mail mémoire. Sauvegarde source initiale : 81 tables, empreintes des lignes et DDL comparées après restauration.

133 assertions principales : catalogue 14, droits 41, paramètres 30, authentification 32, audit 16. Compléments : concurrence réelle dernier Admin, huit contrôles du seed, recherche/tri/plafonds utilisateurs, refus historique caisse, régression métier et navigateur.

La campagne actuelle et ses limites sont détaillées dans [phase6-regression.md](phase6-regression.md) : **146 cas OK, 0 BUG restant, 0 cas retenu non rejoué**. Les verdicts de la campagne antérieure ne sont pas repris sans preuve nouvelle. Quatre premières assertions invalides de dates/portée ont été conservées puis reprises : sessions ouvertes le 12 comparées au 9 ; totaux de toutes les ventes comparés à une seule fixture ; péremption de la nouvelle seed comparée à l'ancienne date. Les contrôles reprennent les dates réelles et la portée des agrégats, sans modifier les règles financières pour satisfaire une assertion.

Navigateur réel : déconnexion/changement Admin → Jean → Marie → Admin ; Jean charge clients/catalogue sans droit d'ouverture caisse, Marie accède à sa caisse ; FR/EN, clair/sombre, 390×844, 768×1024 et 1440×900. `localhost:8123` reste déconnecté tandis que la session `127.0.0.1:8123` reste active : cookies séparés par hôte. Exports produit et synthèse : PDF privés extraits, rapprochés des chiffres SQL ou du payload immuable, rendus avec Poppler puis inspectés.

Contrôles POS finaux : Marie sans caisse reçoit le message et lien d'ouverture ; 5 000 XAF proposés, 6 000 saisis et monnaie 1 000 ; la saisie 6 000 reste après ajout d'une palette. Deux lignes Sac/Palette, quantités initiales 1, client de passage et remise 0 cachée. Boutons visibles mesurés ≥44 px et focus clavier visible (contour 2 px). Montant négatif : Encaisser désactivé et motif visible. Scan connu, inconnu, 256 caractères refusés puis nouveau scan valide ajoutant une seule unité ; verrou libéré. Scan simulé au clavier, aucun encaissement réalisé pour ces derniers essais UI. Captures tablette/bureau POS et catalogue reprises après chargement effectif des données.

Syntaxe des 40 fichiers PHP ajoutés/modifiés contrôlée ; build Vite et garde CSS réussis ; `git diff --check` réussi. Preuves et checkpoints privés : `storage/app/phase6/`. Le manifeste des assets et le nouveau CSS font partie de la livraison.

## Livraison sur qpos

Sauvegarde actuelle `qpos-before-phase6-delivery.sql`, restaurée dans `qpos_restore_phase6_20261012` et vérifiée sur 81 tables avant migration. Les deux migrations sont **Ran sur qpos**. Comparaison après livraison : **78 tables existantes inchangées**, y compris utilisateurs/mots de passe/tokens, affectations, stocks, ventes, paiements, sessions, historiques et synthèses. Seules les références permissions/attributions de rôle/migrations ont été enrichies, avec deux nouvelles tables. Aucun seed fresh ni jeu de simulation importé.

Nettoyage source : config, routes, vues compilées et cache de permissions. Aucune purge de sessions. Inventaire réel : 219 routes, 100 codes enregistrés, aucun code statique de middleware manquant. Lecture source HTTP kernel : Admin dashboard/audit/utilisateurs/paramètres 200 ; Jean POS/clients 200 et caisse 403 ; Marie POS/caisse 200. Identité injectée pour les lectures ; les mots de passe existants ont été comparés par hash sans changement. Aucun POST métier sur source pour les essais.

Comparaison finale : 77 tables originales inchangées. La seule évolution technique de la 78e (`reporting_runtime`) est `scheduler_last_run`, de `2026-10-12 14:09:06` à `2026-10-12 20:34:07` lors du contrôle détaillé ; aucun autre réglage, consentement ou état de livraison modifié. Il s'agit de l'horodatage du scheduler actif, pas d'un changement de vente/stock/compte. La preuve finale distingue cet avancement technique des écritures métier ; comptes/tokens et `.env` restent inchangés.

Le port 8000 était arrêté ; serveur principal relancé sur `http://127.0.0.1:8000`, connexion HTTP 200 vérifiée. `.env` et `APP_URL` existants préservés (`localhost/qpos/public`, configuration XAMPP) ; les routes HTTP utilisent l'hôte de la requête. Conserver un même hôte pour toute une connexion. Les URL des notifications CLI devront être alignées au domaine définitif lors de l'exploitation.

## Fichiers concernés

- Contrôleurs : catalogue, utilisateurs, rôles/permissions, devises, paramètres, authentification, audit ; retrait du contrôleur Google.
- Support/services : UserAdministration, RoleAssignment, AdministrationAudit, SystemSettings, AuditLog/UtcDateTime ; Helper, FileHandler, ValidImageType ; providers, middleware Admin, menu et routes.
- Seeders et deux migrations additives ; vues catalogue/utilisateurs/profil/rôles/paramètres/audit ; traductions FR/EN ; assets compilés.
- Documentation : cartographie, bilan, régression, roadmap, contrats, schéma et routes/permissions. Le bilan consolidé Phase 5 fourni reste inchangé.

## Limites

- Tous les cas retenus pour cette régression sont documentés et OK. Ce résultat porte sur les scénarios exécutés ; il ne constitue pas une certification d'absence de toute faille ni une validation de scénarios métier non retenus.
- Aucun email ni paiement réel ; SMTP réel et périphériques physiques ne sont pas certifiés par ces essais. L'auto 23h55 est vérifiée par horloge simulée, pas par attente du scheduler Windows.
- Filtre stock bas facultatif non ajouté. Le dashboard utilise son seuil existant `<10` unités de base hors ruptures ; aucun nouveau seuil paramétrable catalogue inventé.
- Google retiré de l'accès actif ; colonnes `google_id`/données conservées. `fetchPageData` conservée faute de suppression nécessaire ; `roleWisePermissions` reste appelée par une route.
- Retour arrière destructif des tables de preuve refusé : utiliser une restauration vérifiée. Sauvegarde quotidienne/externe, rétention d'audit, SMTP et exploitation restent les sujets Phase 7.

L'affichage monétaire à deux décimales des parcours POS/rapports est conservé ; aucun changement de calcul ou règlement XAF, aucune conversion BDT/fuseau historique.

## Clôture

**OK 6.G — Phase 6 terminée.** Aucun commit assistant. Les modifications et assets sont prêts pour l'unique commit du propriétaire. Sauvegardes, mesures, captures et checkpoint de reprise conservés dans le stockage privé ; aucun fichier de test à publier avec l'application.

<!-- phase6-files:start -->
## Inventaire exact des fichiers

| Fichier | État |
|---|---|
| `app/Casts/UtcDateTime.php` | ajouté |
| `app/Helper.php` | modifié |
| `app/Http/Controllers/AuthController.php` | modifié |
| `app/Http/Controllers/Backend/AuditLogController.php` | ajouté |
| `app/Http/Controllers/Backend/CurrencyController.php` | modifié |
| `app/Http/Controllers/Backend/Pos/CartController.php` | modifié |
| `app/Http/Controllers/Backend/Product/ProductController.php` | modifié |
| `app/Http/Controllers/Backend/RolePermission/PermissionController.php` | modifié |
| `app/Http/Controllers/Backend/RolePermission/RoleController.php` | modifié |
| `app/Http/Controllers/Backend/SystemSettingsController.php` | modifié |
| `app/Http/Controllers/Backend/UserManagementController.php` | modifié |
| `app/Http/Controllers/Backend/WebsiteSettingController.php` | modifié |
| `app/Http/Controllers/GoogleController.php` | supprimé |
| `app/Http/Middleware/AdminMiddleware.php` | modifié |
| `app/Models/AuditLog.php` | ajouté |
| `app/Providers/AppServiceProvider.php` | modifié |
| `app/Providers/RouteServiceProvider.php` | modifié |
| `app/Rules/ValidImageType.php` | modifié |
| `app/Services/AdministrationAudit.php` | ajouté |
| `app/Services/SystemSettings.php` | ajouté |
| `app/Support/BackendMenu.php` | modifié |
| `app/Support/RoleAssignment.php` | modifié |
| `app/Support/UserAdministration.php` | ajouté |
| `app/Trait/FileHandler.php` | modifié |
| `database/migrations/2026_10_12_140000_create_administration_audit_and_settings.php` | ajouté |
| `database/migrations/2026_10_12_143000_register_native_administration_permissions.php` | ajouté |
| `database/seeders/CurrencySeeder.php` | modifié |
| `database/seeders/RolePermissionSeeder.php` | modifié |
| `database/seeders/SaleWorkflowPermissionSeeder.php` | modifié |
| `database/seeders/StartUpSeeder.php` | modifié |
| `docs/contrats.md` | modifié |
| `docs/phase6-cartographie.md` | ajouté |
| `docs/phase6-regression.md` | ajouté |
| `docs/phase6-report.md` | ajouté |
| `docs/roadmap.md` | modifié |
| `docs/routes-permissions.md` | modifié |
| `docs/schema-cible.md` | modifié |
| `lang/en/administration.php` | ajouté |
| `lang/fr/administration.php` | ajouté |
| `public/build/assets/app-Bb-Zrv12.css` | supprimé |
| `public/build/assets/app-D6WbTixa.css` | ajouté |
| `public/build/manifest.json` | modifié |
| `resources/views/backend/administration/audit-index.blade.php` | ajouté |
| `resources/views/backend/administration/audit-show.blade.php` | ajouté |
| `resources/views/backend/products/index.blade.php` | modifié |
| `resources/views/backend/profile/index.blade.php` | modifié |
| `resources/views/backend/settings/role/index.blade.php` | modifié |
| `resources/views/backend/settings/role/permissions.blade.php` | modifié |
| `resources/views/backend/settings/website-settings/general.blade.php` | modifié |
| `resources/views/backend/users/index.blade.php` | modifié |
| `routes/web.php` | modifié |

Le fichier local `docs/phase5-bilan-final-consolide.md` reste préservé ; il n'est pas inclus dans cet inventaire de modifications Phase 6. Les scripts/preuves privés sous storage/app/phase6 sont ignorés par Git.
<!-- phase6-files:end -->

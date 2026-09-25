# QPOS — feuille de route de modernisation

Ce document est le plan de travail persistant de QPOS. Il est destiné à permettre la reprise du chantier sans dépendre de l'historique d'une conversation.

## Objectif

Faire évoluer QPOS vers une application de point de vente professionnelle, sûre, rapide, accessible et maintenable, tout en conservant les parcours métier qui fonctionnent. Les changements sont livrés par petites tranches cohérentes. Le propriétaire du dépôt réalise les commits après revue de chaque tranche.

## État de départ constaté

- Dépôt GitHub relié à la branche `main`; commit initial `aa9fed1` et arbre de travail propre au début du chantier.
- Dépendances actuelles : Laravel `^10.8` verrouillé en `10.48.22`, contrainte PHP `^8.1`; runtime CLI observé sur ce poste : PHP `8.2.12` (XAMPP). Vérifier séparément la version PHP chargée par Apache avant toute migration du runtime.
- Le `Dockerfile` utilise aussi PHP `8.2-fpm`; la CLI Docker n'est pas installée/disponible sur ce poste (`docker` introuvable), donc les builds d'image ne sont pas vérifiables ici.
- Blade/AdminLTE et Bootstrap 4 pour l'interface historique; jQuery et plugins front chargés par les vues; React utilisé pour les widgets POS/achats.
- Le socle front a depuis été mis à jour vers Vite 8.3, Laravel Vite Plugin 3.2, React 19.3, Tailwind 4.3, Sonner, Lucide, react-datepicker 9 et react-select 5.10. Les versions sont consignées dans `package-lock.json`.
- Chart.js 4.5.1 est maintenant géré par npm et compilé uniquement dans l'entrée Vite du tableau de bord; les vues n'utilisent plus de script CDN et les pages non concernées ne téléchargent plus Chart.js, Moment ni DateRangePicker. Les deux graphiques d'accueil sont responsives, adaptés au thème actif et exposés avec un nom accessible.
- Le conteneur Node a été aligné sur Node 24 LTS; la ligne Node 20 est arrivée en fin de vie en mars 2026.
- La locale de Laravel et la langue HTML sont actuellement l'anglais; le fuseau Laravel est `Asia/Dhaka`.
- Le dépôt contient déjà un premier passage de corrections de sécurité et de logique métier. Revalider les comportements concernés au fil des lots plutôt que les réécrire sans examen.

## Décisions et principes

1. **Langue** : français par défaut, anglais disponible au choix de l'utilisateur; préférence conservée en session. Le texte de chaque écran est traduit au moment où cet écran est migré.
2. **Plateforme cible** : viser PHP 8.4 et Laravel 13, après inventaire de compatibilité des dépendances et des extensions XAMPP. Laravel 12 accepte PHP 8.2, mais sa période de corrections fonctionnelles s'est terminée le 13 août 2026 et sa sécurité se termine le 24 février 2027; Laravel 13 accepte PHP 8.3–8.5, avec corrections jusqu'au troisième trimestre 2027 et sécurité jusqu'au premier trimestre 2028 ([politique officielle Laravel](https://laravel.com/framework/docs/12.x/releases)). L'upgrade guide Laravel 13 doit servir de contrôle de changements ([guide officiel](https://laravel.com/framework/docs/13.x/upgrade)). Ne pas modifier le `composer.lock` avant d'avoir préparé et validé le runtime.
3. **Interface** : migration progressive, page par page. Garder Blade comme rendu principal des pages métier; React reste réservé aux interactions riches qui en ont réellement besoin, notamment le POS.
4. **Dépendances** : retenir des versions stables compatibles entre elles, enregistrer les versions dans les fichiers lock, éviter les mises à jour majeures en bloc.
5. **Métier** : conserver le comportement existant tant qu'une règle n'a pas été vérifiée avec les données et les parcours réels de QPOS. Pas de migration destructive ni de changement de devise/fuseau sans validation du besoin.
6. **Livraison** : une tranche lisible par commit, sans commit automatique. Préserver `.env`, les données locales et les fichiers importés.

## Feuille de route détaillée

### Lot 0 — Base de travail et sécurité du dépôt — terminé

- Dépôt local initialisé, relié à GitHub, branche `main` synchronisée.
- Fichiers d'environnement et données générées exclus du dépôt selon `.gitignore`.
- Le propriétaire vérifie et crée les commits après chaque lot.

### Lot 1 — Fondations produit : localisation et préférences — en cours

- Mettre le français en locale et en langue HTML par défaut.
- Ajouter un changement FR/EN explicite, validé côté serveur et conservé en session.
- Poser les catalogues de traduction FR/EN et migrer d'abord la navigation, le compte utilisateur, les libellés partagés et les erreurs de validation.
- Parcourir ensuite les écrans dans l'ordre d'usage : connexion, accueil, caisse, ventes, produits, achats, clients/fournisseurs, rapports, utilisateurs et paramètres.
- Préférence clair/sombre avec choix système au premier chargement, bascule accessible et choix utilisateur mémorisé localement; vérifier le contraste sur tous les écrans avant de déclarer le lot terminé.
- Laisser le fuseau `Asia/Dhaka` inchangé jusqu'à confirmation du fuseau métier voulu; l'environnement utilisateur indique `Africa/Douala`, ce qui mérite une décision distincte.

**Critères de fin** : toutes les pages de premier niveau affichent la langue sélectionnée; les formulaires gardent la langue après soumission; les clés manquantes sont visibles en revue; le HTML expose une langue correcte; changement de thème lisible et persistant.

### Lot 2 — Compatibilité runtime et framework

- Inventorier les versions réellement verrouillées par `composer.lock`, les contraintes PHP, extensions utilisées, Dockerfiles et packages Laravel tiers. Cet inventaire révèle maintenant : Dompdf wrapper 2.2.0 → 3.1.2 disponible; Maatwebsite Excel 3.1.58 → 3.1.70; Guzzle 7.9.2 → 8.2.0; Intervention Image 2.7.2 → 3.11.8 (API à adapter dans `app/Trait/FileHandler.php`); Sanctum 3.3.3 → 4.3.3; Spatie Permission 5.11.1 → 6.25.0; Yajra DataTables 10.0.0 → 12.0.0. Vérifier les matrices de compatibilité Laravel 13 avant de fixer les contraintes.
- Préparer PHP 8.3 (ou une version compatible plus récente) pour XAMPP et Docker et vérifier la cohérence CLI/Apache.
- Mettre à niveau Laravel par étapes vers 13 et résoudre les incompatibilités des dépendances tierces individuellement.
- Actualiser les fichiers d'installation et la documentation pour correspondre aux versions qui fonctionnent réellement.

**Critères de fin** : installation reproductible avec Composer, démarrage local XAMPP et Docker documentés, aucun package abandonné ou bloquant sans décision explicite, lockfiles cohérents.

### Lot 3 — Socle visuel et front-end moderne — fondations commencées

- Définir les jetons visuels (couleurs, typographie, espacements, rayons, états) et les règles responsive/accessibles.
- Tailwind 4 est configuré via son plugin Vite, sans Preflight global afin de préserver les écrans AdminLTE existants pendant leur migration.
- Migrer progressivement depuis AdminLTE/Bootstrap/jQuery, en commençant par le layout partagé, la navigation et les formulaires communs; ne pas supprimer les anciennes feuilles tant que toutes les pages ne les ont pas quittées.
- Lucide est adopté sur quelques contrôles POS/achats; poursuivre le remplacement icône par icône lors des migrations d'écran.
- Les notifications des widgets React utilisent Sonner; `react-hot-toast` a été supprimé. Le pont commun pour les messages flash Blade reste à construire.
- Les widgets React ne se chargent désormais que sur les routes POS et création d'achat. Les racines React gardent des points de montage explicites.

**Critères de fin** : navigation au clavier, focus visibles, états chargement/vide/erreur cohérents, mode sombre lisible, aucune dépendance front ancienne supprimée avant le remplacement de ses usages.

### Lot 4 — Architecture applicative

- Cartographier routes, contrôleurs, permissions et vues; retirer les routes de diagnostic ou d'administration non destinées aux utilisateurs.
- Déplacer validation et autorisation vers Form Requests, Policies/Gates ou middleware dédiés plutôt que dupliquer les règles dans les contrôleurs.
- Extraire les opérations métier sensibles (vente, achat, règlement, stock, import) en services/actions transactionnels avec responsabilités limitées.
- Garder une architecture Laravel modulaire et simple; ne pas ajouter de couches abstraites sans besoin métier démontré.
- Ajouter journalisation métier ciblée pour les opérations sensibles sans enregistrer de secrets ni données de paiement inutiles.

**Critères de fin** : chaque route a un niveau d'accès explicite; les contrôleurs restent courts; les règles de vente/stock ne sont pas dupliquées; les opérations critiques ont des frontières transactionnelles claires.

### Lot 5 — Parcours POS et règles métier

- Documenter le cycle complet : panier, prix, remise, taxe, stock, paiement, monnaie rendue, dette, règlement partiel, facture et annulation.
- Confirmer les règles de priorité et d'arrondi avec des cas réels avant de changer les montants.
- Garantir le calcul serveur des totaux; ne jamais faire confiance aux totaux fournis par le navigateur.
- Vérifier concurrence et stock lors de ventes simultanées, doublons réseau, panier appartenant à un autre utilisateur, collectes supérieures au solde et transition d'état des commandes.
- Rendre les erreurs récupérables pour la caisse et afficher clairement le résultat d'une opération.

**Critères de fin** : scénario de vente documenté de bout en bout; totaux et mouvements de stock traçables; échec au milieu d'une vente sans écriture partielle incohérente.

### Lot 6 — Sécurité et données

- Revoir authentification, OTP/réinitialisation, sessions, CSRF, permissions et séparation des comptes.
- **Première correction faite** : suppression des comptes admin/caisse/ventes et mots de passe fixes des seeders; création interactive du premier admin via `php artisan qpos:admin:create`; seeders de démonstration déplacés vers `DemoDataSeeder`, refusé en production. Vérifier encore les autres accès/authentifications.
- Le setup Docker conserve maintenant un `.env` existant, migre sans `migrate:fresh`, ne réécrit pas une clé déjà définie et ne rend plus tout `bootstrap/` accessible en écriture.
- Valider uploads/imports (type réel, taille, contenu, nommage); empêcher stockage/exécution publique non voulue.
- Revoir échappement Blade, colonnes HTML DataTables, filtres de requêtes, secrets, CORS, debug et réponses d'erreur.
- Vérifier contraintes/index/migrations avant toute correction de données; sauvegarde et restauration documentées.
- Définir une politique de rétention pour journaux, exports, sessions et fichiers importés.

**Critères de fin** : aucune route mutative accessible en GET; accès par permission vérifié sur le serveur; données utilisateurs échappées par défaut; secrets et données locales absents du dépôt.

### Lot 7 — Performance et capacité

- Remplacer les chargements globaux par pagination et DataTables serveur là où les volumes peuvent croître.
- Mesurer puis corriger N+1, agrégats de tableaux de bord, chargement des relations et appels répétés.
- Référence avant découpage : 559,17 kB minifiés / 168,73 kB gzip. Build actuel : entrée commune 221,70 kB / 69,45 kB gzip; écran achats 173,17 kB / 43,44 kB gzip; POS 13,66 kB / 4,23 kB gzip; dépendance react-select chargée avec les écrans concernés 262,21 kB / 82,59 kB gzip.
- Entrée dashboard séparée avec Chart.js 4.5.1 : 203,93 kB / 69,98 kB gzip, chargée uniquement sur l'accueil. Moment/DateRangePicker locaux restent limités aux écrans utilisant la sélection de dates.
- Ajouter uniquement les index justifiés par les filtres et jointures réellement utilisés.
- Définir cache/queue après avoir clarifié l'environnement mono-poste XAMPP et le déploiement visé.
- Réduire les bibliothèques chargées sur les écrans qui n'en ont pas besoin; produire des assets versionnés et minifiés.

**Critères de fin** : pages listes bornées, requêtes principales identifiées, bundle front adapté par usage, pas de cache susceptible de mélanger utilisateurs ou langues.

### Lot 8 — UX métier, responsive et accessibilité

- Simplifier formulaires, recherche, filtres, confirmations et retours après sauvegarde.
- Optimiser l'écran POS tactile et clavier, les états hors réseau/latence, et l'affichage des totaux.
- Vérifier tablette/mobile, contrastes, labels, messages de validation, focus, navigation clavier et lecteurs d'écran.
- Uniformiser les formats monétaires et dates sans changer les règles comptables.

### Lot 9 — Exploitation et continuité

- Maintenir un guide d'installation XAMPP, un guide Docker, un guide de mise à jour et un guide de sauvegarde/restauration.
- Documenter les variables `.env`, extensions PHP, droits `storage`/`bootstrap/cache`, tâches planifiées et mail.
- Prévoir une procédure de retour arrière pour les déploiements et changements de schéma.
- Tenir ce fichier à jour : statut, décisions prises, commandes exactes, blocages et prochaine tranche.

## Ordre d'exécution immédiat

1. Terminer le contrôle de couverture FR/EN sur toutes les vues et les erreurs de validation, puis vérifier les deux langues dans XAMPP.
2. Continuer la refonte visuelle de l'application : layout/AdminLTE, dashboard, listes/formulaires, puis POS; AdminLTE/Bootstrap 4 reste aujourd'hui le socle principal.
3. Étendre les protections aux imports/uploads, rôles/permissions, encaissements, ventes concurrentes et transitions de commande; vérifier les suppressions et l'archivage des données liées.
4. Préparer PHP 8.4 pour XAMPP et Docker, puis inventorier les packages Composer et passer directement de Laravel 10 à 13 si la compatibilité le permet. Laravel 13 exige PHP 8.3 minimum; PHP 8.2 est supporté par le projet PHP pour la sécurité jusqu'au 31 décembre 2026.
5. Finaliser les opérations métier, mesures de performance, formats de date/devise, accessibilité et guides d'installation/sauvegarde. Les commits restent ceux du propriétaire.

## Journal de reprise

- **Point de départ** : dépôt `main` synchronisé; état propre avant la création de ce document.
- **En cours** : FR/EN avec sélection sécurisée en session, traductions du POS/panier, ventes/encaissements/factures et premier écran produits/achats; thème clair/sombre et fondations visuelles; Vite/React/Tailwind/Sonner/Lucide mis à jour. Seeders sans identifiants fixes et commande de création du premier admin ajoutés. La couverture complète de toutes les vues n'est pas terminée.
- **Vérifications locales** : `npm run build` passe sans avertissement de taille; routes locale enregistrées; JSON des catalogues valide; PHP syntaxe des contrôleurs modifiés valide; `npm ls --depth=0` cohérent sans vulnérabilités annoncées à l'installation. Aucun test automatisé n'a été lancé.
- **Prochaine action** : compléter la traduction des champs et tableaux des écrans CRUD, rapports et paramètres ainsi que des messages serveur; ensuite vérifier le thème dans XAMPP et moderniser le dashboard/POS. Le passage Laravel 13 reste suspendu à PHP 8.3+.
- **Commits** : à créer par le propriétaire après revue; Codex ne commit pas.
- **Tests** : ne pas ajouter ni lancer de tests automatisés sans demande explicite; consigner séparément les vérifications manuelles demandées.

## Dernier point de reprise — 2026-09-24

- État de runtime relevé : PHP CLI `8.2.12`, Composer `2.10.3`, Node `24.14.0`, npm `11.9.0`; Laravel effectivement installé/verrouillé `10.48.22`. `composer check-platform-reqs --lock` passe sur le runtime CLI installé. La version du PHP du module Apache XAMPP doit encore être confirmée.
- Audit statique des appels littéraux `__()`, `@lang()` et `translate()` dans `resources/views`, `resources/js` et `app` : 420 clés distinctes référencées, aucune absente des catalogues. Les sélecteurs de dates du tableau de bord et des rapports utilisent maintenant les libellés FR/EN, les noms de jours/mois localisés, et le calendrier est chargé seulement sur ces pages. Cet audit ne remplace pas le contrôle des contenus générés dynamiquement ni la vérification visuelle de chaque page.

- Le catalogue FR/EN contient 401 clés identiques, sans doublons exacts. Plusieurs écrans produits, achats, listes, tableaux DataTables, pages d'authentification et paramètres utilisent maintenant les clés traduites; un audit visuel complet de chaque vue reste à faire.
- Les messages flash des contrôleurs modifiés sont désormais passés par le traducteur Laravel.
- Le flux d'achat a été renforcé : calcul des totaux au serveur, permissions distinctes création/modification, validation des lignes, transaction avec verrouillage, delta de stock vérifié et réponse d'erreur sans détail SQL. Le formulaire d'édition conserve le fournisseur et sélectionne la date sans décalage UTC.
- Correction des réponses JSON fournisseurs/produits, pagination de la recherche d'achat et requêtes DataTables paginables. Les produits présents dans l'historique des ventes ou achats ne sont plus supprimables.
- Les ventes, achats et encaissements empêchent désormais la suppression de leurs clients, fournisseurs et utilisateurs liés; le client/fournisseur interne et le dernier compte Admin sont protégés. La liste des ventes utilise une requête paginée avec somme des articles calculée en base, et le checkout refuse les montants supérieurs aux limites des colonnes SQL.
- Les mutations du panier POS sont sérialisées par utilisateur; l'ajout verrouille produit puis ligne du panier, le checkout verrouille l'utilisateur avant de lire le panier et réserve les produits. La suppression produit vérifie l'historique sous verrou avant suppression, pour éviter une course avec le checkout.
- Les prix/remises produits sont bornés aux capacités SQL et aux règles métier (remise fixe ≤ prix, pourcentage ≤ 100); le checkout refuse également une remise corrompue qui donnerait un prix incohérent.
- Le balayage a aussi retiré la vue d'achat et le composant React d'exemple restés sans référence; la vue d'achat orpheline pointait à tort vers les contrôleurs clients.
- Les formulaires actifs de gestion des rôles n'utilisent plus Laravel Collective; sa contrainte Composer demeure à retirer lors de la régénération coordonnée du lockfile. `composer validate --no-check-publish` passe sur le manifeste actuel.
- Les catalogues FR/EN contiennent maintenant 428 clés identiques, sans différence de couverture. Les formulaires utilisateur et de gestion des rôles utilisent les traductions; le hash du mot de passe n'est plus rendu dans le formulaire d'édition, et un mot de passe laissé vide n'écrase pas l'actuel. Le rôle Admin est protégé par son nom plutôt qu'un ID supposé; le dernier Admin ne peut pas être rétrogradé et les rôles attribués ne peuvent pas être supprimés.
- Retrait de la route de diagnostic `/test` et de la route produits dupliquée. Le thème sombre couvre les composants et alertes courants; Sonner suit le thème choisi.
- Vérifié localement : `php -l` sur les contrôleurs touchés, `php artisan view:cache`, `php artisan route:list --path=admin/products`, absence de route `/test`, catalogues parsables, `npm run build` sans avertissement de taille. Aucun test automatisé n'a été exécuté.
- `composer audit` fonctionne avec un bundle temporaire construit depuis les autorités racines Windows (TLS demeure vérifié) : 81 avis sur 19 packages (2 critiques, 30 élevés, 39 moyens, 9 faibles, 1 sans niveau indiqué) et `laravelcollective/html` est abandonné. Versions directement relevées : Laravel 10.48.22, Dompdf 2.0.8, PhpSpreadsheet 1.29.2, Guzzle 7.9.2, League CommonMark 2.5.3, Symfony HttpFoundation 6.4.13. `composer update --dry-run --with-all-dependencies` est bloqué par les contraintes de `laravel/framework ^10.8` et `barryvdh/laravel-dompdf ^2.0`, dont toutes les versions résolubles sont signalées vulnérables. Le seul usage actif de `laravelcollective/html` a été remplacé par des formulaires Blade natifs; suppression de sa contrainte Composer et régénération du lock à faire dans la migration. Ne pas ignorer les avis.
- Vérifié sur la tranche actuelle : `php -l` sur les contrôleurs modifiés; `php artisan view:cache`; JSON FR/EN valide et couverture 428/428; 420 clés littérales extraites des sources sans clé manquante; `git diff --check` sans erreur de whitespace (les avertissements restants indiquent seulement une normalisation CRLF/LF). `npm run build` et `npm run build:docker` réussissent (Vite 8.3.1, 2336 modules transformés); `npm ls --depth=0` cohérent; `npm audit` réussit avec 0 vulnérabilité. Les contrôleurs POS et produits modifiés cette tranche passent `php -l`. Aucun test automatisé n'a été lancé. La connexion au navigateur de prévisualisation a échoué avant ouverture; le rendu réel XAMPP doit encore être contrôlé.
- Poursuite du contrôle des textes d'interface : réinitialisation de mot de passe, import de produits, résumé des ventes et champs restants des paramètres passent par le catalogue. Le formulaire de renvoi du code a été sorti du formulaire principal imbriqué, ce qui corrige le HTML invalide sur cette page.
- Les vues inutilisées `login-otp` et `sign-up` et la méthode d'inscription publique sans attribution de rôle ont été retirées : elles n'avaient aucune route ni référence active.
- L'import produit exige maintenant un fichier borné à 5 Mio avec extension/type pris en charge, valide chaque ligne, limite les quantités et les montants, utilise le fournisseur interne configuré et englobe produits, achats et lignes dans une transaction. Le gabarit d'exemple reste compatible avec ces règles.
- État produit : fondations et améliorations ciblées uniquement. Pas encore de refonte visuelle complète ni de mise à niveau Laravel; le prochain gros lot commence par le contrôle visuel XAMPP puis le layout et le dashboard.

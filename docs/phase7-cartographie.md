# Phase 7 — cartographie et audit en cours

Base `0d6abc2`, préparation du 12/10 puis audit complété le 13/10/2026. Contrat consolidé : [phase7-plan.md](phase7-plan.md). **OK 7.A — cartographie et audit de référence terminés.** Les corrections visuelles et leur vérification détaillée appartiennent à 7.C et 7.H.

## OK — préparation isolée

- Dépôt initial propre, branche main et HEAD confirmés.
- Nouvelle sauvegarde privée `storage/app/phase7/qpos-before-phase7.sql`, 83 tables. Nouvelle copie `qpos_test_phase7_20261012` créée depuis qpos, sans réutiliser les simulations Phase 6.
- Données et DDL rapprochés par empreinte après restauration ; manifeste `storage/app/phase7/baseline.json`.
- Les deux migrations Phase 6 du 12/10 sont présentes dans la copie. Comptes Admin/Marie/Pauline/Jean/Pierre et trois boutiques conservés.
- Runtime privé, cookie qpos_phase7_session, serveur 8124, mail mémoire et stockage/cache/logs privés. Source sans écriture métier, aucun envoi externe.
- Sous sandbox Windows, PHP is_writable renvoie faux sur un dossier permettant effectivement l'écriture. Les manifestes packages/services existants du même commit ont été copiés dans le cache privé pour démarrer l'environnement sans modifier vendor ni le cache source. À recontrôler si les dépendances changent. L'accès SQL local a nécessité l'exécution autorisée hors sandbox.

## Cartographie technique initiale

| Domaine | Existant réutilisé | Adaptation prévue |
|---|---|---|
| Administration | RoleAssignment, UserAdministration, contrôleur utilisateurs, audit Phase 6 | shop_admin, droits directs délégables, origine des droits, protections conservées |
| Portée boutique | PointOfSale::accessibleBy, StockContext, middleware contexte | Boutique de travail fixe autorisée, sans élargissement des rapports |
| Présentation | master-tailwind, composants backend, WorkspaceUI.jsx, workspaces.css | Consolidation des composants et densité par écran |
| Vente | Pos.jsx, services vente/caisse/passation | Séparation visuelle paiement/tender/monnaie/dette ; invariants serveur préservés |
| Rapports | ReportingService, filtres, dashboard/statistics et exports | Dashboard rapide, comparaisons explicitement définies, tableaux accessibles pour graphiques |
| Synthèses | DailySummaryService, SummaryDeliveryService, SummaryMailApproval | Conserver circuit/version/consentement et états de livraison ambigus |
| Notifications | Pas de table notifications dans la copie | Notifications natives + préférences + outbox/tentatives |
| Tâches | Queue effective sync ; jobs absent ; failed_jobs existant | Queue durable explicite pour notifications et traitement surveillé |
| Scheduler | sales:expire-pending, reports:daily, reports:deliver | Ajouter tâches Phase 7 sans dupliquer les synthèses |
| Paramètres | system_settings whitelist JSON | Réglages ordinaires uniquement ; secrets hors cette table |
| Sauvegarde | Sauvegardes privées de chantier | Service exploitation, destination externe et exercice chronométré |

Inventaire détaillé des routes et migrations : `storage/app/phase7/inventory.json`. Les anciens noms supposés de permissions ne seront pas ajoutés sans rapprochement des routes natives. Le rôle Admin est explicitement référencé dans les protections actuelles.

## OK — premier relevé navigateur

Connexion réelle Admin sur copie réussie. Quatorze écrans parcourus dans les deux dimensions : **390×844 et 1440×900**, soit 28 observations et 28 captures complètes avant refonte. Ce relevé est une étape de l'audit, pas la validation générale des parcours ou de WCAG AA.

Écrans : dashboard, statistiques, POS, sessions de caisse, catalogue, création produit, achats, création achat, clients, fournisseurs, utilisateurs, rôles, paramètres généraux, journal d'audit.

Preuves : `storage/app/phase7/ui-audit.json` et fichiers `before-mobile-*` / `before-desktop-*`. Aucun dépassement de largeur globale mesuré sur ces 28 observations. Le contrôle automatique d'un texte d'erreur serveur est indicatif ; les titres de chaque page ont aussi été relevés. Les chargements métier, modales ouvertes et validations de formulaires nécessitent leurs propres scénarios.

### Priorités initiales

| Priorité | Constat | Action prévue |
|---|---|---|
| Haute | Dashboard mobile : bloc de filtres détaillé avant les indicateurs ; chiffres hors première vue | Quatre cartes prioritaires, filtres usuels compacts, détails dépliables ; conserver portée et période visibles |
| Haute | Certaines commandes de rôles et de création sous 44 px dans le relevé DOM | Vérifier les contrôles concernés et appliquer le composant commun sans inclure les éléments réellement masqués |
| Haute | Notifications futures impossibles en mode sync sans queue durable | Outbox, jobs et worker séparés ; tests rollback/rejeu/révocation avant activation |
| Moyenne | Sélecteur boutique mobile tronqué, action de gestion très présente | Contexte boutique compact et lisible, gestion réservée à son parcours |
| Moyenne | Dashboard et statistiques partagent filtres/navigation/graphiques | Distinguer les usages tout en conservant les données et les droits acquis |
| Moyenne | Libellés All encore visibles dans certains filtres FR | Traductions communes FR/EN |
| Moyenne | Nombreux composants déjà disponibles | Les réutiliser ; inventaire des dépendances avant toute suppression |

## Audit complémentaire du 13/10

158 observations enregistrées sur 78 URL distinctes, aux dimensions 390×844 et 1440×900 ; deux premières observations lots automatiques en erreur sont conservées et suivies de deux reprises réussies. Les titres et largeurs figurent dans ui-audit.json, avec captures privées. Les 49 pages statiques incluent les écrans principaux et secondaires ; les cibles dynamiques incluent les treize rapports, détails et éditions des référentiels, conditionnements, utilisateur Marie, synthèse et audit présents en base. Les URL de détail viennent des IDs réels. La route show catégorie renvoie une réponse de données, pas un écran HTML. Éditions du fournisseur interne et du client Walking : refus 403 attendus ; aucun contournement.

Défaut confirmé et corrigé immédiatement : automatic-lots.blade.php avait deux héritages, dont backend.layouts.master inexistant ; suppression de celui-ci, chargement réussi mobile/bureau. Pas de modification des opérations ou des données de lot. Le style hérité de cette page reste à harmoniser en 7.C.

POS chargé effectivement avant examen : F4 ouvre le panier, message de session absente et lien d'ouverture visibles ; F2 revient au catalogue et place le focus dans son champ. Thème sombre et anglais observés, capture conservée ; pas de débordement global sur le POS à 320 px. Les alertes et sélecteurs de paiement ne sont pas suffisamment hiérarchisés : le champ non espèces est tronqué dans le panier mobile et doit recevoir une largeur adaptée. Le montant remis reste distinct de l'allocation. Aucun encaissement réalisé pour cet audit.

Les rôles métier et refus sont cartographiés à partir des routes/services et du rapport Phase 6 ; les scénarios de nouvelles attributions/révocations seront exécutés en 7.B. Contrastes détaillés, zoom réel 200 %, focus de chaque modale et parcours avec vente persistée constituent des contrôles de réception 7.C/7.H ; ce relevé initial ne prétend pas certifier WCAG AA.

### Matrice de traitement

| Écrans | Fichiers principaux | Intervention |
|---|---|---|
| Shell, référentiels, création/édition, boutiques/rôles | composants backend, shell CSS/JS, selector, roles | Cibles 44 px, contexte lisible, états clavier/modales, tableaux adaptés |
| POS/panier | Pos.jsx, WorkspaceUI.jsx, workspaces.css | Zones total/paiements/tender/monnaie/dette, moyens lisibles, action selon rôle |
| Dashboard/statistiques/rapports | reporting/*.blade.php, ReportingService, dashboard.js | Indicateurs prioritaires, filtres progressifs, comparaisons et alternatives aux graphiques |
| Lots automatiques | stock/automatic-lots.blade.php | Layout réparé, migration de présentation aux composants communs en 7.C |
| Utilisateurs/rôles/boutiques | contrôleur utilisateurs, RoleAssignment, contexte boutique, seeders | Nouveau rôle local et droits directs avec audit, sans nouvelle hiérarchie parallèle |
| Notifications/exploitation | futur outbox, commandes, navbar, état système | Réutilisation synthèses et audit, permissions dédiées, traitement durable |

## Critères de réception des lots suivants

1. Auditer les autres listes, formulaires, détails et rapports, avec données effectivement chargées ; distinguer modèles communs et écrans spécifiques.
2. Inspecter les captures et les états POS catalogue/panier avec une fixture privée ; caisse et vendeur, permissions négatives et menu par rôle.
3. Contrôles clavier/focus/modales, thèmes FR/EN, zoom et largeur 320 ; mesures de performance de référence.
4. Compléter la matrice écran → défaut → fichier → action → preuve et les critères d'acceptation.
5. Vérifier que la source, .env et tokens restent préservés à la livraison ; les tests restent sur copie. Empreinte .env identique à la préparation, contrôlée le 13/10. Aucun seed source ni modification de mot de passe/token.

7.B–7.H à exécuter. Le commit 7.A sélectionne les deux documents et la correction d'héritage vérifiée ; les preuves privées sont exclues. Aucun push.

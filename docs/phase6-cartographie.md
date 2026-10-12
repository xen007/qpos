# Phase 6 — cartographie de départ

12/10/2026. Branche main, HEAD `2bf0e2a`. Le rapport local `phase5-bilan-final-consolide.md` est conservé sans modification.

## Isolation vérifiée

Sauvegarde privée de `qpos`, restauration neuve dans `qpos_test_phase6_20261012`, comparaison du schéma et des empreintes de toutes les lignes des 81 tables. Manifeste privé : `storage/app/phase6/baseline.json`. Aucun réemploi des mutations des simulations antérieures. Port de contrôle prévu : 8123 ; stockage, sessions, caches et transport mail isolés.

Source observée : Admin global affecté aux trois boutiques ; Marie et Pauline `Demo Cashier`, Jean `sales_associate`, Pierre `Demo Stock`, affectés à MAIN. XAF est déjà la devise par défaut. `audit_logs` est absent. Les migrations Phase 5 sont présentes dans la sauvegarde.

## Écarts et intervention prévue

| Lot | Constat | Fichiers principaux |
|---|---|---|
| 6.B | Recherche/tri/pagination présents ; pas de filtres catégorie, statut ou marque. Sans boutique sélectionnée, la liste peut utiliser des valeurs historiques de stock. | ProductController, vue products/index, traductions |
| 6.C | Cases de permissions : valeurs noms ; validation contrôleur : IDs. Reproduction nécessaire sur copie. Suspension/suppression ne réutilisent pas la protection de cible de l'édition. Dernier Admin protégé partiellement ; utilisateurs listés sans portée locale. Gestion des permissions permet de renommer les codes utilisés par les routes. | RoleController, PermissionController, UserManagementController, RoleAssignment, vues utilisateurs/rôles |
| 6.D | writeConfig réécrit config/system.php à chaque champ. Plusieurs actions acceptent tout le POST. Devise par défaut non atomique et cache incomplet après modification/suppression. | Helper, paramètres, CurrencyController, service de paramètres |
| 6.E | Routes Google actives ; connexion/récupération limitées en fréquence, CSRF actif ; profil à vérifier (ancien fichier, confirmation/changement du mot de passe). | AuthController, routes/web, vues auth/profil |
| 6.F | Pas de table/service/page d'audit ; contrats prévoient acteur, boutique, cible, avant/après filtrés, résultat, motif, correlation et dates. | Migration additive, modèle/service/controller d'audit, routes/menu, seeder |
| 6.G | Campagne antérieure 146/146 documentée ; nouveaux essais à distinguer des résultats anciens. | Bilan, documentation des routes/contrats et preuves privées |

## Contrôles de fin retenus

Droits côté serveur et accès directs ; aucun élargissement implicite des permissions ou boutiques. Préparation vendeur distincte de l'encaissement. Protection du dernier administrateur actif sous verrou commun. Journaux Phase 6 atomiques et filtrés ; aucun événement historique inventé. Paramètres validés et persistés sans écriture de secrets ni de configuration PHP en HTTP. Affichage XAF à deux décimales, calculs et BDT inchangés.

Suppression de méthodes seulement si leur absence d'usage est démontrée et utile au lot. Tests d'écriture exclusivement sur la copie ; données source et secrets contrôlés au bilan. Après validation sur copie et nouvelle restauration vérifiée, les deux migrations additives Phase 6 ont été livrées sur qpos ; 78 tables existantes inchangées. L'unique commit reste au propriétaire.

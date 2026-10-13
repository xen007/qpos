# Rôles et permissions — Phase 7.B

Les rôles sont cumulables. Les permissions des rôles et les permissions directes s'additionnent ; les affectations actives limitent les opérations et rapports par boutique. Ni un rôle ni une préférence ne créent une affectation implicite hors du parcours de création décrit ci-dessous.

| Rôle technique | Libellé et usage | Portée |
|---|---|---|
| Admin | Administrateur global ; identifiant existant conservé | Supervision globale selon permissions natives ; dernier Admin actif protégé |
| shop_admin | Administrateur de boutique | Boutique(s) affectée(s) ; utilisateurs locaux, rôles en lecture, rapports/audit locaux et opérations autorisées |
| cashier / Demo Cashier | Encaissement et session personnelle | Affectations actives ; aucune entrée stock native par défaut |
| sales_associate | Préparation de vente et passation | Affectations actives ; pas d'ouverture de caisse sans droit explicite |
| Demo Stock | Stock et inventaires, réception d'achat | Affectations actives ; purchase_view + purchase_receive natifs |

Le profil shop_admin exclut point_of_sale_manage_all, modification des définitions de rôles, paramètres globaux, suppression d'utilisateurs et écritures du catalogue partagé. Il ne peut attribuer Admin/shop_admin ni un rôle contenant des capacités non délégables. Le propriétaire global crée les administrateurs locaux et configure leurs affectations.

## Droits directs

Gestion utilisateurs → modifier/créer → Permissions additionnelles. La liste vient des références natives et des droits détenus par l'acteur. Un droit absent n'est jamais enregistré depuis une requête falsifiée. Les droits hérités et directs sont présentés séparément ; chaque rôle expose sa liste. Retirer un rôle ne supprime pas les droits directs. Les anciens droits directs hors liste éditable sont conservés, pas effacés silencieusement.

Une modification réelle de droits directs ou de boutique fixe exige un motif et crée user.access_updated dans audit_logs, avec valeurs avant/après, acteur, boutique autorisée, corrélation et date. Aucune valeur de mot de passe ou de token dans cet événement. L'opération partage la transaction de modification du compte. Les contrôles de cible et d'acteur sont revérifiés après verrouillage.

Pour autoriser une réception au vendeur/caissier : accorder purchase_view et purchase_receive. Inventaires/ajustements natifs utilisent stock_inventory ; transferts stock_transfer_dispatch / stock_transfer_receive. L'ancien stock_adjust n'est consommé par aucune route native : le rôle Demo Stock utilise désormais les capacités effectives ; la référence SQL elle-même et les anciens droits directs ne sont pas supprimés. Les corrections des métadonnées globales de lots et la validation des ouvertures ne sont pas délégables par le profil local.

## Boutique de travail fixe

Choisir une boutique active déjà autorisée au compte. À la création par l'Admin global, choisir une boutique fixe crée aussi cette affectation active ; les autres affectations se gèrent dans Boutiques. À la création locale, le compte est affecté à la boutique de travail autorisée de l'acteur.

La boutique fixe remplace le sélecteur opérationnel et refuse les IDs falsifiés côté serveur. Les rapports gardent leur portée autorisée et leurs filtres. Si l'affectation ou la boutique devient inactive, les opérations sont bloquées avec indication de contacter l'administration ; aucun accès de repli à une autre boutique. Une caisse ouverte ou une préparation active (vendeur ou caissier) d'une autre boutique doit être terminée avant le changement. Effacer la préférence ne crée aucun droit supplémentaire.

## Consultation des rôles

La page Rôles affiche libellé, permissions et nombre d'utilisateurs dans la portée de gestion. Les définitions restent modifiables uniquement par l'administration globale. Admin et shop_admin gardent leurs noms techniques stables ; shop_admin ne peut recevoir de pouvoir global par modification de sa définition. Les retraits personnalisés de ses permissions sont conservés lors d'une réexécution du seeder.

## Vérifications de 7.B

13 cas service/SQL et 20 cas par noyau HTTP exécutés sur qpos_test_phase7_20261012 ; fixtures annulées après contrôle des mutations SQL. Le noyau HTTP injecte l'identité pour les refus/permissions ; ce contrôle ne remplace pas les parcours de connexion navigateur. Deux premières attentes HTTP invalides (Accept JSON sur écran POS, mauvais nom de filtre point_of_sale_id au lieu de shop_id) ont été corrigées dans les scénarios, pas dans les protections métier. Preuves privées access-proof.json et http-access-proof.json.

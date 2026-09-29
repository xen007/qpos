# QPOS — feuille de route unifiée

Document de reprise synthétique. Les lots historiques sont regroupés en sprints ; les sprints à venir reprennent les lots indiqués ci-dessous. État décrit au 29 septembre 2026.

## 1. Objectif du projet

- Faire de QPOS une application de point de vente professionnelle, sûre, rapide, accessible et maintenable.
- Préserver les parcours métier qui fonctionnent et vérifier les règles avant de changer les calculs.
- Servir une interface française par défaut, avec l’anglais au choix et un thème clair/sombre lisible.
- Faire évoluer les écrans progressivement : Blade pour les pages métier, React pour les interactions qui le justifient.
- Livrer des tranches revues et cohérentes, avec commits réalisés par le propriétaire du dépôt.

## 2. Décisions et principes

1. Français par défaut ; anglais sélectionnable, préférence conservée en session.
2. Blade reste le rendu principal ; React est réservé aux interactions riches, notamment les îles POS/achats existantes.
3. La migration visuelle se fait page par page ; les dépendances legacy ne sont retirées qu’après migration de leurs usages.
4. Tailwind 4, Vite 8 et React 19 constituent le socle front moderne déjà installé ; versions exactes dans les lockfiles.
5. Les plugins front sont chargés à la demande par page, pas globalement sur les vues migrées.
6. Autorisation côté serveur explicite par middleware, Policy ou Gate ; validation centralisée dans des Form Requests quand pertinent.
7. Totaux, stocks et paiements sont calculés et vérifiés côté serveur, dans des transactions cohérentes.
8. Pas de migration destructive ni de changement de devise ou de fuseau avant confirmation du besoin métier.
9. Cible envisagée : PHP 8.4 et Laravel 13, après vérification de compatibilité XAMPP, Docker et dépendances.
10. Chaque lot reste réversible et documenté ; préserver `.env`, les données locales et les fichiers importés.

## 3. Sprints terminés — S0 à S3

### S0 — Fondation du dépôt — terminé et poussé

- Dépôt Git initialisé et relié à GitHub.
- Branche de travail `main` créée et synchronisée avec `origin/main`.
- Fichiers d’environnement et données locales exclus du suivi selon `.gitignore`.
- Dépendances PHP et JavaScript conservées dans leurs lockfiles.
- Base de travail documentée pour permettre une reprise indépendante de la conversation.
- Le propriétaire configure son identité Git et garde la responsabilité des commits.
- `.env`, fichiers importés et données de la base locale restent préservés.
- L’environnement documenté inclut Laravel 10, PHP 8.2 et XAMPP.
- Docker est documenté comme environnement complémentaire, sous réserve des outils disponibles.
- Critère de fin : dépôt versionné, distant configuré, état publiable et données locales protégées.

### S1 — Sécurité des uploads — terminé et poussé

- Validation du type réel d’image, de ses dimensions et de son extension.
- Rejet des fichiers dont le contenu ne correspond pas à une image autorisée.
- Réencodage des images avant stockage.
- Noms de fichiers générés côté serveur et non dérivés du nom fourni.
- Limite de 2 Mio pour profil, logo et favicon.
- Limite de 10 Mio pour produits et marques.
- Lien de stockage public activé pour servir les fichiers autorisés.
- Route de diagnostic `/test` supprimée.
- Contrôles consignés : image valide acceptée, PHP déguisé et fichier trop lourd refusés.
- Critère de fin : upload borné, contrôlé par contenu et stocké sous un nom sûr.

### S2 — Internationalisation et thème — terminé et poussé

- Langue française par défaut et sélection FR/EN conservée en session.
- Traductions étendues aux vues, messages de validation, messages flash et courriels.
- Catalogues FR/EN mis en parité ; les règles et libellés de validation sont traduits.
- Préférence de thème clair/sombre et contrastes des composants historiques améliorés.
- Styles d’impression, tables, pagination, boutons, badges et alertes adaptés au thème.
- Protection Apache ajoutée pour bloquer l’accès HTTP aux fichiers et sources sensibles.
- Configuration d’URL locale corrigée ; appels `env()` retirés des vues actives.
- Méthodes et vues mortes identifiées supprimées ; formulaires actifs passés en Blade natif.
- Vérifications consignées : syntaxe PHP, compilation Blade, courriels FR/EN et sondes HTTP.
- Recette visuelle navigateur identifiée comme contrôle restant à effectuer sur l’environnement réel.
- Critère de fin : contenu traduit selon la langue choisie et interface lisible dans les deux thèmes.

### S3 — Layout Tailwind, architecture et durcissement — terminé et poussé

- Jetons visuels centralisés dans `public/css/qpos-tokens.css` pour les thèmes clair, sombre et impression.
- 42 pages du back-office migrées vers `backend.master-tailwind` et Tailwind pur.
- Kit de composants Blade partagé et navigation pilotée par `BackendMenu`.
- Plugins déclarés par page ; les pages migrées ne chargent pas globalement Bootstrap/AdminLTE.
- Les deux îles React (`cart/index` et `purchase/create`) restent sur le layout legacy pour conserver leur montage.
- Routes et permissions cartographiées dans `docs/routes-permissions.md`.
- 108 des 134 routes protégées par permission ; exceptions et autorisations dynamiques documentées.
- Contrôles d’autorisation déplacés vers middleware, Form Requests et Policies/Gates selon les cas.
- Permissions obsolètes supprimées ; parcours caisse groupé sous `sale_create`.
- Filtres de dates consolidés, invalides tolérés et fin de journée incluse dans les périodes.
- Message de fermeture du site désormais enregistré et affiché depuis le réglage.
- Vérification finale consignée : 73 contrôles puis 41 contrôles complémentaires, sans échec.
- Critère de fin : navigation protégée, fondations UI migrées et seules les exceptions documentées subsistent.

## 4. Sprint en cours

Aucun sprint en cours dans la feuille de route. Le travail local observé au moment de cette rédaction est non commité ; voir le journal de reprise avant de commencer une nouvelle tranche.

## 5. Sprints à venir

Les regroupements S4/S5 ci-dessous sont déduits des lots restants, conformément à la clarification du propriétaire. Les critères sont établis par la roadmap ; les décisions métier et la cible de déploiement restent à confirmer lorsqu’elles sont indiquées.

### S4 — Métier, sécurité et performance (lots 5, 6, 7)

**Objectif :** fiabiliser le cycle POS et les données, compléter les contrôles de sécurité, puis mesurer et corriger les goulots d’étranglement.

- Documenter panier, prix, remise, taxe, stock, paiement, monnaie rendue, dette, règlement partiel, facture et annulation.
- Confirmer les règles de calcul et d’arrondi sur des cas métier réels avant toute modification.
- Vérifier calcul serveur, ventes concurrentes, doublons réseau, propriété des paniers et transitions d’état.
- Contrôler les flux d’authentification, permissions, uploads/imports, échappement, secrets et réponses d’erreur.
- Vérifier les contraintes et index avant toute correction de données ; documenter sauvegarde et restauration.
- Borner les listes, mesurer les requêtes et corriger N+1 et chargements inutiles selon les volumes observés.
- Maintenir le découpage des bundles et n’ajouter des index que pour des requêtes identifiées.
- Critère : vente documentée de bout en bout, totaux et mouvements traçables, aucune écriture partielle incohérente.
- Critère : routes mutatives protégées, accès serveur explicite et données utilisateur échappées par défaut.
- Critère : listes bornées, requêtes principales identifiées, bundles adaptés et cache sans mélange d’utilisateurs/langues.
- À confirmer : règles métier finales, stratégie de rétention et cible de déploiement/cache/queue.

### S5 — Upgrade, UX/accessibilité et exploitation (lots 2, 8, 9)

**Objectif :** préparer une plateforme maintenue, achever les contrôles d’usage et rendre installation, mise à jour et reprise documentées.

- Inventorier versions verrouillées, extensions PHP, contraintes des packages et compatibilité Laravel 13.
- Préparer PHP 8.3 ou plus récent ; cible envisagée PHP 8.4/Laravel 13 après validation.
- Vérifier séparément PHP CLI et Apache XAMPP, puis les images et procédures Docker.
- Mettre à niveau le framework et les packages par étapes ; résoudre les incompatibilités individuellement.
- Simplifier formulaires, recherche, filtres, confirmations et retours de sauvegarde.
- Vérifier POS tactile/clavier, responsive, contrastes, libellés, focus et lecteurs d’écran.
- Contrôler visuellement thèmes et impressions ; corriger les incohérences UX héritées retenues.
- Documenter installation XAMPP/Docker, variables, extensions, permissions, planification et mail.
- Documenter sauvegarde/restauration et procédure de retour arrière pour les déploiements et schémas.
- Critère : installation reproductible, lockfiles cohérents et aucune dépendance bloquante sans décision documentée.
- Critère : parcours prioritaires accessibles et lisibles sur mobile/tablette, clavier et impression.
- À confirmer : versions finales compatibles, fuseau métier, hébergement visé et calendrier de migration.

## 6. Points hors scope

1. Refonte massive ou remplacement global de Blade par une SPA.
2. Migration Laravel/PHP avant inventaire de compatibilité et préparation du runtime.
3. Mise à jour majeure en bloc de toutes les dépendances.
4. Changement de règle comptable, devise ou fuseau sans validation métier.
5. Migration destructive de données sans sauvegarde et procédure de retour arrière.
6. Suppression d’un plugin ou d’un layout legacy avant migration et vérification de ses usages.
7. Ajout d’abstractions ou services sans besoin métier démontré.
8. Journalisation de secrets ou de données de paiement inutiles.
9. Retrait des deux îles React du layout historique avant décision et migration dédiées.
10. Commit par l’agent ; revue et commits restent sous la responsabilité du propriétaire.

## 7. Journal de reprise

- **Date :** 29/09/2026. `main` est alignée sur `origin/main` au commit `c43af5d` (`feat(ui): migration des pages vers master-tailwind`).
- **État local :** nombreuses modifications non commitées et nouveaux fichiers, dont du code et les deux documents de référence ; ils sont préservés et hors de cette consolidation.
- **Écart résolu :** l’ancienne roadmap disait S3 « non commité » et Lot 1 « en cours » ; la clarification du propriétaire prévaut : S0–S3 sont terminés et poussés.
- **Autres écarts à garder visibles :** ancienne roadmap et matrice plugins mentionnent encore une recette visuelle ; plusieurs décisions (fuseau, règles métier, cible d’hébergement) restent à confirmer.
- **Prochaine reprise :** examiner le diff local séparément, puis cadrer S4 ; ne pas présumer que les changements locaux non committés sont déjà livrés.

Documents détaillés archivés dans `docs/archives/modernization-roadmap.md`. Les cartographies restent dans `docs/routes-permissions.md` et `docs/plugins-front-par-page.md`.

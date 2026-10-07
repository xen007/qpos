# QPOS — Assouplissement contrôlé du stock

Date : 07/10/2026. Code non commité; le propriétaire conserve le commit final.

## Livré

### 1. Lots automatiques et ouvertures

- Ajout de `auto_generated`, `cost_unknown`, `estimated_expiry` et de `product_batch_amendments`.
- Commande `qpos:automatic-opening-lots` avec base attendue obligatoire, préflight, mode sans écriture par défaut, application transactionnelle et rejeu idempotent.
- Les 314 unités des 52 produits restent toutes dans MAIN ID 1. Conversion en 23 lots `LOT-AUTO-OUVERTURE`, coût `0.000000` avec coût marqué inconnu, expiration inconnue conservée. Les 23 mouvements d'ouverture d'origine sont conservés; 46 mouvements documentent les reclassements.
- Les lots auto sont inclus dans la disponibilité même quand l'expiration est inconnue. Les inconnus non automatiques restent exclus. Interface admin avec badge AUTO, filtres, drapeaux et corrections à motif journalisées.

### 2. Politique d'expiration

- Catégories existantes et nouvelles : `non_perishable` par défaut; politique `perishable` et délai de catégorie facultatif disponibles dans le CRUD.
- Les réceptions sans vraie date utilisent la durée catégorie ou `default_expiry_months`; la date générée est marquée `estimated_expiry`.
- Écran d'alertes à 90/30/7 jours, avec estimation affichée. Le stock expiré reste visible au POS; le checkout demande une confirmation et un motif. Les mouvements enregistrent le motif et l'utilisateur.

### 3. Mono/multi-boutique

- `system.multi_shop_enabled` pilote l'accès. Une boutique active masque le sélecteur et l'affectation pivot n'est pas nécessaire; plusieurs boutiques gardent la portée stricte.
- En mono, MAIN reste accessible, la liste/transferts multi-boutiques est masquée; l'édition MAIN reste proposée selon permission. Retour au multi réaffiche les boutiques et leurs données.
- Passage multi→mono refusé si un solde non nul ou un document métier existe dans une autre boutique. Les rôles, permissions, suspension et accès serveur restent requis.

### 4. SKU doublons dans les imports

- SKU unique SQL conservé. La prévisualisation signale les doublons; l'utilisateur choisit ligne par ligne ignorer, suffixer ou mettre à jour le catalogue existant.
- Le suffixe est enregistré dans `sku_auto_suffix`. La mise à jour ne modifie ni stock ni historique de vente/achat.

### 5. Paramètres et documentation

- Réglages admin : `default_expiry_months`, `auto_generate_lots`, `default_lot_prefix`, `allow_sale_without_lot`, `multi_shop_enabled`.
- Registre D42/D46/D48 remplacé et D49–D52 ajouté. Roadmap, schéma cible, contrats, stratégie de conversion et rapports 3.E mis à jour.

## Préflight, cutover et tests

- Sauvegarde locale `storage/app/backups/flexibility-cutover-20261007-2224` : restauration SQL en base probe et empreintes fichiers vérifiées (44 tables métier, 870 fichiers).
- Copie fraîche `qpos_phase2_probe_20261008_032509_2a514ab3` : migrations réussies; préflight 314 unités/23 produits d'ouverture; conversion puis rejeu idempotent réussis.
- Source `qpos` migrée sous maintenance, permission admin seedée, conversion appliquée puis rejouée; soldes et disponibilités rapprochés; application rouverte. Toutes les migrations sont `Ran`.
- `php artisan view:cache` réussi; `npm run build` réussi; `/login` renvoie HTTP 200.
- `php artisan test --filter=PointOfSaleAccessTest` : 4 tests, 11 assertions passés, y compris accès automatique à une boutique et affectation obligatoire en multi-boutique.
- La suite entière contient encore un test générique qui demande 200 sur `/`; en environnement live la racine renvoie 404. Durant le cutover, le test voyait 503 car le site était maintenu. Ce test ne correspond pas au parcours login réel (`/login` = 200).

## Limites restantes

- Le parcours réel d'import SKU pour chacune des trois décisions et la confirmation d'une vente expirée n'ont pas été exécutés dans un navigateur authentifié; la compilation, les routes et les validations de code sont contrôlées.
- L'écran des alertes est une consultation dynamique, pas une notification planifiée; scheduler et emails n'ont pas été ajoutés.
- L'application n'a pas encore été revue visuellement pour ces nouveaux écrans en FR/EN, mobile et mode sombre.
- Les changements sont non commités. Aucun token n'a été modifié.

# Réapprovisionnement — audit A
14/10/2026 ; main fe7e79d ; dépôt initial propre.

## Fonctions présentes et écarts
- NotificationSignals stockState et NotificationRelevance : seuil global notification_low_stock_threshold ; franchissement/réarmement existants, à unifier avec les politiques locales.
- ProductController : catégorie, marque, statut, pagination/tri numérique. StockAvailability projette les mêmes compartiments disponibles que StockService.
- CartController : recherche nom/SKU et scan ProductBarcode ; conditionnements, quantité disponible/expirée. Filtres catégorie/fournisseur à compléter sans casser le parcours expiré.
- PurchaseService : commandes, idempotence, coût/facteur figés, réception partielle transactionnelle ; création reçoit par défaut si quantité reçue omise. Adaptateur bon impose received_quantity=0.
- PurchaseService::receive refuse surplus ; avenant motivé requis, jamais un contournement.
- Product/Supplier : pas de relation explicite catalogue fournisseur. Nouvelle relation nécessaire, ne pas déduire de la marque.
- StoreProductRequest : catégorie facultative, prix requis, quantité interdite. Création rapide réutilise ces contrats.
- Stock initial : InitialStockService et import existants, aucune nouvelle entrée automatique depuis création produit.
- Réceptions/paiements historiques inconnus : non convertis. Affichage XAF deux décimales ; precision/arrondi de règlement préservés.
- Scheduler Kernel et outbox existants : à étendre ; aucun nouvel envoi réel pendant tests.
- Matériel, SMTP réel et revue visuelle récente restent les limites du rapport Phase 7, pas des succès à reprendre.

## Copie
Nouvelle copie qpos_test_replenishment_20261014 restaurée depuis source qpos, 93 tables avec SHA des lignes et DDL identiques. Preuves baseline.json et sauvegarde SQL sous storage/app/replenishment, privées. Aucun compte/produit remplacé. Les migrations Phase 7 sont dans le manifest source, la migration du module sera faite sur copie d'abord.

Contrats consolidés : replenishment-contracts.md. Périmètre A–H approuvé. Les défauts nécessaires à ce module seront corrigés et revérifiés ; aucun reset source.

# QPOS — Schéma cible

Date : 01/10/2026. Sous-lot 2 de Phase 1. Statut : proposition documentaire à valider avant implémentation.

Référence : [roadmap.md](roadmap.md), sections 3.5, 4 et 5. Documents associés : [conversion-strategie.md](conversion-strategie.md) et [contrats.md](contrats.md).

Ce document décrit une cible, pas des migrations exécutées. Aucun nouvel identifiant Dxx n'est créé. Les décisions D02–D10, D13, D38 et D39 sont validées ; les choix structurels ci-dessous restent soumis à validation. Les paramètres non fixés sont marqués **à proposer**.

## 1. Cartographie de l'existant

Lecture des migrations du dépôt, des modèles et des consommateurs ciblés au commit `5546975`. Aucun relevé du schéma réel ni contrôle des données de la base courante n'a été exécuté dans ce sous-lot. Les migrations décrivent le schéma attendu ; un rapprochement avec les métadonnées de la base sera nécessaire avant conversion.

| Table existante | Structure et usage observés | Adaptation prévue |
|---|---|---|
| `units` | `id`, `title`, `short_name`, timestamps | Préserver les IDs ; ne pas recréer la table |
| `products` | Unité nullable ; SKU/slug uniques ; prix/coût/remise en `double` ; quantité entière globale ; une date d'expiration nullable | Catalogue partagé, unité de référence conservée, stock déplacé vers le journal et les soldes par boutique |
| `users` | Identité, authentification, suspension ; `HasRoles` Spatie | Conserver les rôles globaux ; ajouter les affectations boutique |
| `pos_carts` | Produit/utilisateur, quantité entière ; aucun contexte boutique/panier distinct | Contexte stable par panier et onglet, conditionnement, quantités décimales |
| `orders` | Utilisateur/client ; total, remise, payé, dû, monnaie en `double` ; `status` lié au solde ; indicateur `is_returned` | Boutique, session, état métier distinct du paiement, instantanés et idempotence |
| `order_products` | Ligne liée à vente/produit ; quantité entière ; prix, coût de référence, remise et totaux conservés | Conserver cette table et ses IDs ; aucun `order_items` parallèle |
| `order_transactions` | Montant, vente, client, utilisateur nullable, `paid_by`, référence nullable | Conversion contrôlée vers paiements et affectations ; conservation de la source |
| `purchases` | Fournisseur/utilisateur, date, statut, total, taxe, remise, transport | Boutique, état réception/paiement et historique de corrections |
| `purchase_items` | Achat/produit ; quantité entière, prix d'achat et de vente | Conditionnement et instantanés ; distinguer commande et réception |
| `customers`, `suppliers` | Identité et contact ; aucun solde historique complet indépendant | Référentiels partagés ; dettes dérivées des opérations, tiers internes protégés |
| Tables Spatie | Rôles, permissions et affectations existants | Réutiliser ; ne pas dupliquer le système de rôles |

Sources principales : migrations `create_units_table`, `create_products_table`, `create_pos_carts_table`, `create_orders_table`, `create_order_products_table`, `create_order_transactions_table`, `create_purchases_table`, `create_purchase_items_table`, `create_users_table` ; migrations complémentaires des remises et de `change_amount`.

Consommateurs lus : `Product`, `Unit`, `Order`, `OrderProduct`, `OrderTransaction`, `Purchase`, `PurchaseItem`, `PosCart`, `User`, `OrderController::store/collection`, `PurchaseController::store`, `ProductController::import`, `ProductsImport`. Le checkout verrouille déjà les produits et décrémente `products.quantity`. L'achat ajuste ce même solde puis remplace les lignes lors d'une modification. L'import crée produit, achat et ligne d'achat. Les montants historiques ne doivent donc pas être recalculés depuis les prix courants.

## 2. Conventions proposées

- PK `id` en entier non signé compatible avec les IDs Laravel existants ; FK de même type. Les noms proposés sont stables entre les trois documents.
- Montants/coûts en `DECIMAL`, jamais en flottant dans la cible. **À proposer** : `DECIMAL(20,6)` pour prix/coûts de calcul, `DECIMAL(20,6)` pour quantités/facteurs et précision de règlement selon devise. Les tailles ne fixent pas l'arrondi métier. Conversion des `double` après analyse des valeurs et des écarts.
- Instants nouveaux conservés en UTC ; dates métier et journées calculées en `Africa/Douala`. Les timestamps anciens sont conservés bruts tant que leur interprétation n'est pas prouvée.
- Tables métier : `created_at`, `updated_at` lorsqu'elles sont modifiables ; journaux immuables : `occurred_at`, `recorded_at`. `point_of_sale_id` porte une boutique explicite pour chaque opération nouvelle.
- Historique incomplet : `provenance` (`native`, `legacy`, `opening`) et `conversion_run_id` lorsque pertinent ; valeurs inconnues nulles, jamais remplacées implicitement par zéro.
- Restrictions de suppression sur les références historiques ; désactivation des référentiels plutôt que cascade destructive. Adapter les cascades existantes dans les phases propriétaires.
- États et transitions validés côté serveur ; contraintes SQL locales et contrôles transactionnels pour invariants entre tables. Pas de confiance accordée au seul filtre d'interface.

## 3. Tables à créer

Les listes de colonnes sont la proposition minimale de chaque domaine. Toutes les tables ont une PK `id` sauf mention contraire.

### 3.1 Boutique, catalogue et prix — phases 1–2

| Table | Colonnes principales | Relations et contraintes |
|---|---|---|
| `points_of_sale` | `code`, `name`, `address` nullable, `is_active`, timestamps | Code unique ; boutique = établissement, pas terminal de caisse |
| `point_of_sale_user` | `user_id`, `point_of_sale_id`, `is_active`, timestamps | Couple unique ; FK users/boutiques ; rôle global conservé |
| `product_units` | `product_id`, `unit_id`, `code`, `label`, `factor`, `is_reference`, `is_active` | Facteur strictement positif ; code unique par produit ; une seule référence de facteur 1 par produit, garantie lors des écritures |
| `product_barcodes` | `product_unit_id`, `barcode`, `is_active` | Code-barres texte, zéros initiaux conservés ; unicité du code dans le catalogue pour une résolution sans ambiguïté |
| `price_rules` | `product_unit_id`, `point_of_sale_id` nullable, `customer_id` nullable, `minimum_quantity` nullable, `price_ttc`, dates de validité, `is_active` | Portée et conditions explicites ; priorité exacte et chevauchements **à proposer** ; D22 reste ouvert |
| `promotions`, `promotion_items` | Code, type, paramètres, validité ; promotion/conditionnements concernés | Une promotion applicable par ligne ; départage exact **à proposer** ; pas de cumul automatique |

Phase 1 implémente seulement les structures minimales boutiques/affectations dans un lot ultérieur autorisé. Les tarifs/promotions complets appartiennent à la Phase 2 et leur consommation au checkout à la Phase 4.

### 3.2 Stock et approvisionnement — phase 3

| Table | Colonnes principales | Relations et contraintes |
|---|---|---|
| `product_stock` | Boutique, produit, `saleable_quantity`, `unsaleable_quantity`, `in_transit_quantity`, `unallocated_opening_quantity` | Couple boutique/produit unique ; soldes dérivés du journal, réconciliables ; ouverture inconnue identifiée séparément |
| `purchase_receipts`, `purchase_receipt_items` | Achat, boutique, auteur, date effective ; ligne d'achat, unité/facteur instantanés, quantité de base reçue | Réceptions partielles ; quantité reçue positive ; référence achat cohérente avec la boutique |
| `product_batches` | Produit, `batch_number` nullable, `expires_on` nullable, `received_at`, `unit_cost` nullable, `purchase_receipt_item_id` nullable, `provenance` | Une réception homogène de coût/expiration ; numéro fabricant facultatif ; aucune création historique sans preuve |
| `batch_stock` | Boutique, lot, `saleable_quantity`, `unsaleable_quantity` | Couple boutique/lot unique ; quantités non négatives ; produit déterminé par le lot |
| `stock_movements` | Boutique, produit, lot nullable, `bucket`, `quantity_delta`, `type`, `occurred_at`, auteur nullable, `unit_cost` nullable, `correlation_key`, références opérationnelles, mouvement compensé nullable, conversion nullable | Delta signé non nul ; mouvement d'ouverture sans lot explicitement admis ; lot obligatoire pour nouveaux flux exigeant traçabilité ; journal immuable |
| `order_stock_allocations` | `order_product_id`, mouvement de sortie, lot nullable, quantité de base, coût unitaire/total nullable, provenance | Quantité positive ; somme égale à la quantité de base vendue pour les nouvelles ventes ; coût inconnu signalé |
| `stock_transfers`, `stock_transfer_items`, `stock_transfer_receipts`, `stock_transfer_receipt_items` | Boutiques source/destination, états, auteurs, dates ; produit/lot/quantités ; réception et reliquats | Source différente de destination ; liens expédition/réception ; transit réconcilié, annulations compensées |
| `stock_counts`, `stock_count_items` | Boutique, état, début/fin, auteur ; produit, quantité comptée, instant de comptage, repère de mouvement | Une ligne par produit/comptage ; ajustement basé sur les mouvements entre comptage et validation |

`stock_movements` utilise des FK explicites nullable vers `purchase_receipt_items`, `order_products`, `return_items`, lignes de transfert et de comptage. Une seule origine opérationnelle pour un mouvement normal ; une ouverture référence sa conversion. Les liens circulaires seront ajoutés après création des tables dépendantes.

FEFO désigne l'ordre de sortie des périssables ; FIFO celui des autres réceptions. Le coût de la vente provient des allocations effectivement sorties, pas d'une réécriture du coût de référence produit. Départage des dates identiques, expiration inconnue et frais d'acquisition : **à proposer**. Le reliquat d'ouverture sans provenance n'est pas déguisé en lot : il doit être documenté et validé avant usage ; si la traçabilité est indispensable, le produit reste bloqué.

### 3.3 Ventes, règlements et retours — phase 4

| Table | Colonnes principales | Relations et contraintes |
|---|---|---|
| `payments` | Boutique, session nullable pour legacy, auteur nullable, client ou fournisseur, `direction`, `method`, `received_amount`, `change_amount`, `net_amount`, `currency_code`, `external_reference` nullable, date, état, clé d'idempotence, provenance/source legacy | Net = reçu - monnaie pour encaissement ; méthode carte = terminal externe ; absence de données sensibles ; client/fournisseur cohérent avec affectation |
| `payment_allocations` | Paiement, vente ou achat, `amount`, provenance | Une destination par ligne ; montants positifs ; sommes affectées bornées par le net et le solde restant sous verrou |
| `returns`, `return_items` | Boutique, vente, auteur, état, date, motif ; ligne originale, quantité retournée, base/remise/taxe/total historiques, destination du stock | Retour borné par le solde retournable ; FK vers `order_products`, aucune ligne de vente inventée |
| `return_stock_allocations` | Ligne retour, allocation de vente, quantité, mouvement de retour | Quantité bornée par l'allocation d'origine ; distingue vendable et non vendable |
| `refund_allocations` | Paiement sortant, retour, montant | Remboursement réel distinct de réduction de dette ; borne par montant remboursable |
| `credit_notes`, `credit_note_movements` | Client, boutique émettrice, retour source, montant initial ; utilisation/restitution, vente, auteur, date | Solde dérivé du journal ; aucun usage au-delà du disponible ; portée interboutiques **à proposer** |
| `carts` | Boutique, utilisateur, `token`, état, expiration nullable | Jeton unique ; panier distinct par contexte d'onglet, aucune réservation de stock |
| `operation_requests` | Boutique, auteur, type, `idempotency_key`, empreinte requête, état, `order_id` nullable, dates | Unicité boutique/auteur/type/clé ; rejouer une clé identique restitue le résultat ; payload différent refusé |

Les dettes sont des soldes de documents et d'affectations, jamais des encaissements fictifs. Les remboursements utilisent un paiement sortant rattaché à la session où ils surviennent. Aucun solde à la date d'un ancien reçu n'est reconstitué sans preuve (D34 reste ouvert).

### 3.4 Caisse et dépenses — phase 4

| Table | Colonnes principales | Relations et contraintes |
|---|---|---|
| `cash_registers` | Boutique, code, libellé, actif | Code unique par boutique ; support physique distinct de la session individuelle |
| `cash_sessions` | Boutique, caisse, caissier, ouverture/clôture, fonds initial, espèces attendues/comptées, écart, état | Une session ouverte par caisse et par caissier proposée ; exclusivité garantie transactionnellement sous verrou, pas par un UNIQUE incluant un état nullable |
| `cash_movements` | Session, auteur, type, montant signé, date, paiement/dépense nullable, motif, mouvement compensé nullable | Espèces seulement ; source unique pour empêcher double comptage ; journal immuable |
| `expense_categories` | Code, libellé, actif | Code unique ; désactivation si utilisée |
| `expenses` | Boutique, catégorie, auteur, date, montant, devise, méthode, justificatif privé nullable, état | Session obligatoire si espèces ; dépense non espèces exclue du comptage physique |

Clôture : attendu = fonds initial + somme des mouvements physiques ; écart = compté - attendu. Une passation clôt la responsabilité précédente avant ouverture de la suivante. Les règles détaillées d'ouverture et de correction restent proposées à valider.

### 3.5 Exploitation et conversion — phases 1 puis 5–7

| Table | Colonnes principales | Relations et contraintes |
|---|---|---|
| `audit_logs` | Acteur nullable, boutique nullable, événement, objet/ID, champs avant/après filtrés, résultat, motif, correlation, date | Métadonnées de cible sans cascade ; pas de secrets ; événements append-only |
| `notification_outbox` | Type événement, objet, boutique, destinataire autorisé, canal, clé de dédoublonnage, payload filtré, état, tentatives, prochaine tentative, expiration | Unicité de dédoublonnage ; écriture transactionnelle puis livraison après commit |
| `notification_deliveries` | Outbox, tentative, date, résultat, référence fournisseur nullable, erreur filtrée | Historique des tentatives sans copier de secret |
| `daily_summaries` | Boutique, date métier, version, cutoff, totaux, état, référence outbox | Boutique/date/version unique ; version explicite pour correction d'une synthèse |
| `backup_runs` | Début/fin, état, identifiant archive, manifeste/empreinte, dernier succès, erreur filtrée | Aucun secret ni contenu de sauvegarde en base ; manifeste extérieur indispensable après perte de la base |
| `restore_drills` | Archive, dates, durée, résultat, rapprochements, emplacement du rapport | Distinguer restauration technique et validation métier |
| `conversion_runs`, `conversion_issues`, `conversion_mappings` | Version, commit source, empreinte sauvegarde, état ; source/ID, type d'anomalie, résolution/preuve ; source vers cible | Unicité run/source/ID/type de cible ; reprise idempotente, provenance complète |

## 4. Tables à adapter

| Table | Ajouts/adaptations proposés | Préservation et phase |
|---|---|---|
| `units` | `is_active` ; normalisation des libellés après revue | IDs/libellés conservés ; pas d'unicité ajoutée avant traitement des doublons ; phase 2 |
| `products` | Unité de référence via `unit_id` conservée ; `allows_fractional`, `tracks_expiry`, `tracks_batches` ; prix/coût de référence décimaux | Valeurs des indicateurs historiques **à proposer**, jamais inférées uniquement d'une date absente ; quantity/expire_date conservés comme legacy jusqu'à bascule ; phase 2–3 |
| `users` | Boutique préférée nullable, éventuellement préférences métier | La boutique préférée ne confère aucun droit ; affectations séparées ; phase 1–2 |
| `pos_carts` | `cart_id`, `product_unit_id`, quantité décimale | UNIQUE(cart_id, product_unit_id) après résolution ; anciens paniers traités à la bascule ; phase 4 |
| `orders` | Boutique/session, état vente, état paiement, devise instantanée, total HT/taxe/TTC, clé opération, provenance | IDs, total/paid/due/change_amount et timestamps legacy conservés ; pas d'interprétation de status sans mapping ; phase 4 |
| `order_products` | Conditionnement, unité/libellé/facteur instantanés, quantité de base, prix/coût/CMV décimaux, taxes, règles tarifaires appliquées, complétude historique | Colonnes inconnues legacy nulles ; coût existant conservé comme référence historique, pas renommé CMV réel ; phase 2 puis 4 |
| `purchases`, `purchase_items` | Boutique, devise, états, échéance, instantanés unité/facteur/taxes ; décimaux | Ne pas attribuer de paiement ou de réception historique sur simple présence d'une ligne ; phase 3 |
| `order_transactions` | Provenance et mapping vers paiement, selon conversion validée | Table conservée en lecture pendant transition, jamais comptée en plus de payments ; phase 4 |
| `customers`, `suppliers` | Actif, indicateur tiers interne protégé ; éventuelles échéances par document | Pas de duplication par boutique automatique ; accès déterminé par périmètre d'opérations ; phases 3–4 |

## 5. Relations et invariants transversaux

- Produit → plusieurs conditionnements, lots et soldes boutique ; unité de référence unique par produit.
- Boutique ↔ utilisateurs par affectation ; rôle global ET affectation active requis. L'accès global administrateur doit être explicite, sans contournement implicite.
- Vente → lignes `order_products` → allocations → sorties ; retour → ligne et allocations originales.
- Paiement → plusieurs affectations ; document → plusieurs paiements ; session réelle de l'encaissement indépendante de celle de la vente originale.
- Cohérence boutique/session/produit/lot vérifiée côté serveur sous transaction ; FK composites possibles pour les liens tenantés lorsqu'une clé candidate le permet. Aucune FK polymorphe supposée contrôler seule la cohérence métier.
- Journal, allocations, soldes et audit métier se valident ensemble. Verrouillage des ressources dans un ordre stable ; retry borné des conflits ; idempotence des commandes externes.
- Pour chaque produit/boutique : solde = somme des mouvements par bucket depuis l'ouverture ; stock vendable = stock lots vendables + ouverture vendable sans lot explicitement approuvée. Le transit ne devient disponible qu'à réception.

## 6. Index proposés et justification

| Table | Index | Accès justifié |
|---|---|---|
| `point_of_sale_user` | UNIQUE(user_id, point_of_sale_id), (point_of_sale_id, is_active, user_id) | Autorisation utilisateur et liste du personnel |
| `product_units`, `product_barcodes` | (product_id, is_active), UNIQUE(barcode) | Conditionnements et scanner |
| `product_stock`, `batch_stock` | Couples uniques décrits plus haut | Lecture/verrouillage du solde sans doublon |
| `product_batches` | (product_id, expires_on, received_at, id) ; (product_id, received_at, id) | FEFO/FIFO ; jointure avec batch_stock de la boutique |
| `stock_movements` | (point_of_sale_id, product_id, bucket, occurred_at, id), index FK opérationnelle | Réconciliation, historique et anti-duplication par source |
| `orders`, `purchases` | (point_of_sale_id, created_at, id), respectivement (customer_id, created_at, id) / (supplier_id, date, id) | Listes bornées, rapports boutique et historique du tiers |
| `order_stock_allocations`, `return_items` | FK ligne originale ; UNIQUE(source sortie) lorsque une allocation par mouvement | Traçabilité et calcul du retournable |
| `payments` | (point_of_sale_id, occurred_at, id), (cash_session_id, occurred_at), UNIQUE(source legacy) | Trésorerie, caisse, conversion sans double encaissement |
| `payment_allocations` | (order_id, payment_id), (purchase_id, payment_id) | Solde du document |
| `cash_sessions`, `cash_movements` | (cash_register_id, state), (user_id, state), (cash_session_id, occurred_at, id) | Session ouverte et clôture |
| `audit_logs` | (point_of_sale_id, recorded_at, id), (object_type, object_id, recorded_at) | Audit par période ou objet |
| `notification_outbox` | (state, next_attempt_at, id), UNIQUE(deduplication_key) | Worker et dédoublonnage |
| `conversion_issues` | (conversion_run_id, state, source_table, source_id) | Suivi des anomalies bloquantes |

Vérifier les index déjà créés par les FK avant d'en ajouter. Mesurer les plans sur copie représentative au lot d'implémentation ; aucun index par colonne ajouté sans requête identifiée. Pagination obligatoire pour journaux/listes.

## 7. Points à proposer avant activation

Précisions numériques et arrondis ; taux/exemptions ; priorité exacte des tarifs et départage des promotions ; traitement des périssables d'expiration inconnue ; valorisation des stocks d'ouverture sans coût ; coût d'acquisition/transport ; portée des avoirs ; désignation de la boutique de reprise et affectations ; états détaillés ; durées de rétention et canaux/destinataires. Les décisions D21–D23 et D34 restent ouvertes à leur phase. Ces propositions ne sont pas approuvées par la seule validation de la cartographie.

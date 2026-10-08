# Phase 4 — Vente complète et caisse

Date : 08/10/2026. Branche `main`, base Git `5c41b3556714c22c218bf0c67fbab9ea87e5972a`. Exécution autorisée dans l'ordre **4.A → 4.C → 4.B → 4.D → 4.E → 4.F**. Un seul commit final, à réaliser par le propriétaire. Aucun commit, staging ou push effectué.

## 1. État des six sous-lots

| Sous-lot | État | Livré |
|---|---|---|
| 4.A — Clients, dettes, avoirs | Implémenté et vérifié sur copie | CRUD, désactivation des clients référencés, identité Walking protégée, solde/échéance, journal de modification d'échéance et de relance manuelle, avoirs par boutique sans expiration par défaut et utilisations partielles, filtre AJAX client conservé |
| 4.C — Caisse et paiements | Implémenté et vérifié sur copie | Session individuelle unique par caissier, ouverture, mouvements, clôture/comptage/écart motivé, passation, journal payments/payment_allocations commun, vente et encaissement ultérieur rattachés à la session active |
| 4.B — POS et checkout | Implémenté et vérifié sur copie | React tactile/clavier, panier isolé par utilisateur/boutique/onglet, conditionnements et fractions, scanner par conditionnement, tarifs/promotions serveur, paiements mixtes, monnaie espèces, XAF final, dettes/avoirs, verrous, idempotence/retry, ticket 80 mm |
| 4.D — Corrections | Implémenté et vérifié sur copie ; D35 historique non reproduit | Retours partiels, remboursement, échange atomique avec différence, avoir, annulation, lots et inspection du produit, document correctif distinct, reçus figés, agrégat articles corrigé, impression courte sur une page |
| 4.E — Dépenses | Implémenté et vérifié sur copie | Catégories et dépenses espèces/carte, autorisations, session active, sortie physique uniquement pour espèces, référence carte, contrôle du disponible et idempotence |
| 4.F — Étiquettes | Implémenté et PDF vérifiés | Nom/conditionnement/prix/code-barres Code128 ; Avery L7160 A4, Avery 5160 Letter, thermique 58 × 40 et 80 × 50 mm ; copie multiple et limite de densité du code |

## 2. Contrats appliqués

- D53 : calculs décimaux exacts à six places, puis HALF_UP à zéro décimale sur le **total final** XAF. Les prix des étiquettes restent précis, puisqu'ils représentent le prix unitaire.
- D54 : Walking est identifié par `internal_code=walking`, protégé contre modification/suppression et interdit de dette/avoir. Un paiement intégral carte est accepté.
- D55 : carte externe avec référence, aucune monnaie carte ; le trop-perçu est déduit uniquement des espèces.
- D56 : avoir lié à client/boutique/devise, solde verrouillé, utilisation partielle historisée, aucune expiration automatique.
- D57/D02/D39 : pas de réservation panier. Checkout sous verrous utilisateur, produits et ressources financières/stock dans un ordre déterministe. Les transactions de vente utilisent READ COMMITTED, rétablissent ensuite l'isolation précédente et retentent les conflits transactionnels.
- Vente, lignes et instantanés, paiements/affectations, sortie stock, dette et consommation d'avoir appartiennent à une transaction unique. Une erreur annule tout. Une clé rejouée avec un contenu différent est refusée.
- Échange : correctif et nouvelle vente dans la même transaction. La compensation est explicitement journalisée ; seuls le supplément ou le reliquat constituent un nouveau paiement/remboursement/avoir. Walking ne reçoit jamais d'avoir.
- Retours : quantité maximale égale au reliquat réellement vendu ; restitution aux lots d'origine tracée. Inspection requise ; un produit périmé ne retourne pas dans le stock vendable. Le correctif conserve les valeurs historiques, sans régénérer la facture originale.
- Reçu : instantané après **ce paiement**, auteur/session/devise/vente et solde figés. Les règlements ultérieurs restent des opérations distinctes avec la session du jour.
- Autorisation métier et portée boutique contrôlées sur le serveur. Les montants envoyés par le navigateur ne remplacent pas le recalcul des tarifs/promotions.

## 3. Vérifications réalisées

Les écritures d'essai ont été confinées à `qpos_phase4_probe_20261008_001956_770710`, restaurée depuis la source. Les scripts et preuves se trouvent dans `storage/app/phase4/`, ignoré par Git et inaccessible par HTTP. Aucun test d'écriture métier effectué sur la source.

| Contrôle | Résultat |
|---|---|
| Contrôles métier principaux | **38 réussis** ; paiements mixtes/monnaie, caisse, rejeu/conflit de clé, Walking, arrondi total, dettes/reçus, avoirs/portée/partiel, retours/annulation/échange/rollback, tarifs/promotions, fractions, isolation paniers et filtre client |
| Contrôles complémentaires | **22 réussis** ; échange Walking et supplément, échec de remplacement sans correction partielle, scanner exact avec zéros initiaux, dépenses espèces/carte, clôture/passation/session unique, périmés, échéances/relances idempotentes, dates natives UTC et compatibilité paiements fournisseurs historiques |
| Deux checkouts réels simultanés | Deux processus PHP, barrière commune, même dernier article, deux caissiers : **1 succès, 1 refus de stock**, aucune survente ; preuve finale vente copie #74 |
| Deux rejeux réels simultanés | Même utilisateur et clé : **2 réponses pour la même vente #76**, aucune vente supplémentaire |
| Navigateur authentifié sur copie | Vente #32 par code de carton de 6 ; 700,5 → **701 XAF**, espèces 500 + carte 300, monnaie **99**, variation espèces **401** ; contrôle mobile 390 × 844 sans débordement horizontal, FR/EN et sombre |
| PDF | Quatre formats étiquettes HTTP 200, quatre copies par format ; inspection des sept rendus (étiquettes, facture A4, ticket 80 mm, correctif). Facture courte **1 page**. Étiquettes Avery 1 page ; rouleau 1 page par étiquette |
| Construction | Pint ciblé, syntaxe PHP, build Vite production, compilation Blade et cache des routes réussis |

Les preuves privées comprennent `checks-results.json`, `extended-results.json`, `race-results.json`, `pdf-results.json`, les PDF/PNG et captures du POS. Les essais ont été expressément autorisés par la mission, y compris les deux checkouts concurrents. Aucun nouveau jeu PHPUnit transversal n'a été introduit.

## 4. Diagnostic des défauts

- **D31 :** gabarits natifs A4 et 80 mm indépendants, contrôles d'impression masqués ; PDF de facture courte confirmé sur une page. Une facture longue peut légitimement avoir plusieurs pages. Le gabarit historique a reçu le réglage A4 et le déclenchement d'impression corrigé.
- **D32 :** le filtre client accompagne la requête AJAX et la requête SQL ; contrôle d'isolation réussi.
- **D33 :** `select()` supprimait l'agrégat construit par `withSum()`. Ordre corrigé et quantités fractionnaires conservées. Les deux ventes originales ont des lignes ; aucune vente vide historique à supprimer.
- **D34 :** reçus natifs figés au paiement, une seule vente par affectation ; réimpression stable même après un nouveau règlement. Les anciens reçus sans instantané indiquent explicitement le solde courant et l'absence de preuve historique.
- **D35 :** le cas initial « deux ventes sur une facture d'encaissement » n'est pas reproduit avec les deux ventes originales. Les nouveaux reçus ciblent explicitement une affectation/vente. Les IDs et l'URL du cas initial restent nécessaires pour conclure sur cet incident historique.

## 5. Sauvegarde, migrations et source

Avant les migrations : dump SQL, archive du code au HEAD, archive privée des fichiers locaux nécessaires, hash `.env`, restauration de la copie et contrôle des données. Les archives privées contiennent des informations sensibles : elles restent locales, ignorées et interdites d'accès HTTP ; ne pas les commiter.

Trois migrations Phase 4 appliquées sur la source après les essais de copie, plus `SaleWorkflowPermissionSeeder`. Deux difficultés MariaDB détectées en répétition (index nécessaire à une FK et champs de conditionnement déjà présents en Phase 2) ont été corrigées **avant** l'application sur la source.

La preuve de migration compare les anciennes colonnes de **54 tables de données**, avec normalisation des valeurs numériques converties en DECIMAL. Historique conservé : **2 ventes, 52 produits, 69 mouvements stock, 314 unités vendables**. Aucune conversion des anciennes devises, aucun rejeu stock. Les tables de caisse/vente native/dépense/correctif sont vides sur la source à la remise en service. `.env` inchangé. L'application a été remise en service par `php artisan up`.

Les migrations refusent un rollback destructif : une restauration doit être préparée depuis les sauvegardes en tenant compte de toute nouvelle opération effectuée depuis la mise en service. Les sauvegardes et la copie de validation restent locales pour la revue du propriétaire.

## 6. Limites et préparation opérationnelle

1. Historiques BDT et fuseau global hérité : inchangés, reprises Phase 5/6. Les nouvelles opérations vente/caisse sont UTC en stockage et Africa/Douala à l'affichage. Les rapports/tableaux de bord historiques ne sont pas encore une consolidation correcte des flux natifs XAF. Les dettes de ventes historiques exigent une réconciliation explicite avant encaissement natif ; aucune conversion implicite.
2. Prix TTC appliqués. Taux/exemptions de taxes non décidés : instantané fiscal marqué non configuré, aucune taxe fictive ni exemption inventée. Validation fiscale détaillée encore nécessaire.
3. Carte = terminal externe ; QPOS enregistre la référence, sans débit/remboursement bancaire automatique. L'atomicité couvre la base QPOS ; rapprochement du terminal manuel.
4. Relances = actions manuelles consignées dans le journal. Aucun SMS/email envoyé ; canaux et programmation automatiques restent à définir.
5. Scanner simulé par saisie HID/Entrée, code exact testé, aucun lecteur USB physique disponible. PDF vérifiés ; calibrage réel imprimante 80 mm/Avery à faire à taille réelle sans mise à l'échelle. Seules les références Avery annoncées sont fournies.
6. Les anciens paniers sans identité d'onglet restent historiques et ne sont pas repris automatiquement dans les paniers natifs. Un résultat de checkout incertain est conservé dans le navigateur pour retenter **la même** opération ; après indisponibilité durable du stockage local, retrouver le résultat côté vente avant une nouvelle opération.
7. Distribution des nouvelles capacités : Admin reçoit les neuf permissions ; rôles ayant `sale_create` reçoivent la gestion de leur caisse, ceux ayant `sale_update` reçoivent l'encaissement. Remises, crédit, retours, mouvements manuels et dépenses nécessitent leurs permissions spécifiques. Les affectations boutique ne sont pas inventées.

## 7. Prêt pour le commit du propriétaire

Code, vues, traductions, trois migrations, seeder, contrats, schéma et assets compilés forment **un seul lot Phase 4**. Aucun changement des tokens Soft Modern, du moteur PricingService ou du moteur StockService ; pas de dépendance ajoutée. Les changements des modèles/journaux/menus partagés raccordent les nouveaux flux et préservent les flux fournisseurs historiques vérifiés.

Les anciens assets hachés devenus inutilisés sont remplacés par le manifeste et les nouveaux assets Vite. Le bundle achat change aussi par partage du bundle UI, sans modification de sa source. La liste exhaustive du diff est consignée ci-dessous. Ne pas inclure `storage/app/phase4/`, sauvegardes, fixtures, identifiants de test ou `.env` dans le commit.

## 8. Inventaire des fichiers

Inventaire généré à la clôture depuis les fichiers modifiés et non suivis non ignorés ; `M` modification, `D` ancien asset supprimé, `A` ajout.

- `A app/Casts/SaleDateTime.php`
- `M app/Http/Controllers/Backend/Pos/CartController.php`
- `M app/Http/Controllers/Backend/Pos/OrderController.php`
- `A app/Http/Controllers/Backend/PriceLabelController.php`
- `A app/Http/Controllers/Backend/SaleWorkflowController.php`
- `M app/Http/Controllers/CustomerController.php`
- `M app/Http/Requests/StoreCustomerRequest.php`
- `M app/Http/Requests/UpdateCustomerRequest.php`
- `A app/Models/CashMovement.php`
- `A app/Models/CashSession.php`
- `M app/Models/Customer.php`
- `M app/Models/Order.php`
- `M app/Models/OrderProduct.php`
- `M app/Models/Payment.php`
- `M app/Models/PaymentAllocation.php`
- `M app/Models/PosCart.php`
- `M app/Policies/CustomerPolicy.php`
- `A app/Services/CashService.php`
- `A app/Services/CustomerDebtService.php`
- `A app/Services/SaleCorrectionService.php`
- `A app/Services/SaleService.php`
- `M app/Support/BackendMenu.php`
- `A app/Support/Code128.php`
- `A app/Support/SaleFormat.php`
- `A app/Support/SaleOperation.php`
- `A app/Support/SaleTransaction.php`
- `A database/migrations/2026_10_08_120000_create_sale_and_cash_workflows.php`
- `A database/migrations/2026_10_08_130000_add_exchange_settlements.php`
- `A database/migrations/2026_10_08_140000_create_customer_debt_events.php`
- `A database/seeders/SaleWorkflowPermissionSeeder.php`
- `M docs/contrats.md`
- `A docs/phase4-report.md`
- `M docs/roadmap.md`
- `M docs/routes-permissions.md`
- `M docs/schema-cible.md`
- `M lang/en.json`
- `M lang/fr.json`
- `A public/build/assets/Pos-B-wvRm4L.js`
- `D public/build/assets/Pos-Cd4WuNaa.js`
- `A public/build/assets/Purchase-C0otmoPy.js`
- `D public/build/assets/Purchase-Cz6gB-NQ.js`
- `A public/build/assets/WorkspaceUI-B9oWEvd7.js`
- `D public/build/assets/app-CK69Hncx.js`
- `A public/build/assets/app-v9SW8rXS.js`
- `D public/build/assets/beep-02-FDF-YbRr.mp3`
- `D public/build/assets/beep-07a-BpTYY3WP.mp3`
- `D public/build/assets/useStateManager-7e1e8489.esm-BYU6m3zv.js`
- `M public/build/manifest.json`
- `M resources/js/components/Cart.jsx`
- `M resources/js/components/Pos.jsx`
- `M resources/views/backend/customers/create.blade.php`
- `M resources/views/backend/customers/edit.blade.php`
- `M resources/views/backend/customers/index.blade.php`
- `M resources/views/backend/orders/collection/invoice.blade.php`
- `M resources/views/backend/orders/index.blade.php`
- `M resources/views/backend/orders/print-invoice.blade.php`
- `A resources/views/backend/phase4/cash.blade.php`
- `A resources/views/backend/phase4/collection.blade.php`
- `A resources/views/backend/phase4/context.blade.php`
- `A resources/views/backend/phase4/correction-document.blade.php`
- `A resources/views/backend/phase4/customer-finance.blade.php`
- `A resources/views/backend/phase4/customer-profile.blade.php`
- `A resources/views/backend/phase4/expenses.blade.php`
- `A resources/views/backend/phase4/label-pdf.blade.php`
- `A resources/views/backend/phase4/labels.blade.php`
- `A resources/views/backend/phase4/payments.blade.php`
- `A resources/views/backend/phase4/receipt.blade.php`
- `A resources/views/backend/phase4/returns.blade.php`
- `A resources/views/backend/phase4/sale-document.blade.php`
- `M routes/web.php`

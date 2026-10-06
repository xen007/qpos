# Phase 3.D — fournisseurs, achats, réceptions et règlements

Travaux commencés le 05/10/2026, poursuivis le 06/10/2026. Base Git : ad303b6 (3.C), précédent sous-lot be82e34 (3.B), commits et push annoncés par le propriétaire. Aucun commit, staging ni push par l’agent.

## Résultat

3.D implémenté, vérifié sur copie puis appliqué localement le 06/10/2026. Application rouverte. Première archive de fichiers rejetée pour empreintes différentes pendant des écritures concurrentes ; elle n’est pas retenue comme sauvegarde utilisable.

Préflight local : 52 produits, 314 unités legacy ; 314 unités d’ouverture bloquées uniquement dans MAIN, 23 mouvements. 12 achats historiques, 29 lignes. Un fournisseur interne identifié par la règle du seeder historique Own Supplier. La FK fournisseur des achats était en CASCADE.

La devise active héritée est BDT, le fuseau applicatif hérité est Asia/Dhaka. Le propriétaire confirme le 06/10/2026 : nouveaux achats/réceptions/règlements en XAF ; aucune conversion des montants historiques. Le paramétrage global hérité reste un sujet Phase 4 ; les nouveaux documents et coûts de lots portent leur devise explicite.

## Périmètre implémenté

- Fournisseurs : contacts cohérents HTML/JSON, téléphone facultatif, activation, consultation des achats et soldes accessibles, indicateur interne stable, protections serveur et FK restrictives.
- Correction ciblée du down() historique fournisseurs : suppliers, et non customers.
- Achats : décimaux à six places, instantanés conditionnement/unité/facteur/règle fractionnaire, boutique autorisée et devise XAF explicites, création idempotente.
- Réceptions : totales/partielles, lots et péremption, coût réel issu de la ligne confirmée, FKs posées dès création du journal. Les modifications avant la première réception/paiement conservent un avant/après motivé ; une réception ne réécrit pas silencieusement le montant dû.
- Journal commun payments et payment_allocations, FKs explicites fournisseur/client et achat/vente ; pas de lien polymorphe dépourvu de FK. Paiements fournisseurs partiels/complets, contrôles de solde et devise, idempotence, correction par nouvelle entrée liée.
- Annulation motivée : compensation des lots entièrement intacts, refus après utilisation/transfert ou tant que les règlements n’ont pas été corrigés/remboursés. Documents et journaux conservés.
- Dettes et réceptions historiques restent inconnues ; aucune reconstitution d’un paiement ou d’une réception. Les imports catalogue internes ne produisent pas de dette envers un tiers fictif.
- Montant source exact conservé en texte en plus du montant à six décimales ; coût de base calculé HALF_UP à six décimales. L’arrondi final XAF à zéro décimale reste Phase 4.

## Reprise des fichiers 3.C

PurchaseController, Purchase/PurchaseItem, ReceiptStockService, ProductsImport, ProductController/ProductResource, écrans d’achat/import et assets Vite sont repris pour remplacer l’écriture d’achat directe par le service et exposer les conditionnements. StockService, StockMovement et ProductBatch reçoivent des liens d’origine, la devise du lot et une compensation ciblée ; les mouvements 3.A–3.C existants ne sont pas réécrits.

Les sources POS ne sont pas refondues. Un changement de nom des bundles POS après build peut résulter des imports partagés et doit être livré avec le manifeste cohérent.

## Sauvegarde et preuves

Sauvegarde utilisable : storage/app/backups/phase3d-20261006-final-01, hors Git et protégée. SQL restauré et rapproché sur qpos_phase2_probe_20261006_162340_7caa51cf : 30 tables métier ; archive de 827 fichiers restaurée et contrôlée par SHA-256. Manifeste privé manifest.json.

Archive complémentaire baseline-code-3c.zip du HEAD ad303b6, extraite et vérifiée ; empreinte dans baseline-code-proof.json. Le SQL avant migration correspond au schéma 3.C : pour une restauration de coupure, utiliser ce code 3.C dans un répertoire propre avec les fichiers privés/uploads nécessaires de files.zip. L'archive files.zip conserve également le travail 3.D à sa date de création ; elle ne constitue pas, seule, le code compatible avec l'ancien SQL.

Après réouverture, inventorier les nouvelles opérations avant toute récupération : aucune restauration aveugle ni rollback général. Le down 3.D refuse la suppression si des documents/règlements/preuves nouveaux existent. Aucune restauration de source ni suppression de journal métier exécutée pendant ce lot.

Migration répétée sur copie : empreintes de toutes les colonnes historiques avant/après identiques ; adaptation monétaire contrôlée à six places sans changement de montants. 54 contrôles ponctuels privés passent avec rollback transactionnel des fixtures et rapprochement final. Preuve purchase-proof.json : base, code/assets, sauvegarde, liste des cas et date ; contrôles renouvelés après les derniers changements d'écran.

Cas vérifiés : plusieurs conditionnements, réception partielle puis solde, facteur instantané malgré modification du catalogue, coût/facteur HALF_UP, montant source exact, fractions et refus d'excès de précision, péremption inconnue/expirée, refus d'excès reçu, correction avant réception, paiements partiels/complets, devises, surpaiement, droits et portée boutique, idempotence et conflits de clés, compensation sans effacer les originaux, refus après consommation/transfert, historiques non rejoués, fournisseur interne protégé. Import 3.C réellement raccordé au service commun, coût 1,25 conservé, rejeu sans double stock et adaptateur historique refusé.

Contrôle navigateur authentifié sur copie avec compte fictif : liste et création d'achats, coût par carton et total, réception partielle, historique des lots, solde/règlements, commande modifiable avant réception et fiche fournisseur. FR/EN, mobile 390×844 et thème clair/sombre inspectés ; libellés FR et mobiles complétés. Une boutique : sélecteur masqué ; deuxième boutique ajoutée uniquement sur copie : sélecteur apparu dynamiquement. Aucun formulaire financier exécuté sur qpos. Fixtures navigateur nettoyées avec rapprochement identique ; compte de contrôle absent de qpos.

Captures privées : purchase-preview-fr.jpg, purchase-detail-fr.jpg, purchase-detail-fr-dark.jpg, purchase-create-en.jpg et purchase-mobile-fr-final.jpg. Les modifications métier sont validées par les parcours privés ; la revue visuelle ne constitue pas un contrôle exhaustif de tous les écrans 3.C ni une course concurrente réelle.

Syntaxe de 25 fichiers PHP, JSON FR/EN, compilation Blade et build Vite final réussis ; git diff --check réussi. Aucune suite PHPUnit ajoutée ou exécutée. Scripts privés et preuves hors Git. Sources POS, .env et tokens de thème inchangés.

## Application locale et rapprochement final

Migration create_purchase_finance appliquée à qpos sous maintenance, après comparaison du code final avec les preuves sur copie et des données source avec le préflight. Durée DDL locale : 5 min 34 s ; les diagnostics n'ont montré aucun verrou SQL en attente. PurchasePermissionSeeder exécuté : nouvelles capacités de réception/règlement/annulation pour Admin ; aucune affectation boutique inventée.

À 15:55 Africa/Douala : 314.000000 unités d'ouverture bloquées, uniquement MAIN (ID 1), 23 mouvements ; zéro vendable, non vendable physique ou transit. Les 52 produits, 12 achats et 29 lignes historiques conservent leurs empreintes. Les 12 achats ont devise inconnue et états réception/paiement unknown ; aucun replay. Zéro paiement, réception ou modification 3.D fictif sur qpos. Utilisateurs source préservés. Preuve privée purchase-source-proof.json, maintenance=false.

Caches Blade/routes renouvelés et artisan up réussi. GET http://localhost/qpos/public/login : 200 ; accès HTTP au manifeste de sauvegarde sous storage/app/backups : 403. OPcache validate_timestamps=1 et revalidate_freq=2 constatés dans le runtime PHP ; aucun paramètre modifié. Serveur de contrôle 127.0.0.1:8013 arrêté, refus de connexion confirmé.

Concurrence réelle entre deux checkouts : test Phase 4 demandé par le propriétaire. CSV complet, doublons SKU, transferts avec transit/reliquats, inventaires et validation des ouvertures : 3.E. Panier/ventes POS avec conditionnements et quantités décimales : Phase 4.

Limites financières explicites : XAF natif pour les nouveaux documents ; paramètre global BDT et tableaux/rapports financiers legacy inchangés. Arrondi final devise, sessions de caisse, règlements clients et éventuelle conversion documentée restent Phase 4/5. L'interface enregistre un paiement/remboursement effectué ; elle ne déclenche pas d'opération bancaire. Le remboursement/correctif de règlement 3.D est intégral par paiement. Coût d'achat confirmé avant réception ; pas de réévaluation silencieuse après réception. Les imports legacy restent entiers et coûts à deux décimales jusqu'à 3.E.

## Commit propriétaire

Un seul commit 3.D après bilan complet. Inclure modifications source, migration/seeder nouveaux, modèles/services/vues et assets Vite remplacés. Exclure scripts privés, sauvegardes, mappings, preuves et captures sous storage.

HEAD conservé : ad303b6, index vide. Nouveau service PurchaseService, modèles Payment/PaymentAllocation/PurchaseReceipt/PurchaseReceiptItem, migration finance et seeder de permissions à inclure ; aucun fichier neuf oublié. Les bundles remplacés et le manifeste doivent être committés ensemble. Prochain sous-lot : 3.E après validation/commit propriétaire du bilan 3.D.

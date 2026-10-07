# Phase 3.E — imports, transferts, inventaires et ouvertures

Date : 07/10/2026. Base de code : `f957c1e`, commit propriétaire 3.D. Un seul commit propriétaire 3.E attendu ; aucun commit/push effectué par l'agent. Aucun test automatisé ajouté ou lancé.

## Livré

- Migration additive de neuf tables : runs d'import, transferts/lignes/allocations/réceptions, inventaires/lignes, validations d'ouverture. FK restrictives, opérations idempotentes, quantités/coûts DECIMAL(20,6). `down()` refuse la suppression de preuves métier.
- ProductImportService commun au formulaire et à l'adaptateur ProductsImport repris de 3.C. Prévisualisation sans écriture métier ; une erreur bloque tout le fichier. SKU existant/doublon interne refusé, sans renommage ni mise à jour implicite. Validation encodage/en-têtes/CSV/quantités/coûts/unités ; rapport CSV d'erreurs. Réception réelle via PurchaseService puis StockService, avec lot explicite et coût source conservé.
- StockTransferService : brouillon, expédition FEFO/FIFO, transit, réception totale/partielle, reliquat, retour physique et perte motivée. Conservation des lots/coûts/devises ; annulation de brouillon sans mouvement. Après réception, retour par nouveau transfert.
- InventoryService : portée active unique par produit/boutique ; référence journal sous verrou au début du comptage. Mouvement pendant comptage : refus et recomptage. Après comptage enregistré : ajustement par écart sur solde courant, sans effacer les ventes suivantes. Validation atomique, comptages complets requis, vide distinct de zéro.
- Écran admin « Ouvertures à valider » : quantité, lot, coût, devise, péremption, motif, preuve, validation partielle. Reliquat bloqué ; aucun import/inventaire ne débloque implicitement une ouverture. La date FIFO d'un lot d'ouverture reste celle de la coupure, sans date historique inventée.
- Droits distincts de consultation, expédition, réception, inventaire et validation d'ouverture ; boutique active et affectation explicite contrôlées côté serveur. Menu stock, vues FR/EN, boutons adaptés au mobile. Aucun changement des tokens du thème ni refonte POS.

## Sauvegarde et incident de connexion

Point frais privé : `storage/app/backups/phase3e-20261007-final-01`. SQL restauré et empreintes de 35 tables métier vérifiées ; archive de 894 fichiers restaurée. Archive séparée du code compatible 3.D `f957c1e`, 2 570 fichiers extraits et vérifiés. Le code 3.E dans l'archive courante ne doit pas être associé seul au schéma 3.D restauré.

Avast a mis en quarantaine les helpers privés navigateur puis cutover ; restauration manuelle par le propriétaire, sans pilotage de l'antivirus ni modification de protection.

Une première tentative de migration sur copie changeait la connexion après bootstrap : le builder Schema avait gardé la source. Deux tables vides (`product_import_runs`, `stock_transfers`) ont été créées sur `qpos`, sans migration enregistrée. Comparaison avec la sauvegarde fraîche : aucune différence des tables métier. Suppression limitée à ces deux tables vides sous maintenance, puis réouverture. Aucun rollback historique ni restauration globale.

Correction : connexion de copie choisie avant chargement des providers ; vérification des noms SQL réels des connexions Schema et DB. La migration refuse des connexions différentes. Répétition complète sur la copie fraîche réussie, puis application locale sous maintenance : données historiques préservées, 314 unités bloquées, 52 produits, 23 mouvements d'ouverture, aucun document/compte fictif. Preuves privées : `schema-copy-proof.json`, `baseline-code-proof.json`, `stock-source-proof.json`.

L'application a été rouverte après succès ; cache des routes renouvelé. Les droits nouveaux sont attribués au rôle Admin sans affectation boutique automatique.

Clôture technique : syntaxe PHP valide, compilation Blade et caches de routes réussis, build frontend réussi, `git diff --check` sans erreur. Page login HTTP 200, accès HTTP à la sauvegarde refusé (403). Empreinte `.env` inchangée et aucune modification des sources de tokens/CSS du thème. Preuve source après réouverture : `source-read-20261007.json` dans le point frais.

L'onglet navigateur de contrôle est fermé et le serveur temporaire de copie est arrêté ; port 8014 inaccessible après arrêt, application locale toujours HTTP 200. Les fichiers privés et copies de parcours sont conservés pour audit, hors Git.

## Parcours manuels sur copie

| Parcours | Résultat constaté |
|---|---|
| Transfert partiel | 10 expédiées, 6 reçues, 4 retournées ; transit soldé, lot/coût conservés |
| Transfert complet / annulation / perte | Réception totale, annulation de brouillon rejouée sans doublon, perte motivée : documents clos et transit soldé |
| Vente pendant comptage | Sauvegarde du comptage refusée ; nouveau comptage requis |
| Vente après comptage | Référence 93, compté 91, vente ultérieure 3 : solde 90 puis ajustement −2, résultat 88 |
| Inventaire et ouverture | Tentative de gain débloquant une ouverture refusée ; reclassement du seul lot prouvé accepté, ouverture inchangée |
| Validation partielle | Produit 5 : validation partielle, reliquat bloqué. Quantité excessive refusée sans écriture |
| Expiration / coût | Date obligatoire si lot daté ; lot expiré validé mais indisponible. Coût/devise BDT explicites conservés sans conversion |
| FIFO ouverture | Lot approuvé tardivement avec date de coupure consommé avant une réception récente |
| CSV invalide | SKU existant, doublon interne et sept décimales signalés ; aucune ligne valide du fichier appliquée |
| CSV catalogue | SKU texte avec zéros initiaux conservé ; prix natif 123.123456 ; stock zéro |
| CSV réception | 2 unités, coût 1.234567, expiration inconnue : stock bloqué, disponible zéro |
| CSV fractionnaire | 1.25 kg, coût 0.123456, montant exact 0.15432000 conservé ; rejeu sans doublon |
| Coût manquant / CSV mal formé | Refus du fichier sans nouveau run ni mouvement |
| Journal et soldes | Zéro écart produit/boutique/bucket et lot ; 52 mouvements sur la copie de parcours |

Les fixtures sont uniquement sur une ancienne copie isolée ; ses 310 unités d'ouverture restantes après simulations ne décrivent pas la source. Contrôle final source : **314 unités bloquées, disponibles zéro, MAIN ID 1, aucun nouveau document ni compte de contrôle**. Les achats/ventes/imports historiques ne sont pas rejoués ; `products.quantity` reste figé.

## Validation visuelle et limite du téléchargement

Écrans authentifiés FR/EN, mobile 390 × 844, thèmes clair/sombre : imports, transferts, inventaires, validation partielle d'ouverture et consultation POS vérifiés sur copie. Le POS affiche le disponible issu du service commun ; aucune vente réelle ni refonte Phase 4. Captures conservées hors Git dans le dossier privé de la copie de parcours.

Le premier fichier d'erreurs téléchargé comportait un avertissement PHP de dossier temporaire en préfixe. Le contrôleur nettoie maintenant ses buffers avant émission CSV et utilise un nom unique. Corps CSV final vérifié propre avec BOM UTF-8 et trois erreurs ; réponse HTTP navigateur vérifiée 200 avec disposition attachment. La récupération du fichier final par le navigateur intégré n'a pas été confirmée : contrôle manuel propriétaire encore nécessaire. La première capture de téléchargement ne prouve pas le fichier final.

## Limites restantes

- Concurrence réelle entre deux checkouts non testée : Phase 4.
- Moteur natif tarifs/promotions/conditionnements au checkout, caisse/paiements et arrondis finaux XAF : Phase 4.
- Les inconnus restent bloqués ; preuves métier de validation à fournir par le propriétaire, aucune valeur inventée.
- Configuration globale BDT/Asia-Dhaka héritée préservée ; nouveaux documents XAF et instants stock Africa/Douala explicites. Historique non converti.
- Sauvegardes locales : copie externe et ordonnanceur quotidien à compléter dans la phase d'exploitation.
- Copies et helpers privés conservés pour audit hors Git ; ne pas les déployer comme routes de production.

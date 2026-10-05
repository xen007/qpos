# Prompt de reprise — Phase 3 après validation propriétaire

```text
REPRISE QPOS — PHASE 3 (DeepSeek Harness)

Dépôt : C:\xampp\htdocs\qpos. Terminal PowerShell : Get-Content et Select-Object pour lectures bornées, rg pour recherches. Éviter les commandes mêlant CMD/PowerShell. Lire les gros fichiers par sections.

ÉTAT CONSTATÉ LE 05/10/2026
- Branche main.
- HEAD et référence locale origin/main : 8a47f85545ce4a6fefbf6e468b5ba8074e7d26ba, commit WIP du propriétaire.
- Des corrections finales Phase 2 sont non commitées. L’assistant n’a ni commité ni poussé. Le hash après le commit/push propriétaire n’est pas encore connu : relever git status, HEAD et origin/main au démarrage.
- Laravel 12.69.3, PHP 8.2.12, XAMPP, MariaDB 10.4.32 ; OPcache actif, JIT off, APP_DEBUG=false.
- Base locale qpos : six migrations boutiques/catalogue/prix/suivi appliquées en batch 3, application rouverte.

PHASE 2 LIVRÉE
- Unités, conditionnements, conversions exactes à six décimales, codes-barres.
- Catalogue : nom/prix obligatoires ; marque/catégorie/autres références facultatives, imports compris ; consultation produit protégée.
- Boutiques : CRUD, affectations, désactivation, sélecteur et préférence ; affectation active requise pour opérer, même pour administrateur.
- Tarifs D24 : client+boutique > client global > boutique > global ; seuil décroissant, priorité décroissante, ID croissant.
- Promotions D25 : priorité décroissante, remise la plus avantageuse, ID croissant ; une par ligne. Pourcentage, fixe, quantité offerte et lot d’un même conditionnement.
- Prix/coûts natifs DECIMAL(20,6), calcul exact, HALF_UP à six décimales pour les remises. Coût réel réception distinct ; modification manuelle du coût de référence par Admin.
- Sauvegarde restaurée ; conversion sur copie, rejeu sans doublons, puis application qpos.
- 51 produits préservés, 51 références et codes-barres créés, 50 promotions reprises ; quatre lignes de vente historiques inchangées sans snapshots inventés.
- 51 anomalies fractional_rule_unknown à configurer explicitement. Zéro affectation utilisateur automatique.
- Syntaxe PHP, Blade, build frontend, cache de routes renouvelé et exemples manuels sur copie vérifiés ; aucune suite automatisée. Revue visuelle/parcours propriétaire encore à confirmer.
- Sauvegarde privée : C:\qpos-backups\phase2-20261005-b3e84b6f ; manifest.json et rapports conversion-probe/source.json. Ne pas exposer .env ou archives.
- Copie finale : qpos_phase2_probe_20261005_160804_59f78aa1. Anciennes copies partielles conservées ; consulter le rapport pour choisir la bonne.

DOCUMENTS À LIRE
docs/roadmap.md : sections 3, 4, 5 et registre 3.5.
docs/phase2-report.md, docs/schema-cible.md, docs/contrats.md, docs/conversion-strategie.md.
Puis docs/phase0-report.md, docs/ui-composants.md, docs/refonte-ui-complete.md selon le périmètre.

RÈGLES ACTIVES
- Préparer, expliquer et valider avant modification ; une passe principale par groupe de fichiers.
- Préserver le travail existant ; pas de changements hors périmètre.
- Le propriétaire commite ; demander sa validation du bilan avant commit. Ne pas supposer une autorisation de push.
- Aucun test automatisé sans accord explicite.
- Vérifier avant clôture ; sauvegarde récente restaurable avant conversion sensible, répétition sur copie puis maintenance.
- Préserver units, products.unit_id, order_products, IDs et données source ; aucun lot/coût/historique fictif.
- Facteur strictement positif ; exactement une référence facteur 1 pour produit configuré. Quantités/facteurs max six décimales, dépassement refusé sans arrondi ; produit non fractionnaire : quantité entière, y compris après conversion.
- Arrondi FINAL paiement/facture selon devise en Phase 4, FCFA zéro décimale.
- Arrêter et signaler un blocage réel.
- Le mandat précédent s’arrêtait à Phase 2 : confirmer sa validation et l’autorisation Phase 3 avant de modifier.

MISSION IMMÉDIATE PHASE 3 APRÈS AUTORISATION
1. Relever Git et migrations réellement appliquées ; confirmer validation de Phase 2, règles fractionnaires et affectations boutique.
2. Lire les contrats et préparer le premier sous-lot journal/soldes/lots + StockService ; présenter les fichiers et choix avant modification.
3. Confirmer boutique de reprise, provenance/coûts/expiration des stocks existants et traitement des inconnus ; ne pas répartir le stock global arbitrairement.
4. Implémenter dans l’ordre roadmap : journal/soldes/lots et StockService ; mouvements d’ouverture ; raccordement des écrivains existants y compris POS ; fournisseurs/achats et PurchaseService ; imports/transferts/inventaires.
5. Le stock reste aujourd’hui products.quantity global et legacy. Tous les écrivains doivent utiliser le même journal avant activation du nouveau stock.
6. Corriger dans le lot fournisseurs le down() dangereux de create_suppliers_table, qui cible customers. Traiter les bugs achats D40–D41 (anciens D24–D25 Phase 0), D26–D27.
7. Les imports de stock ont encore quantités entières/coûts réels à deux décimales et traitement legacy des SKU doublons ; les reprendre avec les réceptions.
8. Le calculateur natif Phase 2 n’est pas raccordé au checkout : intégration tarifs/promotions, paniers, caisse, paiements et arrondis finaux restent Phase 4.
9. Fuseau actuel encore Asia/Dhaka : planifier les nouvelles journées Africa/Douala sans réécrire aveuglément l’historique.
```

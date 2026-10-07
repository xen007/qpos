# Prompt de reprise — Phase 3 après validation propriétaire

```text
REPRISE QPOS — PHASE 3 (DeepSeek Harness)

Dépôt : C:\xampp\htdocs\qpos. Terminal CMD demandé par le propriétaire ; rg pour recherches. Éviter les commandes mêlant CMD/PowerShell. Lire les gros fichiers par sections.

ÉTAT CONSTATÉ LE 07/10/2026
- Branche main.
- HEAD : `f957c1e` (3.D), précédent `ad303b6` (3.C), `be82e34` (3.B), `a65781a` (3.A), Phase 2 `9862796`. Commit 3.D confirmé. 3.E implémenté et appliqué localement ; bilan dans docs/phase3-3e-report.md, un seul commit propriétaire attendu après revue.
- Laravel 12.69.3, PHP 8.2.12, XAMPP, MariaDB 10.4.32 ; OPcache actif, JIT off, APP_DEBUG=false.
- Base locale qpos : socle 3.A et ouvertures 3.B appliqués ; 314 unités bloquées dans MAIN, données legacy préservées. Le raccordement 3.C est consigné séparément dans docs/phase3-3c-report.md ; lire ce bilan avant reprise.

PHASE 2 LIVRÉE
- Unités, conditionnements, conversions exactes à six décimales, codes-barres.
- Catalogue : nom/prix obligatoires ; marque/catégorie/autres références facultatives, imports compris ; consultation produit protégée.
- Boutiques : CRUD, affectations, désactivation, sélecteur et préférence ; affectation active requise pour opérer, même pour administrateur.
- Tarifs D24 : client+boutique > client global > boutique > global ; seuil décroissant, priorité décroissante, ID croissant.
- Promotions D25 : priorité décroissante, remise la plus avantageuse, ID croissant ; une par ligne. Pourcentage, fixe, quantité offerte et lot d’un même conditionnement.
- Prix/coûts natifs DECIMAL(20,6), calcul exact, HALF_UP à six décimales pour les remises. Coût réel réception distinct ; modification manuelle du coût de référence par Admin.
- Sauvegarde restaurée ; conversion sur copie, rejeu sans doublons, puis application qpos.
- 51 produits préservés, 51 références et codes-barres créés, 50 promotions reprises ; quatre lignes de vente historiques inchangées sans snapshots inventés.
- 0 produit sans règle `allows_fractional` et 0 produit dont l'unité de base est « Dozen ». Les 51 anomalies fractional_rule_unknown restent conservées comme historique d'audit. Zéro affectation utilisateur automatique.
- Syntaxe PHP, Blade, build frontend, cache de routes renouvelé et exemples manuels sur copie vérifiés ; aucune suite automatisée. Le rapport ne détaille pas toute la validation visuelle/authentifiée ; Phase 2 a depuis été validée par le propriétaire.
- Sauvegarde privée : C:\qpos-backups\phase2-20261005-b3e84b6f ; manifest.json et rapports conversion-probe/source.json. Ne pas exposer .env ou archives.
- Copie finale : qpos_phase2_probe_20261005_160804_59f78aa1. Anciennes copies partielles conservées ; consulter le rapport pour choisir la bonne.

DOCUMENTS À LIRE
docs/roadmap.md : sections 3, 4, 5 et registre 3.5.
docs/phase2-report.md, docs/schema-cible.md, docs/contrats.md, docs/conversion-strategie.md.
docs/phase3-report.md : sauvegarde, contrôles 3.A et reprise 3.B appliquée.
docs/phase3-3c-report.md : raccordement des consommateurs et limites restantes ; deux commits séparés avant nouvelle passe sur les fichiers achats.
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
- Phase 2 validée par le propriétaire ; Phase 3 autorisée le 05/10/2026. Le propriétaire effectue les commits des sous-lots.

MISSION À LA PROCHAINE REPRISE
1. Relever Git et migrations réellement appliquées ; lire les bilans 3.D et 3.E avant nouvelle passe achats/stock. Ne pas supposer le commit 3.E effectué.
2. 3.A–3.C sont livrés ; 3.D est traité dans docs/phase3-3d-report.md. Ne pas relancer les ouvertures ni les anciens achats/ventes.
3. MAIN est la boutique de reprise confirmée ; conserver les 314 unités bloquées sans preuve de lot/coût/péremption et les traces d'origine.
4. Sous-lot 3.E livré : imports, transferts, inventaires, ventes pendant comptage et validation motivée des ouvertures. Lire docs/phase3-3e-report.md ; un seul commit propriétaire attendu après bilan global.
5. products.quantity reste une source historique figée. Les consommateurs raccordés en 3.C utilisent le journal et les soldes par boutique ; pas de réécriture ni de nouvelle autorité de stock dans cette colonne.
6. Lire docs/phase3-3d-report.md pour les preuves et limites actuelles du lot fournisseurs/achats : correction du down() fournisseurs, journal de règlements, réceptions partielles et PurchaseService. Nouveaux documents XAF (D44), aucun montant historique converti.
7. Imports 3.E : dry-run sans écriture métier, application atomique, erreurs CSV, SKU existants refusés sans renommage/mise à jour. Fractions selon produit ; calculs natifs à six décimales.
8. Le calculateur natif Phase 2 n’est pas raccordé au checkout : intégration tarifs/promotions, paniers, caisse, paiements et arrondis finaux restent Phase 4.
9. Fuseau actuel encore Asia/Dhaka : planifier les nouvelles journées Africa/Douala sans réécrire aveuglément l’historique.
```

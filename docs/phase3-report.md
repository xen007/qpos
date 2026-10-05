# Phase 3 — suivi d'exécution

Date : 05/10/2026. Le propriétaire effectue les commits. Aucun commit ni push exécuté par l'agent.

## 3.A — socle et contrôles après commit

Commit propriétaire : `a65781a`. La migration `2026_10_05_200000_create_stock_core_tables` est appliquée sur `qpos` en batch 5. Les données legacy et les consommateurs existants n'ont pas été basculés.

### Sauvegarde et récupération

`New-Item C:\qpos-backups\test` indique que le dossier existe déjà ; la création d'un nouveau sous-dossier daté est refusée. Repli explicitement autorisé : `storage/app/backups/phase3-20261005-151137`, ignoré par Git. Le dossier parent porte une protection Apache `Require all denied`. La commande exclut tous les backups de l'archive des fichiers pour éviter une inclusion récursive.

`qpos:catalogue-backup` a vérifié l'export SQL et sa restauration isolée dans `qpos_phase2_probe_20261005_201147_ef17e3be`, les empreintes des tables et la restauration de 803 fichiers. Le manifeste privé conserve les empreintes ; il ne doit pas être publié. Le repli reste une copie sur le disque local ; l'externalisation relève de la Phase 7.

La première tentative de DDL sur la copie a été interrompue avant enregistrement de la migration et a laissé deux tables vides. Le `down()` protecteur a supprimé uniquement le socle vide sur cette copie, puis la migration entière a réussi. L'application locale a ensuite réussi avec la limite de durée PHP désactivée pour cette commande (`-d max_execution_time=0`). Aucun rollback historique général ni restauration sur `qpos`.

### Résultats sur copie

Les parcours du service ont été exécutés par commandes ponctuelles sur la copie restaurée. Les fixtures étaient enfermées dans une transaction annulée ; les empreintes de la copie avant/après concordent. Aucune suite de tests automatisés ajoutée ou lancée ; aucune écriture métier de contrôle sur `qpos`.

| Parcours | Résultat |
|---|---|
| FEFO | 3 unités du lot à expiration proche, puis 1 unité du lot à expiration éloignée |
| FIFO | Réception ancienne consommée avant une réception plus récente, indépendamment de l'ordre de création |
| Expiré / inconnu / expiration aujourd'hui | 3 expirées exclues, 2 inconnues bloquées, 1 expirant aujourd'hui disponible |
| Survente | Refus sans mouvement ni modification de solde |
| Classification | `dated` sans date, `unknown` avec date et classification connue contradictoire refusés |
| Ajustements | Entrées/sorties vendables et non vendables exactes ; motif vide refusé |
| Fractions | `1.250001 - 0.250001 = 1.000000` ; fraction interdite sur produit entier ; sept décimales refusées |
| Rejeu | Réception, sortie et transfert rejoués sans doublon ; valeurs différentes et signe inversé refusés |
| Vente non vendable | Refus même sans référence à une ligne de vente |
| Transfert | Lot et coût `2.500000` conservés ; conservation des quantités et journal rapproché |
| Allocation vente | Quantité `0.250000`, coût unitaire `2.500000`, coût total `0.625000` |
| Rapprochement | Tous les soldes par produit/boutique/bucket et tous les soldes de lots correspondent aux mouvements |

21 contrôles ponctuels réussis. Le détail privé est dans `storage/app/phase3-manual-results.json`. Pas de contrôle concurrent avec deux connexions ni de validation visuelle POS dans ce sous-lot ; le verrouillage réel au checkout sera raccordé en 3.C.

### Comportement `expiry_status`

- `dated` exige une date ; FEFO avec départage par réception puis ID. La date d'expiration reste vendable ce jour-là ; le lot est expiré à partir du lendemain selon `Africa/Douala`.
- `not_applicable` indique explicitement l'absence de péremption et suit FIFO.
- `unknown` n'est jamais assimilé à non périssable : quantité exclue du disponible, affichée dans `blocked_expiry`.
- Les lots expirés augmentent le non vendable calculé ; l'expiration ne crée pas spontanément un mouvement. Le solde brut `saleable` peut donc différer du disponible.
- Un même produit ne mélange pas des classifications connues `dated` et `not_applicable`. Les réceptions inconnues restent bloquées.
- L'ouverture sans lot est distincte (`unallocated_opening`) et exclue du disponible ; aucune date produit n'est étendue arbitrairement à un lot.

Les nouveaux horaires métier de réception et de mouvement utilisent `Africa/Douala` explicitement ; la configuration historique `Asia/Dhaka` et les dates anciennes ne sont pas réécrites.

Correctifs de consolidation découverts après le commit 3.A : tables explicites dans les modèles de soldes, contrôle du signe au rejeu, refus des ventes sur bucket non vendable, fuseau des nouveaux mouvements et repli de sauvegarde. Ils sont présentés avec les modifications 3.B pour conserver un seul commit propriétaire par sous-lot.

## 3.B — reprise appliquée et rapprochée

### Implémentation

- Migration additive `2026_10_05_210000_create_stock_opening_tracking` : runs, sources d'ouverture uniques par produit et FK de conversion dans le journal. Refus de suppression des preuves après reprise.
- `qpos:stock-opening` : prévisualisation par défaut ; manifeste restaurable de moins de 24 heures ; contrôle des empreintes legacy ; affectations explicites par produit ; somme exacte des allocations ; boutiques actives ; interdiction des stocks négatifs/ambiguïtés fractionnaires.
- Sans mapping : affiche sources et boutiques, sans choisir de destination. Chaque produit, y compris à zéro, doit figurer dans le mapping ; un produit à zéro porte une liste vide.
- Application répétée sur la copie du manifeste avant la source ; empreintes de code/mapping/sources identiques exigées. Source en maintenance, à conserver gelée jusqu'au raccordement 3.C.
- Chaque quantité reprise est un mouvement `opening` en bucket `unallocated_opening`, daté de la coupure, lié au run. Coûts inconnus nullables ; expiration source conservée comme preuve, aucun lot fictif. Les achats et ventes historiques ne sont pas rejoués.
- Rejeu identique sans doublon ; ouverture incompatible ou stock déjà utilisé sans run refusés. Rapprochement sources/mouvements et soldes/journal avant commit transactionnel.

### Répétition technique isolée

La copie contient 52 produits, 314 unités globales, aucun stock négatif et aucune règle fractionnaire inconnue. Une seule boutique active : MAIN, ID 1. Un mapping de répétition **uniquement sur copie**, explicitement identifié comme tel, a produit 52 sources conservées, 23 mouvements, 314 unités bloquées, zéro lot créé et zéro coût inventé. Rejeu réussi sans mouvement supplémentaire ; les empreintes des tables legacy correspondent toujours à la sauvegarde. Ce mapping technique ne constitue pas une attribution approuvée sur la source.

### Application locale après confirmation propriétaire

Le propriétaire confirme que les 314 unités des 52 produits appartiennent à MAIN (ID 1), que les inconnus restent bloqués et que toute future boutique sera approvisionnée par transfert explicite. Le mapping approuvé porte cette provenance ; aucune répartition déduite automatiquement.

Application figée avec `artisan down` avant sauvegarde de coupure : `storage/app/backups/cutover3b-20261005-160114`. Restauration SQL vérifiée dans `qpos_phase2_probe_20261005_210134_da7fd9d1`, 28 tables métier et 814 fichiers vérifiés. Preflight puis ouverture avec le même mapping/code sur cette copie, puis sur `qpos` : réussite. Migration de suivi appliquée localement ; 52 preuves source, 23 mouvements d'ouverture pour les produits à quantité positive, 29 sources à zéro sans mouvement nul. Total `314.000000`, uniquement MAIN et `unallocated_opening`, disponible zéro, aucun lot/coût fictif, empreintes legacy inchangées.

Le schéma interdit les mouvements nuls : la reprise d'un produit à zéro est attestée par `stock_opening_sources`, sans journal artificiel. Les manifestes privés `stock-opening-probe.json` et `stock-opening-source.json` conservent les rapprochements. Le gel est maintenu jusqu'au raccordement 3.C pour éviter un second stock actif. Aucun ancien achat/vente rejoué.

La confirmation est inscrite en D43 dans la roadmap. Fichiers du seul commit propriétaire 3.B : `app/Console/Commands/BackupCatalogue.php`, `ConvertStockOpening.php`, `app/Models/BatchStock.php`, `ProductStock.php`, `app/Services/StockService.php`, migration `2026_10_05_210000_create_stock_opening_tracking.php`, `docs/roadmap.md`, `conversion-strategie.md`, `reprise-phase3.md`, `phase3-report.md`. Les fichiers 3.C sont consignés dans un rapport séparé ; aucun commit par l'agent.

Exemple de commande de prévisualisation :

```powershell
php artisan qpos:stock-opening --backup=<dossier-prive> --mapping=<mapping-json>
```

L'option `--database` accepte uniquement la base restaurée indiquée par ce manifeste ; `--apply` écrit l'ouverture après les contrôles. Ne jamais utiliser le mapping de répétition comme preuve de répartition réelle.

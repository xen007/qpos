# QPOS — Stratégie de conversion

Date initiale : 01/10/2026. Mise à jour : 07/10/2026. La reprise catalogue Phase 2 est implémentée; la décision de souplesse stock post-Phase 3 est consignée ci-dessous.

## Mise à jour souplesse stock — 07/10/2026

Les 314 unités d'ouverture restent attribuées à MAIN et ne sont pas réparties. Elles sont converties sous sauvegarde vérifiée en lots `LOT-AUTO-OUVERTURE` vendables; coût nul marqué inconnu, péremption inconnue conservée. Le mouvement source demeure et les mouvements de reclassement sont idempotents. Une correction exige un motif et un audit avant/après. Cette décision remplace le blocage historique décrit dans les procédures antérieures.

Références : [roadmap.md](roadmap.md), [schema-cible.md](schema-cible.md), [contrats.md](contrats.md). Le document ne s’exécute pas lui-même ; les opérations réellement effectuées le 05/10/2026 sont consignées dans `phase2-report.md`.

## 1. Principes

Adapter `units`, `products.unit_id`, `order_products` et les autres tables existantes. Préserver IDs, références, documents, prix/coûts/remises historiques et valeurs source. Séparer extension du schéma, conversion et activation des consommateurs. Les créations de tables suivent leurs phases propriétaires ; le schéma complet n'est pas déployé d'un seul bloc en Phase 1.

Le relevé actuel est fondé sur les migrations/code au commit `5546975`. Les effectifs de Phase 0 sont historiques ; ils ne servent pas de compteurs actuels. Avant exécution : vérifier schéma réel, migrations appliquées, données et contraintes sur copie dédiée, par lectures bornées. Aucun lot, coût, paiement, acteur, date ou taux historique ne doit être inventé.

## 2. Ordre des migrations et bascules

| Étape / phase | Structures et conversion | Condition de passage |
|---|---|---|
| Préparation | Sauvegarde complète récente, manifeste, restauration isolée ; préflight des données ; rapport d'anomalies | Restauration utilisable ; source et périmètre identifiés ; boutique de reprise validée |
| Phase 1 | Boutiques, affectations et structures minimales de suivi/audit selon lot autorisé | Droits globaux et portée boutique explicite ; aucune attribution automatique universelle |
| Phase 2 | Extension units/products ; product_units/barcodes ; instantanés de lignes nullable ; contrats de prix | Unités et facteurs documentés ; produits ambigus isolés ; nouveaux écrans compatibles |
| Phase 3 — structure | Soldes, réceptions, lots, mouvements, allocations, transferts, inventaires ; FK différées si dépendances circulaires | StockService cible défini ; mappings et ordre de verrous prêts |
| Phase 3 — ouverture | Stock de référence à l'instant de coupure ; mouvements d'ouverture uniques ; allocation des quantités prouvées uniquement | Rapprochement par produit/boutique sans double reprise des achats/imports |
| Phase 3 — bascule stock | Raccorder POS existant, achats, modifications et imports au même journal | Aucun écrivain ne modifie encore seul products.quantity ; scénarios concurrents autorisés vérifiés |
| Phase 4 — structure | Caisses/sessions, paiements/affectations, retours/avoirs, paniers et idempotence | États et contrats de règlement validés avant checkout complet |
| Phase 4 — conversion | Rapprocher order_transactions, orders et lignes ; copier les paiements prouvés ; maintenir les historiques incomplets | Pas de double encaissement ; écarts de soldes traités ou isolés |
| Phases 5–7 | Rapports sur sources nouvelles ; notifications/outbox ; sauvegardes régulières et exercices | Rapports réconciliés, contrats d'exploitation appliqués |
| Retrait différé | Retirer les écritures/colonnes de compatibilité après preuve de non-usage | Approbation distincte ; sauvegarde adaptée ; aucun consommateur restant |

Les colonnes nouvelles commencent nullables lorsque l'historique est incomplet. Les exigences obligatoires s'appliquent aux nouvelles opérations par validation ; les contraintes globales ne sont durcies qu'après résolution documentée. Ne pas imposer une session ou un lot fictif aux ventes anciennes.

## 3. Reprise par domaine

### Unités et produits

Conserver chaque unité et chaque lien valide. Pour un produit avec unité reconnue : créer un conditionnement de référence de facteur 1 qui décrit cette unité actuelle, sans prétendre reconstruire d'anciens conditionnements. Unités semblables : proposer des correspondances, jamais fusionner sur simple ressemblance. Unité absente ou incohérente : ouvrir une anomalie ; pas de valeur par défaut « pièce ».

La vente historique ne contient pas d'instantané d'unité. L'unité courante du produit ne prouve pas celle utilisée à la date de vente : laisser l'instantané historique inconnu sauf preuve externe. Conserver prix/coût/discount source et D36 : purchase_price produit est un coût de référence, pas le coût prouvé du stock restant.

Quantités entières convertibles exactement vers DECIMAL ; prix en double : extraire valeur brute, représentation cible et delta avant conversion. Aucune réécriture silencieuse des totaux par un nouvel arrondi. Si nécessaire, préserver une colonne source et stocker la valeur cible séparément jusqu'à approbation. Phase 2 : DECIMAL(20,6), conversion exacte sans tolérance implicite. Valeur négative, hors borne ou exigeant plus de six décimales : anomalie, source conservée, cible laissée inconnue.

### Boutique de reprise et stock

La boutique d'origine n'existe pas dans les opérations actuelles. Faire confirmer la boutique de reprise et le périmètre des données ; sinon laisser le mapping en attente et bloquer la bascule. Ne pas distribuer le stock global entre plusieurs boutiques selon une règle arbitraire.

À une coupure approuvée, figer les écritures et relever products.quantity ainsi qu'un comptage physique si disponible. Le stock repris est porté par un mouvement d'ouverture daté de cette coupure, avec source produit/run, quantité et statut de provenance. Cette date est celle de la reprise ; elle n'est jamais présentée comme une réception historique.

Ne pas additionner les achats historiques au stock courant : celui-ci inclut déjà leurs effets et ceux des imports/ventes. Ne pas rejouer à nouveau les ventes anciennes contre l'ouverture. La conservation du passé et l'ouverture de la nouvelle comptabilité de stock sont deux traitements distincts.

Créer un lot uniquement si quantité restante, produit, expiration et provenance sont documentés. La date products.expire_date seule ne prouve ni plusieurs lots ni la quantité par réception ; conserver cette valeur source sans l'étendre à toutes les réceptions. Un reliquat sans lot reste une quantité d'ouverture explicitement non allouée, avec coût inconnu nullable. Usage vendable ou blocage : **à proposer et valider**, notamment pour périssables. Tout coût inconnu rend la valorisation partielle, pas égale à zéro.

### Achats et imports

Mise à jour 3.E — 07/10/2026 : ProductsImport est désormais un adaptateur du service CSV commun. Nouveaux stocks uniquement par réception PurchaseService/StockService ; aucune écriture dans le solde global historique et aucun ancien import rejoué. Les descriptions historiques ci-dessous ne sont pas une instruction de réexécution.

Les ouvertures bloquées sont reclassées uniquement par validation administrative motivée et prouvée ; validation partielle, reliquat bloqué. Le lot prend la date de coupure pour FIFO, sans inventer une ancienne réception. Imports/inventaires ne constituent pas une validation de provenance.

Avant toute DDL de répétition, choisir la connexion isolée avant les providers puis vérifier les noms SQL réels des connexions de données et Schema. Un changement après bootstrap peut laisser un builder Schema lié à la source. L'incident 3.E et son nettoyage limité aux tables vides sont consignés dans `phase3-3e-report.md`.

Conserver achats et lignes actuelles avec leurs IDs, prix et dates brutes. PurchaseController remplace les lignes lors des modifications : les versions effacées ne sont pas récupérables depuis ces tables seules. La présence d'un achat ne suffit pas à prouver la répartition des lots encore présents. Préserver l'achat sans générer une deuxième réception physique.

ProductsImport crée un produit et un achat par ligne ; mapper ces enregistrements comme sources, sans déduire un deuxième stock d'ouverture. Le modèle PurchaseItem mentionne des champs de remise absents de la migration lue : vérifier le schéma réel avant toute reprise de ces champs.

### Ventes et paiements

Conserver orders et order_products. Les coûts de référence copiés dans les lignes restent identifiés comme tels ; ne pas reconstruire un CMV réel FEFO/FIFO pour les ventes sans allocations. Ventes sans ligne ou quantité nulle : anomalie D33, pas de ligne artificielle. is_returned ne suffit pas à créer un retour détaillé ou un remboursement.

Pour chaque order_transaction valide : proposer un paiement avec origine unique table/ID, montant net connu et affectation à sa vente. Le checkout actuel stocke le montant affecté après plafonnement, tandis que orders.change_amount est global à la vente : aucun montant reçu ou monnaie par ancien paiement ne doit être déduit sans preuve. Paiement legacy autorise ces valeurs inconnues nulles ; les nouvelles opérations exigent le détail.

Comparer somme des transactions, orders.paid, due, total et change_amount sans écraser la source. Un montant paid sans transaction correspondante devient une anomalie ; ne pas créer d'encaissement de compensation automatique. paid_by inconnu, référence absente ou utilisateur nullable : conserver l'inconnu. Aucune session fictive et aucun mouvement de caisse rétroactif. Un achat sans preuve de règlement ne génère pas de paiement fournisseur.

### Dates et paniers

Conserver les timestamps historiques tels quels avec leur interprétation connue/inconnue. Asia/Dhaka dans la configuration ne prouve pas que chaque date ancienne représente une heure locale Dhaka. Comparer code d'écriture, configuration au moment des faits et documents disponibles avant toute conversion UTC. Utiliser Africa/Douala pour nouvelles journées métier après bascule validée ; pas de décalage global aveugle de cinq heures.

Les anciens paniers n'ont pas d'identité par onglet/boutique. **À proposer** : faire terminer ou abandonner explicitement les paniers avant coupure, puis créer les nouveaux contextes ; jamais convertir un panier en vente ni réserver son stock.

## 4. Cas ambigus à isoler

| Cas | Conservation / action | Blocage |
|---|---|---|
| Produit sans unité, FK orpheline, unité ambiguë | Valeur source, anomalie, résolution par preuve propriétaire | Produit exclu des nouveaux flux jusqu'à résolution |
| Boutique d'origine inconnue | Mapping en attente | Bascule de ces opérations/stocks bloquée |
| Stock négatif ou différent du comptage | Source et comptage conservés ; correction motivée approuvée | Pas d'ouverture normalisée silencieusement |
| Expiration/coût/lot restant inconnus | Quantité d'ouverture sans lot ; coût nullable ; complétude affichée | Flux traçables périssables bloqués si preuve indispensable |
| Vente sans ligne/quantité invalide | Vente conservée, anomalie D33 | Retour automatique et indicateurs détaillés exclus pour ce cas |
| Paiements différents du payé/dû | Sources conservées, rapprochement manuel | Bascule financière du document bloquée |
| Retour seulement marqué is_returned | Aucune création de lignes ni remboursement | Traitement manuel documenté requis |
| Montants hors bornes ou conversion décimale avec delta | Rapport brut/cible/delta | Transformation bloquée sans règle approuvée |
| Timestamp sans fuseau prouvé | Valeur brute et statut inconnu | Pas de réécriture automatique |
| SKU/code-barres en doublon | Pas de renommage automatique pendant reprise | Résolution avant scanner/unicité cible |

conversion_issues conserve source, ID, valeurs pertinentes filtrées, type, sévérité, preuve, décision, auteur et date. Isoler signifie préserver et empêcher l'usage dangereux, pas supprimer le document ni masquer l'écart des rapports.

## 5. Sauvegarde, exécution et récupération

Sauvegarde obligatoire immédiatement avant chaque conversion sensible : base, fichiers métier, configuration/secrets sous protection, commit et manifeste. La sauvegarde Phase 0 est un point historique ; elle ne remplace pas celle de la future coupure. Restaurer d'abord sur copie isolée et vérifier les données nécessaires.

DDL séparé des transformations : ne pas présumer qu'une transaction annule une migration de structure MariaDB. Scripts futurs idempotents par run/source, reprise par lots avec checkpoint, totaux et anomalies. Effectuer les répétitions sur copie dédiée ; aucun essai d'écriture sur qpos. Geler les écrivains pendant ouverture/bascule ; éviter une double écriture libre sans stratégie transactionnelle commune.

Deux récupérations : avant réouverture, restauration de la sauvegarde de coupure avec code compatible ; après reprise d'activité, inventorier les opérations nouvelles et choisir conversion corrective/compensations avant toute restauration. Un rollback Git seul ne restaure pas les données.

Obstacle constaté : `2024_10_16_123030_create_suppliers_table.php::down()` cible customers. Ne pas utiliser un rollback général des migrations historiques comme procédure de récupération. Correction éventuelle dans un lot distinct autorisé ; aucun code modifié ici.

## 6. Critères de validation future

- Schéma réel rapproché ; nombre et IDs des objets préservés ; source et cible documentées.
- Quantités rapprochées par produit/boutique/bucket ; ouverture comptée une fois ; allocations nouvelles et journal/soldes cohérents.
- Paiements rapprochés avec documents ; aucun doublon source, aucune session/transaction inventée.
- Inconnus visibles dans marges/valorisation, distincts des coûts nuls ; listes d'anomalies accessibles.
- Autorisations boutique, dernière unité concurrente, double checkout, perte de réponse et retours partiels vérifiés dans les lots propriétaires.
- Sauvegarde restaurable et point de récupération documenté ; comptes rendus conservés hors secrets.

Les scénarios stock/paiements restent dans leurs phases. Aucun test automatisé n’est autorisé pour cette livraison. Le rapport Phase 2 distingue les contrôles techniques et les vérifications manuelles sur copie.

## 7. Procédure effective de reprise catalogue Phase 2

1. Geler les écritures (`artisan down`) ; aucun worker ou écrivain externe ne doit continuer à écrire.
2. Créer un répertoire privé vide hors application, protégé pour le propriétaire et SYSTEM. Repli autorisé le 05/10/2026 si ce chemin est inaccessible : sous-dossier vide de `storage/app/backups`, ignoré par Git et protégé contre l'accès Apache ; tous les backups sont exclus de l'archive des fichiers. Exécuter `php artisan qpos:catalogue-backup <répertoire>` : SQL complet, fichiers/configuration/médias et manifeste SHA-256 ; restauration SQL sur une base `qpos_phase2_probe_*` et restauration des fichiers vérifiées par empreintes. Ne jamais publier ces archives ni leur `.env`.
3. Lire `manifest.json` pour connaître la base de copie. Exécuter `php artisan qpos:catalogue-convert --backup=<répertoire> --database=<base-copie> --apply`. Cette commande applique seulement les six migrations boutiques/catalogue/prix/suivi autorisées ; elle crée des références facteur 1, codes-barres SKU non ambigus et promotions historiques prouvées. Les snapshots des ventes restent inconnus.
4. Examiner compteurs/anomalies et vérifier manuellement les parcours sur copie. Rejouer la conversion sur copie pour contrôler l’absence de nouveaux doublons.
5. Exécuter `php artisan qpos:catalogue-convert --backup=<répertoire> --apply` sur la source encore gelée. La commande exige la même sauvegarde, les données historiques inchangées et une preuve de conversion réussie sur la copie avec les mêmes empreintes du convertisseur et des migrations.
6. Conserver `conversion-probe.json` et `conversion-source.json` dans le répertoire privé. Réouvrir avec `artisan up` après contrôle. Les données historiques sont comparées ligne par ligne via leurs empreintes ; nouvelles colonnes/tables natives, permissions et boutique MAIN sont des ajouts explicites.

Les erreurs DDL MariaDB peuvent laisser une structure partiellement préparée : la restauration privée constitue le point de récupération, pas un rollback général. La preuve de répétition doit être renouvelée après toute modification du convertisseur ou d’une migration. Les anomalies fractionnaires ou d’unité ne sont pas résolues arbitrairement : produits à configurer, source intacte.

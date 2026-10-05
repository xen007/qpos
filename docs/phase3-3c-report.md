# Phase 3.C — raccordement des consommateurs

Date : 05/10/2026. 3.B appliqué avant ce sous-lot ; voir [phase3-report.md](phase3-report.md). Aucun commit ni push exécuté par l'agent. Le propriétaire conserve un seul commit par sous-lot.

## Résultat

Le POS existant, les réceptions d'achats et les imports consomment le journal `StockService` et les mêmes soldes par boutique. `products.quantity` reste une source historique figée ; elle n'est plus incrémentée/décrémentée par ces consommateurs. Les écrans raccordés affichent le disponible ; les ouvertures bloquées restent conservées dans `unallocated_opening`, disponibles pour les parcours de validation ultérieurs.

La migration additive `2026_10_05_220000_connect_stock_consumers` est appliquée localement : boutique nullable sur commandes, achats et paniers ; unicité utilisateur/boutique/produit sur les nouveaux paniers ; lien réception/lot sur les lignes d'achat. Les anciens documents et paniers conservent une boutique inconnue (`NULL`) : aucune attribution historique inventée.

Application rouverte après répétition sur copie et rapprochement source : 52 preuves d'ouverture, 23 mouvements, `314.000000` unités uniquement MAIN et bloquées ; disponible zéro ; données legacy inchangées. Aucune opération métier de contrôle effectuée sur `qpos`.

## Changements

- Stock POS : sélection SQL du disponible conforme au service, exclusion des expirés, des péremptions inconnues et des ouvertures non validées. Recherche/scanner existants conservés, y compris la limite Phase 4 des conditionnements non unitaires.
- Panier : boutique explicite ; chaque lecture/mutation est limitée à l'utilisateur et à cette boutique. Aucune réservation de panier. Le contrôle vérifie la quantité après ajout, sans dépasser le disponible.
- Checkout : verrou de l'utilisateur, produits verrouillés par ID croissant, contrôle de disponibilité et `StockService::decrease` dans la transaction de vente ; allocation aux lots et coûts conservés. Seul le panier de la boutique vendue est supprimé. Les paniers anciens sans contexte restent conservés et exclus du checkout.
- Achats : réception nouvelle journalisée avec lot/coût réel et boutique explicite. Péremption absente : `unknown`, donc bloquée, avec message traduit. La réécriture d'un achat réceptionné/historique est refusée ; les corrections traçables relèvent des parcours 3.D–3.E.
- Imports existants : produit créé à quantité legacy zéro ; réception rattachée au journal et à la boutique. Pas de second stock alimenté. Les compléments CSV/doublons/compte rendu restent 3.E.
- Catalogue : quantités directes interdites dans les requêtes de création/modification et à la sauvegarde du modèle ; suppression interdite lorsqu'une preuve de reprise ou un mouvement existe. Les lectures de stock et l'inventaire existant utilisent les soldes du journal. Les lectures groupées évitent une requête stock par produit/ligne d'achat dans les parcours raccordés.
- Autorisations : boutique active et affectation requises, même pour Admin. Les nouveaux documents sont filtrés selon les boutiques accessibles, y compris les accès directs, impressions et encaissements existants. L'accès historique aux documents sans boutique conserve ses permissions antérieures.
- D42 : une boutique active sélectionnée dynamiquement si elle est affectée ; sélecteur masqué. Deux boutiques actives ou plus : sélecteur visible avec les choix autorisés. Aucun droit/stock/affectation créé automatiquement.
- Onglets : contexte capturé à l'affichage, envoyé dans les appels React ; import natif et listes de stocks conservent aussi leur contexte. Un changement dans un autre onglet ne déplace pas le panier ni le checkout.
- D13 : les nouvelles heures de mouvement/réception sont écrites et relues en `Africa/Douala` via `StockDateTime`. Sérialisation UTC vérifiée sur une ouverture locale. Configuration historique `Asia/Dhaka` et données historiques inchangées.

## Sauvegarde, répétition et application

L'application est restée en maintenance entre l'ouverture 3.B et cette bascule. Deuxième sauvegarde restaurable : `storage/app/backups/cutover3c-20261005-162506`, hors Git, protégée par la procédure de repli. Restauration SQL/fichiers vérifiée : 30 tables métier, 799 fichiers ; copie `qpos_phase2_probe_20261005_212541_d57defd6`.

Schéma 3.C appliqué sur copie ; parcours répétés avec le code final, empreintes de code conservées dans le manifeste privé `stock-consumers-probe.json`. Application locale sous contrôle de la même sauvegarde, des empreintes de code et des données source inchangées. DDL exécuté hors transaction métier ; migration locale terminée en 2 min 29 s. Rapprochement final réussi, puis `artisan up`.

Avant réouverture, une récupération aurait exigé code/base compatibles, à partir des sauvegardes de coupure. Après réouverture, inventorier les nouvelles opérations avant toute restauration ; ne pas annuler la base ni supprimer les preuves aveuglément. Les `down()` du socle/suivi/raccordement refusent la suppression lorsque des preuves existent. Le défaut du `down()` fournisseurs reste à corriger en 3.D ; aucun rollback général historique utilisé.

## Vérifications

22 contrôles ponctuels réussis sur la copie fraîche ; fixtures transactionnelles annulées et empreintes avant/après identiques. Détail privé : `storage/app/phase3-consumers-results.json`. Pas de suite de tests automatisés ajoutée ou lancée.

| Groupe | Résultat |
|---|---|
| Boutiques | Auto-détection une/deux boutiques ; boutique non affectée et contexte absent refusés |
| Panier | MAIN conservée ; incrément limité ; disponible inchangé sans réservation ; ouverture bloquée refusée |
| Recherche | Quantité issue du journal, sans stock legacy parallèle |
| Checkout | Sortie/allocation exactes ; autre boutique et ancien panier préservés ; survente refusée avec rollback complet |
| Achat | Réception, lot et boutique tracés ; quantité legacy inchangée ; péremption inconnue bloquée |
| Protections | Document d'une boutique non affectée exclu ; réécriture historique et écriture directe de stock refusées |
| Import | Réception journalisée ; quantité legacy zéro ; absence de seconde comptabilité de stock |
| Fin de session | Toutes les fixtures annulées et données de la copie préservées |

`php -l` réussi sur tous les fichiers PHP modifiés/ajoutés ; JSON FR/EN valide ; `git diff --check` réussi ; `artisan view:cache` réussi ; build Vite réussi. Une première lecture JSON PowerShell sans `-AsHashtable` refusait des clés préexistantes différant seulement par casse (`Remember Me`/`Remember me`) ; contrôle relancé avec `-AsHashtable`, sans modifier ces clés.

Limites de vérification : pas de course réelle de deux connexions de checkout et pas de revue navigateur authentifiée des écrans. Les contrôles ponctuels du service et des contrôleurs ne constituent pas une validation visuelle. La logique existante de panier entier, tarifs/promotions, calcul financier, tickets et paiements reste à sa Phase 4 ; le raccordement stock n'effectue pas cette refonte.

## Commits propriétaire séparés

1. Commit 3.B : les dix chemins du bilan 3.B ; inclure les deux fichiers neufs (commande et migration) ainsi que le rapport.
2. Commit 3.C : les autres modifications, dont ce rapport, `app/Casts/StockDateTime.php`, les services de réception/projection, les supports de contexte/document, migration de raccordement, contrôleurs/requêtes/modèles, traductions, vues, `resources/js/app.jsx` et les assets Vite générés dans `public/build` (remplacements et manifeste).

Les fichiers 3.B/3.C sont séparés pour permettre ces deux commits sans nouveau commit 3.A. Aucun script de contrôle, mapping, manifeste ni backup privé n'est à ajouter à Git.

Prochaine passe : 3.D, fournisseurs/achats multi-conditionnements, `PurchaseService`, dettes/échéances/paiements et correction du `down()` fournisseurs ; puis 3.E, CSV/doublons, transferts, inventaires et validation motivée des ouvertures. Les fichiers achats 3.C seront repris pour 3.D après conservation de cette frontière de commit.

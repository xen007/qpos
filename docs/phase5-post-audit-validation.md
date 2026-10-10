# Bilan final — corrections et fonctions Phase 5

**176 OK / 0 BUG restant confirmé / 0 AMÉLIORATION ouverte / 1 NON IMPLÉMENTÉ / 2 NON EXÉCUTÉS**, sur les 179 cas de référence. **146/146 régressions demandées réussies**. Ce total décrit les scénarios exécutés ; il ne certifie pas toutes les situations possibles ni la production.

Base Git : main, HEAD 25f27f5, changements locaux conservés. Copie de simulation : qpos_test_phase5_20261009, port 8122. Installation neuve contrôlée : qpos_test_phase5_fresh_20261010. Source du propriétaire : qpos, port 8000. Aucun commit effectué.

## État des sous-lots

| Lot | Résultat et preuve |
|---|---|
| Préparation | Sauvegarde restaurée et comparée ; cible de copie gardée avant les écritures ; baseline.json. |
| 1 — anomalies et connexion | OK : trésorerie virement 8000 une fois, mono ID4 sans élargissement de droits, tri prix/stock asc/desc, catalogue mobile, achats 60.22. Connexions normales admin/vendeuse/caissière vérifiées ; stage1-proof.json et ui-resume-evidence.json. |
| 2 — paiements | OK : canaux manuels, références, allocations, remboursements par origine, espèces physiques ; stage2-proof.json. UI mixte #61 : 2500 espèces + 2500 OM, seul 2500 en caisse ; final-acceptance-proof.json. |
| 3 — répartition | OK : vente unique vs allocations, flux, filtres vendeur/caissière/session/boutique/période et exports ; stage3-4-proof.json et final-acceptance-proof.json. Comparer deux périodes signifie consulter/exporter chacune, sans écran de comparaison côte à côte ajouté. |
| 4 — rôles cumulables | OK : ajout/retrait immédiat, refus escalade admin et boutique non affectée ; stage3-4-proof.json. |
| 5 — passation | OK : tables normalisées, pas de réservation, clés, bail/expiration, refus/revalidation, auteurs et concurrence réelle ; stage5-proof.json et final-targeted-proof.json. Parcours UI #60 vendeur39/caissier40 puis clôture #18 ; ui-role-layout-proof.json. |
| 6 — supervision | OK : activité réelle, pause distincte, comptage/motif/audit/idempotence et refus403 ; stage6-7-proof.json. Aucune clôture automatique de caisse. |
| 7 — mono/multi | OK : transitions1→2 et2→1 sans perte historique, refus désactivation si état opérationnel ; stage6-7-proof.json. |
| 8 — fonctions et reports | OK : dettes natives en retard, reste dû/date métier/badge/filtre. Filtres catalogue Phase6 ; bons intelligents et assurances à définir Phase7. |

## Règles et corrections complémentaires

- XAF natif affiché à deux décimales selon la décision actuelle ; 4.9.8/4.9.9 réévalués. Précision métier inchangée ; BDT/fuseau historiques non convertis ; coûts inconnus exclus de la valorisation et marge provisoire signalée.
- Recherche DataTables corrigée : le champ virtuel DT_RowIndex provoquait un SQL500 ; filtrage paramétré nom/SKU. Recherche réelle « Ciment » donne deux produits, stock97 après trois ventes UI.
- Catalogue mobile : largeur inline DataTables corrigée et coupure normale. Vérification390×844 ; cinq vues à768×1024 et1440×1000 sans dépassement mesuré.
- Mode sombre : courbes et barres utilisent la couleur de premier plan de marque pour rester visibles. Champs/bouton échantillonnés : contraste minimal5.47 ; captures réelles inspectées. Contrôle ciblé, pas certification accessibilité complète.
- Compilation sous sandbox omettait des utilitaires Tailwind malgré un code de sortie0. Build local complet et garde scripts/check-build-css.mjs ajoutée ; build final46.67k CSS réussi.
- Paiement fournisseur espèces et annulation exigent cash_session_manage. Libellés supervision et client de passage traduits.

## Scénarios par ID

Les attentes détaillées, entrées, sorties et snapshots avant/après sont conservés dans les JSONL privés ; cette table donne le verdict actuel et la preuve de reprise. Les preuves de service/HTTP ne sont pas présentées comme des clics UI. Les ventes UI #59/#60/#61 ont leurs propres snapshots indépendants.

| ID | Scénario | Verdict actuel | Preuve |
|---|---|---|---|
| 4.1.1 | Produit et trois conditionnements | OK | results.jsonl : 4.1.1 ; assertions et snapshots avant/après |
| 4.1.2 | Deux conditionnements, deux lignes | OK | results.jsonl : 4.1.2 ; assertions et snapshots avant/après |
| 4.1.3 | Modification prix nouvelle proposition | OK | results.jsonl : 4.1.3 ; assertions et snapshots avant/après |
| 4.1.4 | Prix courant ne réécrit pas vente passée | OK | results.jsonl : 4.1.4 ; assertions et snapshots avant/après |
| 4.1.5 | Désactivation exclut nouvelle vente | OK | results.jsonl : 4.1.5 ; assertions et snapshots avant/après |
| 4.1.6 | Suppression utilisée refusée | OK | results.jsonl : 4.1.6 ; assertions et snapshots avant/après |
| 4.1.7 | Suppression inutilisée sans conditionnement | OK | results.jsonl : 4.1.7 ; assertions et snapshots avant/après |
| 4.1.8 | Recherche par nom | OK | results.jsonl : 4.1.8 ; assertions et snapshots avant/après |
| 4.1.9 | Code-barres actif exact | OK | results.jsonl : 4.1.9 ; assertions et snapshots avant/après |
| 4.1.10 | SKU et barcode dupliqués refusés | OK | results.jsonl : 4.1.10 ; assertions et snapshots avant/après |
| 4.1.11 | Filtres catégorie et état du catalogue | NON IMPLEMENTE | Report explicite Phase 6, docs/roadmap.md |
| 4.1.12 | Tri numérique prix et quantité | OK | stage1-proof.json |
| 4.1.13 | Pagination sans doublons | OK | results.jsonl : 4.1.13 ; assertions et snapshots avant/après |
| 4.1.14 | Facteur nul/négatif refusé | OK | results.jsonl : 4.1.14 ; assertions et snapshots avant/après |
| 4.1.15 | Prix négatif et quantité initiale refusés | OK | results.jsonl : 4.1.15 ; assertions et snapshots avant/après |
| 4.2.1 | Réception lot coût péremption | OK | results.jsonl : 4.2.1 ; assertions et snapshots avant/après |
| 4.2.2 | Réception partielle et reliquat | OK | results.jsonl : 4.2.2 ; assertions et snapshots avant/après |
| 4.2.3 | Lot automatique ouverture inconnue | OK | results.jsonl : 4.2.3 ; assertions et snapshots avant/après |
| 4.2.4 | FEFO et FIFO des lots | OK | results.jsonl : 4.2.4 ; assertions et snapshots avant/après |
| 4.2.5 | Transfert complet conserve lots | OK | results.jsonl : 4.2.5 ; assertions et snapshots avant/après |
| 4.2.6 | Transfert partiel et reliquat | OK | results.jsonl : 4.2.6 ; assertions et snapshots avant/après |
| 4.2.7 | Inventaire écart traçable | OK | results.jsonl : 4.2.7 ; assertions et snapshots avant/après |
| 4.2.8 | Ajustement positif motivé | OK | results.jsonl : 4.2.8 ; assertions et snapshots avant/après |
| 4.2.9 | Ajustement négatif et garde-fous | OK | results.jsonl : 4.2.9 ; assertions et snapshots avant/après |
| 4.2.10 | Stock bas disponible | OK | results.jsonl : 4.2.10 ; assertions et snapshots avant/après |
| 4.2.11 | Rupture refuse sortie | OK | results.jsonl : 4.2.11 ; assertions et snapshots avant/après |
| 4.2.12 | Proche péremption et expiré séparés | OK | results.jsonl : 4.2.12 ; assertions et snapshots avant/après |
| 4.3.1 | Comptant passage exact | OK | results.jsonl : 4.3.1 ; assertions et snapshots avant/après |
| 4.3.2 | Client enregistré | OK | results.jsonl : 4.3.2 ; assertions et snapshots avant/après |
| 4.3.4 | Remise manuelle | OK | results.jsonl : 4.3.4 ; assertions et snapshots avant/après |
| 4.3.5 | Espèces et monnaie | OK | results.jsonl : 4.3.5 ; assertions et snapshots avant/après |
| 4.3.6 | Carte externe | OK | results.jsonl : 4.3.6 ; assertions et snapshots avant/après |
| 4.3.7 | Paiement mixte | OK | results.jsonl : 4.3.7 ; assertions et snapshots avant/après |
| 4.3.10 | Dette client avec échéance | OK | results.jsonl : 4.3.10 ; assertions et snapshots avant/après |
| 4.3.3 | Multi-conditionnements même produit | OK | results.jsonl : 4.3.3 ; assertions et snapshots avant/après |
| 4.3.8 | Paiement orange_money | OK | stage2-proof.json |
| 4.3.9 | Paiement mtn_mobile_money | OK | stage2-proof.json |
| 4.3.11 | Dette passage refusée | OK | results.jsonl : 4.3.11 ; assertions et snapshots avant/après |
| 4.3.12 | Utiliser avoir client | OK | results.jsonl : 4.3.12 ; assertions et snapshots avant/après |
| 4.3.13 | Avoir passage refusé | OK | results.jsonl : 4.3.13 ; assertions et snapshots avant/après |
| 4.3.14 | Rupture après mise au panier | OK | results.jsonl : 4.3.14 ; assertions et snapshots avant/après |
| 4.3.15 | Vente expirée avec confirmation | OK | results.jsonl : 4.3.15 ; assertions et snapshots avant/après |
| 4.3.16 | Retour partiel | OK | results.jsonl : 4.3.16 ; assertions et snapshots avant/après |
| 4.3.17 | Retour total | OK | results.jsonl : 4.3.17 ; assertions et snapshots avant/après |
| 4.3.18 | Sur-retour refusé | OK | results.jsonl : 4.3.18 ; assertions et snapshots avant/après |
| 4.3.19 | Échange différence payable | OK | results.jsonl : 4.3.19 ; assertions et snapshots avant/après |
| 4.3.20 | Échange avec reliquat avoir | OK | results.jsonl : 4.3.20 ; assertions et snapshots avant/après |
| 4.3.21 | Annulation contrepassée | OK | results.jsonl : 4.3.21 ; assertions et snapshots avant/après |
| 4.3.22 | Deux caissiers concurrents dernier stock | OK | results.jsonl : 4.3.22 ; assertions et snapshots avant/après |
| 4.3.23 | Encaissement rejoué même clé | OK | results.jsonl : 4.3.23 ; assertions et snapshots avant/après |
| 4.3.24 | Même clé contenu différent | OK | results.jsonl : 4.3.24 ; assertions et snapshots avant/après |
| 4.3.25 | Paiement fractionnaire XAF refusé | OK | results.jsonl : 4.3.25 ; assertions et snapshots avant/après |
| 4.4.1 | Ouverture explicite avec fond | OK | results.jsonl : 4.4.1 ; assertions et snapshots avant/après |
| 4.4.2 | Pause indicative ne ferme pas caisse | OK | results.jsonl : 4.4.2 ; assertions et snapshots avant/après |
| 4.4.3 | Reprise conserve session | OK | results.jsonl : 4.4.3 ; assertions et snapshots avant/après |
| 4.4.4 | Clôture session écart 0 | OK | results.jsonl : 4.4.4 ; assertions et snapshots avant/après |
| 4.4.5 | Clôture session écart 50 | OK | results.jsonl : 4.4.5 ; assertions et snapshots avant/après |
| 4.4.6 | Clôture session écart -50 | OK | results.jsonl : 4.4.6 ; assertions et snapshots avant/après |
| 4.4.7 | Clôture manuelle ne ferme pas caisse | OK | results.jsonl : 4.4.7 ; assertions et snapshots avant/après |
| 4.4.8 | Auto23h55 sessions ouvertes | OK | results.jsonl : 4.4.8 ; assertions et snapshots avant/après |
| 4.4.9 | Tardif original immuable correction | OK | results.jsonl : 4.4.9 ; assertions et snapshots avant/après |
| 4.4.10 | Correction notifiée sans double silencieux | OK | results.jsonl : 4.4.10 ; assertions et snapshots avant/après |
| 4.4.11 | Clôtures concurrentes idempotentes | OK | results.jsonl : 4.4.11 ; assertions et snapshots avant/après |
| 4.4.12 | Échec livraison puis retry simulé | OK | results.jsonl : 4.4.12 ; assertions et snapshots avant/après |
| 4.4.13 | Deux sessions parallèles soldes séparés | OK | results.jsonl : 4.4.13 ; assertions et snapshots avant/après |
| 4.4.14 | Session orpheline traitement dédié | OK | stage6-7-proof.json |
| 4.4.15 | Mouvement et dépense exacts | OK | results.jsonl : 4.4.15 ; assertions et snapshots avant/après |
| 4.5.1 | Commande fournisseur et snapshot conditionnement | OK | results.jsonl : 4.5.1 ; assertions et snapshots avant/après |
| 4.5.2 | Précision native achat XAF | OK | results.jsonl : 4.5.2 ; assertions et snapshots avant/après |
| 4.5.3 | Amendement avant réception tracé | OK | results.jsonl : 4.5.3 ; assertions et snapshots avant/après |
| 4.5.4 | Réception partielle4 reliquat6 | OK | results.jsonl : 4.5.4 ; assertions et snapshots avant/après |
| 4.5.5 | Réception totale du reliquat | OK | results.jsonl : 4.5.5 ; assertions et snapshots avant/après |
| 4.5.6 | Deux lots et deux péremptions | OK | results.jsonl : 4.5.6 ; assertions et snapshots avant/après |
| 4.5.7 | Réception excessive et invalide atomiquement refusées | OK | results.jsonl : 4.5.7 ; assertions et snapshots avant/après |
| 4.5.8 | Paiement fournisseur partiel | OK | results.jsonl : 4.5.8 ; assertions et snapshots avant/après |
| 4.5.9 | Paiement fournisseur total | OK | results.jsonl : 4.5.9 ; assertions et snapshots avant/après |
| 4.5.10 | Dette fournisseur et refus surpaiement | OK | results.jsonl : 4.5.10 ; assertions et snapshots avant/après |
| 4.5.11 | Annulation avant réception | OK | results.jsonl : 4.5.11 ; assertions et snapshots avant/après |
| 4.5.12 | Annulation réception non consommée contrepassée | OK | results.jsonl : 4.5.12 ; assertions et snapshots avant/après |
| 4.5.13 | Deux réceptions concurrentes même commande | OK | results.jsonl : 4.5.13 ; assertions et snapshots avant/après |
| 4.5.14 | Réception idempotente et contenu différent refusé | OK | results.jsonl : 4.5.14 ; assertions et snapshots avant/après |
| 4.5.15 | Deux achats distincts concurrents | OK | results.jsonl : 4.5.15 ; assertions et snapshots avant/après |
| 4.6.1 | Dette native client et journal | OK | results.jsonl : 4.6.1 ; assertions et snapshots avant/après |
| 4.6.2 | Recouvrement partiel | OK | results.jsonl : 4.6.2 ; assertions et snapshots avant/après |
| 4.6.3 | Recouvrement total | OK | results.jsonl : 4.6.3 ; assertions et snapshots avant/après |
| 4.6.4 | Recouvrement excessif refusé | OK | results.jsonl : 4.6.4 ; assertions et snapshots avant/après |
| 4.6.5 | Recouvrement idempotent | OK | results.jsonl : 4.6.5 ; assertions et snapshots avant/après |
| 4.6.6 | Échéance dépassée et information utilisateur | OK | stage6-7-proof.json |
| 4.6.7 | Relance interne sans communication réelle | OK | results.jsonl : 4.6.7 ; assertions et snapshots avant/après |
| 4.6.8 | Avoir issu de retour autorisé | OK | results.jsonl : 4.6.8 ; assertions et snapshots avant/après |
| 4.6.9 | Avoir utilisé partiellement puis totalement | OK | results.jsonl : 4.6.9 ; assertions et snapshots avant/après |
| 4.6.10 | Avoir refusé dans autre boutique | OK | results.jsonl : 4.6.10 ; assertions et snapshots avant/après |
| 4.6.11 | Passage dette et avoir refusés serveur | OK | results.jsonl : 4.6.11 ; assertions et snapshots avant/après |
| 4.6.12 | Dette historique non réconciliée refusée | OK | results.jsonl : 4.6.12 ; assertions et snapshots avant/après |
| 4.7.1 | Dashboard données et structure | OK | results.jsonl : 4.7.1 ; assertions et snapshots avant/après |
| 4.7.2 | Statistiques avancées et refus permission | OK | results.jsonl : 4.7.2 ; assertions et snapshots avant/après |
| 4.7.3 | Résultat ventes nettes moins CMV moins charges | OK | results.jsonl : 4.7.3 ; assertions et snapshots avant/après |
| 4.7.4 | Rapport seller | OK | results.jsonl : 4.7.4 ; assertions et snapshots avant/après |
| 4.7.5 | Rapport shop | OK | results.jsonl : 4.7.5 ; assertions et snapshots avant/après |
| 4.7.6 | Rapport category | OK | results.jsonl : 4.7.6 ; assertions et snapshots avant/après |
| 4.7.7 | Rapport product | OK | results.jsonl : 4.7.7 ; assertions et snapshots avant/après |
| 4.7.8 | Rapport sales | OK | results.jsonl : 4.7.8 ; assertions et snapshots avant/après |
| 4.7.9 | Profil moyen24h et top3 | OK | results.jsonl : 4.7.9 ; assertions et snapshots avant/après |
| 4.7.10 | Stock valorisé coûts des lots | OK | results.jsonl : 4.7.10 ; assertions et snapshots avant/après |
| 4.7.11 | Coût inconnu exclu et marge signalée | OK | results.jsonl : 4.7.11 ; assertions et snapshots avant/après |
| 4.7.12 | Rapport péremptions et disponibilité | OK | results.jsonl : 4.7.12 ; assertions et snapshots avant/après |
| 4.7.13 | Écarts caisse consolidés | OK | results.jsonl : 4.7.13 ; assertions et snapshots avant/après |
| 4.7.14 | Dépense déduite une seule fois | OK | results.jsonl : 4.7.14 ; assertions et snapshots avant/après |
| 4.7.15 | Historique BDT séparé du XAF | OK | results.jsonl : 4.7.15 ; assertions et snapshots avant/après |
| 4.7.16 | Bornes locales jour semaine mois et minuit | OK | results.jsonl : 4.7.16 ; assertions et snapshots avant/après |
| 4.7.17 | Export Excel données et totaux | OK | results.jsonl : 4.7.17 ; assertions et snapshots avant/après |
| 4.7.18 | Export PDF données et lisibilité | OK | results.jsonl : 4.7.18 ; assertions et snapshots avant/après |
| 4.7.19 | Pagination plafonds export et volume mesuré | OK | results.jsonl : 4.7.19 ; assertions et snapshots avant/après |
| 4.7.20 | Synthèse PDF versions et indicateurs | OK | results.jsonl : 4.7.20 ; assertions et snapshots avant/après |
| 4.8.1 | Mode mono-boutique avec ID non1 | OK | stage1-proof.json |
| 4.8.2 | Ajout boutique par workflow | OK | results.jsonl : 4.8.2 ; assertions et snapshots avant/après |
| 4.8.3 | Affectation et portées | OK | results.jsonl : 4.8.3 ; assertions et snapshots avant/après |
| 4.8.4 | Vente MAIN stock autre intact | OK | results.jsonl : 4.8.4 ; assertions et snapshots avant/après |
| 4.8.5 | Vente boutique2 stock MAIN intact | OK | results.jsonl : 4.8.5 ; assertions et snapshots avant/après |
| 4.8.6 | Consolidation transfert conservation | OK | results.jsonl : 4.8.6 ; assertions et snapshots avant/après |
| 4.8.7 | Vendeur refus boutique par requête directe | OK | results.jsonl : 4.8.7 ; assertions et snapshots avant/après |
| 4.8.8 | Admin consolidation boutiques affectées | OK | results.jsonl : 4.8.8 ; assertions et snapshots avant/après |
| 4.8.9 | Sessions et journées séparées | OK | results.jsonl : 4.8.9 ; assertions et snapshots avant/après |
| 4.8.10 | Exports même portée que écran | OK | results.jsonl : 4.8.10 ; assertions et snapshots avant/après |
| 4.10.1 | Compte suspendu refusé | OK | results.jsonl : 4.10.1 ; assertions et snapshots avant/après |
| 4.10.2 | Rôle sans permission route directe | OK | results.jsonl : 4.10.2 ; assertions et snapshots avant/après |
| 4.10.3 | Boutique non affectée ID manipulé | OK | results.jsonl : 4.10.3 ; assertions et snapshots avant/après |
| 4.10.4 | Documents autre boutique par ID | OK | results.jsonl : 4.10.4 ; assertions et snapshots avant/après |
| 4.10.5 | Exports sans droits et portée détournée | OK | results.jsonl : 4.10.5 ; assertions et snapshots avant/après |
| 4.10.6 | CSRF absent et invalide | OK | results.jsonl : 4.10.6 ; assertions et snapshots avant/après |
| 4.10.7 | Entrée SQL inoffensive recherche | OK | results.jsonl : 4.10.7 ; assertions et snapshots avant/après |
| 4.10.8 | Encodage HTML persistant inoffensif | OK | results.jsonl : 4.10.8 ; assertions et snapshots avant/après |
| 4.10.9 | Upload interdit inoffensif | OK | results.jsonl : 4.10.9 ; assertions et snapshots avant/après |
| 4.10.10 | Totaux prix utilisateur boutique falsifiés | OK | results.jsonl : 4.10.10 ; assertions et snapshots avant/après |
| 4.11.1 | Sélecteur moyens et montant proposé | OK | stage2-proof.json |
| 4.11.2 | Traçage orange_money | OK | stage2-proof.json |
| 4.11.3 | Traçage mtn_mobile_money | OK | stage2-proof.json |
| 4.11.4 | Traçage wave | OK | stage2-proof.json |
| 4.11.5 | Traçage mixed-orange | OK | stage2-proof.json |
| 4.11.6 | Reçu paiement carte distinct | OK | results.jsonl : 4.11.6 ; assertions et snapshots avant/après |
| 4.11.7 | Caisse physique exclut non espèces | OK | stage2-proof.json |
| 4.12.1 | Caissière autorisée vend encaisse | OK | results.jsonl : 4.12.1 ; assertions et snapshots avant/après |
| 4.12.2 | Vendeuse prépare et ouverture refusée | OK | results.jsonl : 4.12.2 ; assertions et snapshots avant/après |
| 4.12.3 | Union de deux rôles configurés | OK | results.jsonl : 4.12.3 ; assertions et snapshots avant/après |
| 4.12.4 | Vendeuse route caisse refusée | OK | results.jsonl : 4.12.4 ; assertions et snapshots avant/après |
| 4.12.5 | Caissière sans session motif clair | OK | ui-resume-evidence.json / stage3-4-proof.json / stage6-7-proof.json |
| 4.12.6 | Admin configuration rôles cumulables | OK | stage3-4-proof.json |
| 4.12.7 | Caissières sessions distinctes | OK | results.jsonl : 4.12.7 ; assertions et snapshots avant/après |
| 4.12.8 | Transmission vendeuse caisse | OK | stage5-proof.json |
| 4.13.1 | Répartition mois avec nombre ventes et flux | OK | stage3-4-proof.json |
| 4.13.2 | Filtres jour session vendeur boutique | OK | final-acceptance-proof.json |
| 4.13.3 | Export Excel répartition | OK | stage3-4-proof.json |
| 4.13.4 | Export PDF répartition | OK | stage3-4-proof.json |
| 4.13.5 | Comparaison périodes par moyen | OK | final-acceptance-proof.json |
| R.1 | Parcours combiné préparation prise en charge encaissement clôture | OK | ui-resume-evidence.json / stage3-4-proof.json / stage6-7-proof.json |
| R.2 | Préparation transmission vendeuse | OK | stage5-proof.json |
| R.3 | Reprise vente par caissière | OK | stage5-proof.json |
| R.4 | Concurrence reprise même vente | OK | stage5-proof.json |
| R.5 | Revalidation prix stock avant paiement | OK | stage5-proof.json |
| R.6 | Abandon vente transmise | OK | stage5-proof.json |
| R.7 | Attribution vendeuse et caissière | OK | stage5-proof.json |
| R.8 | Supervision admin et portées des trois rôles | OK | ui-resume-evidence.json / stage3-4-proof.json / stage6-7-proof.json |
| 4.9.1 | POS et panier390x844 | OK | ui-resume-evidence.json |
| 4.9.2 | Dashboard statistiques mobile | OK | results.jsonl : 4.9.2 ; assertions et snapshots avant/après |
| 4.9.3 | Catalogue et étiquettes mobile | OK | ui-resume-evidence.json |
| 4.9.4 | Ticket mobile et impression | NON EXECUTE | PDF et captures inspectés ; imprimante/scanner physique indisponibles |
| 4.9.5 | Cinq vues tablette768 | OK | ui-resume-evidence.json / stage3-4-proof.json / stage6-7-proof.json |
| 4.9.6 | Cinq vues desktop1440 | OK | ui-resume-evidence.json / stage3-4-proof.json / stage6-7-proof.json |
| 4.9.7 | Thèmes réels, contraste des champs et graphiques inspectés | OK | ui-resume-evidence.json / stage3-4-proof.json / stage6-7-proof.json |
| 4.9.8 | Format montants fr | OK | ui-resume-evidence.json |
| 4.9.9 | Format montants en | OK | ui-resume-evidence.json |
| 4.9.10 | Auto-remplissage espèces et saisie préservée | OK | ui-resume-evidence.json |
| 4.9.11 | Motifs bouton désactivé | OK | ui-resume-evidence.json / stage3-4-proof.json / stage6-7-proof.json |
| 4.9.12 | Scan connu inconnu et invalide sans verrou permanent | OK | ui-resume-evidence.json |
| 4.9.13 | Défauts et deux conditionnements | OK | ui-resume-evidence.json |
| 4.9.14 | Cibles44px et focus clavier | OK | ui-resume-evidence.json |
| 4.9.15 | Quatre formats étiquettes et matériel | NON EXECUTE | PDF et captures inspectés ; imprimante/scanner physique indisponibles |
| E.1 | Trésorerie omet virements fournisseur | OK | stage1-proof.json |
| E.2 | Achats affiche six décimales | OK | stage1-proof.json |

## Limites et intégrité

- BUGS CRITIQUES : aucun confirmé restant dans le périmètre testé. BUGS MINEURS : aucun confirmé restant. AMÉLIORATIONS : aucune ouverte dans ce lot ; reports ci-dessous.
- NON IMPLÉMENTÉ : 4.1.11 filtres catalogue avancés, Phase6. Commandes intelligentes et assurances à préciser, Phase7, hors de ce lot.
- NON EXÉCUTÉS : 4.9.4/4.9.15 pour impression et scanner physiques. Rendus ticket/PDF contrôlés. SMTP, API opérateurs et règlements réels non exécutés ; aucun email ni paiement réel.
- Des interactions de navigateur ont parfois nécessité un rechargement après navigation. Cause intermittente non attribuée ; parcours finaux effectués et vérifiés, sans revendiquer sa résolution.
- Intégrité source : .env et personal_access_tokens inchangés. Deux tables divergent depuis le relevé initial : daily_summaries, reporting_runtime. Origine non attribuée ; ne pas présenter toute la source comme inchangée. Les scripts de ce lot gardent la connexion aux copies.
- Volumes/pagination : échantillon et cas1001dépenses ; limites export vérifiées. Aucun benchmark de charge ni pentest exhaustif.
- Les copies contiennent les mutations de simulation. Ne pas les importer telles quelles dans la base de démonstration du propriétaire. Les migrations additives de ce lot ne sont pas encore appliquées à qpos.

## Livrables et préparation du commit

Guide : [phase5-post-audit-guide.md](C:/xampp/htdocs/qpos/docs/phase5-post-audit-guide.md). La procédure conserve les données et prévoit sauvegarde/restauration vérifiée avant migration source, après acceptation du bilan. Ne pas utiliser migrate:fresh sur qpos. Le propriétaire réalise l’unique commit.

Captures et exports : storage/app/phase5-corrections/ (privé, ignoré par Git). Reçus59/60/61, catalogue mobile final, dashboard sombre corrigé, rapports PDF/Excel. Checkpoint canonique : storage/app/session-b/checkpoint.md.

## Contrôles finaux et état de livraison

Build Vite complet et garde CSS réussis. Syntaxe de 60 fichiers PHP modifiés/ajoutés vérifiée ; JSON de traduction valide ; git diff --check réussi. final-static-proof.json conserve les résultats. Le lot logiciel est prêt pour revue et pour l’unique commit du propriétaire, avec les limites et reports ci-dessus. La migration de la base source demeure une étape de déploiement après acceptation du bilan.

## Fichiers modifiés ou ajoutés

- `M` app/Console/Kernel.php
- `M` app/Http/Controllers/AuthController.php
- `M` app/Http/Controllers/Backend/PointOfSaleController.php
- `M` app/Http/Controllers/Backend/Pos/OrderController.php
- `M` app/Http/Controllers/Backend/Product/ProductController.php
- `M` app/Http/Controllers/Backend/Product/PurchaseController.php
- `M` app/Http/Controllers/Backend/ReportingController.php
- `M` app/Http/Controllers/Backend/SaleWorkflowController.php
- `M` app/Http/Controllers/Backend/UserManagementController.php
- `M` app/Http/Middleware/SetPointOfSaleContext.php
- `M` app/Http/Requests/StoreUserRequest.php
- `M` app/Http/Requests/UpdateUserRequest.php
- `M` app/Models/CashSession.php
- `M` app/Models/PointOfSale.php
- `M` app/Policies/PointOfSalePolicy.php
- `M` app/Services/PurchaseService.php
- `M` app/Services/ReportingService.php
- `M` app/Services/SaleCorrectionService.php
- `M` app/Services/SaleService.php
- `M` app/Support/BackendMenu.php
- `M` app/Support/PointOfSaleContext.php
- `M` app/Support/ReportFilter.php
- `M` database/migrations/2023_07_18_170442_create_permission_tables.php
- `M` database/migrations/2026_10_05_180500_fix_dozen_products.php
- `M` database/migrations/2026_10_08_120000_create_sale_and_cash_workflows.php
- `M` database/seeders/CameroonDemoSeeder.php
- `M` database/seeders/RolePermissionSeeder.php
- `M` docs/contrats.md
- `M` docs/roadmap.md
- `M` docs/schema-cible.md
- `M` lang/en.json
- `M` lang/en/reporting.php
- `M` lang/fr.json
- `M` lang/fr/reporting.php
- `M` package.json
- `D` public/build/assets/Pos-CyU7YmAp.js
- `D` public/build/assets/Purchase-C-wcLOy6.js
- `D` public/build/assets/app-BwFd7jaY.js
- `D` public/build/assets/app-D0oAXB21.css
- `D` public/build/assets/dashboard-BMBCK3Rg.js
- `M` public/build/manifest.json
- `M` resources/css/workspaces.css
- `M` resources/js/components/Pos.jsx
- `M` resources/js/dashboard.js
- `M` resources/views/backend/orders/index.blade.php
- `M` resources/views/backend/phase4/collection.blade.php
- `M` resources/views/backend/phase4/receipt.blade.php
- `M` resources/views/backend/phase4/returns.blade.php
- `M` resources/views/backend/phase4/sale-document.blade.php
- `M` resources/views/backend/purchase/products.blade.php
- `M` resources/views/backend/reporting/dashboard.blade.php
- `M` resources/views/backend/reporting/filters.blade.php
- `M` resources/views/backend/reporting/metrics.blade.php
- `M` resources/views/backend/reporting/peaks.blade.php
- `M` resources/views/backend/reporting/report-pdf.blade.php
- `M` resources/views/backend/reporting/report.blade.php
- `M` resources/views/backend/reporting/statistics.blade.php
- `M` resources/views/backend/reporting/summary-pdf.blade.php
- `M` resources/views/backend/shops/form.blade.php
- `M` resources/views/backend/users/create.blade.php
- `M` resources/views/backend/users/edit.blade.php
- `M` routes/web.php
- `??` app/Console/Commands/ExpirePendingSales.php
- `??` app/Http/Controllers/Backend/CashSupervisionController.php
- `??` app/Http/Controllers/Backend/PendingSaleController.php
- `??` app/Services/CashSupervisionService.php
- `??` app/Services/PaymentMethodService.php
- `??` app/Services/PaymentReportService.php
- `??` app/Services/PendingSaleService.php
- `??` app/Services/ShopWorkflowSettings.php
- `??` app/Support/RoleAssignment.php
- `??` database/migrations/2026_10_09_230000_add_post_audit_workflows.php
- `??` docs/phase5-post-audit-guide.md
- `??` docs/phase5-post-audit-validation.md
- `??` public/build/assets/Pos-DXX6xFm5.js
- `??` public/build/assets/Purchase-Clqs9bqu.js
- `??` public/build/assets/app-Bb-Zrv12.css
- `??` public/build/assets/app-CRTGVh99.js
- `??` public/build/assets/dashboard-C_qXReaE.js
- `??` resources/views/backend/phase5/cash-supervision.blade.php
- `??` resources/views/backend/phase5/pending-sales.blade.php
- `??` resources/views/backend/users/roles.blade.php
- `??` scripts/check-build-css.mjs

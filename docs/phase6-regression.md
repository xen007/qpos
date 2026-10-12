# Phase 6 — régression réellement exécutée

12/10/2026. Copie `qpos_test_phase6_20261012`, transport mémoire, aucun paiement réel. Les quatre premières assertions invalides de date/portée restent conservées dans les preuves privées ; les reprises utilisent des fixtures cohérentes. Les verdicts de la campagne Phase 5 ne sont pas recyclés.

Total : 146 cas documentés ; {"OK":146}.

| ID | Contrôle | Résultat |
|---|---|---|
| 4.1.1 | Produit et trois conditionnements | OK |
| 4.1.2 | Deux conditionnements, deux lignes | OK |
| 4.1.3 | Modification prix nouvelle proposition | OK |
| 4.1.4 | Prix courant ne réécrit pas vente passée | OK |
| 4.1.5 | Désactivation exclut nouvelle vente | OK |
| 4.1.6 | Suppression utilisée refusée | OK |
| 4.1.7 | Suppression inutilisée sans conditionnement | OK |
| 4.1.8 | Recherche par nom | OK |
| 4.1.9 | Code-barres actif exact | OK |
| 4.1.10 | SKU et barcode dupliqués refusés | OK |
| 4.1.13 | Pagination sans doublons | OK |
| 4.1.14 | Facteur nul/négatif refusé | OK |
| 4.1.15 | Prix négatif et quantité initiale refusés | OK |
| 4.2.1 | Réception lot coût péremption | OK |
| 4.2.2 | Réception partielle et reliquat | OK |
| 4.2.3 | Lot automatique ouverture inconnue | OK |
| 4.2.4 | FEFO et FIFO des lots | OK |
| 4.2.5 | Transfert complet conserve lots | OK |
| 4.2.6 | Transfert partiel et reliquat | OK |
| 4.2.7 | Inventaire écart traçable | OK |
| 4.2.8 | Ajustement positif motivé | OK |
| 4.2.9 | Ajustement négatif et garde-fous | OK |
| 4.2.10 | Stock bas disponible | OK |
| 4.2.11 | Rupture refuse sortie | OK |
| 4.2.12 | Proche péremption et expiré séparés | OK |
| 4.3.1 | Comptant passage exact | OK |
| 4.3.2 | Client enregistré | OK |
| 4.3.4 | Remise manuelle | OK |
| 4.3.5 | Espèces et monnaie | OK |
| 4.3.6 | Carte externe | OK |
| 4.3.7 | Paiement mixte | OK |
| 4.3.10 | Dette client avec échéance | OK |
| 4.3.3 | Multi-conditionnements même produit | OK |
| 4.3.11 | Dette passage refusée | OK |
| 4.3.12 | Utiliser avoir client | OK |
| 4.3.13 | Avoir passage refusé | OK |
| 4.3.14 | Rupture après mise au panier | OK |
| 4.3.15 | Vente expirée avec confirmation | OK |
| 4.3.16 | Retour partiel | OK |
| 4.3.17 | Retour total | OK |
| 4.3.18 | Sur-retour refusé | OK |
| 4.3.19 | Échange différence payable | OK |
| 4.3.20 | Échange avec reliquat avoir | OK |
| 4.3.21 | Annulation contrepassée | OK |
| 4.3.22 | Deux caissiers concurrents dernier stock | OK |
| 4.3.23 | Encaissement rejoué même clé | OK |
| 4.3.24 | Même clé contenu différent | OK |
| 4.3.25 | Paiement fractionnaire XAF refusé | OK |
| 4.4.1 | Ouverture explicite avec fond | OK |
| 4.4.2 | Pause indicative ne ferme pas caisse | OK |
| 4.4.3 | Reprise conserve session | OK |
| 4.4.4 | Clôture session écart 0 | OK |
| 4.4.5 | Clôture session écart 50 | OK |
| 4.4.6 | Clôture session écart -50 | OK |
| 4.4.7 | Clôture manuelle ne ferme pas caisse | OK |
| 4.4.8 | Auto 23h55 — dates de sessions cohérentes | OK |
| 4.4.9 | Tardif original immuable correction | OK |
| 4.4.10 | Correction notifiée sans double silencieux | OK |
| 4.4.11 | Clôtures concurrentes idempotentes | OK |
| 4.4.12 | Échec livraison puis retry simulé | OK |
| 4.4.13 | Deux sessions parallèles soldes séparés | OK |
| 4.4.15 | Mouvement et dépense exacts | OK |
| 4.5.1 | Commande fournisseur et snapshot conditionnement | OK |
| 4.5.2 | Précision native achat XAF | OK |
| 4.5.3 | Amendement avant réception tracé | OK |
| 4.5.4 | Réception partielle4 reliquat6 | OK |
| 4.5.5 | Réception totale du reliquat | OK |
| 4.5.6 | Deux lots et deux péremptions | OK |
| 4.5.7 | Réception excessive et invalide atomiquement refusées | OK |
| 4.5.8 | Paiement fournisseur partiel | OK |
| 4.5.9 | Paiement fournisseur total | OK |
| 4.5.10 | Dette fournisseur et refus surpaiement | OK |
| 4.5.11 | Annulation avant réception | OK |
| 4.5.12 | Annulation réception non consommée contrepassée | OK |
| 4.5.13 | Deux réceptions concurrentes même commande | OK |
| 4.5.14 | Réception idempotente et contenu différent refusé | OK |
| 4.5.15 | Deux achats distincts concurrents | OK |
| 4.6.1 | Dette native client et journal | OK |
| 4.6.2 | Recouvrement partiel | OK |
| 4.6.3 | Recouvrement total | OK |
| 4.6.4 | Recouvrement excessif refusé | OK |
| 4.6.5 | Recouvrement idempotent | OK |
| 4.6.7 | Relance interne sans communication réelle | OK |
| 4.6.8 | Avoir issu de retour autorisé | OK |
| 4.6.9 | Avoir utilisé partiellement puis totalement | OK |
| 4.6.10 | Avoir refusé dans autre boutique | OK |
| 4.6.11 | Passage dette et avoir refusés serveur | OK |
| 4.6.12 | Dette historique non réconciliée refusée | OK |
| 4.7.1 | Dashboard données et structure | OK |
| 4.7.2 | Statistiques avancées et refus permission | OK |
| 4.7.3 | Résultat sur fixture isolée et charges de journée | OK |
| 4.7.4 | Rapport seller | OK |
| 4.7.5 | Rapport shop | OK |
| 4.7.6 | Rapport category | OK |
| 4.7.7 | Rapport product | OK |
| 4.7.8 | Rapport sales | OK |
| 4.7.9 | Profil moyen24h et top3 | OK |
| 4.7.10 | Stock valorisé coûts des lots | OK |
| 4.7.11 | Coût inconnu exclu et marge signalée | OK |
| 4.7.12 | Péremptions comparées à la date métier courante | OK |
| 4.7.13 | Écarts caisse consolidés | OK |
| 4.7.14 | Dépense unique et formule consolidée | OK |
| 4.7.15 | Historique BDT séparé du XAF | OK |
| 4.7.16 | Bornes locales jour semaine mois et minuit | OK |
| 4.7.17 | Export Excel données et totaux | OK |
| 4.7.18 | Export PDF données et lisibilité | OK |
| 4.7.19 | Pagination plafonds export et volume mesuré | OK |
| 4.7.20 | Synthèse PDF versions et indicateurs | OK |
| 4.8.2 | Ajout boutique par workflow | OK |
| 4.8.3 | Affectation et portées | OK |
| 4.8.4 | Vente MAIN stock autre intact | OK |
| 4.8.5 | Vente boutique2 stock MAIN intact | OK |
| 4.8.6 | Consolidation transfert conservation | OK |
| 4.8.7 | Vendeur refus boutique par requête directe | OK |
| 4.8.8 | Admin consolidation boutiques affectées | OK |
| 4.8.9 | Sessions et journées séparées | OK |
| 4.8.10 | Exports même portée que écran | OK |
| 4.10.1 | Compte suspendu refusé | OK |
| 4.10.2 | Rôle sans permission route directe | OK |
| 4.10.3 | Boutique non affectée ID manipulé | OK |
| 4.10.4 | Documents autre boutique par ID | OK |
| 4.10.5 | Exports sans droits et portée détournée | OK |
| 4.10.6 | CSRF absent et invalide | OK |
| 4.10.7 | Entrée SQL inoffensive recherche | OK |
| 4.10.8 | Encodage HTML persistant inoffensif | OK |
| 4.10.9 | Upload interdit inoffensif | OK |
| 4.10.10 | Totaux prix utilisateur boutique falsifiés | OK |
| 4.11.6 | Reçu carte avec référence | OK |
| 4.12.2 | Jean prépare, ouverture caisse refusée | OK |
| 4.12.3 | Union et retrait réel des rôles | OK |
| 4.12.4 | Jean route directe caisse refusée | OK |
| 4.12.5 | Caissière sans session : refus et motif visible | OK |
| 4.12.7 | Deux caissières, propriété isolée | OK |
| R.1 | Préparation, prise, encaissement et clôture | OK |
| R.8 | Supervision et portée trois rôles | OK |
| 4.12.1 | Parcours complet Marie | OK |
| 4.9.1 | POS et panier 390x844 | OK |
| 4.9.2 | Dashboard et statistiques mobile | OK |
| 4.9.5 | Cinq vues tablette 768 | OK |
| 4.9.6 | Cinq vues bureau 1440 | OK |
| 4.9.7 | Thèmes clair/sombre, champs et graphiques | OK |
| 4.9.10 | Espèces automatiques et saisie préservée | OK |
| 4.9.11 | Motifs du bouton désactivé | OK |
| 4.9.12 | Scans connu, inconnu, invalide puis nouveau scan | OK |
| 4.9.13 | Défauts et deux conditionnements | OK |
| 4.9.14 | Cibles 44px et focus clavier | OK |

## Couverture et limites

Aucun ID des 146 cas retenus de la campagne précédente ne reste sans nouvelle preuve.

Les contrôles Phase 6 distincts couvrent les rôles cumulables, retrait effectif, connexion/déconnexion trois comptes, thèmes et dimensions 390/768/1440. Le scan est simulé au clavier ; les périphériques physiques (scanner, imprimante, étiquettes) ne sont pas certifiés. Les doubles processus caisse/vente/stock/achats et dernier admin sont réellement concurrents ; le déclenchement quotidien repose ici sur l'horloge simulée, pas sur une attente du scheduler Windows à 23h55. Les parcours R.1 et de cumul de rôles ont été observés en base avant annulation volontaire de la transaction privée ; leur persistance après cette annulation n'est pas attendue.

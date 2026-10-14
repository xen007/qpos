# Phase 7 — avancement du 13/10/2026

## Lots vérifiés

- **OK 7.A**, commit `72b75a5` : audit initial, 158 observations sur 78 URL et correction de vue lots automatiques.
- **OK 7.B**, commit `8504ebc` : shop_admin, droits directs avec motif/audit, boutique fixe et références stock. 13 contrôles service/SQL et 20 HTTP sur copie ; fixtures annulées.
- **OK fonctionnel 7.C**, commit `07fb0c7` : composants, POS, dashboard/statistiques. Les limites d'accessibilité ci-dessous restent ouvertes pour 7.H ; aucune certification WCAG globale.

## Livraison 7.C

Boutons 44 px, contrôles/focus, sélecteur boutique mobile. Dashboard : quatre cartes rapides, filtres dépliables, deux graphiques avec valeurs consultables. POS : espèces/autres paiements, monnaie distincte, actions selon capacités, remise autorisée uniquement, onglets clavier. Le vendeur prépare sans champs d'encaissement ni faux reste dû ; la caissière règle. Calculs et arrondis serveur inchangés.

Statistiques : année, comparaison précédente de même durée ou même calendrier année précédente, variation exacte, base nulle sans pourcentage inventé, période en cours annoncée et CMV incomplet signalé. Top/flop cinq produits ayant une activité, retours inclus ; produits sans activité exclus. Ligne de comparaison quotidienne, gestion du 29 février. Excel/PDF : comparaison, produits, jours, trésorerie avec dépenses, profil 24 h, trois pics/creux, stock courant et indicateurs complémentaires. Même autorisation/filtres que l'écran.

Lots automatiques : composants communs, IDs uniques, ancienne saisie/erreur limitée au batch du POST. Le batch_id sert seulement à la présentation ; le service garde son ID de route. Les coûts éditables perdent uniquement leurs zéros terminaux, sans réduction de précision métier.

## Preuves 7.C

- **20 contrôles statistiques/HTTP OK**, statistics-proof.json : ventes 10 000/5 000, SQL, −50 %, classement, dates locales, charges non attribuées au vendeur, année bissextile, base zéro, rendus, exports positifs/négatifs et refus hors boutique avec permission présente. Fixtures annulées par rollback.
- PDF rendu en trois pages, texte extrait et pages inspectées ; aucune superposition/coupure de colonnes observée. Quantité à six décimales corrigée. Excel rapproché des montants fixture.
- Connexions/déconnexions réelles Admin → Jean → Marie sans erreur d'autorisation ni page expirée observée. Admin : panier 5 000 prérempli ; 6 000 remis → 1 000 de monnaie.
- Passation réelle en copie : préparation #2 Jean, vente #7 Marie, session #4. Total 5 000, 3 000 espèces + 2 000 carte simulée ; 4 000 remis/1 000 rendu. SQL : stock 100 → 99, caisse +3 000, deux allocations, dette nulle, journal stock et auteurs conservés. Ticket vérifié. Session fermée manuellement à 3 000, écart zéro. Données de simulation uniquement en copie, ui-sale-proof.json.
- Deux fixtures de lots : rendu non vide, IDs distincts, saisie invalide limitée au bon lot, aucun changement métier ; fixtures retirées après contrôle.
- Dashboard/POS/statistiques/lots mobile390 ; statistiques tablette768 ; dashboard bureau1440 et reflow320. Aucun débordement global observé. Clair/sombre et FR/EN, captures privées.
- Contrastes dashboard : textes/commandes sélectionnés ≥4,5:1 clair/sombre, contrast-light.json et contrast-dark.json. Mesure ciblée, pas tous les états de chaque écran.
- Clavier : tiroir Enter/Escape, onglets POS flèches/Home, dialogue client Tab/Escape et retour du focus ; F2/F4 antérieurement vérifiés, keyboard-proof.json.
- Six syntaxes PHP et JSON FR/EN OK, build Vite et garde CSS OK (2 022 modules). CSS 48,89 kB/9,49 gzip ; POS 22,59/7,05 ; dashboard 205,99/70,49. Aucun package ajouté. Diff check OK.

## Limites pour 7.H

- Zoom navigateur réel 200 % indisponible dans l'outil : reflow320 vérifié, zoom réel à contrôler manuellement. Pas de faux équivalent CSS annoncé.
- Tiroir vérifié au clavier ; clics pointer de l'outil incohérents avec son AX : recontrôle dans navigateur utilisateur visible requis. Commandes explicites ouvrir/fermer conservées.
- Scanner/impression physiques et certification WCAG complète non réalisés.

## Suite

OK 7.D interne, commit d319d9f. Voir phase7-notifications.md. 7.G-H restent à faire ; source non migree, aucun envoi externe/push/seed source.

OK 7.E.1 : 13 controles email quotidien Mail::fake, rollback, aucun envoi. Source SMTP incomplet et envois desactives. Voir phase7-email.md ; 7.E reste en cours.

**OK 7.E**, commit 2286d69 : transports optionnels, réglages/préférences, 29 contrôles factices, concurrence réelle/worker, 21 régressions internes. Voir phase7-email.md, limites SMTP/réception/navigateur explicites. Suite 7.F.

**OK 7.F fonctionnel** commit `60dd423` : sauvegarde complète/archives/restauration, S3 facultatif simulé, rétention, workers et état système. 29 contrôles + 7 rétention/RPO + 5 ZIP + verrou/worker réels ; 39 régressions natives. Voir phase7-backups.md et BACKUP-RESTORE.md. Source non migrée, externes non configurés, nouvelle UI navigateur à contrôler en H. Suite 7.G, ajouts propriétaire toujours ouverts.


**OK fonctionnel 7.G** : UX/exploitation et ajouts propriétaire, 87 contrôles ciblés (83 HTTP/SQL + 4 simulations impression), 39 régressions natives + 29 externes simulées. 27 syntaxes, 2 JSON, 2 scripts Windows CheckOnly, build Vite et garde CSS OK. Voir phase7-exploitation.md ; nouvelle UI visuelle reste ouverte pour H. Source non migrée et tâche Windows non installée. Suite 7.H.

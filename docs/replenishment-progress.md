# Réapprovisionnement — progression

## OK H — livraison du 15/10/2026

Base `main`, `fe7e79d`. Périmètre A–H approuvé le 14/10. Les huit lots sont livrés ; D a précédé C pour fournir la relation fournisseur explicite. [Rapport final](replenishment-report.md).

| Lot | État | Contrôles métier/SQL/HTTP distincts | Commit local |
|---|---|---:|---|
| A — cartographie, contrats, copie | OK | restauration initiale de 93 tables | `fc90aed` |
| B — politiques et alertes | OK | 25 | `3226949` |
| C — filtres, rupture, création rapide | OK | 20 | `873ae62` |
| D — catalogue fournisseurs | OK | 12 | `c2fd08c` |
| E — bons, achat lié, exports | OK | 30 | `c0b160c` |
| F — réceptions, surplus, reliquats | OK | 36 | `5be3474` |
| G — suggestions et planification | OK | 57 | `baad0a0` |
| H — intégration, dates, volumes, impression, livraison | OK | 54 | présent lot |

**234 contrôles distincts réussis**, hors réexécutions, syntaxe, revue visuelle et concurrence. Cinq courses réelles (approbation, réceptions, génération), avec effets SQL vérifiés. Copies restaurées et comparées après les courses. Preuves privées : `storage/app/replenishment`.

Source `qpos` : mise à jour additive de 93 à 102 tables après sauvegarde SQL restaurée et vérifiée. Comptes/mots de passe, affectations, données métier et `.env` préservés. Les neuf nouvelles tables étaient vides après migrations. Aucun fresh, seed de démonstration ou import de fixtures source.

Build Vite réussi (2 023 modules), 50 fichiers PHP vérifiés, scripts inline analysés. Navigateur : dashboard/suggestions/paramètres ; FR/EN, clair/sombre ; mobile 390 × 844 et tablette 768 × 1024 sur les vues précisées dans le rapport. PDF A4 de 40 lignes inspecté sur deux pages.

Exploitation : MariaDB relancé sans changer sa configuration ; caches purgés puis workers rechargés. Un scheduler et un worker par file vérifiés. Ancien lanceur `QPOS-Phase5` désactivé pour ce dossier, remplacé par `QPOS-Operations-75927A3AE77B`. Canaux externes et synthèse email désactivés ; aucune livraison externe en attente au contrôle.

Commits locaux sélectionnés, aucun push. Sauvegardes/preuves conservées. En cas d'interruption, consulter `storage/app/replenishment/checkpoint.md` ; ne pas réappliquer les migrations ni restaurer la source par défaut.

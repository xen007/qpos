# Réapprovisionnement — progression

Base main fe7e79d ; périmètre A–H approuvé le 14/10/2026.

- OK A : cartographie/contrats, copie restaurée 93 tables ; fc90aed.
- OK B : politiques héritées produit/boutique, seuils décimaux, audit, écran FR/EN, alertes unifiées. 25 contrôles service/SQL/HTTP réussis sur copie ; revérifiés le 14/10 à 20:29, rollback.
- OK D (fondation nécessaire avant C) : relation produit/fournisseur multiple, préférence unique sous verrou, conditionnement, délais/minimum/multiple et prix daté. 12 contrôles SQL/HTTP réussis ; c2fd08c. Planification par boutique reste en G.
- OK C : filtres combinés catalogue/POS/achats, badges, blocage rupture réel, création rapide sans stock et conservation du brouillon initial. 14 contrôles SQL/HTTP + 6 vues/refus + 17 scripts inline parsés ; build Vite/CSS OK (2 023 modules). Défaut SQL MariaDB corrigé ; 873ae62. Revue visuelle réelle reste en H.
- OK E fonctionnel : bons brouillon/validation/émission/confirmation/archive, achat lié sans réception, snapshots/historique/versions et exports. 30 contrôles SQL/HTTP + deux processus simultanés (même bon, zéro double achat/stock), fixtures retirées. PDF/XLSX structurels et scripts rendus vérifiés ; c0b160c. Revue visuelle reste en H.
- OK F complet (15/10) : 29 contrôles F.1 + 7 contrôles complémentaires SQL/HTTP réussis, rollback. Deux courses réelles avec 4 processus et barrière commune : même clé rejouée sans doublon ; clés/acteurs distincts avec version commune = une réception et un refus. Stock et reçus contrôlés en base. Copie restaurée par import CMD et empreintes des 99 tables identiques au snapshot pré-course.
- OK G (15/10) : suggestions rationnelles, consommation native nette retours, attentes dédupliquées, brouillons seuls, tâches durables quotidiennes/hebdomadaires, alertes ciblées, paramètres et historique FR/EN. Migration copie :102 tables. 31 contrôles métier/SQL +10 HTTP +16 complémentaires (annulation, produit inactif, curseur, commandes/scheduler, rendus FR/EN) réussis avec rollback. Preuves g-proof.json, g-ui-proof.json, g-extra-proof.json. Revue visuelle/volumes/concurrence génération restent en H.
- H : régression complète/volumes/UI/PDF, documentation, sauvegarde restaurée puis déploiement source additif et nettoyage à réaliser.

Copie : qpos_test_replenishment_20261014 ; preuves privées storage/app/replenishment.
Source non migrée pour ce module ; comptes et .env conservés. Aucun paiement/email réel, seed ou fresh source.
Commits locaux sélectionnés par lot validé ; aucun push. Source non migrée pour le module.

Incident de reprise : Avast a mis les nouveaux scripts privés en quarantaine. Le propriétaire les a restaurés ; les 29 contrôles F ont ensuite réussi. Aucune configuration antivirus modifiée par l'agent.

Reprise du 15/10 : limites renouvelées (1 % sur 5 h, 0 % semaine au démarrage). MariaDB était arrêté : redémarrage local et récupération standard, sans remplacement de données. Avast a de nouveau retenu check-f-extra.php ; propriétaire a restauré/autorisé ce fichier, puis ses 7 contrôles ont réussi.

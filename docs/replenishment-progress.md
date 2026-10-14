# Réapprovisionnement — progression

Base main fe7e79d ; périmètre A–H approuvé le 14/10/2026.

- OK A : cartographie/contrats, copie restaurée 93 tables ; fc90aed.
- OK B : politiques héritées produit/boutique, seuils décimaux, audit, écran FR/EN, alertes unifiées. 25 contrôles service/SQL/HTTP réussis sur copie, fixtures annulées. Aucune preuve visuelle récente annoncée.
- OK D (fondation nécessaire avant C) : relation produit/fournisseur multiple, préférence unique sous verrou, conditionnement, délais/minimum/multiple et prix daté. 12 contrôles SQL/HTTP réussis, rollback. Planification par boutique reste en G.
- OK C : filtres combinés catalogue/POS/achats, badges, blocage rupture réel, création rapide avec quantité zéro et conservation du brouillon initial. 14 contrôles SQL/HTTP + 6 vues/refus + 17 scripts inline parsés ; build Vite/CSS OK (2 023 modules). Défaut SQL MariaDB corrigé ; URL de scénario initial corrigée sans changer la route métier. Revue visuelle réelle à faire.
- OK E fonctionnel : bons brouillon/validation/émission/confirmation/archive, achat lié sans réception, snapshots/historique/versions et exports. 30 contrôles SQL/HTTP + deux processus simultanés (même bon, zéro double achat/stock), fixtures retirées. PDF/XLSX structurels et scripts rendus vérifiés ; revue visuelle reste en H.
- F–H : à réaliser (réceptions/avenants/reliquats, suggestions/planification, régression complète et déploiement source).

Copie : qpos_test_replenishment_20261014 ; preuves privées storage/app/replenishment. Source non migrée pour ce module, comptes et .env inchangés. Commits locaux sélectionnés par lot ; aucun push.
\nCorrection B complémentaire : le plafond quotidien saute un événement déjà enregistré sans réutiliser une clé avec un autre payload. Deux réévaluations dans la journée ne bloquent pas le stock. Remises E : coût net à six décimales, total confirmé lors de validation ; précision/contrats natifs conservés.\n
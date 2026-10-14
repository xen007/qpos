# Réapprovisionnement — progression

Base main fe7e79d ; périmètre A–H approuvé le 14/10/2026.

- OK A : cartographie/contrats, copie restaurée 93 tables ; fc90aed.
- OK B : politiques héritées produit/boutique, seuils décimaux, audit, écran FR/EN, alertes unifiées. 24 contrôles service/SQL/HTTP réussis sur copie, fixtures annulées. Aucune preuve visuelle récente annoncée.
- OK D (fondation nécessaire avant C) : relation produit/fournisseur multiple, préférence unique sous verrou, conditionnement, délais/minimum/multiple et prix daté. 12 contrôles SQL/HTTP réussis, rollback. Planification par boutique reste en G.
- C, E–H : à réaliser.

Copie : qpos_test_replenishment_20261014 ; preuves privées storage/app/replenishment. Source non migrée pour ce module, comptes et .env inchangés. Commits locaux sélectionnés par lot ; aucun push.

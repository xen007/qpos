# Phase 7.F — sauvegardes et exploitation

Date : 14/10/2026. État fonctionnel vérifié sur copie ; commit à enregistrer.

## Livré

- Backup complet MariaDB/InnoDB, routines/triggers/events via mysqldump, fichiers métier publics/historiques, logos, PDF conservés, manifeste non secret. .env/APP_KEY/credentials séparés.
- Archive privée sous storage/app/backups/operational, SHA-256 de l'archive et de chaque fichier. AES-256 si mot de passe configuré, obligatoire avant copie cloud.
- Une destination S3 compatible facultative, désactivée par défaut. Réglages bucket/endpoint/préfixe et consentement explicite, credentials exclusivement environnement. Envoi privé puis lecture/empreinte identique.
- Table backup_runs : état, tentatives, artefact, intégrité, cloud, restauration/durée, codes d'erreur sûrs. Erreur cloud ne retire pas la validité locale ; erreur de rétention non plus. Échec sauvegarde/copie produit l'alerte interne système.
- Backup toutes les 23 h, contrôle scheduler cinq minutes et rattrapage après poste arrêté. File backup_database/qpos-backups, réservation 1 800 s, timeout job 1 200 s ; notifications sur file/timeout distincts.
- Verrou par base et recontrôle du besoin après acquisition : ni sauvegardes concurrentes ni doublons des jobs de rattrapage accumulés.
- Vérification CLI vers NOUVELLE base qpos_restore_* et dossier privé ; source/noms existants refusés. Tables/vues et comptages comparés. Chemins ZIP Windows, alias de casse, noms réservés, symlinks, tailles, empreintes ZIP et fichiers extraits contrôlés avant import.
- Rétention locale optionnelle, désactivée par défaut ; protège deux dernières archives valides et dernière restauration vérifiée. Ancienne locale conservée si cloud activé échoué. Métadonnées et preuves de restauration conservées ; pas de suppression distante.
- Écran global system_monitor + Admin : activité scheduler/worker, files/ancienneté, livraisons quotidiennes/internes/externes, SMTP, sauvegardes, espace, RPO/RTO mesurés, téléchargement privé et commandes.
- Contrôles de consentement corrigés : accepted n'est plus implicitement requis pour des réglages locaux. Tout canal externe activé exige toujours son accord.

## Preuves sur qpos_test_phase7_20261012

- 29 contrôles sauvegarde/restauration/HTTP OK. 91 tables, 1 527 lignes au premier snapshot, sept fichiers, ZIP 115 514 octets. Empreintes métier avant/après et dans la restauration identiques.
- Première restauration : 164,514 s ; archive chiffrée : 155,286 s. Option --no-autocommit vérifiée dans le mysqldump installé puis activée. Nouvelle archive 116 208 octets : restauration 55,383 s, 60,668 s total sauvegarde/restauration.
- Recontrôle avec empreintes sur disque et garde-fous Windows avant import : 53,434 s, 91 tables/sept fichiers ; aucune source remplacée.
- Sept contrôles rétention/RPO/rattrapage OK sur clones de métadonnées : désactivation, purge ancienne seulement, dernière preuve et deux dernières archives protégées, échec cloud protège locale, dépassement 24 h signalé, rattrapage dû après panne simulée.
- Cinq ZIP privés jetables rejetés : traversée, collision DATABASE.SQL/database.sql, nom Windows nul.txt, point final et chemin lecteur.
- Deux connexions réelles : sauvegarde concurrente ignorée, aucun enregistrement/artefact en double.
- Vrai POST admin puis queue:work backup_database : archive vérifiée. Deux jobs de rattrapage obsolètes ignorés et acteur vendeur non autorisé ignoré. Aucune copie cloud réelle.
- Deux réglages locaux sans accord externe OK/rollback. Régression 39 déclencheurs natifs OK/rollback. Vingt syntaxes PHP, build Vite 2 023 modules/62 icônes et garde CSS OK ; assets identiques au build E.
- S3 upload/readback uniquement Storage::fake. Dépendance officielle league/flysystem-aws-s3-v3 3.35.3, aws-sdk-php 3.401.1, aws-crt-php 1.2.7, jmespath 2.9.2, symfony/filesystem 7.4.18 : cinq ajouts, zéro mise à jour/suppression. Composer no-scripts ; audit installation sans avis de vulnérabilité retourné.

## Limites explicites

Source qpos non migrée Phase7. SMTP/S3/WhatsApp non configurés et réception réelle non vérifiée. Aucun secret modifié, aucun envoi réel, aucune donnée métier source écrite. Migration/caches/déploiement source prévus en 7.H.

RTO mesuré sur le volume ci-dessus et cet hôte, pas extrapolé à un volume supérieur. RPO contrôlé par l'ancienneté réelle ; un poste éteint ne sauvegarde pas, rattrapage au redémarrage. Base snapshot et fichiers copiés séparément : prévoir fenêtre sans migration ni suppression/modification de médias.

Écran rendu/vérifié en HTTP, pas de nouvelle preuve navigateur : outil indisponible lors des derniers essais, vérification visuelle/clavier à reprendre en H. Pas de certification WCAG générale ni impression/scanner physique.

Procédure : BACKUP-RESTORE.md. Preuves privées : backups-proof.json, backup-optimized-proof.json, backup-hardened-restore-proof.json, backup-retention-proof.json, backup-paths-proof.json, backup-lock-proof.json, backup-queue-proof.json.

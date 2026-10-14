<?php
return [
 'title'=>'État système et sauvegardes','notice'=>'Les dates sont locales Africa/Douala. Une absence de heartbeat ne prouve pas qu’un worker inactif est arrêté : examinez aussi la file et son ancienneté.',
 'heartbeats'=>'Activité des services','notification_scheduler_last_run'=>'Scanner et scheduler notifications','notification_worker_last_run'=>'Dernier traitement notifications','scheduler_last_run'=>'Scheduler synthèses quotidiennes','backup_scheduler_last_run'=>'Scheduler sauvegardes','backup_worker_last_run'=>'Dernière sauvegarde traitée',
 'unknown'=>'Non vérifié','queue'=>'Jobs en attente','queue_age'=>'Ancienneté du job le plus ancien','free'=>'Espace libre (Mo)','transport'=>'Transports','daily'=>'Envoi des synthèses','smtp'=>'Configuration SMTP',
 'local'=>'Sauvegarde locale quotidienne','cloud'=>'Copie S3 chiffrée','retention'=>'Rétention locale automatique','days'=>'Conserver au moins (jours)','bucket'=>'Bucket S3 destinataire','endpoint'=>'Endpoint S3 HTTPS (vide = AWS)','prefix'=>'Préfixe objet',
 'cloud_consent'=>'J’autorise ce bucket à recevoir la base complète et les fichiers métier, contenant données clients/utilisateurs et chiffres financiers. Les secrets .env restent séparés.',
 'cloud_consent_required'=>'Confirmez le bucket et les données transmises avant activation cloud.',
 'archive_notice'=>'Archive privée DB + médias + PDF conservés + manifeste/configuration non secrète. .env, APP_KEY et identifiants sont à conserver séparément. S3 exige un mot de passe d’archive d’au moins 16 caractères et des credentials hors base. Aucun envoi sans activation.',
 'consistency'=>'La base utilise un snapshot transactionnel InnoDB ; les fichiers sont copiés et vérifiés par empreinte. Prévoyez une fenêtre sans modification/suppression de médias ni migration de schéma pour une reprise cohérente.',
 'retention_notice'=>'Rétention désactivée par défaut. Protège les deux dernières archives valides et la dernière restauration vérifiée. Les anciennes copies cloud nécessitent une politique propre au bucket.',
 'run'=>'Sauvegarder maintenant','queued'=>'Sauvegarde mise en file. Vérifiez le worker qpos-backups et son résultat ici.','history'=>'Historique des sauvegardes','download'=>'Télécharger l’archive privée','state'=>'État','cloud_state'=>'Copie cloud','size'=>'Octets','restore'=>'Restauration',
 'restore_help'=>'La commande backups:verify <ID> restaure dans une NOUVELLE base qpos_restore_* et un dossier privé ; jamais dans la source. Conservez le nom original de base au rétablissement définitif des PDF. Voir procédure BACKUP-RESTORE.',
 'rpo'=>'Objectif RPO 24 h','rto'=>'Objectif RTO 8 h','measured_notice'=>'RTO mesuré sur la dernière restauration vérifiée, avec son volume ; ce n’est pas une garantie pour une base beaucoup plus grosse. Si le poste est arrêté, le rattrapage commence au redémarrage et l’ancienneté reste affichée.',
 'ok'=>'Objectif mesuré respecté','late'=>'À vérifier / objectif dépassé',
 'states'=>['running'=>'En cours','verified'=>'Archive vérifiée','failed'=>'Échec','disabled'=>'Désactivé','unconfigured'=>'Non configuré','retained_metadata'=>'Archive purgée, métadonnées conservées'],
];

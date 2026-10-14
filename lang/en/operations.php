<?php
return [
 'title'=>'System status and backups','notice'=>'Times use Africa/Douala. Missing heartbeat does not prove an idle worker is stopped: check queue size and age too.',
 'heartbeats'=>'Service activity','notification_scheduler_last_run'=>'Notification scanner/scheduler','notification_worker_last_run'=>'Last notification processing','scheduler_last_run'=>'Daily summary scheduler','backup_scheduler_last_run'=>'Backup scheduler','backup_worker_last_run'=>'Last completed backup',
 'unknown'=>'Unverified','queue'=>'Pending jobs','queue_age'=>'Oldest job age','free'=>'Free disk space (MB)','transport'=>'Transports','daily'=>'Daily summary sending','smtp'=>'SMTP configuration',
 'local'=>'Daily local backup','cloud'=>'Encrypted S3 copy','retention'=>'Automatic local retention','days'=>'Keep at least (days)','bucket'=>'Destination S3 bucket','endpoint'=>'HTTPS S3 endpoint (blank = AWS)','prefix'=>'Object prefix',
 'cloud_consent'=>'I authorize this bucket to receive the full database and business files, including customer/user data and financial figures. .env secrets remain separate.',
 'cloud_consent_required'=>'Confirm the bucket and transmitted data before enabling cloud backup.',
 'archive_notice'=>'Private archive: DB, media, preserved PDFs and manifest/non-secret configuration. Keep .env, APP_KEY and credentials separately. S3 requires an archive password of at least 16 characters and credentials outside the database. No upload without activation.',
 'consistency'=>'The database uses a transactional InnoDB snapshot; files are copied and hash-verified. Use a window without media changes/deletions or schema migration for coherent recovery.',
 'retention_notice'=>'Disabled by default. Protects the latest two valid archives and the last verified restore. Older cloud copies need the bucket’s own retention policy.',
 'run'=>'Back up now','queued'=>'Backup queued. Check the qpos-backups worker and result here.','history'=>'Backup history','download'=>'Download private archive','state'=>'State','cloud_state'=>'Cloud copy','size'=>'Bytes','restore'=>'Restore',
 'restore_help'=>'backups:verify <ID> restores into a NEW qpos_restore_* database and private directory, never the source. Preserve the original database name during final recovery for preserved PDFs. See BACKUP-RESTORE procedure.',
 'rpo'=>'RPO target 24 h','rto'=>'RTO target 8 h','measured_notice'=>'RTO reflects the last verified restore at its measured volume, not a guarantee for a much larger database. If the workstation is off, catch-up starts after reboot and backup age remains visible.',
 'ok'=>'Measured target met','late'=>'Review / target exceeded',
 'states'=>['running'=>'Running','verified'=>'Archive verified','failed'=>'Failed','disabled'=>'Disabled','unconfigured'=>'Unconfigured','retained_metadata'=>'Archive pruned; metadata kept'],
];

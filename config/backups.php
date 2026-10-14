<?php
return [
 'enabled'=>true,'cloud_enabled'=>false,'retention_enabled'=>false,'retention_days'=>30,
 'root'=>storage_path('app/backups/operational'),
 'mysql_binary'=>env('QPOS_MYSQL_BINARY','C:/xampp/mysql/bin/mysql.exe'),
 'dump_binary'=>env('QPOS_MYSQLDUMP_BINARY','C:/xampp/mysql/bin/mysqldump.exe'),
 'password'=>env('BACKUP_ARCHIVE_PASSWORD'),'cloud_disk'=>'backup_s3','cloud_prefix'=>'qpos-backups',
 'max_unpacked_bytes'=>2147483648,'max_entries'=>20000,
];

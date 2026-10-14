# Sauvegarde et restauration QPOS

## Périmètre

Base MariaDB complète, médias du disque public, public/media, logos, PDF immuables des synthèses et manifeste non secret (tables/comptages, SHA-256 des fichiers, PHP/Laravel, composer.lock). Historiques BDT et fuseau hérité conservés tels quels.

Dump transactionnel InnoDB. Éviter migrations de schéma et modification/suppression de médias pendant la fenêtre de sauvegarde : les fichiers sont copiés et contrôlés séparément du snapshot SQL.

.env, APP_KEY et credentials sont exclus du ZIP. Conserver l'environnement original dans un coffre séparé : APP_KEY est indispensable aux données chiffrées. Conserver également le mot de passe original de chaque archive chiffrée ; sa rotation ne doit pas rendre les anciennes archives inutilisables.

## Windows CMD

Depuis C:\xampp\htdocs\qpos, après migrations :

~~~bat
C:\xampp\php\php.exe artisan backups:run
C:\xampp\php\php.exe artisan backups:verify ID
C:\xampp\php\php.exe artisan queue:work backup_database --queue=qpos-backups --timeout=1200 --tries=1
~~~

Remplacer ID par une archive verified affichée dans État système. Le bouton Sauvegarder maintenant enfile un travail durable ; il requiert ce worker. Le worker notifications possède une connexion/file distincte et un timeout court.

Le scheduler vérifie toutes les cinq minutes si la dernière sauvegarde date de plus de 23 heures. Rattrapage après arrêt nocturne ; le job recontrôle l'ancienneté sous verrou, les jobs accumulés ne produisent pas plusieurs sauvegardes du même rattrapage.

## Restauration isolée

backups:verify exige l'état verified et le SHA-256 enregistré. Contrôles ZIP : chemins, alias de casse Windows, noms réservés, symlinks, tailles et empreintes. Les fichiers extraits sont recontrôlés sur disque avant import.

Crée une NOUVELLE base qpos_restore_DATE_ALEATOIRE et un dossier privé storage/app/backups/operational/restores. Source et noms existants refusés. Comparaison tables/vues/comptages, durée tracée. Bases et dossiers de contrôle conservés pour examen ; aucune purge automatique de ces preuves.

Contrôler ensuite connexion par rôle, stock/lots, POS/passation, paiements, dettes, caisse et rapports sur une copie. Pour une reprise réelle, mettre la source en maintenance, sauvegarder son état, examiner la preuve puis appliquer la remise en service adaptée à l'hôte. La commande ne remplace jamais la source.

Les chemins des PDF immuables incorporent le nom original de base : le rétablissement définitif conserve ce nom et les emplacements attendus. Une base de contrôle différente permet de vérifier données/empreintes sans réécrire historiques ni preuves PDF.

## S3 facultatif

Une destination S3 compatible, désactivée par défaut. Bucket/endpoint/préfixe et activation réservés à l'administrateur global system_monitor. Accord explicite : base complète et fichiers contiennent données personnelles et financières.

Credentials hors base : AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION ; BACKUP_ARCHIVE_PASSWORD d'au moins 16 caractères. Aucun ajout automatique .env. Chiffrement AES-256 avant envoi ; lecture du même objet et SHA-256 exact obligatoires avant cloud verified.

Configuration présente ou put réussi seuls ne prouvent pas la copie vérifiée. Sans configuration/chiffrement : unconfigured. Échec cloud : archive locale conservée et alerte système. Réception cloud réelle à vérifier après configuration propriétaire.

## Rétention / RPO / RTO

Rétention locale désactivée par défaut, minimum sept jours si activée. Protège les deux dernières archives valides et la dernière restauration vérifiée. Une archive locale ancienne reste conservée si le cloud activé a échoué. Métadonnées conservées ; aucune suppression distante automatique, rétention du bucket à définir séparément.

RPO visé 24 h : ancienneté réelle affichée, dépassement signalé. Un poste arrêté ne sauvegarde pas ; rattrapage au redémarrage. RTO visé 8 h : mesure sur dernière restauration, volume et hôte testés ; un volume supérieur nécessite une mesure.

Adaptateur : [documentation officielle Laravel 12](https://raw.githubusercontent.com/laravel/docs/12.x/filesystem.md). Résultats : phase7-backups.md.

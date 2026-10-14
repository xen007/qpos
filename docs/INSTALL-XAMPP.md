# Installation et exploitation avec XAMPP

Installation actuelle : Windows, PHP 8.2, MariaDB 10.4, Laravel 12. Dépôt C:\xampp\htdocs\qpos. Utiliser PHP XAMPP, pas un autre PHP du PATH. Aucune création de compte externe ni modification de secrets par ces procédures.

## Installation neuve

1. Installer les dépendances verrouillées avec Composer install et npm ci, puis npm run build. Vérifier les extensions PHP nécessaires : PDO mysql, mbstring, openssl, fileinfo, zip, intl selon les dépendances ; PHP CLI et Apache doivent être compatibles.
2. Le propriétaire configure .env à partir du modèle, DB, URL, clés et stockage. Une clé existante ne se régénère jamais. Ne pas partager ce fichier ni l'inclure dans une archive publique.
3. Créer une base dédiée vide ; contrôler le nom cible avant migration. php artisan migrate --force installe le schéma. StartUpSeeder concerne uniquement une installation neuve ; ne pas le rejouer sur une installation métier existante. Le seed de démonstration est réservé à une copie dédiée, avec sa garde de base.
4. Configurer le compte global et ses droits selon roles-reference.md. En production, aucun mot de passe de démonstration.
5. php artisan storage:link ; compilation des assets. Le serveur HTTP doit servir public. Pour le développement : C:\xampp\php\php.exe artisan serve --host=127.0.0.1 --port=8000. Ne pas confondre la source 8000 et une copie sur un autre port.

Pour une installation existante : suivre UPDATE.md, sans migrate:fresh.

## Scheduler et deux workers

Les commandes natives sont :

~~~cmd
C:\xampp\php\php.exe artisan schedule:work
C:\xampp\php\php.exe artisan queue:work notification_database --queue=qpos-notifications --sleep=3 --tries=1 --timeout=90
C:\xampp\php\php.exe artisan queue:work backup_database --queue=qpos-backups --sleep=5 --tries=1 --timeout=1200
~~~

Elles restent ouvertes pendant le service. --tries=1 concerne la remise en queue Laravel ; les états métier assurent leurs reprises propres. Le worker sauvegarde utilise retry_after=1800, supérieur à son timeout1200. Ne pas placer les longues sauvegardes dans le worker des notifications.

Les scripts du dépôt démarrent les processus masqués, vérifient les doublons du même dossier et peuvent les relancer via une tâche Windows chaque minute :

~~~cmd
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\Start-QposOperations.ps1 -CheckOnly
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\Start-QposOperations.ps1
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\Install-QposOperationsTask.ps1 -CheckOnly
powershell -NoProfile -ExecutionPolicy Bypass -File scripts\Install-QposOperationsTask.ps1
~~~

La tâche utilise le compte Windows courant, sans mot de passe stocké, et nécessite sa session ouverte. Pour un serveur sans session interactive : faire administrer un service ou une tâche avec compte de service protégé. Ce mode n'est pas installé implicitement. Les scripts ne démarrent ni Apache ni MariaDB : ces services doivent fonctionner.

## Vérification après démarrage

Consulter État du système avec Admin + system_monitor : scheduler récent, worker récent après traitement d'un événement, queues, sauvegarde vérifiée/restaurée et espace disque. Un worker inactif sans événement ne constitue pas à lui seul un échec. Vérifier aussi les processus Windows et journaux.

Le scheduler gère la synthèse23h55, le scan des alertes et le backup23h avec rattrapage. Boutique fermée n'implique pas caisse automatiquement fermée. Un PC éteint ne travaille pas : un hébergement actif est nécessaire pour tenir un RPO24h constant. Voir BACKUP-RESTORE.md.

SMTP/SMS/WhatsApp/S3 restent désactivés tant que configuration, consentement et réception/intégrité réelle ne sont pas vérifiés. Voir phase7-email.md et ENV-VARIABLES.md.

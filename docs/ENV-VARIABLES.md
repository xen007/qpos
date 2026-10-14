# Configuration d'exploitation

Ce document décrit les noms, jamais les valeurs locales. Les secrets sont gérés par le propriétaire dans .env ou dans son stockage protégé. Aucun secret dans system_settings, Git, messages ou ZIP métier.

| Groupe | Variables | Usage |
|---|---|---|
| Application | APP_ENV, APP_URL, APP_DEBUG, APP_KEY | URL serveur définitive, production sans debug ; préserver la clé pour les données chiffrées |
| MariaDB | DB_CONNECTION, DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD | Confirmer la base source avant écriture ; isoler chaque copie |
| Mail | MAIL_MAILER, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_ENCRYPTION, MAIL_FROM_ADDRESS, MAIL_FROM_NAME | Transport SMTP ; présence de paramètres ne prouve pas une réception |
| Twilio | TWILIO_ACCOUNT_SID, TWILIO_AUTH_TOKEN, TWILIO_SMS_FROM, TWILIO_WHATSAPP_FROM, TWILIO_WHATSAPP_TEMPLATE_FR, TWILIO_WHATSAPP_TEMPLATE_EN | SMS et modèles WhatsApp validés ; canal optionnel avec consentement, configuration et tests réels distincts |
| Archive | BACKUP_ARCHIVE_PASSWORD | Au moins16 caractères pour AES256 ; conserver séparément la clé de récupération. Obligatoire pour activer le cloud |
| Binaires | QPOS_MYSQL_BINARY, QPOS_MYSQLDUMP_BINARY | Exécutables CLI ; défauts XAMPP Windows |
| S3 | AWS_ACCESS_KEY_ID, AWS_SECRET_ACCESS_KEY, AWS_DEFAULT_REGION, AWS_BUCKET, AWS_ENDPOINT, AWS_USE_PATH_STYLE_ENDPOINT | Credentials réservés au stockage protégé ; noms définis dans config/filesystems.php ; région/bucket/endpoint peuvent être remplacés par les réglages non secrets |
| Queue | QUEUE_CONNECTION, QUEUE_FAILED_DRIVER | Les traitements Phase7 utilisent explicitement notification_database et backup_database |

## Réglages administrables

- Synthèse quotidienne : destinataires multiples, activation, versions/livraisons. Ne pas ajouter un second circuit.
- Notifications opérationnelles : seuils, canaux, destinataires, consentement et préférences personnelles, sans droits supplémentaires.
- État du système : backup local, cloud S3 privé, région/bucket/endpoint/prefix, rétention facultative.
- Boutique : passation vendeur/caissier, délais, méthodes de paiement, lancement du ticket après encaissement. L'impression silencieuse est une configuration du poste, pas un secret ni une promesse du serveur.

Après changement .env par le propriétaire : config:clear et redémarrage des workers/SAPI appropriés. Vérifier APP_URL : les liens email construits en CLI dépendent de cette URL, contrairement à une visite HTTP sur un autre port.

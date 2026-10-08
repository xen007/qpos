# Phase 5 — exploitation des rapports et récapitulatifs

## Utilisation

Le dashboard et Rapports partagent les filtres boutique/période. Jour/semaine/mois utilisent la date de référence ; Personnalisée utilise Du/Au, au plus 366 jours. Les filtres vendeur/catégorie/produit concernent les ventes ; charges et trésorerie ne sont pas réparties artificiellement. Le stock et les péremptions sont courants, quelle que soit la période de ventes affichée.

Les historiques restent séparés : aucune conversion BDT/XAF, aucune attribution fictive de boutique, aucune réconciliation de dette implicite. L'absence de coût vendu rend marge et résultat provisoires. Les lots à coût inconnu ne contribuent pas au valorisé connu.

Pause personnelle : menu « Commencer une pause » ; aucun changement de caisse/stock. La session est clôturée par le caissier dans son parcours existant avec comptage. L'administrateur clôture la journée dans Récapitulatifs quotidiens, par boutique, à tout moment. Cette action produit immédiatement une version et son PDF privé ; elle ne ferme pas les sessions. Le statut clôturé découle de la présence de cette preuve.

À 23h55 Africa/Douala, le scheduler produit le fallback si nécessaire ou la version corrigée si la photographie a changé. Une notification explicite accompagne la correction ; les deux versions et leurs PDF restent accessibles. Des opérations entre 23h55 et minuit restent au même jour métier : la reprise du lendemain les rapproche. Si la version corrigée est déjà figée, elles restent dans le journal tardif avec un avis admin distinct ; aucune réécriture ni troisième PDF silencieux.

## Planification et transport

L'envoi est désactivé par défaut. Dans **Paramètres > Synthèse quotidienne**, l'admin entreprise renseigne « Adresse email de réception » et « Activer/Désactiver l'envoi ». Une liste de 20 adresses maximum est acceptée : une par ligne, ou séparées par virgule/point-virgule ; doublons normalisés et supprimés. L'activation exige un SMTP renseigné, les adresses, les boutiques affichées et une confirmation explicite du contenu. Le manifeste conserve cette approbation ; une nouvelle adresse ou boutique nécessite une nouvelle confirmation. Droits et suspension restent vérifiés avant envoi. La désactivation préserve les récapitulatifs/PDF et le planificateur local.

Les valeurs par défaut sont `system.daily_summary_email_enabled=false` et `system.daily_summary_email_recipients=[]` dans `config/system.php`. Les changements de l'UI sont persistés dans `reporting_runtime` (`mail_enabled`, `mail_recipients`, `mail_approval`), avec priorité sur ces valeurs ; ils n'exigent pas de reconstruire le cache config. L'adresse autorisée a été enregistrée comme paramètre en base, sans adresse personnelle dans le code. L'adresse de notification est indépendante du compte et ne change pas le login. La liste reçoit une synthèse par boutique sous l'identité de l'admin qui active ; aucun autre utilisateur n'est approuvé automatiquement. Une modification ultérieure de l'adresse du compte ou de la définition du contenu suspend la validité de l'approbation. Les identifiants SMTP et l'expéditeur sont configurés par le propriétaire ; ne pas envoyer les secrets dans le dépôt ou dans les preuves.

Constat source du 08/10/2026 : transport SMTP Mailtrap, port 2525, sans username/password ni URL SMTP. La liste autorisée est enregistrée, mais l'envoi reste désactivé. `.env` inchangé ; aucun envoi externe ni test de connexion SMTP réalisé.

- `php artisan reports:daily` : générations dues, rapprochement et rattrapage, au plus sept jours par passage avec curseur persistant.
- `php artisan reports:deliver --limit=10` : file email, révocation des droits, erreurs filtrées, retry de connexion/génération, cinq tentatives au maximum.
- `php artisan reports:install-scheduler` : tâche Windows `QPOS-Phase5` toutes les minutes, pour la session interactive courante. Le script VBS lance le wrapper CMD sans fenêtre ; PHP XAMPP est utilisé. Une tâche existante n'est pas écrasée.
- Consulter le dernier passage et les états de livraison dans Récapitulatifs quotidiens. Le serveur, MariaDB et la session Windows doivent être actifs. Aucun envoi ne peut fonctionner lorsque le serveur est éteint ; les commandes reprennent à la remise en service.
- La configuration `config/reporting.php` utilise le transport Laravel existant, ou `REPORT_MAILER` si le propriétaire le configure. Aucun secret ni `.env` n'est modifié par cette phase. L'UI exige SMTP authentifié (host/port/username/password), ou une URL SMTP configurée ; les hôtes de développement localhost/mailpit/mailhog ne peuvent pas activer la livraison externe. Log/array restent non livrés. La vérification est une présence de configuration, sans garantie de livraison. Les adresses de notification sont explicitement approuvées ; les droits restent ceux de l'admin et de ses boutiques.
- Une acceptation SMTP suivie d'une perte de confirmation ne permet pas une garantie absolue « exactement une fois ». L'état `ambiguous` évite le retry aveugle ; vérifier le transport avant décision de reprise. L'identifiant de message est stable. Aucun renvoi manuel forcé n'est exposé dans cette phase.

## Exports, preuves et restauration

Écran, Excel et PDF appliquent les mêmes permissions et boutiques. Les exports sont complets dans la sélection : Excel au plus 10 000 lignes, PDF 1 000, avec refus explicite au-delà. Les valeurs DECIMAL Excel sont conservées comme texte exact, sans formule issue d'un libellé utilisateur. Le rapport horaire possède un histogramme Excel, un graphique PDF et les classements.

Les récapitulatifs stockent payload/source IDs et SHA256 du payload ainsi que le chemin/SHA256 du PDF privé, séparés par base de données. Une lecture vérifie les empreintes ; un PDF manquant ou altéré n'est pas régénéré silencieusement. La langue d'origine du PDF est conservée. Aucun document privé n'est accessible par le stockage public.

Les sauvegardes, copies et preuves de vérification restent dans `storage/app/phase5/`, ignoré par Git. La sauvegarde de base et le code au HEAD ont été restaurés/contrôlés avant migration. Les fichiers privés de récapitulatifs doivent également entrer dans la sauvegarde d'exploitation future. RPO24h/RTO8h et sauvegarde quotidienne/externalisation demeurent Phase 7 ; la synthèse n'est pas une sauvegarde.

Le rollback destructif de la migration est refusé. Pour revenir au code antérieur, préparer la restauration de la base et des fichiers privés depuis une sauvegarde compatible, en tenant compte de toute opération réelle effectuée depuis le passage en service. Le propriétaire réalise le commit final unique ; exclure `.env`, caches, sauvegardes, fixtures et preuves privées.

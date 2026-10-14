# Phase 7.E - transports et notifications externes

## OK 7.E.1 : circuit quotidien existant simule

13 controles OK sur qpos_test_phase7_20261012, avec Mail::fake et rollback : desactivation sans prise en charge, destinataire non approuve differe, liste multiple parametree, transport array refuse comme livraison reelle, disponibilite SMTP simulee sans reseau, livraison unique, rejeu sans doublon, PDF natif, rendus FR/EN, sending ancien transforme en ambiguous, aucune relance silencieuse, destinataire suspendu annule.

Preuve privee : email-daily-proof.json. Les adresses example.test et identifiants SMTP simulation existent uniquement dans la configuration memoire du test. Aucun envoi reel, paiement ou secret externe.

## Diagnostic source du 13/10/2026

Lecture seule qpos et .env ; seuls des booleens sont restitues. Envoi quotidien desactive. MAIL_HOST, MAIL_PORT et MAIL_FROM_ADDRESS presents ; MAIL_USERNAME, MAIL_PASSWORD et MAIL_URL absents. Presence SMTP incomplete, connexion/reception reelle non verifiees. Aucun changement .env.

## Contrat livre 7.E

- Reutiliser summary_deliveries et SummaryMailApproval pour les recaps et versions corrigees. Aucun deuxieme circuit quotidien.
- Alertes critiques : activation desactivee par defaut, destinataires explicitement configures et approuves, sans reutiliser automatiquement les adresses autorisees seulement pour les recaps.
- Configuration et droits du responsable global controles au traitement. Les preferences ne donnent aucun droit de lecture supplementaire.
- Une livraison externe unique par evenement/version/destinataire/canal. Reprises bornees apres echec certain avant acceptation ; confirmation incertaine vers ambiguous, sans second envoi silencieux.
- Ne jamais renvoyer email_failed vers email : eviter une boucle d'alertes.
- Rendre visibles disabled / unconfigured / simulated / ready / sent / ambiguous / failed. Ready ne prouve pas reception.
- FR/EN, liens authentifies, contenu limite aux donnees permises, secrets hors base ordinaire et logs.
- SMS/WhatsApp optionnels desactives, un fournisseur seulement, configuration externe reelle et preuve de reception distinguees. Aucun compte, telephone, destinataire ni credential invente.
- Tests exclusivement Mail/HTTP fake sur copie ; aucun envoi externe pour l'audit.

## Etat

7.E reste en cours : alertes critiques externes, preferences externes, canal SMS/WhatsApp et supervision des echecs non encore livres. 7.F sauvegarde/restauration, 7.G ajouts proprietaire/exploitation et 7.H livraison restent a achever. Aucun OK global 7.E.


## OK 7.E - 14/10/2026

Outbox externe transactionnel, destinations chiffrees, cle unique evenement/utilisateur/canal/adresse. Canaux externes desactives par defaut. Reglages globaux audites : destinataires multiples, responsable global qui enregistre, langue FR/EN, alertes choisies, accord explicite. Ne reprend pas l'adresse autorisee seulement pour les recaps quotidiens. Changements appliques aux evenements futurs ; pas de rattrapage silencieux des anciens evenements.

Preferences individuelles limitees aux canaux deja autorises. Roles, suspension, portee boutique, destinataire retire et evenement resolu reverifies lors de la prise en charge. Messages minimaux : type, boutique/reference et lien authentifie ; aucun chiffre financier ni nom de caissier en SMS/WhatsApp ou email critique. Syntheses Phase5 conservees avec leur propre circuit et leurs pieces jointes.

SMTP existant et un fournisseur optionnel Twilio pour SMS/WhatsApp. Aucun SDK additionnel. WhatsApp utilise uniquement ContentSid + ContentVariables, modele approuve FR/EN et destinataires ayant consenti. Secrets lus par config/notifications.php depuis variables d'environnement ; .env inchangé.

La file database existante traite aussi DeliverExternalAlert. Echec certain : reprise bornee a cinq avec backoff. SMTP accepte : accepted, pas reception. Fournisseur accepte : accepted + identifiant ; suivi GET peut enregistrer delivered/read ou failed. Timeout/reponse fournisseur 5xx/confirmation incertaine et sending ancien : ambiguous, aucun renvoi automatique. Echec email externe : alerte interne email_failed ; ce type est exclu des envois critiques, donc pas de boucle.

### Preuves

- 29 controles HTTP/SQL et transports Mail/Http fake OK, rollback : desactivation, idempotence trois canaux, chiffrement, SMTP absent, rejeu, FR/EN, destination retiree, suspension/preference/droit refuse, pertinence retiree, templates, SMS/WhatsApp, confirmation fournisseur distincte, 429/backoff/cinq tentatives, refus certain, ambiguity, consentement, E.164, preferences sans elevation, historique, echec mail alerte interne avec code sûr.
- Deux processus PHP reellement simultanes : plages chevauchantes, une seule acceptation factice, une tentative. Vrai queue:work database sur file de test privee, job consomme et une acceptation factice. Fixtures uniquement en copie ; external-workers-proof.json.
- Regression interne 21 controles OK. Douze syntaxes PHP, build Vite 2023 modules, garde CSS et diff OK. CSS 50,01 kB /9,73 gzip ; aucun package ajoute.
- Formulaire/historique/preferences rendus en HTTP. Verification navigateur du nouveau formulaire reportee : deux delais expires de l'outil, serveur de copie toujours a l'ecoute. Pas de fausse preuve mobile/clavier de ce nouveau formulaire.

### Configuration et limites

Source qpos non migree Phase7. SMTP incomplet, envoi quotidien desactive ; transports externes non configures, aucune reception reelle ni compte fournisseur cree. Tests totalement simules, aucun envoi. Les confirmations de reception fournisseur sont simulees, pas prouvees en production. Verification navigateur a reprendre en 7.H.

References implementation : [Twilio Messages](https://www.twilio.com/docs/messaging/api/message-resource), [WhatsApp templates](https://www.twilio.com/docs/whatsapp/tutorial/send-whatsapp-notification-messages-templates). Les notifications proactives utilisent un modele approuve ; aucune dependance a une conversation libre recente.

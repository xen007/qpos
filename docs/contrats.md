# QPOS — Contrats communs

Date : 01/10/2026. Statut : proposition documentaire du sous-lot 2, à valider avant implémentation.

Références : [roadmap.md](roadmap.md), [schema-cible.md](schema-cible.md), [conversion-strategie.md](conversion-strategie.md). Ce document ne crée aucun service ni automatisation. Aucun nouvel ID Dxx. Les paramètres non fixés restent **à proposer**, même si une valeur indicative est présentée.

## 1. Contrat d'audit

### Événements et contenu

| Domaine | Événements à tracer proposés | Contenu minimal |
|---|---|---|
| Accès et droits | Succès/échec de connexion, refus sensible, suspension, changement de rôle/affectation | Acteur connu ou inconnu, boutique si applicable, date, résultat, correlation |
| Catalogue | Changement d'unité/facteur, prix, activation/désactivation, import | Objet, champs utiles avant/après, origine, auteur, motif |
| Stock | Réception, transfert, perte, correction, inventaire, annulation | Mouvement/document, produit, boutique, quantité, raison, auteur |
| Vente et paiement | Validation, règlement, retour, avoir, remboursement, annulation | Document et montants utiles, état, session effective, origine et résultat |
| Caisse | Ouverture, passation, clôture, écart, dépense, correction | Responsable, session, montants, motif, date |
| Exploitation | Configuration sensible, conversion, sauvegarde, restauration, notification échouée | Run/référence, résultat, durée, erreur filtrée |

Une lecture ordinaire n'est pas journalisée systématiquement. Export de données sensibles ou téléchargement de sauvegarde : événement à tracer proposé. Détails des accès techniques et conservation IP/user-agent : **à proposer** ; aucune collecte par défaut justifiée par ce seul document.

### Garanties

audit_logs conserve acteur nullable, type/ID de cible, boutique nullable, événement, champs filtrés avant/après, raison, résultat, correlation, date effective et date d'enregistrement. Un système automatique est identifié comme système ; il n'emprunte pas l'identité d'un utilisateur.

Le succès métier est écrit dans la même transaction que l'opération. Une transaction annulée ne produit pas un faux succès ; l'échec pertinent est enregistré séparément après rollback. Les journaux techniques ne remplacent pas l'audit métier. L'outbox est écrite dans la transaction, puis traitée après commit.

Pas de modification/suppression via l'interface métier ; correction par nouvelle entrée référencée. Une cible supprimée/désactivée ne supprime pas ses traces. Autorisation de consultation/export par permission et boutique. La table seule n'est pas une preuve inviolable contre un administrateur de base : droits restreints et archivage protégé seront nécessaires au lot exploitation.

Liste d'exclusion : mots de passe, hashes d'authentification, tokens, cookies/session/CSRF, APP_KEY, contenu .env, identifiants DB, secrets prestataires, données sensibles de carte, payloads complets non filtrés, contenu des justificatifs. Messages d'erreur et références passent par filtrage ; accès nominatif limité au besoin.

### Rétention et contrôles

**À proposer** : durée de conservation par famille, archivage, anonymisation et règles de purge ; aucune durée légale supposée. Proposition opérationnelle à discuter : 12 mois consultables en ligne, puis archive selon période validée. La purge exige une règle approuvée et respecte les documents/opérations encore nécessaires ; pas de cascade ni de purge créée maintenant.

Critères futurs : opération et audit atomiques, rollback sans succès, champs exclus absents, auteur/contexte corrects, droits et pagination vérifiés. Les traces historiques manquantes ne sont pas fabriquées pendant conversion.

## 2. Contrat sauvegarde/restauration

### Engagements validés

| Sujet | Règle |
|---|---|
| RPO — D05 | Perte maximale cible de 24 heures |
| Fréquence — D38 | Une sauvegarde par jour en fin de journée, avant extinction nocturne du serveur |
| RTO — D06 | Remise en service cible en 8 heures |
| Avant conversion | Sauvegarde récente obligatoire et restauration isolée vérifiée |
| Conservation roadmap | Proposition initiale : 7 quotidiennes, 4 hebdomadaires, 12 mensuelles, sous réserve de capacité/besoins ; durée définitive **à proposer** |

Heure exacte, responsable et calendrier des jours sans activité : **à proposer**. Les points hebdomadaires/mensuels peuvent être des points quotidiens retenus ; ils ne diminuent pas la fréquence quotidienne. Le dernier succès doit rester compatible avec le RPO pendant l'activité : sauvegarde manquée ou intervalle supérieur à 24 h = alerte et objectif non tenu. Le serveur éteint la nuit ne peut exécuter ni backup ni worker ; la procédure de fin de journée doit attendre un résultat exploitable, avec procédure de rattrapage au démarrage **à proposer**.

### Périmètre et cohérence

Sauvegarder base entière (schéma, données, migrations et objets SQL présents), fichiers importés/uploads/justificatifs nécessaires, configuration et secrets sous accès protégé, référence de code/dépendances requise pour restaurer. Les artefacts doivent être hors public et hors Git. Base et fichiers doivent décrire un état compatible ; gel d'écriture court proposé pour les conversions et manifestes cohérents pour les sauvegardes ordinaires.

Manifeste : version, commit, runtime, base, début/fin UTC et fuseau métier, méthode, inventaire des objets/fichiers, tailles, empreintes SHA-256, état de cohérence, identifiant du backup. Ne pas y écrire de secret. backup_runs aide au suivi ; le manifeste et les rapports doivent aussi exister hors base pour être disponibles si celle-ci est perdue.

**À proposer** : support et capacité, copie externe/offsite, chiffrement et garde des clés, comptes habilités, outil et ordonnanceur Windows. Une copie sur le même disque ne couvre pas sa perte ; l'externalisation est à compléter en Phase 7 selon roadmap. Les secrets de restauration doivent être récupérables par l'intervenant autorisé.

### Exécution et échecs

États proposés : pending, running, succeeded, failed, verified. succeeded signifie export et fichiers terminés avec contrôles d'intégrité ; verified signifie restauration isolée et contrôles concluants. Ne pas supprimer les derniers points utilisables avant réussite du nouveau. Verrou empêchant deux sauvegardes simultanées, arrêt sur erreur, diagnostic filtré, notification de panne et gestion des archives partielles.

Durée de sauvegarde, fenêtre acceptable, timeout et espace minimal : **à proposer** après mesure. Ces paramètres ne se déduisent pas du RTO. Les sauvegardes régulières n'existent pas du seul fait de leur définition ici.

### Restauration et exercices

Avant conversion : restaurer une copie fraîche. Proposition de cadence **à proposer** : exercice mensuel et après changement important de schéma/runtime/outil. Destination isolée, serveur applicatif et workers séparés, envois externes désactivés ; vérifier que la configuration pointe sur la copie avant lancement.

Vérifier empreintes, schéma/objets, effectifs, références, soldes stock/financiers, données historiques et parcours représentatifs autorisés. Comparer des valeurs/agrégats métier en plus des nombres de lignes. Chronométrer depuis prise en charge de l'incident jusqu'au service utilisable : récupération archives/secrets, provisionnement, import, fichiers, configuration et vérifications. Consigner durée et écarts par rapport aux 8 h.

En incident réel : identifier le dernier point utilisable et les opérations postérieures, approuver la destination et les pertes éventuelles avant écrasement, restaurer code/base/fichiers compatibles, contrôler puis rouvrir. Aucune restauration aveugle de Phase 0 sur la base courante. Une sauvegarde ne remplace pas les compensations nécessaires après reprise d'activité.

## 3. Contrat notifications et traitements différés

### Canaux, déclencheurs et destinataires

| Déclencheur | Canal proposé, à proposer | Destinataire proposé, à proposer | Information minimale |
|---|---|---|---|
| Synthèse quotidienne | Email prévu en Phase 5 ; interface | Propriétaire/responsable habilité, périmètre boutique ou global explicite | Journée Africa/Douala, totaux, sessions ouvertes, complétude |
| Stock bas | Interface ; email digest éventuel | Responsable stock de la boutique | Produit, disponible, seuil ; seuils à proposer |
| Péremption | Interface ; email digest éventuel | Responsable stock boutique | Lot prouvé, date, quantité ; délai d'alerte à proposer |
| Dette/échéance | Interface ; email interne éventuel | Responsable habilité | Document et solde ; aucune relance client/fournisseur externe sans autorisation |
| Clôture avec écart | Interface ; email éventuel | Responsable boutique/propriétaire | Session, montant d'écart ; seuil à proposer |
| Sauvegarde/worker en échec | Canal indépendant à proposer ; interface au redémarrage | Responsable exploitation/propriétaire | Dernier succès, erreur filtrée, action requise |
| Conversion bloquée | Interface/rapport ; email éventuel | Responsable du chantier | Run, anomalies, blocage |

Canaux exacts, adresses, préférences et responsables non encore validés. SMS/WhatsApp/autres intégrations ne sont pas activés implicitement. Aucun message externe n'est envoyé dans ce sous-lot.

### Livraison et sécurité

Producteur : opération métier confirmée → événement/outbox persistés dans la transaction → worker après commit → tentative journalisée. Clé stable par événement/destinataire/canal/version ; traitements idempotents. Pas de notification de succès pour une transaction annulée.

États proposés : pending, processing, sent, failed, cancelled. **À proposer** : nombre de tentatives, backoff, délais d'expiration et escalation. Un timeout de prestataire peut survenir après livraison réelle : ne pas promettre une livraison exactement une fois ; utiliser les capacités de dédoublonnage du prestataire et tracer les tentatives.

Les destinataires sont résolus à partir des affectations/permissions et revérifiés avant envoi. Révocation, changement de boutique ou désactivation annulent une livraison devenue non autorisée. Payload filtré et minimal ; secrets/données de carte exclus ; lien applicatif exigeant authentification et portée boutique. Définir fréquence et seuil pour éviter les alertes répétées inchangées.

### Journée métier et arrêt nocturne

Une synthèse utilise une date métier Africa/Douala, une borne de calcul et une version ; elle distingue ventes, règlements, remboursements, espèces, dettes et sessions encore ouvertes. Elle ne clôture pas une caisse. Heure de génération, fenêtre d'opérations tardives et mode de correction : **à proposer**.

Après interruption : détecter les journées/envois manqués et reprendre selon règles de rattrapage, sans créer de doublons. Une correction de chiffres crée une version explicite, pas une réécriture silencieuse d'un message déjà envoyé. Si le serveur est arrêté ou en panne, il ne peut envoyer lui-même une alerte immédiate : prévoir une surveillance externe ou un contrôle au redémarrage, **à proposer**.

### Rétention et validation future

Durées pour payloads, tentatives et synthèses : **à proposer**, coordonnées avec l'audit et la protection des données. Avant activation : simuler transaction annulée, double traitement, destinataire révoqué, prestataire indisponible, rattrapage après arrêt et opérations tardives. Ce sont des scénarios futurs ; aucun test automatisé ni envoi exécuté ici.

## 4. Contrat unités et conditionnements — validé le 02/10/2026

- Quantités et facteurs : `DECIMAL(20,6)`, six décimales maximum, calculs décimaux exacts sans passage par les flottants.
- Facteurs strictement positifs ; exactement un conditionnement de référence par produit configuré avec une unité de base, de facteur 1 et rattaché à `products.unit_id` conservé. Un produit sans unité reste à configurer, conformément à D21.
- Une saisie ou un résultat de conversion dépassant six décimales est refusé avec un message explicite ; aucun arrondi silencieux.
- Les produits non fractionnaires exigent des quantités entières, y compris dans l'unité de base après conversion.
- Les quantités/facteurs hors capacité `DECIMAL(20,6)` sont refusés. D24–D25 valident les montants en DECIMAL(20,6) et les remises HALF_UP à six décimales. Taxes et arrondis finaux des paiements/factures selon devise restent en Phase 4 (FCFA : zéro décimale).

## 5. Contrat catalogue et prix — D21–D23 validées le 02/10/2026

- Nom et prix de vente obligatoires ; marque, catégorie et autres renseignements facultatifs, y compris à l'import. Une unité manquante n'est pas remplacée par une unité fictive : le produit reste à configurer avant les opérations avec conditionnement.
- Référence SKU automatique possible pour une nouvelle création sans SKU ; un SKU existant n'est pas remplacé pendant une simple modification.
- Prix de vente TTC et coût de référence d'achat distincts par conditionnement. Le coût réel appartient à chaque réception ; une réception ne remplace pas automatiquement la référence.
- Mise à jour manuelle du coût de référence réservée à l'administrateur.
- Marque et fournisseur sont des référentiels distincts ; leur lien est facultatif.
- Catalogue partagé. L'administration globale des boutiques exige la capacité explicite `point_of_sale_manage_all` ; elle ne donne pas le droit d'opérer dans une boutique sans affectation active.
- Le sélecteur conserve une préférence sans accorder de droit. Chaque opération doit porter `operation_point_of_sale_id`, contrôlé côté serveur, pour conserver son contexte en présence de plusieurs onglets. Le raccordement des écritures de stock et des ventes appartient aux Phases 3–4.

## 6. Paramètres encore à proposer

| Paramètre | Validation attendue avant mise en service |
|---|---|
| Paiements et factures (Phase 4) | Arrondi final selon devise (FCFA : zéro décimale), taxes et répartition des remises |
| Taxes et précision | Taux/exemptions, précision par devise, arrondi ligne/document et répartition des remises |
| Audit | Durées, archivage/purge, accès, collecte technique éventuelle |
| Sauvegardes | Heure/responsable, outil, support/capacité, copie externe, chiffrement, durées, exercices et monitoring |
| Notifications | Canaux, destinataires, horaires, seuils, retries, rattrapage et rétention |
| Synthèse | Borne journée, sessions ouvertes, opérations tardives et corrections/version |

La validation des trois documents approuve une architecture de travail ; elle ne tranche pas automatiquement ces valeurs ni les décisions métier réservées aux phases suivantes.

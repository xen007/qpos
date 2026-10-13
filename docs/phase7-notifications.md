# Notifications Phase 7 — contrat et progression

État : **OK 7.D interne**, socle vérifié sur copie ; raccordements métier vérifiés ; producteur sauvegarde à vérifier en 7.F. Aucun email/SMS/WhatsApp émis par ce socle.

## Architecture livrée

`notification_events` conserve l'événement et sa clé SHA-256 (type, identité/version métier, boutique). Réutiliser la clé avec un autre contenu est refusé. `notification_jobs` fournit une file database dédiée, indépendante du défaut sync et sans changement .env. Le pump ne travaille que sur des événements validés et reprend les entrées dont la réservation a vieilli de dix minutes.

Le worker verrouille l'événement ; notifications Laravel natives et `notification_deliveries` sont écrites dans la même transaction. Une livraison est unique par événement/destinataire/canal. Les tentatives, disponibilité, échec et traitement sont tracés ; cinq tentatives et backoff. Seuls des codes de classe d'erreur sont conservés, jamais les messages arbitraires. Les préférences internes sont actives par défaut ; autres canaux absents de cette UI pour le moment.

Les droits, rôles, suspension et boutiques actives sont revérifiés dans le worker et les endpoints de lecture. Les alertes hors droits sont masquées même après livraison. Le changement de boutique de travail fixe ne donne aucun accès supplémentaire. La clé reste spécifique à la boutique ; les notifications n'ont aucune incidence sur les calculs, caisses ou réservations de stock.

Cloche, badge, dialogue latéral, lecture individuelle/tout-lu et préférences FR/EN. Flux de 30 lignes et curseur, polling 30 secondes, arrêt en onglet masqué, timeout des requêtes et conservation du focus. Pas de Reverb. Les liens gardent les contrôles des routes métier.

## Raccordements livrés

| Type | Déclencheur / identité | Destinataires soumis aux droits |
|---|---|---|
| Vente critique | Validation vente native ; seuil configurable, null = désactivé ; vente ID | Supervision des ventes boutique |
| Stock bas | Franchissement disponible sous seuil ; génération réarmée après remontée | Stock et vendeurs autorisés ; politique détaillée à compléter |
| Rupture | Franchissement disponible à zéro ; génération réarmée après réapprovisionnement | Responsables stock/boutique autorisés |
| Péremption | Lot/date d'expiration/seuil 90,30,7,0/boutique ; stock restant seulement | Stock autorisé |
| Dette en retard | Échéance locale dépassée, XAF natif, solde positif ; commande/échéance | Encaissement dette autorisé |
| Panier en attente | Événement pending_sale submitted, transaction native | Caissiers autorisés boutique, sale_create + cash_session_manage |
| Panier encaissé | Événement pending_sale completed | Vendeur préparateur encore autorisé |
| Synthèse envoyée | summary_deliveries sent, synthèse/version ; circuit Phase5 réutilisé | Administrateurs éligibles au récap complet |
| Commande fournisseur | Création achat natif validé, achat ID | Achat autorisé boutique |
| Écart caisse | Clôture manuelle avec différence non nulle, session ID | Caissier propriétaire et supervision autorisée |
| Session orpheline | Détection existante, sans pause, sans fermeture automatique | Supervision globale autorisée |
| Sauvegarde échouée | backup_run en échec, ID/tentative | Responsable système global, jamais gestionnaire stock par défaut |
| Email échoué | État retry/failed/ambiguous de la livraison, ID/tentative | Responsable système global |
| Compte créé | AdministrationAudit user.created dans sa transaction | Administration globale ou boutique selon portée audit |
| Rôle/droits modifiés | Audit de rôle/droits directs ; audit ID | Administration avec portée appropriée |

system_monitor, hooks natifs, scanner et états de franchissement livrés ; pump chaque minute Africa/Douala. Les états externes ambigus ne doivent jamais provoquer un nouvel envoi silencieux. Le circuit quotidien existant n'est pas remplacé.

## Preuves actuelles

- Migration 2026_10_13_120000 appliquée uniquement à qpos_test_phase7_20261012 ; source non migrée.
- 21 contrôles socle HTTP/SQL OK, fixtures transactionnelles annulées : idempotence, clé immuable, rollback, cible vendeur, rejeu, propriété de lecture, suspension, boutiques et rôles retirés, préférence désactivée, préférence sans élargissement de droits, tout-lu effectif, flux borné à trente lignes et pagination sans chevauchement.
- File database et véritable queue:work vérifiés ; événement fixture #9 traité et rejoué sans doublon. Fixture conservée uniquement en copie pour navigateur.
- Échec de récupération utilisateur simulé : aucune livraison partielle, état retry/tentative/code sûr enregistrés, reprise réussie. Test annulé.
- Deux processus PHP simultanés, plages temporelles chevauchantes, événement fixture #11 : une seule livraison. Preuve notification-race-proof.json.
- Navigateur Jean : cloche 44 px, panneau affiché, lecture réelle et badge zéro, fermeture Escape avec focus rendu à la cloche ; page préférences limitée à ses capacités. Captures privées. Données fictives #9/#11 ne représentent pas des événements automatiquement raccordés à la vente.
- Liens comparés aux URI du routeur : synthèse, commande fournisseur, vente, stock/péremption, utilisateurs et rôles concordent. L'accès effectif à chaque document déclencheur reste à rejouer après raccordement.
- Syntaxes de onze fichiers PHP OK ; build et garde CSS OK, sprite Lucide 62 icônes. Module notifications ≈3 kB, gzip1,28.


## Verification finale 7.D

39 controles declencheurs/SQL OK : stock/rearmement, vente critique, achat, passation, ecart caisse, peremption 90/30/7/0, dette native soldee exclue, BDT exclu, orpheline sans fermeture, audits et syntheses. Fixtures annulees. 21 socle, 13 droits/service, 20 HTTP, 20 statistiques et 11 navigation OK. Suites d'ecriture rejouees seules apres collision de fixtures concurrentes identifiee.

Worker reel et deux processus simultanes : evenements 239/240, une livraison chacun, rejeu sans doublon ; echec/rollback/reprise OK. Droits retires et situations resolues annulent ou masquent les alertes. Stock bas vendeur : pending_sale_prepare + sale_create sans droits stock ajoutes.

Menu restaure ; un rapport actif, supervision distincte des sessions, aucune gestion boutiques pour Jean/Marie sans capacite existante. Invite : reconnexion distincte du refus de droits.

1440/768/390/320, FR/EN, clair/sombre sans debordement global observe. Polling navigateur 30 s ; suspension arriere-plan et arret 401/403 verifies en test isole du module. Outil navigateur incapable de reproduire document.hidden : aucune preuve navigateur pretendue pour cet etat.

Build : 2023 modules, 62 icones, CSS 49,85 kB/9,69 gzip ; notifications 3,28/1,39. Syntaxes/diff OK. Source qpos non migree, copie uniquement, aucun envoi externe. Producteur sauvegarde/restauration 7.F ; transports 7.E ; deploiement/nettoyage source 7.H.

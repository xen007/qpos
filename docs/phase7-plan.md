# Phase 7 — contrat d'exécution consolidé

Base vérifiée : `main`, `0d6abc2`. Autorisation : prompt du propriétaire et lancement du 12/10/2026. Référence originale conservée dans le stockage privé. Ce document précise le prompt sans supprimer ses livrables.

## Invariants et limites

- Préserver les calculs, historiques BDT, affichage XAF à deux décimales et arrondi final de règlement, lots, caisses, synthèses et permissions acquis en phases 0–6. Les extensions et adaptations de présentation prévues ici sont autorisées ; toute régression est corrigée dans son lot.
- Aucune modification `.env`, secrets, tokens, mots de passe ou données métier source pour les tests. Aucun seed fresh source.
- Tests d'écriture sur une nouvelle copie restaurable, base vérifiée avant chaque scénario. Mail mémoire, canaux externes simulés, aucun paiement ni message réel dans les tests.
- Commits locaux par lot cohérent réellement vérifié, fichiers sélectionnés explicitement ; aucun push. Preuves, SQL et secrets restent hors Git.
- Checkpoint privé après chaque étape : état, preuves, fichiers, prochaine action, commit. Un lot partiel ne reçoit pas OK.
- Contrôle du quota cinq heures entre étapes : arrêt propre au prochain checkpoint lorsque le solde atteint 3 % ou moins. Le contrôle n'est pas une surveillance continue du fournisseur.

## Précisions retenues

1. **Droits** : conserver le rôle technique Admin ; libellé Administrateur global. shop_admin possède une liste explicite de capacités locales. Droits directs limités aux capacités délégables et aux droits de l'acteur ; aucune capacité globale déléguée par un administrateur local. Origine des droits visible ; retrait rôle/droit direct testé ; protection du dernier administrateur global conservée.
2. **Boutique fixe** : préférence administrative d'un utilisateur, active et accessible. Contrôle serveur sur les opérations ; aucune extension de portée. Les rapports et fonctions de supervision globaux conservent leurs portées. Un poste partagé n'implique aucune autorisation liée seulement à son navigateur.
3. **Design** : palette Soft Modern déjà validée, Tailwind/Lucide et composants existants consolidés. Libellés visibles pour actions métier, nom accessible pour icônes secondaires. Mobile 390, tablette 768, bureau 1440 ; contrôle reflow 320 et zoom 200 %. Cibles tactiles 44 px, clavier, focus, contrastes, thèmes FR/EN. Aucun constat d'accessibilité global sans preuves.
4. **Statistiques** : distinguer période précédente de même durée et même période année précédente ; bornes Africa/Douala, retours nets, coûts inconnus signalés. Dépenses/résultat non attribués artificiellement à un vendeur ou produit. Exports et écran appliquent les mêmes filtres et permissions.
5. **Notifications** : outbox durable lié à l'opération validée ; déduplication événement/version/destinataire/boutique/canal. Worker avec reprise, tentatives et échecs ; contrôle des droits au traitement et à la consultation. Préférences sans élargissement d'accès. Réutiliser summary_deliveries et les règles de correction quotidienne. Polling limité, suspendu en arrière-plan ; pas de Reverb sans besoin mesuré.
6. **Événements** : documenter déclencheur, gravité, seuil et destinataires pour les quinze types du prompt. Stock bas au franchissement, réarmement après réapprovisionnement ; péremptions par lot/seuil/date, dettes soldées exclues. Vente critique signifie seuil configurable, désactivé par défaut. Sauvegarde/transport en échec destinés aux responsables système ; gestionnaire stock ne reçoit pas automatiquement les alertes système.
7. **Intégrations** : email via transport existant, sans secrets en clair en base. SMS/WhatsApp désactivés par défaut ; statut distinct simulé/configuré/réception vérifiée. Aucun compte externe, destinataire ou secret inventé. Le développement indépendant continue si la configuration externe manque ; la preuve de réception réelle reste explicitement en attente.
8. **Sauvegarde** : une destination S3 compatible pour la première implémentation, si dépendances disponibles ; pas trois fournisseurs parallèles. Choix de destination et activation administrables, credentials hors réglages ordinaires. Sauvegarde DB + fichiers métier, manifeste et restauration chronométrée sur copie. Plan compatible avec serveur arrêté la nuit ; tâche de rattrapage et alerte sur ancienneté. RPO 24 h/RTO 8 h mesurés. Pas de purge d'une dernière sauvegarde valide ; rétention documentée avant activation.
9. **Exploitation** : état scheduler/worker/sauvegardes/livraisons/espace disque visible avec permission dédiée. Procédure démarrage automatique, redémarrage et reprise Windows documentée. URL CLI à aligner par configuration propriétaire sur l'hôte définitif ; aucune réécriture silencieuse `.env`.
10. **Nettoyage** : suppression uniquement après inventaire des consommateurs actifs ; aucune obligation de supprimer une bibliothèque encore nécessaire. Docker documenté seulement si effectivement préparé et vérifié. Documentation des rôles canonique : `docs/roles-reference.md`.
11. **Hors lot** : prévisions/bons de commande intelligents et « assurances » nécessitent cadrage métier distinct ; aucune intégration de paiement bancaire réelle, synchronisation hors ligne ou migration majeure Laravel/PHP implicite.

## Lots et critères de fin

| Lot | Livraison | Critère OK |
|---|---|---|
| 7.A | Cartographie, copie vérifiée, audit visuel et priorités | Écrans recensés, preuves navigateur, écarts et couverture explicités |
| 7.B | shop_admin, droits directs, boutique fixe, documentation rôles | Autorisations positives/négatives, affectations, révocations et dernier Admin vérifiés sur copie |
| 7.C | Composants, POS, dashboard/statistiques, autres écrans | Parcours par rôle, thèmes/langues, dimensions, clavier ; invariants financiers préservés |
| 7.D | Notifications internes, préférences, worker | Événements, rollback, déduplication, reprise, droits/boutiques et lecture vérifiés |
| 7.E | Email, canaux externes optionnels | Transport simulé vérifié ; configuration/réception réelle distinguées sans faux OK |
| 7.F | Sauvegarde, restauration, état système | Archive restaurée et parcours contrôlés ; limites externes et RPO/RTO documentés |
| 7.G | Exploitation, documentation, nettoyage, performances | Instructions exécutables, dépendances inventoriées, volumes et bundles mesurés |
| 7.H | Régression et livraison contrôlée | Scénarios pertinents rejoués, migration/restauration vérifiées, état source rapproché et bilan complet |

## Preuves et livraison

Les résultats enregistrent scénario, attendu, observé, base cible, effets SQL, date, artefacts et statut. Les fonctionnalités externes non configurées ne sont jamais présentées comme vérifiées en production. Les corrections résolubles dans le périmètre sont appliquées puis vérifiées sans nouvelle demande de validation. Le bilan final indique les huit états, fichiers, résultats, limites, commits et procédure propriétaire.

# Phase 7 — bilan de livraison

14/10/2026. Branche main, base 0d6abc2. Travail approuvé, commits locaux, aucun push. Source qpos mise à jour par migrations additives après sauvegarde/restauration vérifiée. Les résultats ne constituent pas une certification « zéro faille ».

## État des huit sous-lots

| Lot | État | Référence |
|---|---|---|
| 7.A | OK : cartographie/audit et copie restaurable | 72b75a5, phase7-cartographie.md |
| 7.B | OK : administrateur boutique, droits directs, boutique fixe | 8504ebc, roles-reference.md |
| 7.C | Fonctionnel vérifié ; accessibilité complète non certifiée | 07fb0c7, phase7-progress.md |
| 7.D | OK : notifications internes, préférences, droits, outbox/worker | d319d9f, phase7-notifications.md |
| 7.E | Développé et vérifié en simulation ; réceptions réelles non configurées/vérifiées | 2286d69, phase7-email.md |
| 7.F | Backup local/restauration OK ; S3 réel non configuré | 60dd423, phase7-backups.md et BACKUP-RESTORE.md |
| 7.G | OK fonctionnel : UX/exploitation, guides, performances | 39cac31, phase7-exploitation.md |
| 7.H | Régression et installation source vérifiées ; compléments visuels/matériels restent à contrôler | Présent lot, limites ci-dessous |

## Dernière régression réellement exécutée

Sur copie qpos_test_phase7_20261012, aucun paiement/message/impression réel.

- 87 contrôles G :21 dépenses/sélections/supervision,23 apports initiaux,28 audit/tickets/options/références,11 catégories globales/portées ;4 simulations impression. Écritures de ces suites annulées.
- Après G :39 scénarios natifs stock/ventes/caisse/signaux,29 canaux externes factices,20 statistiques/exports,20 droits directs/administration/boutiques. Rollback confirmé.
- 15 contrôles de sessions HTTP réelles sur 8124 : Admin→Jean→Marie, redirections, absence de500/403inattendu/page expirée, déconnexions et refus de conserver le compte précédent.
- Concurrence réelle de deux processus PHP pour le nouvel apport initial. Échec initial conservé dans les preuves privées : une lecture MVCC antérieure au verrou pouvait manquer le document déjà validé et provoquer une erreur de clé unique ; aucun doublon de stock. Correction : verrou acteur/produit avant la première vue de lecture et lecture courante du document. Reprise réussie : même document,10 unités,3 mouvements. Les23contrôles d'apport initial ont ensuite repassé.
- 27 syntaxes PHP,2 JSON,2 scripts PowerShell parsés/CheckOnly. Syntaxe de la correction H validée. Build Vite/garde CSS OK,2 023 modules. Détails et mesures dans phase7-exploitation.md.
- Preuves datées privées sous storage/app/phase7, non commitées. Les verdicts antérieurs des phases 5/6 ne sont pas recyclés comme de nouveaux tests.

## Mise à jour de la source

Avant migration : archive récente privée, base restaurée qpos_restore_20261014094355_e30b7787. **83 tables/1 567 lignes/36 fichiers vérifiés**, schéma, comptages et SHA de chaque fichier. Restauration 457,079 s, soit7min37s. Ce chronométrage concerne DB+extraction/vérification, pas une reprise complète d'une machine perdue.

Huit migrations Phase 7 appliquées : administration boutique, notifications/index/droits, livraisons externes, backup_runs, initial_stock_entries, option receipt_auto_print, user_activity. Source désormais **93 tables, aucune migration en attente**.

Comparaison avant/après : produits, conditionnements, lots, stocks/mouvements, ventes/lignes, paiements/allocations, achats/réceptions, caisses/mouvements, corrections/retours, avoirs, synthèses connues, affectations de rôles/droits directs et champs protégés des comptes conservés. .env inchangé. Aucun fresh, conversion BDT, modification de prix historiques ou réinitialisation de mot de passe.

AdministrationAccessSeeder appliqué aux références prévues. Huit catégories de dépenses actives ajoutées car la table source était vide ; aucun référentiel existant remplacé.

Caches config/routes/vues/permissions nettoyés et signal queue:restart. Pas de purge sessions/données. Aucun redémarrage d'Apache annoncé : vider OPcache CLI ne vaut pas un reset Apache.

## Exploitation installée et observée

- Scheduler PID9736, worker notifications34532, worker backups33468 au lancement. CheckOnly ultérieur : un processus de chaque type, pas de duplication.
- Tâche Windows QPOS-Operations-75927A3AE77B installée, relance chaque minute. Compte Windows courant, session interactive nécessaire, processus masqués ; aucun mot de passe stocké. MariaDB/serveur HTTP doivent être démarrés.
- Première sauvegarde source après migration :backup_runs#1, état verified, cloud disabled, fin le 14/10 à 11:13:38 (Africa/Douala), sans erreur.
- Heartbeats scheduler/notifications/backup observés. À 11:19:38 (Africa/Douala) :12livraisons internes, **0livraison externe**. Ces nombres sont un constat, ils évolueront avec l'exploitation.
- Synthèse email et canaux email/SMS/WhatsApp confirmés désactivés. Pas de modification SMTP/secrets. Le transport/réception restent des étapes de configuration propriétaire.
- RPO 24 h exige une machine active ; rattrapage au démarrage prévu. RTO 8 h n'est pas une garantie globale de récupération matérielle.

## Comptes et boutiques conservés

- Admin : Admin global, préparation+encaissement+supervision système ; boutiques 1/2/3.
- Marie et Pauline : Demo Cashier, sessions/encaissement ; boutique 1.
- Jean : sales_associate, préparation/passation ; boutique 1, pas de session caisse par défaut.
- Pierre : Demo Stock, stock selon capacités ; boutique 1.
- Aucun compte/identifiant/mot de passe recréé. Pour encaisser avec un vendeur, attribuer explicitement les capacités appropriées ; ne pas supprimer les contrôles d'autorisation.

## Ajouts propriétaire livrés

Menus restaurés et états actifs corrigés en D. Achats/péremption, catégories dépenses, parcours de démarrage, sélections bornées, supervision/historique, tickets/actions/quantités, option impression et auth/activité livrés en G, corrigés/revérifiés en H. GUIDE-DEMARRAGE.md fournit les cas concrets.

L'impression automatique reste une option par boutique, désactivée par défaut. Activer dans la boutique après choix du poste ; le dialogue navigateur standard demeure. La source et la copie partagent le même code/schéma ; la copie contient aussi les fixtures de simulation, la source conserve les données du propriétaire.

## Limites restantes

1. Outil navigateur CUA expire encore malgré HTTP réel fonctionnel. Les nouvelles vues E/F/G ont des preuves HTTP, pas une preuve visuelle récente. Les dimensions/thèmes/clavier de C/D sont documentés séparément ; recontrôler les ajouts, reflow, clavier et zoom 200 % sur le navigateur utilisateur. Pas de certification WCAG 2.2 AA globale.
2. Scanner, imprimante/ticket/étiquettes physiques et impression silencieuse non certifiés. Dialogue standard simulé, aucune impression physique.
3. SMTP/SMS/WhatsApp : pas de réception réelle, envois désactivés. S3 : adapter et contrôles factices livrés, aucun bucket réel vérifié ni destination active.
4. Tâche Windows interactive : ne remplace pas un service fonctionnant sans session Windows. Horaires nocturnes nécessitent un hôte actif. Redémarrage complet Windows/logoff non simulé ici.
5. Bibliothèques historiques conservées après inventaire : jQuery/DataTables/Select2 et gabarits Bootstrap/AdminLTE ont encore des consommateurs ; pas de suppression aveugle. Pas de Docker annoncé.
6. Bons de commande/prévisions intelligents et assurances : cadrage métier distinct, non développés dans ce lot.

## Fichiers et commits

Les fichiers de chaque lot sont listés par git show --stat HASH. H corrige InitialStockService et actualise la documentation de livraison ; aucun script privé ni archive/SQL dans Git. Les huit commits locaux sont destinés au push par le propriétaire, après revue de ce bilan. Les checkpoints privés permettent une reprise sans réexécuter migrations/seed/backup ni recréer les processus déjà actifs.

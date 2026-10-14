# Mise à jour non destructive

1. Relever HEAD et git status. Conserver les modifications du propriétaire ; ne pas utiliser reset --hard, clean ou seed fresh.
2. Suspendre les écritures métier pour le basculement. Sauvegarder DB, fichiers métier et configuration protégée ; vérifier SHA et restaurer une copie avec contrôles de tables/lignes/parcours. Ne pas considérer un fichier SQL non restauré comme une preuve suffisante.
3. Installer les dépendances du lock et compiler les assets. Aucun composer update global.
4. Appliquer php artisan migrate --force sur la base confirmée. Phase7 ajoute les droits/notifications/livraisons/sauvegardes, la preuve des apports initiaux, l'option impression et l'activité. Aucune conversion BDT ni modification des ventes/lots historiques.
5. Les permissions de référence utilisent AdministrationAccessSeeder avec revue des droits existants. ExpenseCategoryReferenceSeeder ajoute huit catégories uniquement si le référentiel est complètement vide ; il conserve toutes les catégories existantes et les désactivations. Ne pas exécuter DatabaseSeeder global sur une base métier pour une mise à jour.
6. Nettoyer les caches de compilation : config:clear, route:clear, view:clear, permission:cache-reset. queue:restart, puis relancer les workers. Les sessions utilisateurs et données métier ne sont pas purgées.
7. OPcache est propre à chaque SAPI : une commande PHP CLI ne nettoie pas celui d'Apache. Redémarrer proprement Apache si nécessaire, avec coordination propriétaire. artisan serve ne reçoit pas non plus un nettoyage Apache.
8. Vérifier connexion/déconnexion et les rôles, boutique active, POS, caisse, catalogue/stock, rapports/exports, audit, notifications/état. Même code, même version de schéma et base réellement visée sur l'installation utilisateur.
9. Reprendre les opérations ; noter HEAD, migrations, SHA .env inchangé, contrôles et limites. Les activations externes ne sont jamais un effet caché de la migration.

## Retour arrière

Ne pas annuler une migration en production en supprimant les tables de livraisons/audit déjà alimentées. Restaurer un ensemble cohérent code + DB + fichiers sous contrôle, en préservant les preuves et opérations postérieures. Voir BACKUP-RESTORE.md.

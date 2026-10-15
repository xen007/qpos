# Bons de commande et suggestions

## Préparer une boutique

1. Régler les seuils selon `stock-alerts-guide.md`.
2. Dans **Catalogue fournisseurs**, associer les produits, conditionnements, délais, minimums et multiples de commande. Le prix estimatif est daté ; un coût inconnu reste inconnu. Plusieurs fournisseurs sont possibles, avec au plus un préféré.
3. Vérifier les affectations boutique et les permissions. L'administration gère le catalogue fournisseur. Le rôle de démonstration stock reçoit lecture/création/modification/réception des bons ; approbation, émission, surplus et planification ne sont pas accordés implicitement.
4. Dans **Suggestions > Paramètres**, choisir un responsable et des destinataires habilités. Le défaut est **à la demande** ; aucun fournisseur n'est contacté automatiquement.

Les associations fournisseur et prix réels doivent être renseignés par l'exploitant. La mise à jour du module n'invente ni fournisseur préféré ni tarif d'achat et ne remplace pas ses comptes ou produits.

## Comprendre les suggestions

Fenêtre 30, 60 ou 90 jours complets, Africa/Douala. Consommation native XAF en unité de base, nette des retours à leur date ; BDT historique séparé. L'écran indique la durée observée, le manque d'historique et le délai inconnu.

Avec assez d'historique : cible = max(optimal, consommation moyenne × (délai + cycle de revue + sécurité)). Sinon : cible = optimal. Besoin = max(0, cible − disponible − réceptions attendues avant horizon). Les attentes en retard, trop tardives ou sans date restent affichées séparément. Un achat lié à un bon n'est pas compté deux fois.

La proposition est arrondie au conditionnement, au minimum et au multiple fournisseur. Les transferts sont des alternatives informatives depuis des boutiques accessibles ; aucune réservation ni soustraction automatique. Les données manquantes, prix anciens, maximums et risques de péremption sont explicites. Un brouillon déjà ouvert est indiqué pour éviter une seconde commande.

## Parcours

1. Filtrer les propositions, sélectionner les lignes affichées et créer les brouillons, ou choisir **Bon manuel**.
2. Un bon concerne un fournisseur, une boutique et XAF. Modifier les quantités/coûts dans le brouillon. La création rapide conserve le formulaire en cours.
3. Faire approuver par une personne autorisée. Le serveur revérifie les règles et demande confirmation des avertissements. Un achat lié unique est créé, sans réception ni entrée en stock.
4. Émettre manuellement le bon, utiliser **Imprimer** pour ouvrir la vue A4 puis le dialogue du navigateur, ou télécharger son PDF/Excel ; marquer la confirmation fournisseur quand elle est reçue. Le même droit d'export et la même portée boutique protègent ces trois sorties. **L'émission n'est pas une preuve d'envoi email.** L'impression silencieuse et le matériel ne sont pas certifiés par ce module.
5. À chaque livraison, saisir les quantités réellement reçues, lots et péremptions. Le bon peut être reçu en plusieurs fois. Une réception ajoute le stock une seule fois via les services existants.
6. Pour un surplus, utiliser le parcours autorisé avec motif et avenant. Pour un reliquat abandonné, clôturer le reliquat ; ne pas supprimer les réceptions passées. Une clôture avec montant payé excessif est refusée jusqu'à régularisation.

Après validation, ne pas modifier l'achat lié en contournant le bon. Les versions, acteurs, motifs et instantanés sont conservés. Une reprise ou un double clic avec la même clé rejoue le résultat, sans doubler achat ou réception.

## Planification et livraison

- Quotidien : heure par boutique, défaut 21 h ; hebdomadaire : jour/heure, défaut lundi 8 h. Fuseau Africa/Douala.
- Le scheduler doit exécuter `php artisan schedule:run` chaque minute. Les trois commandes sont `qpos:purchase-suggestions-daily`, `qpos:purchase-suggestions-weekly`, `qpos:purchase-alerts-critical`.
- Chaque créneau est unique par boutique. Le traitement durable avance par 200 produits, conserve ses résultats et reprend les échecs avec attente croissante, au plus cinq tentatives. L'historique expose l'état ; aucune approbation automatique.
- Une programmation ou un responsable changé annule les tâches automatiques en attente correspondantes. Les droits sont revérifiés au traitement et à la notification.
- Un bon créé notifie les destinataires configurés ; un bon entièrement reçu notifie son créateur s'il conserve ses accès. Les préférences de livraison existantes s'appliquent.
- L'envoi fournisseur externe reste optionnel et désactivé. SMTP, adresse confirmée et consentement humain doivent être traités avant tout envoi réel. Aucun test du module n'envoie email ou paiement réel.

Avant d'utiliser un document commercial, vérifier dans les paramètres du site les coordonnées et le logo de l'entreprise : les anciennes valeurs de démonstration ne sont pas corrigées en inventant une adresse. Le bon intègre le logo local configuré sous `public/assets/images` (PNG/JPEG/GIF, au plus 2 Mio) lorsqu'il existe ; aucune image distante n'est téléchargée pendant la génération PDF.

## Exploitation Windows

Le superviseur `scripts/Start-QposOperations.ps1` maintient `schedule:work` et les workers notifications/sauvegardes. `-CheckOnly` donne leur nombre sans les lancer. La tâche `QPOS-Operations-…` relance le superviseur chaque minute pendant la session Windows. L'ancien `QPOS-Phase5` n'est désactivé par l'installateur que si son action correspond au lanceur de ce même dossier : conserver un seul ordonnanceur. L'ordinateur et MariaDB doivent rester disponibles ; ce dispositif local n'est pas un service Windows hors session.

Après déploiement : vider les caches, puis `php artisan queue:restart` et vérifier la reprise des workers. Un nettoyage du cache peut effacer le signal de redémarrage. `php artisan schedule:list` vérifie les commandes ; leur présence ne prouve pas un envoi réel. Les tests de cette livraison utilisent une copie et un transport email simulé.

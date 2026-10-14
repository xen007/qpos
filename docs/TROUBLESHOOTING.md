# Dépannage pratique

## Connexion réussie puis 403

Vérifier le rôle, la permission de la route, l'affectation boutique active et l'éventuelle boutique fixe. Jean vendeur prépare si passation activée ; sa capacité ne comprend pas automatiquement encaissement/session caisse. Marie caissière encaisse dans sa session personnelle. Un rôle ou une préférence de notification ne donne pas toutes les capacités. Ne pas supprimer les contrôles pour contourner le refus.

## Page expirée / changement de compte

Une ancienne page POST conserve un jeton de l'ancienne session. Déconnexion puis nouvelle connexion, recharger le formulaire avant de le soumettre. Logout invalide la session ; plusieurs onglets peuvent conserver l'ancien écran. Vérifier cookies/URL et la bonne application/port. Ne pas afficher les tokens ni désactiver CSRF. Audit distingue login/logout et dernière requête observée, sans déclarer un navigateur « connecté » par supposition.

## Erreur500

Lire le journal local en limitant la divulgation ; ne pas activer APP_DEBUG en production. Vérifier PHP CLI/Apache, extensions, migrations, assets et caches. Différence entre copie8124 et source8000 : contrôler version de code, DB et schéma avant de relancer arbitrairement artisan serve. Ne pas lancer fresh pour un cache.

## Bouton encaisser / caisse

Un vendeur prépare et confie le panier ; un caissier autorisé ouvre sa session puis encaisse. Un utilisateur cumulant les deux capacités peut préparer et encaisser. La caisse physique ne contient que les espèces ; carte/OM/MM/Wave/virement ne constituent pas du cash à compter.

Supervision affiche les sessions ouvertes. Choisir Sessions fermées pour les clôtures déjà réalisées. Une session fermée n'est pas orpheline. Seules les sessions ouvertes inactives et sans pause sont candidates à une clôture supervisée motivée. La clôture journée ne ferme jamais les caisses.

## Catégorie de dépenses vide

L’administrateur global crée ou réactive les catégories depuis Dépenses avec expense_manage ; un administrateur local ne modifie pas ce référentiel partagé. Les catégories sont globales, leur désactivation conserve les anciennes dépenses. Les références additives de mise à jour ne remplacent pas un référentiel existant.

## Stock / péremption

Voir GUIDE-DEMARRAGE.md. Produit=fiche ; apport initial=biens déjà présents avant opérations ; achat=arrivage fournisseur ; inventaire=comptage/écarts. Les ouvertures bloquées nécessitent leur approbation documentée distincte. Un produit garde une seule classification connue datée/non périssable, avec dates propres aux lots. Les contradictions historiques se réconcilient explicitement.

## Ticket ne s'imprime pas automatiquement

Activer Lancer l'impression du ticket après encaissement dans la boutique. Le navigateur standard affiche son dialogue ; le serveur ne choisit pas l'imprimante et ne contourne pas cette protection. Lancer depuis Ventes > ticket demande l'impression ; un nouveau clic volontaire peut réimprimer. Le nonce et le nettoyage de l'URL empêchent le relancement automatique au rechargement. Scanner et imprimante doivent être vérifiés sur le matériel réel.

## Alertes / backup absents

Vérifier scheduler, les deux workers, processus Windows, queues et âge du dernier succès dans État du système. Préférences/droits/portées/pertinence peuvent annuler une alerte. « Accepté par fournisseur » ne signifie pas reçu ; un timeout ambigu ne se renvoie pas silencieusement. Cloud désactivé/non configuré ne se présente pas comme sauvegarde externe vérifiée. PC éteint : rattrapage au démarrage, aucun traitement magique hors ligne.

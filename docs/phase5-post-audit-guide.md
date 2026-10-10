# Guide du lot post-audit Phase 5

## État de validation

Ce document décrit le lot en cours de validation. Le bilan final doit confirmer les parcours navigateur et toutes les régressions avant le commit unique du propriétaire. Les tests et captures proviennent de copies ; le serveur source 8000 conserve sa base `qpos`.

## Organisations et droits

Le responsable global gère les boutiques et affectations. Un responsable local, une caissière et un vendeur n'accèdent qu'aux boutiques autorisées. Le cumul vendeur/caissière permet de préparer et d'encaisser au même poste ; il n'accorde pas une administration globale.

### Un même poste prépare et encaisse

1. Ouvrir sa propre session dans **Sessions de caisse**, saisir le fond réellement compté.
2. Dans **Caisse**, ajouter produits et conditionnements, choisir le client ; passage reste le défaut.
3. Vérifier le total, les allocations, les espèces remises et la monnaie. Un moyen externe exige une référence.
4. Encaisser, consulter le ticket. Fermer sa session avec le comptage réel et un motif en cas d'écart.

### La vendeuse prépare, la caissière encaisse

1. Le responsable active la passation dans les paramètres de la boutique et affecte les permissions de préparation/encaissement.
2. La vendeuse prépare le panier puis **Envoyer à la caissière**. Cette étape ne réserve pas le stock et ne crée pas de paiement.
3. La caissière ouvre sa propre session, consulte **Ventes en attente**, prend une préparation et vérifie le nouveau devis.
4. Elle confirme toute évolution signalée, renseigne les allocations/références et encaisse. Le ticket conserve les deux auteurs.
5. Une préparation peut être restituée avec motif ; son bail et son expiration évitent un blocage permanent. Une issue réseau incertaine se reprend avec la même opération, sans fabriquer une seconde vente.

## Rapports, monnaie et clôtures

**Répartition par moyen** distingue une vente unique de ses allocations. Les flux fournisseurs, dettes et remboursements sont séparés ; seul le flux espèces entre dans le comptage physique. Excel et PDF reprennent les filtres et droits de l'écran.

Une pause personnelle, une session de caisse et une clôture journalière sont trois actions différentes. La journée produit un récapitulatif immuable ; des opérations tardives entraînent une version corrigée à 23h55 et une notification explicite. Aucune fermeture automatique de caisse.

**Supervision des caisses** concerne les sessions sans activité au-delà du délai configuré. Une clôture supervisée exige le comptage, le motif et la permission dédiée ; elle laisse une preuve distincte.

## Déploiement après bilan accepté

Le code modifié est commun aux serveurs locaux, mais les migrations de ce lot ont été exécutées sur les copies uniquement. Après validation du bilan : sauvegarder et vérifier la restauration de `qpos`, vérifier la cible, appliquer la migration additive puis vérifier les rôles et affectations. Ne pas faire `migrate:fresh` sur la base de travail du propriétaire. Redémarrer le serveur ou réinitialiser OPcache selon le mode d'exécution ; recharger les pages après mise à jour des assets.

Une compilation qui annonce un succès ne suffit pas : contrôler la présence des utilitaires Tailwind et le rendu. Dans l'environnement Codex Windows utilisé ici, le build sous sandbox a omis ces utilitaires ; le build local hors sandbox les a rétablis, sans installation de paquets.

Les scripts de reprise et preuves privées sont dans `storage/app/phase5-corrections/`. Le checkpoint canonique est `storage/app/session-b/checkpoint.md`. Les mots de passe de comptes existants et les tokens ne sont pas modifiés.

## Limites à conserver au bilan

Les paiements sont un traçage manuel, sans règlement réel ni API opérateur. Mail de test en mémoire, aucun email externe envoyé. Imprimante, scanner physique, SMTP réel et comptes marchands exigent une validation en conditions réelles. Les filtres avancés catalogue restent en Phase 6 ; commandes intelligentes et assurances à définir restent en Phase 7.

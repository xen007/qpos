# Réapprovisionnement — contrats approuvés

14/10/2026 ; base fe7e79d. Complément des phases 0–7, sans conversion historique. Périmètre A–H approuvé ; les corrections nécessaires et tests sur copie sont autorisés. Commit local par lot vérifié, sans push.

## Politique de stock

Seuils DECIMAL(20,6), en unités de base. Héritage par champ : produit/boutique, produit global, boutique, global. Valeur NULL = hériter ; zéro est une valeur explicite. Alerte activable séparément ; plafond nullable/désactivable. Valeurs effectives : minimum ≤ optimal ≤ maximum si plafond activé. Global minimum reprend le seuil existant, optimal 50, plafond désactivé par défaut. Réglages communs réservés à l'administration globale ; réglages locaux selon permission et affectation. Chaque changement est audité.

État primaire : rupture (0), bas (≤ minimum), normal, surstock (> maximum activé). Indicateur critique distinct : couverture inférieure au délai fournisseur lorsque consommation suffisamment documentée. Le disponible réutilise StockAvailability/StockService ; ni péremptions, ni stock bloqué, ni ouverture non allouée ne sont rendus disponibles. Les notifications existantes utilisent cette politique, franchissement et aggravation distincts, réarmement après réapprovisionnement. Les alertes initiales après configuration sont tracées comme réévaluation. Au plus une alerte de chaque type par produit/boutique/jour métier.

## Fournisseurs et suggestions

Relation explicite multiple produit/fournisseur, conditionnement actif, référence fournisseur, délai, minimum/multiple de commande, prix estimatif daté, préférence unique facultative. Marque ne détermine jamais le fournisseur. Paramètres de planification par boutique : demande/quotidien/hebdomadaire, horaires Africa/Douala, acteur responsable et destinataires habilités.

Consommation native en quantité de base, nette des retours datés dans la fenêtre choisie 30/60/90 jours ; historiques sans preuve non intégrés. Durée observée et insuffisance de données affichées. Sans historique suffisant, cible = optimal. Sinon cible = max(optimal, consommation journalière × (délai + cycle + sécurité)). Besoin = max(0, cible − disponible − réceptions attendues avant horizon). Attentes tardives affichées séparément. Les commandes ouvertes et bons validés non encore liés sont dédupliqués. Paniers ne réservent pas le stock. Arrondi au conditionnement puis minimum/multiple ; tout dépassement du maximum ou risque péremption est explicite et demande validation. Produit sans fournisseur/coût/conditionnement reste à compléter, aucun coût fictif.

## Bons et réception

Un bon = fournisseur/boutique/devise XAF. Draft → approved → issued/confirmed → partial/received ; cancelled avant réception ; closed pour reliquat abandonné. Émission manuelle/export et livraison email ont des états indépendants. Mise à jour/suppression uniquement brouillon. Instantanés des noms, références, unités, facteurs, quantités, coûts et raisons conservés à la validation. Compteur annuel transactionnel. Événements append-only, acteur/reason/avant-après. Clé idempotente et empreinte pour chaque mutation ; double clic, retry et concurrence ne doublent ni bon, ni achat, ni réception.

Approbation crée UN achat sans réception via PurchaseService ; lignes reliées dans purchase_items. Toute réception utilise PurchaseService::receive, une seule entrée via StockService ; quantités du bon dérivées des réceptions existantes. Lots, péremptions, CMV, coût réel et finance gardent leurs contrats. Surplus : permission et motif, avenant explicite sans réécriture des lignes reçues ; reliquat clôturé sans annuler stock ou dette existants. Pas de dette/paiement supplémentaire créé pour une même commande. Changements sur un achat lié doivent passer par le bon.

## UI et sécurité

Écran réapprovisionnement, filtres combinables et bornés ; une sélection ne dépasse pas les résultats accessibles. Création rapide réutilise les règles catalogue (catégorie facultative, prix requis, unité opérationnelle/fractionnement, doublons protégés) et crée zéro stock. POS rupture configurable visible/masqué, scan conservé avec explication ; contrôle serveur vente préservé, cas périmé conserve la confirmation existante. Filtres fournisseur des ventes/stock uniquement si provenance définie, sans attribuer toutes les ventes à un fournisseur de catalogue. Tous écrans/exports/liens/suggestions revérifient permissions et boutique ; FR/EN, mobile/thèmes/clavier. Excel/PDF réutilisent les bibliothèques présentes. Envoi fournisseur désactivé par défaut, autorisation humaine, destinataire confirmé, aucun essai réel.

## Livraison

A cartographie/copie ; B politiques/alertes ; C filtres/rupture/création ; D fournisseurs ; E bons/exports ; F réceptions ; G suggestions/planification ; H régression/doc/source. Preuves réelles séparées des limites SMTP, matériel et navigateur. Copie neuve restaurée depuis source, pas de fresh source ni import de fixtures. Déploiement source après sauvegarde restaurée et contrôles de préservation. Checkpoint privé storage/app/replenishment/checkpoint.md et progression versionnée.

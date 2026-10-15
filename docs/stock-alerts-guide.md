# Seuils, alertes et filtres de stock

## Réglages

Menu **Seuils et alertes de stock**, selon les droits et la boutique active. Les réglages communs demandent l'administration globale. Les valeurs sont des quantités en unité de base, avec au plus six décimales pour conserver les fractions réelles.

Priorité par champ : produit dans la boutique, produit global, boutique, global, défaut. Champ vide = hériter ; **0 reste une valeur explicite**. Une alerte ou un plafond peut être désactivé sans supprimer les autres seuils. La raison de modification est obligatoire et auditée.

Contraintes : minimum ≤ optimal ; si plafond activé, optimal ≤ maximum. Le minimum initial reprend le seuil global existant, l'optimal vaut au moins 50 et le plafond est désactivé par défaut. Ces défauts sont des points de départ à régler selon chaque activité.

## États

| État | Disponible vendable |
|---|---|
| Rupture | 0 |
| Bas | positif et inférieur ou égal au minimum effectif |
| Normal | au-dessus du minimum, sans dépassement du plafond |
| Surstock | au-dessus du maximum activé |

Le risque de couverture est un indicateur supplémentaire : avec un historique suffisant et un délai fournisseur connu, le disponible risque de ne pas couvrir la consommation avant livraison. Il ne remplace pas les états de quantité.

Le disponible reprend le service de stock existant : les lots périmés ou bloqués ne deviennent pas vendables. Le catalogue et les recherches POS/achats utilisent les mêmes seuils. Le dashboard sépare ruptures et stocks bas, indique sa portée et propose les commandes selon les droits ; listes et catégories sont bornées et repliables.

## Exemple

Pour un carton de 12 pièces, minimum 24 et optimal 60 sont exprimés en **pièces**, pas en cartons. Avec 20 pièces disponibles, l'état est bas. Une proposition de 40 pièces sera arrondie au conditionnement et au multiple fournisseur : elle pourra devenir 4 cartons, soit 48 pièces. Un dépassement du maximum est signalé avant approbation.

## Notifications

Les franchissements et aggravations sont tracés ; un réapprovisionnement réarme le suivi. Chaque type d'alerte est limité à un événement par produit/boutique/jour métier. Les destinataires et leurs droits sont revérifiés lors de la livraison. Les alertes de couverture demandent un responsable et des destinataires habilités dans les paramètres de planification.

Les contrôles de stock existants réagissent aux opérations. Le balayage prévisionnel utilise le scheduler, par lots d'au plus 20 boutiques et 200 produits par boutique : son délai de couverture dépend donc du volume et de l'exécution effective du scheduler. Il ne garantit pas une minute pour un catalogue arbitrairement grand.

## Filtres et saisie

- Catalogue/POS : catégorie, fournisseur de catalogue, recherche et état de stock selon l'écran. Un fournisseur de catalogue signifie « peut fournir ce produit ».
- Rapports ventes/stock/péremptions : le fournisseur repose sur les **lots réellement reçus**, jamais sur l'association du catalogue. Provenance inconnue exclue de cette attribution ; elle reste visible dans les rapports non filtrés. Les mêmes filtres s'appliquent à l'écran et aux exports.
- Création rapide : crée le produit et son unité opérationnelle, **zéro stock**. Elle conserve le formulaire initial et ne fabrique pas un coût inconnu.
- Démarrage : créer/importer le catalogue, puis saisir le stock initial réel avec lots et coûts connus. L'inventaire compare un comptage au stock déjà enregistré ; ce n'est pas une création de produit ni une réception fournisseur.
- Rupture au POS : produit visible grisé ou masqué selon réglage ; scan refusé avec explication. Le contrôle serveur reste déterminant.

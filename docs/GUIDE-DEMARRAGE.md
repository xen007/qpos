# Démarrer une boutique et travailler avec les stocks

## 1. Catalogue et conditionnements

Créer la fiche produit : nom, référence, unité de base, prix, catégorie, règles de quantité entière/fractionnaire. Ajouter les conditionnements. Cela n'entre aucun stock.

Exemple : ciment, unité sac ; palette=50 sacs. Clous, unité kg fractionnable ; sachet=5kg, carton=25kg. Une vente « 1 carton + 6 pièces » garde deux lignes et leurs conditionnements. 1,5kg est une quantité valide si le produit est fractionnable ; 1,000000 s'affiche1 sans changer la précision de stockage.

## 2. Marchandises déjà présentes avant démarrage

Depuis Stock > ouvertures, choisir Stock initial documenté. Réservé à stock_opening_approve dans sa boutique autorisée. Choisir le produit, quantité en unités de base, identifiant de lot, coût réel prouvé par unité de base en XAF, date de démarrage, péremption, motif et justificatif.

Exemple :50 sacs de ciment déjà payés avant QPOS, coût prouvé4 000XAF/sac. Entrer quantité50, coût4 000, lotOUV-CIM-01, non périssable, justificatif facture/inventaire initial. Le stock vendable devient50 ; aucun achat ni nouvelle dette fournisseur ni paiement caisse fictif. Si coût non justifiable, ne pas inventer zéro : conserver la réconciliation documentée dans le circuit de stock bloqué existant.

Alimentation : lotRIZ-A,100kg, date réelle de péremption ; lotRIZ-B,50kg, autre date. Saisir séparément, avec la même date de démarrage du produit dans la boutique. Les dates de péremption diffèrent ; la classification reste datée. Chaque lot initial ne se ressaisit pas avec une nouvelle clé.

Après une vente, un achat, transfert, ajustement ou inventaire sur ce produit, l'apport initial est fermé. Ce contrôle évite de compléter un stock « initial » pendant l'exploitation. Un inventaire en cours doit être terminé ou annulé auparavant.

## 3. Ouvertures historiques bloquées

L'écran Ouvertures en attente d'approbation réconcilie une quantité existante non documentée. Exemple :30 unités bloquées ; prouver12 unités dans un lot et approuver12 laisse18 bloquées. La quantité totale ne change pas. Devise historique conservée ; aucune conversion BDT en XAF.

## 4. Nouvel arrivage fournisseur

Créer un achat avec fournisseur réel, conditionnement, quantité commandée, quantité reçue maintenant et coût réel. Réception partielle=seule la quantité réellement reçue augmente le stock. Une date de péremption réelle remplace une estimation de politique pour le nouveau lot ; les anciens lots ne sont pas réécrits.

Les lots connus d'un produit sont tous datés ou tous explicitement non périssables. Un choix incompatible est refusé au serveur. La politique automatique peut estimer une date selon produit/catégorie ; l'écran l'annonce. Pour un produit déjà daté, saisir la vraie date du nouveau lot.

## 5. Inventaire physique

Compter les lots/buckets existants, en unités de base. Ce n'est ni la création de produit ni une réception. Exemple :théorie100kg, comptage98,5kg → écart−1,5kg tracé après validation ; aucun achat fournisseur créé. Un champ vide n'est pas un zéro. Mouvements pendant comptage nécessitent recompter ; mouvements postérieurs au comptage se réconcilient à la validation.

Les contrôles tout sélectionner/désélectionner concernent seulement la liste annoncée, jusqu'à2000 produits. Les étiquettes concernent la page affichée50 conditionnements, avec limite serveur100sélections/20copies ; changer de page commence une nouvelle sélection.

## 6. Rôles et caisse

Admin global supervise selon ses capacités ; administrateur boutique gère seulement son périmètre. Vendeur prépare ; caissier encaisse ; un rôle cumulatif ou droit individuel peut permettre les deux, sans étendre les boutiques.

Ouverture caisse=monnaie initiale disponible. Pause=arrêt temporaire. Clôture session=comptage des espèces et écart. Clôture journée=récapitulatif indépendant, sans fermer les caisses. Une boutique qui ferme21h peut produire son récapitulatif ; les opérations tardives entraînent une version corrigée à23h55, avec notification.

Dépense : faire créer une catégorie par l’administrateur global si vide, ouvrir la session et saisir le moyen réel. Seules les espèces réduisent le comptage physique ; chaque autre moyen nécessite une référence. Les références globales de catégories peuvent être adaptées sans modifier les anciennes dépenses.

<?php
return [
'title'=>'Réapprovisionnement','policies'=>'Seuils et alertes de stock','policy_help'=>'Quantités en unité de base. Vide = valeur héritée. La création produit ne crée aucun stock. Réglages communs réservés à l’administration globale.',
'scope'=>'Portée','product_shop'=>'Produit dans cette boutique','global'=>'Toutes les boutiques','defaults'=>'Valeurs par défaut',
'minimum'=>'Seuil minimum','optimal'=>'Stock cible','maximum'=>'Stock maximum','alerts_enabled'=>'Activer les alertes','maximum_enabled'=>'Activer le plafond','hide_out_of_stock'=>'Masquer les ruptures au POS',
'effective'=>'Valeur effective','inherit'=>'Hériter','saved'=>'Réglage enregistré et audité.','invalid_policy'=>'Les seuils doivent respecter minimum ≤ cible ≤ maximum activé, avec au plus six décimales.','policy_volume'=>'Trop de combinaisons pour une validation interactive ; contactez le responsable système.',
'out'=>'Rupture','low'=>'Stock bas','normal'=>'Normal','over'=>'Surstock','critical'=>'Risque de rupture avant livraison','orders'=>'Bons de commande','suppliers'=>'Catalogue fournisseurs',
'active_supplier'=>'Choisissez un fournisseur externe actif.',
'multiple_help'=>'Le minimum doit être compatible avec le multiple de commande.',
'price_date_required'=>'Renseignez la date de ce prix estimatif.',
'supplier_reference'=>'Référence fournisseur',
'delay_days'=>'Délai en jours',
'minimum_order'=>'Minimum de commande (conditionnements)',
'order_multiple'=>'Multiple de commande (conditionnements)',
'estimated_cost'=>'Prix estimatif par conditionnement (XAF)',
'price_on'=>'Date du prix',
'preferred'=>'Fournisseur préféré',
'supplier_help'=>'Les prix sont estimatifs et datés. Plusieurs fournisseurs sont possibles ; une seule préférence par produit. La marque ne détermine pas le fournisseur.',
'quick_product'=>'Nouveau produit sans quitter',
'fractional'=>'Quantité fractionnaire autorisée',
'zero_stock'=>'Création catalogue uniquement : quantité zéro. Le coût réel sera saisi à la réception.',
'creation_failed'=>'Impossible de créer le produit.'
];

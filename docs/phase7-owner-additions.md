# Phase 7 — compléments propriétaire du 13/10/2026

Autorisation : restaurer le menu de simulation, intégrer/corriger les points ci-dessous avec choix raisonnables et rendre compte à la fin, sans nouvelle validation intermédiaire.

## À livrer et vérifier

1. Restaurer les accès visibles selon permissions ; corriger les plusieurs entrées actives pour une même route, notamment caisse/supervision. Aucun élargissement de droits vendeur/caissier.
2. Achats/péremption : expliquer et aider à choisir la classification compatible avec le produit et ses lots connus ; dates de lots distinctes permises. Pas de conversion arbitraire des lots historiques.
3. Dépenses : catégorie obligatoire utilisable. Gestion des catégories autorisée, état vide explicite, références de démarrage cohérentes ; ne pas contourner la validation par une catégorie inventée dans le formulaire.
4. Parcours démarrage : catalogue → conditionnements → stock d'ouverture avec lots/coûts/date ; réception d'achat pour les arrivages ; inventaire pour le comptage et les écarts. Guide avec cas concrets.
5. Sélectionner/désélectionner tout dans les écrans multisélection concernés (étiquettes, inventaires, autres formulaires recensés). Indiquer la portée (filtre/page/liste) et conserver les bornes et autorisations serveur.
6. Supervision caisse : état vide explicatif (sessions ouvertes inactives non en pause), accès à l'historique/clôtures au bon écran. Ne pas transformer une session correctement fermée en session orpheline.
7. Boutique : rôle opérationnel sélectionne uniquement sa boutique autorisée ; bouton de gestion seulement si permission appropriée. Contrôles serveur conservés.
8. Ticket : actions stylées, contenu client de passage compact, totaux/paiements/monnaie/dette utiles et mentions métier conservés. Quantités lisibles sans six zéros.
9. Impression : déclenchement après vente et depuis ventes, option administrable par boutique, pas de double impression automatique au rechargement. Le dialogue standard du navigateur et le mode silencieux sur poste configuré sont deux états distincts ; aucun test d'impression physique non annoncé.
10. Quantités : entiers sans décimales inutiles ; fractions utiles (kg/litre, etc.) sans arrondir les données en base. L'affichage n'impose pas un entier à une unité fractionnable.
11. Audit : vérifier les événements connexion/déconnexion/changement de compte, filtre utilisateur, et proposer une vue d'activité récente si besoin. Distinguer preuve de connexion, activité récente et session réellement valide ; ne pas afficher de tokens/secrets.

## Ordre

Finir le raccordement notifications7.D, puis emails7.E et sauvegarde/état7.F. Regrouper ces compléments UI/exploitation dans7.G, les rejouer avec les phases5/6 en7.H, puis mettre à jour/nettoyer l'installation source. Les bogues bloquant un parcours sont corrigés dès leur reproduction.

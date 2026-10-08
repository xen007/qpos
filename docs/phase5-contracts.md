# Phase 5 — contrats de calcul et cartographie

Validé par le propriétaire le 08/10/2026 ; exécution 5.A → 5.H, commit final propriétaire.

## Sources et périmètre

Le dashboard (`Backend/DashboardController`, `backend/index`) et les trois anciennes entrées `Backend/Report/ReportController` sont le périmètre Phase 5. Le profil demeure intact. Les moteurs vente, prix, stock, caisse et les vues Phase 4 sont conservés. Les raccordements partagés se limitent aux routes, au menu, au provider de rapports, au scheduler et aux traductions.

Excel (Maatwebsite) et PDF (Dompdf) sont déjà installés. `PointOfSale::accessibleBy` demeure la source de portée opérationnelle, y compris mono-boutique. Aucune permission de rapport ne crée une affectation boutique.

## Indicateurs

| Indicateur | Source et règle |
|---|---|
| Ventes nettes | Lignes `effective_total` positives au checkout : elles incluent déjà la répartition de l'arrondi final Phase 4 ; `sale_corrections.amount`, négatif à la date du correctif. Le résidu d'arrondi d'un retour est affecté à sa dernière ligne, sans changer les documents. |
| CMV connu XAF | Coût des allocations d'origine, avec preuve de devise/coût capturée au checkout. Un retour vendable reprend ce coût ; un retour non vendable conserve son coût dans le CMV (perte économique). Les achats de la période ne sont pas le CMV. |
| Marge brute | Ventes nettes moins CMV connu ; affichée comme provisoire si un coût vendu est inconnu. |
| Dépenses | `expenses`, à leur date d'opération, XAF, sans doubler leur mouvement de caisse. |
| Résultat de gestion | Ventes nettes − CMV − dépenses. Provisoire si CMV incomplet ; indicateur de gestion TTC, fiscalité non configurée, sans intégration comptable réglementaire. |
| Trésorerie | Paiements entrants − sortants − dépenses, séparés par moyen ; fonds initial et mouvements manuels présentés séparément. Achats réglés inclus, achats commandés exclus. Dates fournisseur héritées conservées selon leur convention. |
| Écart caisse | Compté − attendu des sessions clôturées à leur date de clôture. Les sessions ouvertes sont signalées sans écart fictif. |
| Dettes natives | Solde à la borne depuis total original, avoir/échange consommé, affectations entrantes et réductions de dette datées. Les dettes créées dans la période lisent le snapshot du checkout. Aucun solde historique n'est lu depuis le `due` actuel mutable. |
| Stock valorisé | Stock courant par lot/boutique × coût réel connu, devise XAF. Lots à coût inconnu/devise inconnue exclus du total ; vendable et non vendable distincts. Ce rapport est courant, pas une reconstitution historique. |
| Péremptions | Stock courant expiré, échéances 7/30/90 jours, dates inconnues et dates estimées visibles. |
| Heures de pointe | Somme des ventes nettes locales par heure / nombre de jours calendaires du filtre, jours sans ventes inclus ; 24 heures, top 3 pics/creux, égalités par heure croissante. |

Le catalogue courant ne sert jamais à recalculer un prix ou un coût historique (D37). Un provider additif capture la catégorie de la ligne et la connaissance/devise du coût de chaque allocation ; toute allocation antérieure sans preuve est marquée incomplète. Les corrections ultérieures d'un lot ne réécrivent pas ces preuves.

## Périodes et accès

- Journées natives : bornes UTC correspondant à `Africa/Douala`, intervalle début inclus / fin exclue ; au plus 366 jours par sélection. Les périodes jour/semaine/mois s'ancrent sur la date choisie (semaine lundi).
- XAF natif et historique sont deux rapports distincts. Aucun historique BDT ou fuseau hérité n'est converti. Les historiques sans boutique ne sont visibles que pour Admin explicitement habilité à consulter l'historique. Devise historique non documentée affichée « devise non documentée » ; jamais inventée depuis le réglage global.
- Écran, Excel et PDF utilisent le même service, les mêmes filtres et contrôles. Permissions existantes : `dashboard_view`, `reports_summary`, `reports_sales`, `reports_inventory`. Synthèses : capacités dédiées ; clôture réservée à Admin. Export complet borné explicitement, refus visible au-delà de la limite ; aucune troncature silencieuse.
- Les filtres vendeur/produit/catégorie concernent les ventes ; charges et trésorerie ne sont pas réparties artificiellement par produit. Résultat de gestion disponible sur le périmètre boutique/période complet.

## Trois niveaux et synthèses

- Pause : journal personnel indicatif, reprise possible ; aucun changement de stock, session ou paiement.
- Session : parcours Phase 4 existant, clôture par le caissier avec comptage.
- Journée : récapitulatif seulement, clôture manuelle Admin à tout moment ; aucune fermeture automatique des caisses. Le récapitulatif mentionne les sessions ouvertes à sa borne.
- Synthèse immuable avec boutique/date/type (`manual`, `automatic`, `corrected`), version, borne UTC, empreinte et payload. Clé unique boutique/date/type. Le premier récap est conservé.
- À 23h55 : génération de fallback ou version corrigée si des opérations pertinentes ont été enregistrées après la borne initiale. La correction porte la notification explicite « Récap corrigé disponible » et les deux versions restent consultables. L'heure de coupure réelle est visible : 23h55 n'est pas minuit. Les opérations des cinq dernières minutes sont rapprochées à la reprise suivante via la même version corrigée unique, ou signalées si celle-ci est déjà figée ; aucune opération n'est déplacée au lendemain.
- Outbox par destinataire/type, états et tentatives, retry borné avec attente ; les permissions, la suspension et l'affectation sont revérifiées avant livraison. SMTP ne garantit pas l'exactement-une-fois après une panne entre acceptation du message et enregistrement local : identifiant stable et état ambigu signalé, sans retry aveugle de cette fenêtre.
- Le scheduler exige Windows et XAMPP actifs. Rattrapage des journées manquées ; transport configuré sans modification de `.env` ni exposition de secrets. Un transport de journal ne vaut pas livraison externe.
- Envois désactivés avant confirmation explicite : contenu et destinations sont présentés à l'admin entreprise. Destinataires sélectionnés, adresses de notification séparées du login, doublons de boîte/boutique refusés. Manifeste approuvé et droits revérifiés avant livraison ; aucune adresse de démonstration activée implicitement.

## Critères de fin

Pagination SQL, agrégats SQL, contrôle D37, frontières horaires et égalité écran/export, coûts inconnus visibles, séparation historique, idempotence et correction de synthèse documentées. Vérifications de syntaxe PHP et construction, contrôles ciblés sur copie ; aucun test métier écrit sur la source, aucun nouveau jeu PHPUnit, aucun commit/staging/push par l'assistant.

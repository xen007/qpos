# Phase 7.G — cartographie des compléments UX

14/10/2026. Préparation, aucune correction de ce groupe encore livrée. Autorisation : phase7-owner-additions.md.

## Constats confirmés dans le code

- Menus et gestion boutiques corrigés en D : conserver les permissions, vérifier de nouveau en H.
- Dépenses : phase4/expenses possède déjà une gestion des catégories sous expense_manage. Le sélecteur reste vide si aucune catégorie active ; l'état vide n'aide pas et le formulaire reste soumettable. Prévoir références initiales additives, messages/actions cohérents et aucune catégorie fictive cachée.
- SaleWorkflowController::expense utilise method required|string|max:24,transfer : validation à reproduire, puis normaliser avec le registre réel des moyens autorisés ; ne pas supposer un enum contrôlé par cette règle. Conserver session/mouvement espèces, idempotence et références non espèces.
- Catalogue : StoreProductRequest quantity prohibited et Product interdit toute quantité initiale non nulle. Créer un produit ne réceptionne donc pas de marchandises.
- Ouvertures actuelles : écrans stock/openings et opening servent à approuver des quantités historiques déjà bloquées, avec lot/coût/devise/péremption/motif/preuve ; total stock conservé. Ils ne créent pas un nouvel apport.
- Inventaire : compte les buckets/lots existants, refuse d'augmenter 0:unallocated_opening et d'alimenter un lot depuis des ouvertures bloquées. N'est pas une réception ni une saisie de stock initial.
- Démarrage d'une nouvelle boutique vide : compléter un parcours d'apport initial documenté si aucun flux existant équivalent n'est trouvé. Utiliser les primitives stock/lots autorisées, preuve de coût/classification, idempotence et portée boutique. Aucun achat/fournisseur/dette/dépense fictifs, aucune conversion des historiques. Les ouvertures bloquées gardent leur procédure distincte.
- Achats : StockService impose une classification unique parmi lots connus d'un produit (dated ou not_applicable), mais dates de lots distinctes autorisées. PurchaseService estime selon politique produit/catégorie si non renseigné. UI à préremplir/aider selon classe déjà connue sans réécrire les anciens lots.
- Inventaires : sélection product_ids existante, sans commandes tout sélectionner/désélectionner. Recenser étiquettes et autres multisélections, afficher la portée liste/page/filtre et conserver les caps serveur.
- CashSupervisionService::query sélectionne state=open ; toutes les sessions ouvertes sont consultables, suspected identifie les inactives sans pause. Fermées exclues ; action supervisée limitée aux inactives non en pause. Ajouter état vide explicite et historique des clôtures avec droits, sans changer cette protection.
- Reçus/tickets : vues natives phase4/sale-document, receipt, correction-document et collection ; vues historiques orders/* à recenser selon contrôleur. Adapter seulement les vues réellement utilisées.
- Audit : contrôler AuthController, AuditLogController et vues administration/audit*. Distinguer connexion, activité récente et session valide ; pas de tokens/secret ni faux état connecté.

## Groupe d'implémentation prévu

1. Parcours stock initial / achats péremption / catégories dépenses et guide concret.
2. Multisélections, caisse supervision/historique et quantités lisibles.
3. Tickets/client de passage/impression par boutique, une seule impression automatique, dialogue standard ; silencieux seulement sur poste configuré.
4. Événements auth et activité consultable avec portées.
5. Documentation XAMPP/UPDATE/ENV/TROUBLESHOOTING, démarrage scheduler/deux workers ; performances mesurées et inventaire des consommateurs avant suppression.
6. Régression et rapprochement source en H, backup/restauration SOURCE récente avant migration additive ; aucun fresh/seed destructif.

## À conserver lors de la reprise

Code E/F vérifié et commité, détails phase7-email.md / phase7-backups.md. G n'a reçu aucun OK d'implémentation. Source qpos non migrée Phase7. Copie qpos_test_phase7_20261012, 8124. Outil navigateur indisponible lors des derniers essais ; reprendre sans prétendre à une preuve visuelle non réalisée.

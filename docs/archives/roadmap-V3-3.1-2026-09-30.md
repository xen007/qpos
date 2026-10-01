markdown
# QPOS — Roadmap V3 (document unique)

Version : 3.1 (finale)
Date : 30/09/2026
Base technique : Laravel 12.69.3 + PHP 8.2.12 + Tailwind 4 + React 19
Remplace : ROADMAP-UNIFIEE.md + REFERENCE-MAP.md + ancienne modernization-roadmap.md

---

## 1. Vision

QPOS devient une solution POS multi-type (pharmacie, quincaillerie, 
alimentation, boutique) avec :
- Produits a unites multiples (piece, kg, carton, sac, plaquette)
- Plusieurs points de vente avec stocks separes + vue globale
- Retours, remboursements, caisse, depenses
- Peremption, lots, multi-tarifs, scanner code-barres
- Rapports detailles (vendeur, boutique, categorie, date, marges)
- Cloture journaliere automatique avec envoi email
- Notifications email, SMS, WhatsApp + notifications instantanees
- Audit complet + sauvegarde automatique
- UX Soft Modern (theme clair/sombre, responsive, toasts)

---

## 2. Decisions actees

1. Multi-type : un seul schema pour tous les commerces
2. Unites predefinies + extensibles par l'admin
3. Multi-conditionnements par produit, prix distincts
4. Stock par point de vente + total cumule (admin)
5. User lie a un point de vente (sauf admin = tous)
6. Style UI : Soft Modern (bleu petrole #1E5F74 + vert sauge #88B04B, Inter)
7. Notifications : Email -> SMS -> WhatsApp (progressif)
8. Notifications instantanees UI : toasts + polling/WebSocket
9. Laravel 13 reporte (Phase 9)
10. Laravel 12, PHP 8.2 (XAMPP), branche main

---

## 3. Regles d'or

### 3.1 Aucune dependance vers le futur
Ordre obligatoire des lots dans chaque phase :
1. Migrations
2. Seeders
3. Modeles Eloquent
4. Services
5. Form Requests + Policies
6. Controleurs
7. Routes
8. Vues Blade
9. Composants React/POS

Chaque lot ne depend QUE des lots precedents. Jamais l'inverse.

### 3.2 Anticipation
Si une phase future necessite une structure, on la cree des maintenant 
avec des valeurs par defaut.

Exemple : points_of_sale et product_stock crees en Phase 2 (avec POS 
defaut) pour ne pas reecrire CartController/OrderController en Phase 3.

### 3.3 Un fichier touche une seule fois
Dans une phase, un fichier est touche une seule fois. Si retouche 
necessaire, c'est qu'on a mal ordonne.

Exception : correction de regression revelee par verification.

### 3.4 Commandes multi-fichiers : OK
Une commande qui touche plusieurs fichiers est acceptable si elle est 
dans le bon lot.

### 3.5 Commits par nature
Un commit par nature : feat(db), feat(model), feat(service), 
feat(controller), feat(ui), fix(bug). Le proprietaire commite.

### 3.6 Verification avant cloture
Avant de fermer une phase :
- Scenario principal verifie + cas d'erreur
- Permissions et refus d'acces verifies
- Traductions FR/EN completes
- Themes clair/sombre + responsive
- Navigation clavier + messages comprehensibles
- Verifications PHP + compilation front
- Performances sur donnees representatives
- Recapitulatif fichiers + resultats + limites

---

## 4. Etat actuel

### Termine et pousse
- S0 : Fondation du depot Git
- S1 : Securite uploads (MIME, reencodage, noms hashes)
- S2 : i18n FR/EN + theme sombre (449 cles, contrastes AA)
- S3 : 42 pages Tailwind + Form Requests + Policies + 108 routes protegees
- Migration Laravel 10 -> 12.69.3 (branche upgrade/laravel-12, a merger)

### Verifications deja faites
- Syntaxe PHP, demarrage Laravel, routes (142), view:cache, config:cache
- Composer audit : 0 vulnerabilite
- Smoke test serveur : 20 pages HTML + 9 reponses JSON

### A valider
- Tests navigateur : login, POS, achats, listes, rapports
- Ventes reelles, paiements, concurrence
- Interactions JavaScript, imports reels

---

## 5. Structure des phases
PHASE 0 — Cloture Laravel 12 1 j
PHASE 2 — Unites multiples + multi-boutique 5-7 j
PHASE 2.5 — Retours + Annulations + Caisse 4-5 j
PHASE 3 — Multi-boutiques UI + POS refondu 5-7 j
PHASE 3.5 — Peremption + Lots + Multi-tarifs 4-5 j

Scanner + Promotions
PHASE 4 — Elements communs UI 3-5 j
PHASE 4.5 — Depenses + Audit + Backup + Inventaire 4-5 j
PHASE 5 — Passes verticales metier 15-20 j
PHASE 6 — Rapports detailles + cloture auto 7-10 j
PHASE 7 — UX Premium Soft Modern 5-7 j
PHASE 8 — Notifications + travaux globaux 5-7 j
PHASE 9 — Runtime PHP 8.3+ + Laravel 13 Reporte
PHASE 10 — Reportes (hors scope actuel)

text

Total estime : 10-12 semaines

---

## 6. Detail des phases

### PHASE 0 — Cloture Laravel 12 (1 j)

1. Verifier PHP Apache (vs CLI)
2. Tests navigateur : login, POS, achats, listes, rapports
3. Actualiser documents decrivant Laravel 10
4. Merger upgrade/laravel-12 dans main
5. Push origin/main

Critere : App demarre, parcours valides, main a jour.

---

### PHASE 2 — Unites multiples + structure multi-boutique (5-7 j)

**Objectif :** Chaque produit a une unite de base + conditionnements. 
Structure multi-boutique preparee.

**Ordre des 8 migrations :**
1. create_units_table
2. create_points_of_sale_table
3. add_point_of_sale_to_users_table (dep 2)
4. add_base_unit_to_products_table (dep 1)
5. create_product_units_table (dep 1, 4)
6. create_product_stock_table (dep 2, 4)
7. add_product_unit_to_order_items_table (dep 5)
8. migrate_existing_products_data (dep tout)

**Schema :**
- units : id, name, symbol, type (base|package), is_system, is_active
- points_of_sale : id, name, address, phone, is_active, is_default
- product_units : id, product_id, unit_id, factor, price, 
  purchase_price, barcode, is_default_sale, is_default_purchase, is_active
- product_stock : id, product_id, point_of_sale_id, quantity
- users : + point_of_sale_id (FK nullable)
- products : + base_unit_id (FK nullable)
- order_items : + product_unit_id (FK), factor_used

**Seeder :**
- Unites : piece, kg, litre, metre (base) ; carton, sac, boite, 
  plaquette, sachet (package)
- POS defaut : "Boutique principale", is_default = true

**Migration donnees :**
Chaque produit : base_unit_id=piece, product_units (factor=1, price), 
product_stock (stock, POS defaut). Users lies au POS defaut.

**9 lots :**
1. 8 migrations
2. Seeder unites + POS defaut
3. Modeles Unit, ProductUnit, PointOfSale, ProductStock
4. Services ProductUnitService, StockService
5. Form Requests + Policies Unit
6. Controleurs Unit + ProductController + CartController + OrderController
7. Routes + permissions unit_*
8. Vues products, units
9. POS React (dropdown conditionnement)

**Critere :**
- "Paracetamol" avec 3 conditionnements
- Vendre 3 comprimes / 1 plaquette / 1 boite
- Stock deduit en unite de base
- Aucun produit existant casse

---

### PHASE 2.5 — Retours + Annulations + Caisse (4-5 j)

**Objectif :** Workflow complet de vente : ouvrir caisse -> vendre -> 
cloturer -> retours.

**2.5.A Retours / Remboursements**

Migrations :
- returns : id, order_id, user_id, point_of_sale_id, reason, 
  total_refund, refund_method (cash|credit), status, timestamps
- return_items : id, return_id, order_item_id, product_id, 
  quantity, condition (good|damaged), refund_amount, restock (bool)

Workflow :
- Client ramene produit -> caissier cree retour
- Retour total ou partiel
- Choix : remboursement especes / avoir / echange
- Remise en stock automatique si produit OK (option)
- Facture d'avoir generee

**2.5.B Annulation de vente**

- Annuler vente validee (admin uniquement par defaut)
- Motif obligatoire
- Trace dans audit_log
- Remise en stock automatique
- Regeneration facture ou avoir

**2.5.C Caisse (ouverture / cloture)**

Migrations :
- cash_sessions : id, user_id, point_of_sale_id, opened_at, 
  closed_at, opening_amount, closing_amount, expected_amount, 
  difference, status (open|closed), notes
- cash_movements : id, cash_session_id, type (in|out), amount, 
  reason, timestamps

Workflow :
- Caissier ouvre caisse avec fond de caisse
- Toutes ventes liees a cash_session
- Depenses/retraits lies a cash_session
- Cloture : comptage, ecart calcule, notes
- Rapport de caisse par session

**Critere :**
- Ouvrir caisse -> 5 ventes -> cloturer avec ecart
- Faire un retour total (remboursement especes)
- Faire un retour partiel (avoir)
- Annuler une vente (admin)
- Stock correct apres tous ces mouvements

---

### PHASE 3 — Multi-boutiques UI + POS refondu (5-7 j)

**3.A Multi-boutiques UI (2-3 j)**
- CRUD points de vente
- Transferts de stock entre boutiques
- Filtres par boutique dans toutes les vues
- Vue globale admin + vue boutique vendeur
- Selecteur boutique dans navbar

Migrations :
- stock_transfers : id, from_pos_id, to_pos_id, user_id, status, 
  notes, timestamps
- stock_transfer_items : id, transfer_id, product_id, quantity

**3.B POS refondu (3-4 j)**
- Interface React tactile (grands boutons)
- Dropdown conditionnement
- Checkout : especes / carte / mixte
- Monnaie rendue
- Ticket imprimable 80mm
- Gestion erreurs reseau (retry, idempotence)
- Etats chargement clairs

**Critere :**
- 2 boutiques avec stocks separes
- Vendeur voit sa boutique, admin voit tout
- Vendre 10 articles en < 1 min
- Aucune vente partielle en cas de coupure

---

### PHASE 3.5 — Peremption + Lots + Multi-tarifs + Scanner + Promos (4-5 j)

**3.5.A Peremption + Lots (pharmacie)**

Migrations :
- product_batches : id, product_id, point_of_sale_id, 
  batch_number, expiry_date, quantity, purchase_price, timestamps
- Alerte automatique si expiry_date < 90 jours
- Blocage vente si produit perime
- Ordre de sortie FIFO (First In First Out) ou FEFO (First Expired 
  First Out) - configurable

**3.5.B Multi-tarifs**

Migrations :
- price_tiers : id, product_id, name (gros|demi-gros|detail), 
  min_quantity, discount_percent, timestamps
- customer_price_tiers : id, customer_id, price_tier_id

Option : prix automatique selon client OU choix manuel au POS.

**3.5.C Scanner code-barres**

- Integration scanner USB (input clavier)
- Recherche automatique produit par barcode
- Option : scanner via camera (mobile/tablette)

**3.5.D Etiquettes prix**

- Generation PDF d'etiquettes (nom, prix, code-barres)
- Impression sur planches Avery ou rouleau thermique
- Choix taille / format

**3.5.E Promotions automatiques**

Migrations :
- promotions : id, name, type (percent|fixed|bogo|bundle), 
  conditions (json), discount_value, start_date, end_date, 
  is_active, scope (all|category|product)
- Appliquees automatiquement au POS

Exemples :
- "3 achetes = 1 offert" sur categorie X
- "10% sur tout le magasin samedi"

**Critere :**
- Produit avec date de peremption + alerte DLC
- Vente bloquee si perime
- Client gros a 15% auto
- Scan code-barres ajoute le produit
- Etiquette imprimee
- Promo "3+1" appliquee automatiquement

---

### PHASE 4 — Elements communs UI (3-5 j)

**Objectif :** Stabiliser les composants partages avant passes verticales.

- Structure : sidebar, navigation, header, largeur
- Couleurs, typographie, espacements, responsive
- Boutons, champs, tableaux, modales
- Etats : chargement, vide, erreur, notifications
- FR/EN Blade + React (preference conservee)
- Theme clair/sombre coherent
- Conventions Sonner + Lucide
- Accessibilite : clavier, focus, labels, contrastes

**Critere :** Composants communs permettent d'ameliorer chaque ecran 
sans reinventer.

---

### PHASE 4.5 — Depenses + Audit + Backup + Inventaire (4-5 j)

**4.5.A Depenses / Charges**

Migrations :
- expenses : id, point_of_sale_id, user_id, category_id, 
  amount, description, receipt_path, expense_date, 
  payment_method, timestamps
- expense_categories : id, name, is_active

Categories par defaut : Loyer, Salaires, Electricite, Eau, 
Transport, Fournitures, Maintenance, Autres.

Impact : le benefice net = ventes - achats - depenses.

**4.5.B Logs d'audit**

Migrations :
- audit_logs : id, user_id, action (create|update|delete|login|...), 
  model_type, model_id, old_values (json), new_values (json), 
  ip_address, user_agent, timestamps

Traces :
- Modifications produits/prix
- Modifications utilisateurs/roles
- Suppressions
- Annulations ventes
- Connexions/deconnexions
- Acces refuses

UI : page "Historique" filtrable (admin).

**4.5.C Sauvegarde automatique**

- Commande qpos:backup
- Laravel Scheduler (quotidien a heure configurable)
- Destination : local + cloud (Google Drive, Dropbox, S3) configurable
- Retention : 7 quotidiens, 4 hebdomadaires, 12 mensuels
- Notification email si echec
- Test de restauration documente

**4.5.D Inventaire physique**

Migrations :
- inventories : id, point_of_sale_id, user_id, status 
  (draft|validated), started_at, validated_at, notes
- inventory_items : id, inventory_id, product_id, 
  theoretical_qty, counted_qty, difference, notes

Workflow :
- Lancer inventaire -> saisie des comptages -> validation -> 
  ajustement du stock + trace

**Critere :**
- Saisir 5 depenses -> benefice recalcule
- Modifier un prix -> trace dans audit
- Backup lance automatiquement -> fichier present
- Inventaire physique -> ecart calcule

---

### PHASE 5 — Passes verticales metier (15-20 j)

**Principe :** Chaque parcours est traite de bout en bout une seule fois.

**Ordre :**

1. **POS + Stock** (3-4 j) : fiabiliser calculs, remises, stock, 
   reglement, facture ; isolation paniers, ventes simultanees, 
   doubles soumissions, pertes reseau ; extraire operations dans 
   un service ; finaliser UI + traductions
2. **Dashboard** (1-2 j) : indicateurs + periodes, requetes, 
   graphiques lisibles, responsive, deux themes
3. **Produits** (2-3 j) : recherche, listes, formulaires, images, 
   autorisations (products.show), pagination
4. **Clients / Fournisseurs** (2 j) : selection, recherche, 
   historique, regles suppression, speciaux
5. **Achats + Imports** (2-3 j) : extraire operations, securiser 
   stock, doublons import, ecran React achat
6. **Rapports** (2-3 j) : aligner chiffres/filtres/periodes, 
   controles acces, pagination, exports
7. **Utilisateurs / Roles** (2-3 j) : permissions serveur, dernier 
   admin protege, formulaires, methodes mortes, ecart noms/IDs 
   (RoleController:106)
8. **Parametres** (2 j) : readConfig/writeConfig, valeurs sensibles, 
   devises, URLs, coherence caches + Linux
9. **Authentification** (2 j) : connexion, deconnexion, recuperation, 
   Google, throttling, sessions, messages FR/EN

**Regle :** POS + Stock traite les ventes entierement (historique, 
reglements, factures, retours). Achats traite les achats/imports.

**Critere par parcours :** voir regle 3.6.

---

### PHASE 6 — Rapports detailles + cloture auto (7-10 j)

**9 rapports :**
1. Par vendeur/caissiere
2. Par point de vente
3. Par date (jour/semaine/mois/annee)
4. Par categorie
5. Par produit (top/flop)
6. Marges globales
7. Stock valorise
8. Peremptions
9. Ecarts caisse

Chaque rapport : ecran + Excel + PDF.

**Rapports supplementaires :**
- Depenses par categorie / periode
- Benefice net (ventes - achats - depenses)
- Audit trail (qui a fait quoi)

**Cloture journaliere automatique :**
- Commande qpos:daily-report
- Laravel Scheduler (heure configurable)
- PDF + email automatique
- Configuration destinataires
- Option : verrouillage ventes du jour

**Dettes fournisseurs :**
- Achats a credit, echeances, paiements
- Alerte echeance proche

**Dettes clients avancees :**
- Echeances, relances auto SMS/email

---

### PHASE 7 — UX Premium Soft Modern (5-7 j)

- Palette : bleu petrole #1E5F74 + vert sauge #88B04B + gris clair
- Typographie : Inter
- Style : cartes arrondies, ombres douces, animations subtiles
- Navbar redessinee (notifications, avatar, selecteur boutique)
- Sidebar redessinee (sous-menus animes)
- Icones Lucide (remplacement progressif Font Awesome)
- Toasts Sonner styles (succes, erreur, warning, info)
- Theme clair/sombre refait
- Responsive complet (tablette + mobile)
- Fix bug modification mot de passe
- Accessibilite AA

---

### PHASE 8 — Notifications + travaux globaux (5-7 j)

**8.A Notifications (3 j)**
- Email : stock bas, rupture, peremption, cloture journaliere, 
  echeances dettes
- SMS : Twilio ou Orange SMS API
- WhatsApp : API Meta (a valider budget)
- Notifications instantanees UI : Laravel Reverb ou Pusher
- Badge + toast + centre de notifications

**8.B Travaux globaux (2-4 j)**
- Retirer AdminLTE, Bootstrap, Font Awesome (derniers consommateurs)
- Reglages globaux : sessions, cookies, CORS, journalisation
- Decoupage bundles + caches
- Politique retention : logs, exports, imports
- Test sauvegarde + restauration
- Documentation installation, exploitation, deploiement, reprise

---

### PHASE 9 — Runtime + Laravel 13 (reporte)

**Declencheur :** PHP 8.2 support jusqu'au 31/12/2026. Laravel 12 
jusqu'au 24/02/2027. Preparer avant fin de ces fenetres.

1. Choisir environnement PHP compatible (avec ou sans Docker)
2. Preparer runtime + extensions
3. Recalculer incompatibilites Composer
4. Adapter packages bloquants (Yajra, etc.)
5. Migrer Intervention Image 2 -> 3
6. Migrer Laravel 12 -> 13
7. Rejouer les 9 parcours

---

### PHASE 10 — Reportes (hors scope actuel)

A traiter plus tard, si besoin :
- Mode hors-ligne POS (PWA + sync BDD)
- Affichage client secondaire
- Multi-devises avance (taux de change temps reel)
- Integration comptable externe (Sage, etc.)
- Programme fidelite + commissions vendeurs
- Objectifs de vente
- Application mobile native
- E-commerce / boutique en ligne
- API publique
- Pointage employes
- Planning RH

---

## 7. Duree totale

| Phase | Duree |
|---|---|
| 0 — Cloture Laravel 12 | 1 j |
| 2 — Unites multiples | 5-7 j |
| 2.5 — Retours + Caisse | 4-5 j |
| 3 — Multi-boutiques + POS | 5-7 j |
| 3.5 — Peremption + Multi-tarifs + Scanner | 4-5 j |
| 4 — Elements communs UI | 3-5 j |
| 4.5 — Depenses + Audit + Backup | 4-5 j |
| 5 — Passes verticales | 15-20 j |
| 6 — Rapports + cloture auto | 7-10 j |
| 7 — UX Premium | 5-7 j |
| 8 — Notifications + global | 5-7 j |
| 9 — Runtime + Laravel 13 | Reporte |
| **Total** | **~10-12 semaines** |

---

## 8. Journal de reprise

- **Date :** 30/09/2026
- **Base :** Laravel 12.69.3, PHP 8.2.12
- **Branche :** main (upgrade/laravel-12 a merger)
- **Dernier accomplissement :** migration Laravel 10 -> 12
- **Prochaine action :** Phase 0 (cloture Laravel 12)
- **Documents lies :**
  - docs/routes-permissions.md
  - docs/plugins-front-par-page.md
  - docs/archives/modernization-roadmap.md
# Réapprovisionnement — bilan final A–H

15/10/2026. Branche `main`, base `fe7e79d`. Complément des phases 0–7 suivant [les contrats approuvés](replenishment-contracts.md). Commits locaux par lot, sans push. Historiques BDT/fuseau conservés ; aucun secret, token ou `.env` modifié.

## 1. État des huit lots

| Lot | État | Livraison | Commit local |
|---|---|---|---|
| A | OK | Cartographie, contrats, copie restaurée, empreintes initiales de 93 tables | `fc90aed` |
| B | OK | Politiques héritées par champ produit/boutique/global, décimales, zéro explicite, audit et alertes | `3226949` |
| C | OK | Filtres catalogue/POS/achats, badges, rupture visible/masquée, scan protégé, création rapide sans stock | `873ae62` |
| D | OK | Catalogue produit/fournisseur, préférence unique, conditionnement, délai/minimum/multiple et prix daté | `c2fd08c` |
| E | OK | Cycle du bon, versions/idempotence, instantanés/événements, achat lié unique sans réception, exports | `c0b160c` |
| F | OK | Réceptions partielles/multiples, surplus motivé et avenant, reliquat, protection des achats liés | `5be3474` |
| G | OK | Suggestions expliquées, calcul rationnel, consommation nette XAF, attentes dédupliquées, planning durable et notifications | `baad0a0` |
| H | OK | Rapports fournisseur réels, dashboard, dates UTC/Douala, volumes/concurrence, impression/logo, guides et déploiement source | présent lot |

D a précédé C pour éviter d'inférer un fournisseur à partir d'une marque. Les contrats consolidés remplacent les seuils entiers/formules approximatives du prompt initial : fractions conservées, alerte inclusive, plafond désactivable et historique fiable. Le coût inconnu reste inconnu.

## 2. Règles livrées

- Héritage **par champ** : produit/boutique → produit global → boutique → global → défaut. Vide = hériter ; 0 = valeur explicite. Minimum ≤ optimal ≤ maximum si plafond actif. États fondés sur le disponible vendable existant, sans rendre disponibles les lots bloqués/périmés.
- Consommation native XAF sur 30/60/90 journées complètes Douala, nette des retours à leur date. Sans historique/délai fiable : cible optimale. Sinon : max(optimal, consommation × délai/cycle/sécurité). Arrondis rationnels au conditionnement, minimum et multiple fournisseur.
- Réceptions fiables avant horizon déduites ; retard, date inconnue et attente après horizon séparés. Aucun double compte bon/achat lié. Transferts proposés sans réservation ni soustraction automatique.
- Planification → **brouillon seulement**. Approbation humaine → un achat sans stock. Réception → entrée réelle via les services existants. Versions, clés et verrous protègent les mutations. Maximum/péremption demandent confirmation ; coût inconnu à résoudre avant approbation.
- Écrans, actions, sorties et notifications revérifient permissions/boutique. Catalogue fournisseur géré par Admin. Au rôle de démonstration stock : lecture/création/modification/réception des bons seulement ; aucune approbation, planification ou acceptation de surplus implicite.
- Fournisseur dans les rapports : **lots et allocations de réception réels**, jamais simple relation de catalogue. Une vente depuis deux fournisseurs est répartie en conservant montant/CMV. Provenance inconnue conservée au global et exclue de l'attribution fournisseur. Filtre refusé sur rapports incompatibles ; écran/exports cohérents.
- Dashboard : ruptures et stocks bas distincts, boutiques couvertes explicites, listes/catégories bornées. Nouveaux bons/lignes datés en UTC et filtrés/affichés selon Douala ; aucun changement du fuseau hérité ni conversion historique.

## 3. Vérifications et preuves

Écritures de simulation exclusivement dans **`qpos_test_replenishment_20261014`**, avec garde sur configuration et `SELECT DATABASE()`. Email de copie `array`. Aucun paiement/email réel. Fixtures transactionnelles annulées ; courses préparées avec sauvegarde et restauration vérifiée.

| Groupe | Contrôles distincts réussis | Preuves privées |
|---|---:|---|
| B | 25 | `b-proof.json` |
| C | 20 | `c-proof.json`, `c-extra-proof.json` |
| D | 12 | `d-proof.json` |
| E | 30 | `e-proof.json` |
| F | 36 | `f-proof.json`, `f-extra-proof.json` |
| G | 57 | `g-proof.json`, `g-ui-proof.json`, `g-extra-proof.json` |
| H | 54 | `h-proof.json`, `h-final-proof.json`, `h-time-proof.json`, `h-print-proof.json` |
| **Total** | **234** | Réexécutions des mêmes cas exclues |

### Concurrence

Cinq courses réelles : une approbation à deux processus, deux courses de réception à deux processus, deux courses de génération à deux processus. Barrières/chevauchement et effets SQL documentés. Même clé rejouée sans doublon ; acteurs/clés distincts sur version commune ne créent pas deux réceptions. Génération sans achat/stock. Preuves `e-race-proof.json`, `f-race-proof.json`, `h-race-proof.json` ; restaurations et empreintes de 99 puis 102 tables selon l'étape.

### Volumes, syntaxe et sorties

- 10 puis 200 produits : **15 requêtes** dans les deux mesures ; 200 à chaud : **1,172 s**, pic **48 234 496 octets**. Mesure locale, pas garantie générale. Suggestions 50/page, exécutions 25/page, détails 50/page, traitement 200 produits. Scan critique au plus 20 boutiques × 200 produits par passage.
- PDF : au plus 200 bons/1 000 lignes ; Excel liste : 5 000 bons ; détail soumis aux 200 lignes métier du bon. Dépassement PDF refusé avant chargement massif des lignes.
- **50 fichiers PHP** contrôlés ; scripts inline rendus analysés, dont 18 en intégration H. Build Vite réussi, 2 023 modules, manifest/CSS actualisés. Dernier scan du module sans les séquences d'encodage corrompu repérées sur le détail du bon.
- PDF/Excel structurels contrôlés. PDF A4 de **40 lignes, deux pages**, rendu et inspecté : tableau lisible, en-tête répété. Logo local valide embarqué ; images distantes et chemins hors répertoire refusés. Impression HTML `window.print()` protégée comme les exports ; refus de permissions vérifiés.

### Navigateur réel

Connexion Admin, dashboard, suggestions et paramètres inspectés. Suggestions en FR/EN, clair/sombre, **390 × 844** et **768 × 1024** ; dashboard et paramètres également en mobile. Aucun débordement horizontal global constaté sur ces vues. Tabulation recherche → fenêtre de consommation, sélectionner/désélectionner les destinataires et exclusion des cases désactivées vérifiées. Preuve `h-ui-final-proof.json` et captures `h-*.png`. Les premiers délais CDP de l'outil ne sont pas comptés comme succès ; la revue a abouti à la reprise.

Ces contrôles supplémentaires ne gonflent pas les 234 cas. Les preuves résident dans `storage/app/replenishment`, hors Git car elles contiennent sauvegardes/données privées. Elles attestent les cas exécutés, pas une absence universelle de défaut.

## 4. Base principale et préservation

**`qpos` : 93 → 102 tables**, quatre migrations additives puis `ReplenishmentPermissionSeeder`. Aucun `migrate:fresh`, seed de démonstration ou import de fixtures sur source.

`h-source-before.sql` : **721 721 octets**, restauré dans `qpos_verify_replenishment_20261015`. Données et DDL des 93 tables comparés (`h-source-restored.json`). `h-source-upgraded.json` confirme données métier, comptes/mots de passe, rôles affectés, boutiques et empreinte `.env` préservés. Anciennes colonnes comparées ; colonnes/permissions/migrations ajoutées attendues. Tables runtime volatiles explicitement exclues des empreintes de données.

Les neuf tables du module étaient **vides après déploiement** : politiques, catalogue fournisseur, compteurs/bons/lignes/événements, réglages/exécutions/résultats de planning. L'exploitant renseigne ses relations et tarifs réels ; aucune simulation imposée à sa base. Comptes existants conservés.

## 5. Exploitation

MariaDB arrêté à la reprise : relancé avec la configuration XAMPP existante, disponibilité/connexion vérifiées. Aucune réparation destructive des fichiers MariaDB. Caches Laravel purgés après ajout de la route d'impression, puis workers rechargés. `schedule:list` contient les trois nouvelles commandes. Contrôle final : **un scheduler, un worker notifications, un worker sauvegardes**.

Le superviseur `QPOS-Operations-75927A3AE77B` est conservé. Ancien `QPOS-Phase5` du même dossier désactivé car doublon d'ordonnancement. L'installateur vérifie le chemin avant désactivation et laisse les autres tâches intactes. Installation locale liée à une session Windows ouverte ; ordinateur et MariaDB doivent rester disponibles.

Contrôle source en lecture seule : synthèse email et canaux externes email/SMS/WhatsApp désactivés ; aucune livraison externe ni tâche notifications en attente. Aucun test SMTP réel. Serveur/onglet de copie arrêtés en fin de contrôle ; MariaDB et workers d'exploitation conservés. Sauvegardes, preuves et checkpoint conservés.

## 6. Configuration et limites restantes

1. **Métier** : renseigner seuils, fournisseurs/conditionnements/délais/prix, responsables et destinataires par boutique. Les suggestions expliquent les données manquantes ; aucune approbation automatique.
2. **Documents commerciaux** : anciennes coordonnées de démonstration possibles, notamment Dhaka/Bangladesh. Le propriétaire doit configurer adresse/téléphone/logo avant usage ; aucune identité inventée.
3. **Externes** : email fournisseur optionnel non implémenté/activé, émission manuelle/export livrés. SMTP et réception réelle, SMS/WhatsApp, prestataires de paiement et imprimantes physiques ne sont pas certifiés ici. L'impression passe par le dialogue normal du navigateur.
4. **Prévision** : moyenne observée et règles explicites ; aucune modélisation de saisonnalité ni apprentissage automatique. Historique insuffisant signalé, cible optimale utilisée.
5. **Couverture** : captures/mesures limitées aux vues et volumes cités, pas à tout appareil ou combinaison de rôles. Scan critique progressif ; pas de garantie d'une minute pour tout catalogue.
6. **Délégation** : catalogue fournisseur réservé à Admin. La revue automatique a refusé l'extension de délégation ; cette voie a été abandonnée, `UserAccessSettings` inchangé.

## 7. Fichiers et documentation

Ensembles A–G : quatre migrations du module ; modèles politiques/catalogue/bons ; services politiques, bons, réceptions, suggestions et planning ; contrôleurs/vues `backend/replenishment` ; intégrations catalogue/POS/stock initial/import/notifications/scheduler ; routes, traductions, seeder de permissions et assets. Détail exact par commit avec `git show --stat <commit>`.

Fichiers applicatifs du lot H :

- `app/Http/Controllers/Backend/PurchaseOrderController.php`, `ReportingController.php`.
- `app/Models/PurchaseOrder.php`, `PurchaseOrderItem.php`.
- `app/Services/ReplenishmentDashboard.php`, `SupplierReportAllocation.php`, `ReportingService.php`, `PurchaseOrderSuggestionService.php` ; `app/Support/ReportFilter.php`.
- `resources/views/backend/replenishment/dashboard-alerts.blade.php`, `order.blade.php`, `orders-pdf.blade.php`, `suggestions.blade.php` ; `backend/reporting/dashboard.blade.php`, `filters.blade.php`.
- `lang/fr/replenishment.php`, `lang/en/replenishment.php`, `routes/web.php`, `scripts/Install-QposOperationsTask.ps1`, CSS compilé et manifest.

Guides : [seuils/alertes](stock-alerts-guide.md), [bons/suggestions/exploitation](purchase-orders-guide.md), [flux](purchase-orders-flux.md), [audit](replenishment-audit.md), [progression/commits](replenishment-progress.md). Roadmap actualisée.

**Bilan : A–H livrés et vérifiés selon les preuves ci-dessus ; source mise à jour sans remplacement des comptes/données. Aucun push. Configurations métier et validations externes restantes explicites.**

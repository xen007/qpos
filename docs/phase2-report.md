# QPOS — Bilan Phase 2

Date : 05/10/2026. Implémentation et conversion locale exécutées ; validation finale du propriétaire attendue. Aucun commit ni push effectué par l’assistant.

## 1. État Git et périmètre

- Répertoire : C:/xampp/htdocs/qpos ; branche main.
- HEAD et référence locale origin/main : 8a47f85545ce4a6fefbf6e468b5ba8074e7d26ba, commit WIP du propriétaire. Aucun fetch effectué pendant cette reprise.
- Point de comparaison de la Phase 2 : fin de Phase 1, 50edb2d. Le WIP déjà commité et les corrections non commitées constituent ensemble cette livraison.
- Travail existant conservé. Pas de reset, retrait de fichiers métier, commit ou push. Le remplacement d’un asset CSS provient du build Vite.
- Les sauvegardes et rapports privés ne sont pas dans Git. Les inventaires complets figurent à la fin du document.

## 2. Résultat par sous-lot

| Sous-lot | Résultat |
|---|---|
| 2.1 Unités et conditionnements | Implémenté ; migrations appliquées. Facteur positif, référence facteur 1, conversion décimale exacte, max six décimales, refus des fractions pour produits non fractionnaires. Codes-barres texte uniques ; pas de suppression de conditionnements historiques. |
| 2.2 Catalogue | Implémenté. Nom et prix obligatoires, marque/catégorie/autres références facultatives, imports compris. Consultation produit protégée. Recherche, pagination et images ; désactivation/contrôle de suppression des référentiels utilisés. |
| 2.3 Boutiques | CRUD, affectations, désactivation et sélecteur implémentés. Administration globale distincte du droit d’opérer ; affectation active obligatoire. Préférence utilisateur persistée sans octroyer de droit. |
| 2.4 Tarifs et promotions | Implémenté. Prix TTC/coût de référence par conditionnement ; coût réel de réception distinct. Tarifs D24 et promotions D25 déterministes ; quatre types : pourcentage, fixe, quantité offerte, prix par lot du même conditionnement. Calculateur et paramètres appliqués exposés. |
| 2.5 Conversion | Exécutée sur copie puis sur qpos. Sauvegarde restaurée, rejeu sans doublons, empreintes historiques inchangées. Anomalies conservées et visibles. |

La validation visuelle et les parcours authentifiés complets du propriétaire restent attendus. Le moteur natif est livré comme catalogue/calculateur ; le checkout et les paiements sont Phase 4.

## 3. Sauvegarde, répétition et application

Sauvegarde privée utilisée : C:/qpos-backups/phase2-20261005-b3e84b6f, droits protégés pour le compte Windows propriétaire et SYSTEM, hors du dossier web.

- SQL complet ; 21 tables métier comparées, et toutes les tables SQL comparées lors de la restauration.
- Archive de 796 fichiers : sources sélectionnées, configuration privée, stockage et médias ; extraction et empreintes vérifiées. Les dépendances vendor/node_modules et le runtime XAMPP ne sont pas archivés. Une récupération doit partir d’un checkout compatible puis rétablir les fichiers privés et dépendances.
- SHA-256 SQL : 541746b07cbbe363fd029c4f1815e04d40682f383aa6a8b3239e965b5810ab87.
- Copie restaurée : qpos_phase2_probe_20261005_160804_59f78aa1.
- Premier passage sur copie : run d67a9150-32ed-4293-a2c6-227fa034db00.
- Rejeu sur copie : run 54bb6d08-98d3-41c0-836f-b931f99ad99a ; aucun nouvel objet catalogue créé.
- Application source : run 4183b30e-8c7f-4471-b742-cf35bd956b34.
- Preuves privées : manifest.json, conversion-probe.json et conversion-source.json dans le dossier de sauvegarde.
- Convertisseur SHA-256 : 9f0888ba3bd6ba1b5f968efa75e645b59dc73902997f3e259460848444730b40. Les rapports enregistrent aussi les empreintes des six migrations.
- Application gelée durant sauvegarde/conversion et rouverte après réussite ; maintenance false constaté.

| Compteur | Première conversion copie | Rejeu copie | Conversion qpos |
|---|---:|---:|---:|
| Produits examinés | 51 | 51 | 51 |
| Références créées | 51 | 0 | 51 |
| Codes-barres créés | 51 | 0 | 51 |
| Promotions créées | 50 | 0 | 50 |
| Anomalies du passage | 51 | 51 | 51 |

Toutes les anomalies source sont fractional_rule_unknown : aucun choix fractionnaire inventé. Prix et coûts de référence connus : aucune valeur cible manquante. Les quatre lignes de vente historiques restent conservées avec leurs nouveaux snapshots inconnus : zéro product_unit_id renseigné rétroactivement. Aucune affectation utilisateur créée automatiquement.

Le premier essai avait échoué sur une copie précédente avec une FK vers point_of_sales. Correction : constrained('points_of_sale') explicite dans la migration des tarifs. Recherche dans les migrations : aucune autre coquille trouvée. Cette copie partielle et les anciennes sauvegardes sont conservées pour traçabilité ; elles ne constituent pas la preuve finale de conversion.

## 4. Vérifications réalisées

Aucune suite de tests automatisés lancée et aucun fichier de test ajouté. Les manipulations d’exemples sont des commandes manuelles sur la copie, dans des transactions annulées.

- Syntaxe PHP : tous les fichiers PHP de la Phase 2, y compris nouveaux supports ; migration corrigée revérifiée.
- Compilation Blade réussie ; build Vite réussi, 2026 modules et sprite de 61 icônes.
- git diff --check réussi ; avertissements de normalisation CRLF/LF uniquement.
- Cache de routes ancien détecté pendant un rendu ; route:cache renouvelé, routes catalogue actives.
- Six migrations locales boutiques/catalogue/prix/suivi en statut Ran, batch 3.
- Exemple Paracetamol : comprimé facteur 1, plaquette 10, boîte 100 ; une seule référence et trois couples prix/coûts distincts à six décimales.
- Prix 1000.123456, quantité 2, remise 10 % : brut 2000.246912, remise 200.024691, total 1800.222221.
- Portées tarifaires successives contrôlées : client+boutique 600 ; client global 700 ; boutique 800 ; global 900.
- Promotions : remise la plus avantageuse à priorité égale ; priorité supérieure choisie même moins avantageuse. Deux unités à 1000 : remise fixe 150/unité -> 300 ; promotion 10 % prioritaire -> 200.
- Promotion 2 achetés + 1 offert, quantité commandée 3 : remise 1000. Lot de 3 à 2500, quantité 4 au prix unitaire 1000 : remise 500.
- HALF_UP : prix 0.000005, remise 10 % -> remise 0.000001.
- Quantité à sept décimales refusée avec message explicite ; quantité 0.5 refusée pour produit non fractionnaire.
- Policy boutique : administrateur peut gérer, mais ne peut opérer sans affectation ; autorisé après affectation active sur copie, refusé après révocation. Transaction annulée.
- Validation d’une création limitée au nom et au prix 123.123456 acceptée sans marque.
- Rendu de la page tarifs : 831 identifiants HTML, tous uniques ; saisie rejetée restaurée dans le bon formulaire, autres conditionnements avec leurs propres identifiants.
- Restauration SQL/fichiers et empreintes métier contrôlées ; historique inchangé après chaque passage.

Non vérifié : parcours navigateur authentifié complet, upload réel d’image, fichier Excel importé bout en bout, concurrence simultanée, toutes les combinaisons de dates/seuils/ex aequo. Ces limites ne sont pas présentées comme des scénarios validés.

## 5. Limites et décisions restantes

1. Le propriétaire doit définir explicitement allows_fractional pour les 51 produits historiques. Le calculateur natif refuse un produit dont cette règle reste inconnue.
2. MAIN existe ; zéro affectation utilisateur. Créer les boutiques voulues et affecter explicitement leurs utilisateurs via les nouveaux écrans. La préférence seule ne donne pas accès.
3. Stocks toujours legacy globaux : journal, lots, ouverture, soldes par boutique et raccordement des écrivains sont Phase 3. Aucune répartition arbitraire du stock faite.
4. POS et achats historiques utilisent encore les montants DOUBLE(10,2). Les valeurs natives DECIMAL(20,6) font autorité pour le catalogue/calculateur ; copie de compatibilité à deux décimales lorsque représentable. Le checkout natif et l’arrondi final devise, FCFA zéro décimale, restent Phase 4.
5. Imports avec stock : quantités entières et coûts de réception à deux décimales jusqu’à Phase 3. L’import catalogue sans stock accepte les référentiels facultatifs et ne crée pas d’achat fictif. Traitement historique des SKU doublons à reprendre en Phase 3.
6. Promotions quantité/lot : un seul conditionnement, aucun panier mixte interproduits.
7. Fuseau de l’application existante encore Asia/Dhaka ; les preuves CLI portent leur offset +06:00. La bascule des nouvelles journées métier Africa/Douala et l’interprétation des dates anciennes restent à traiter dans leurs phases ; aucune réécriture de timestamps faite.
8. La preuve de répétition doit être renouvelée après modification du convertisseur ou d’une migration. Après réouverture, ne pas restaurer aveuglément cette sauvegarde sur de nouvelles opérations.
9. Aucun travail Phase 3 commencé. La validation finale et le commit appartiennent au propriétaire.

## 6. Inventaires Git

Les listes suivantes distinguent le diff cumulé Phase 2 et les changements encore non commités. A = nouveau, M = modifié, D = supprimé ; ?? = non suivi.

### Diff cumule depuis 50edb2d (fichiers suivis)

```text
A	app/Console/Commands/BackupCatalogue.php
A	app/Console/Commands/ConvertCatalogue.php
A	app/Http/Controllers/Backend/CatalogueConversionController.php
A	app/Http/Controllers/Backend/PointOfSaleController.php
A	app/Http/Controllers/Backend/PricingController.php
M	app/Http/Controllers/Backend/Product/BrandController.php
M	app/Http/Controllers/Backend/Product/CategoryController.php
M	app/Http/Controllers/Backend/Product/ProductController.php
A	app/Http/Controllers/Backend/Product/ProductUnitController.php
M	app/Http/Controllers/Backend/Product/UnitController.php
A	app/Http/Middleware/SetPointOfSaleContext.php
M	app/Http/Requests/StoreProductRequest.php
M	app/Http/Requests/UpdateProductRequest.php
M	app/Imports/ProductsImport.php
A	app/Models/PriceRule.php
M	app/Models/Product.php
A	app/Models/ProductBarcode.php
A	app/Models/ProductUnit.php
A	app/Models/Promotion.php
M	app/Models/Unit.php
M	app/Policies/PointOfSalePolicy.php
A	app/Services/CatalogueFingerprint.php
A	app/Services/PricingService.php
A	app/Services/ProductUnitService.php
A	app/Services/ReferencePricingService.php
M	app/Support/BackendMenu.php
A	app/Support/CatalogueSchema.php
A	app/Support/MoneyDecimal.php
A	app/Support/PointOfSaleContext.php
A	app/Support/PricingSchema.php
A	app/Support/QuantityDecimal.php
M	config/ui-icons.php
A	database/migrations/2026_10_02_170000_add_catalogue_units.php
A	database/migrations/2026_10_02_170100_add_shop_preference.php
A	database/migrations/2026_10_02_170200_add_catalogue_pricing.php
A	database/migrations/2026_10_02_170300_add_catalogue_conversion_tracking.php
A	database/seeders/PointOfSalePermissionSeeder.php
A	database/seeders/PricingPermissionSeeder.php
M	database/seeders/RolePermissionSeeder.php
M	docs/contrats.md
M	docs/conversion-strategie.md
M	docs/roadmap.md
M	docs/schema-cible.md
M	lang/en.json
M	lang/fr.json
D	public/build/assets/app-B0HVlcmw.css
M	public/build/manifest.json
M	public/icons/lucide/sprite.svg
M	resources/views/backend/layouts/tailwind/topbar.blade.php
A	resources/views/backend/pricing/conversion.blade.php
A	resources/views/backend/pricing/index.blade.php
A	resources/views/backend/pricing/rule-form.blade.php
M	resources/views/backend/products/create.blade.php
M	resources/views/backend/products/edit.blade.php
M	resources/views/backend/products/index.blade.php
A	resources/views/backend/products/packaging-form.blade.php
A	resources/views/backend/products/show.blade.php
A	resources/views/backend/products/units.blade.php
A	resources/views/backend/shops/form.blade.php
A	resources/views/backend/shops/index.blade.php
A	resources/views/backend/shops/selector.blade.php
A	resources/views/backend/shops/show.blade.php
M	resources/views/backend/units/create.blade.php
M	resources/views/backend/units/edit.blade.php
M	resources/views/backend/units/index.blade.php
M	resources/views/components/backend/input.blade.php
M	resources/views/components/backend/select.blade.php
M	resources/views/components/backend/switch.blade.php
M	routes/web.php
```

### Nouveaux fichiers non suivis, inclus dans la livraison

```text
app/Support/CatalogueCodes.php
app/Support/LegacyMoney.php
app/Support/ProductMoneyValidation.php
docs/phase2-report.md
docs/reprise-phase3.md
public/build/assets/app-ChhukfIV.css
```

### git status --short (avant validation et commit)

```text
 M app/Console/Commands/ConvertCatalogue.php
 M app/Http/Controllers/Backend/PricingController.php
 M app/Http/Controllers/Backend/Product/ProductController.php
 M app/Http/Controllers/Backend/Product/ProductUnitController.php
 M app/Http/Requests/StoreProductRequest.php
 M app/Http/Requests/UpdateProductRequest.php
 M app/Imports/ProductsImport.php
 M app/Models/Product.php
 M app/Services/CatalogueFingerprint.php
 M app/Services/PricingService.php
 M app/Services/ReferencePricingService.php
 M config/ui-icons.php
 M database/migrations/2026_10_02_170200_add_catalogue_pricing.php
 M docs/contrats.md
 M docs/conversion-strategie.md
 M docs/roadmap.md
 M docs/schema-cible.md
 M lang/en.json
 M lang/fr.json
 D public/build/assets/app-B-ZVn2xe.css
 M public/build/manifest.json
 M public/icons/lucide/sprite.svg
 M resources/views/backend/pricing/index.blade.php
 M resources/views/backend/pricing/rule-form.blade.php
 M resources/views/backend/products/create.blade.php
 M resources/views/backend/products/edit.blade.php
 M resources/views/backend/products/show.blade.php
 M resources/views/components/backend/input.blade.php
 M resources/views/components/backend/select.blade.php
 M resources/views/components/backend/switch.blade.php
?? app/Support/CatalogueCodes.php
?? app/Support/LegacyMoney.php
?? app/Support/ProductMoneyValidation.php
?? docs/phase2-report.md
?? docs/reprise-phase3.md
?? public/build/assets/app-ChhukfIV.css
```

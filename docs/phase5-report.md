# Phase 5 — Dashboard et rapports

Date : 08/10/2026. Branche `main`, base `1de04e70072deb098f6fd4715c89ac1ddcdfe98d`. Huit sous-lots autorisés 5.A → 5.H. Un seul commit final par le propriétaire ; aucun commit, staging ou push par l'assistant.

## 1. État des huit sous-lots

| Sous-lot | État | Livré |
|---|---|---|
| 5.A — Cartographie et contrats | Terminé | Sources, dictionnaire, périodes, devises, accès et clôtures dans `phase5-contracts.md` |
| 5.B — Agrégations | Implémenté et vérifié | SQL borné, instantanés de catégorie/connaissance du coût, retours datés, CMV et dettes à leur borne |
| 5.C — Dashboard | Implémenté et vérifié | Filtres, six indicateurs, trésorerie, stock connu/inconnu, graphiques quotidiens et horaires, FR/EN clair/sombre/mobile |
| 5.D — Rapports | Implémenté et vérifié | Vendeur, boutique, période, catégorie, produit, journal net, stock, péremptions, caisse, dépenses/résultat et historique séparé |
| 5.E — Heures de pointe | Implémenté et vérifié | 24 heures locales, profil moyen calendrier, top 3 pics/creux, filtres et histogrammes |
| 5.F — Excel/PDF | Implémenté et vérifié | Même service/filtres/accès, pagination serveur, limites explicites, DECIMAL exacts et protection contre les formules |
| 5.G — Synthèse et clôture | Implémenté ; SMTP à configurer | Pause personnelle/session/journée distinctes, PDF/payload immuables, clôture manuelle, fallback/correction 23h55, outbox et reprises ; tâche Windows installée ; paramètres admin et destinataires multiples livrés ; adresse autorisée enregistrée ; envoi désactivé car SMTP incomplet |
| 5.H — Documentation et clôture | Terminé | Contrats, schéma, roadmap, routes/plugins, exploitation, preuves privées et inventaire ci-dessous |

## 2. Vérifications et règles métier

Toutes les écritures de contrôle sont restées sur une copie restaurée de QPOS. Aucun jeu PHPUnit n'a été ajouté, aucun test métier sur la source.

- D37 : changement du prix/coût courant et du coût/statut d'un lot sans réécriture du revenu ou du CMV des allocations historisées.
- CMV réel : allocations des ventes, reprise cumulative à six places du coût original sur retours vendables ; trois retours fractionnaires reprennent exactement le total initial. Les retours non vendables conservent leur coût économique dans le CMV.
- Prix XAF finaux, retours et dépenses rapprochés ; achats de période non assimilés au CMV ; résultat = ventes nettes − CMV − dépenses.
- Coûts inconnus exclus du valorisé ; marges/résultats provisoires. Les quantités inconnues ne se masquent pas par compensation ventes/retours.
- Dette native : total original − avoir/échange consommé − encaissements − réductions de dette à la borne ; ne lit pas simplement le `due` mutable actuel. Historique BDT/indéterminé séparé, aucun encaissement historique reconstruit.
- Journées Douala en UTC, fin exclusive ; supplier payments natifs déjà locaux lus selon leur convention, sans réécriture des timestamps. Profil horaire sur jours calendaires, zéros inclus.
- Clôture de journée sans fermeture de caisse, deux versions conservées, PDF vérifié par SHA256, langue d'origine conservée. Transactions visibles après photographie identifiées même si elles avaient commencé avant la borne.
- Permissions/refus boutique identiques écran/Excel/PDF ; pages et histograms rendus, refus de dépassement des exports, formules Excel neutralisées.
- Pipeline mail testé en mémoire exclusivement : PDF joint, retry, pas de renvoi après succès, suspension/affectation et approbation recontrôlées, état ambigu sans retry aveugle.

**Contrôles ciblés réussis : 141** (base 63, complémentaires 27, clôtures/PDF 14, visibilité tardive 6, fractions 3, dettes 5, approbation 6, adresses 17). Syntaxe PHP : 39 fichiers contrôlés. Build Vite production, caches Blade/config/routes réussis. Navigateur sans interface sur copie : pages HTTP200, 24 lignes horaires, deux graphiques dashboard, 390×844 sans débordement horizontal, FR/EN/sombre et aucune erreur JavaScript. L'outil navigateur intégré avait expiré ; contrôle repris via navigateur local isolé.

## 3. Volumes mesurés

Simulation sur copie, insertion temporaire et rollback ; ce sont des mesures locales, pas une garantie de charge de production. Mesure combinée synthèse + rapport produit paginé + profil horaire, mémoire PHP incluant les vérifications PDF/mail.

| Ventes supplémentaires | Ventes de période | Durée ms | Requêtes SQL | Pic mémoire Mo | Lignes page | Heures |
|---|---|---|---|---|---|---|
| 0 | 2 | 36.67 | 13 | 62 | 2 | 24 |
| 1000 | 1002 | 232.66 | 13 | 64 | 2 | 24 |
| 10000 | 10002 | 7126.96 | 13 | 78 | 2 | 24 |

Le plan EXPLAIN a conduit à supprimer une agrégation des lignes déjà réparties au checkout et à ajouter l'index boutique/création/ID. Les requêtes restent en nombre constant ; aucun chargement général de ventes en PHP. Les produits de sélection sont limités à 500 suggestions, avec saisie d'ID possible.

## 4. Source et sauvegarde

Dump SQL et archive Git au HEAD, restauration contrôlée de 66 tables d'origine. Avant/après migration, 65 tables existantes comparées intégralement (table de migrations exclue), puis permissions additives accordées au rôle Admin. Migration Phase 5 appliquée après les contrôles de copie. Historiques conservés : 2 ventes, 52 produits, 69 mouvements, 314.000000 unités vendables ; 0 vente native au relevé final. `.env` inchangé, tokens et moteurs des phases 0–4 inchangés. Application remise en service.

Tâche `QPOS-Phase5` enregistrée, wrapper VBS/CMD sans fenêtre, `schedule:run` toutes les minutes en session interactive Windows. Dernier passage observé : 2026-10-08 17:11:07 UTC. Génération et PDF disponibles ; mail désactivé.

## 5. Limites opérationnelles

1. Paramètres > Synthèse quotidienne livré : champ de réception multiple (maximum 20), interrupteur, boutiques et consentement. Defaults dans `config/system.php`, valeurs admin persistées dans `reporting_runtime`. Adresse autorisée enregistrée en base ; login inchangé. SMTP actuel : Mailtrap port2525 sans identifiants ni URL, donc envoi désactivé. Configurer transport/expéditeur puis activer depuis l'UI. Aucun secret `.env` modifié, aucun envoi externe effectué.
2. La revue automatique a refusé la commande qui lançait aussi le worker live : elle pouvait envoyer des données financières à des destinations non confirmées. L'installation a été séparée ; l'envoi est désormais désactivé par défaut et contrôlé par un manifeste explicite. Le planificateur a été installé avec cette protection, sans contourner le refus.
3. Windows, sa session interactive et MariaDB doivent rester actifs ; rattrapage prévu après interruption. Une acceptation SMTP suivie d'une perte de confirmation est signalée comme ambiguë ; l'exactement-une-fois absolu ne peut être garanti par SMTP.
4. 23h55 n'est pas minuit : cinq dernières minutes rapprochées au passage suivant ; si la correction est déjà figée, journal tardif et avis distinct, sans troisième PDF silencieux.
5. Stock courant, coûts/dates/provenances inconnus visibles. Aucun coût historique ni devise inconnue inventé. Dettes historiques toujours soumises à réconciliation explicite avant encaissement natif. Fiscalité détaillée non configurée, resultats de gestion TTC.
6. Exports bornés : Excel 10 000 lignes, PDF 1 000 ; restriction visible, aucune troncature silencieuse. Les nombres Excel restent en texte pour conserver leur précision.
7. RPO24h/RTO8h, sauvegarde quotidienne et externalisation demeurent Phase 7. Les PDF privés de synthèse doivent entrer dans cette future sauvegarde. Bons de commande intelligents également reportés en Phase 7 ; aucun algorithme de prévision ajouté ici.

## 6. Prêt pour le commit du propriétaire

Code, migration, seeder, vues/traductions, scheduler, documentation et assets compilés constituent un seul lot Phase 5. Exclure `.env`, caches, sauvegardes, copies et `storage/app/phase5/`. Les raccordements partagés sont ceux du périmètre de rapports : dashboard, anciennes entrées de rapports, routes/menu, provider additif et scheduler. Aucun moteur prix/stock/vente/caisse ni token modifié.

L'inventaire ci-dessous comprend `M` modifié, `D` ancien asset remplacé et `A` ajouté. Aucun staging/commit/push réalisé.
- `M app/Console/Kernel.php`
- `M app/Http/Controllers/Backend/DashboardController.php`
- `M app/Http/Controllers/Backend/Report/ReportController.php`
- `M app/Support/BackendMenu.php`
- `M config/app.php`
- `M config/system.php`
- `M docs/contrats.md`
- `M docs/plugins-front-par-page.md`
- `M docs/roadmap.md`
- `M docs/routes-permissions.md`
- `M docs/schema-cible.md`
- `D public/build/assets/app-CqNSNfG_.css`
- `D public/build/assets/dashboard-bunpBbrW.js`
- `M public/build/manifest.json`
- `M resources/js/dashboard.js`
- `M routes/web.php`
- `A app/Console/Commands/InstallReportSchedulerCommand.php`
- `A app/Console/Commands/ReportDailyCommand.php`
- `A app/Console/Commands/ReportDeliverCommand.php`
- `A app/Exports/ReportExport.php`
- `A app/Http/Controllers/Backend/ReportingController.php`
- `A app/Mail/DailySummaryMail.php`
- `A app/Providers/ReportingServiceProvider.php`
- `A app/Services/DailySummaryService.php`
- `A app/Services/ReportEvidence.php`
- `A app/Services/ReportingService.php`
- `A app/Services/SummaryDeliveryService.php`
- `A app/Services/SummaryMailApproval.php`
- `A app/Support/ReportFilter.php`
- `A config/reporting.php`
- `A database/migrations/2026_10_08_160000_create_reporting_workflows.php`
- `A database/seeders/ReportingPermissionSeeder.php`
- `A docs/phase5-contracts.md`
- `A docs/phase5-operations.md`
- `A docs/phase5-report.md`
- `A lang/en/reporting.php`
- `A lang/fr/reporting.php`
- `A public/build/assets/app-Bbmmz66Y.css`
- `A public/build/assets/dashboard-BMBCK3Rg.js`
- `A resources/views/backend/reporting/dashboard.blade.php`
- `A resources/views/backend/reporting/email.blade.php`
- `A resources/views/backend/reporting/filters.blade.php`
- `A resources/views/backend/reporting/metrics.blade.php`
- `A resources/views/backend/reporting/navigation.blade.php`
- `A resources/views/backend/reporting/pdf-style.blade.php`
- `A resources/views/backend/reporting/peaks.blade.php`
- `A resources/views/backend/reporting/report-pdf.blade.php`
- `A resources/views/backend/reporting/report.blade.php`
- `A resources/views/backend/reporting/summaries.blade.php`
- `A resources/views/backend/reporting/summary-pdf.blade.php`
- `A resources/views/backend/reporting/summary-settings.blade.php`
- `A resources/views/backend/reporting/summary.blade.php`
- `A resources/views/backend/reporting/workday.blade.php`
- `A scripts/qpos-scheduler.cmd`
- `A scripts/qpos-scheduler.vbs`

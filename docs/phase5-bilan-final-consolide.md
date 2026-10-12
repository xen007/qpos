# QPOS — Bilan final consolidé de la Phase 5

**État :** version consolidée proposée pour revue propriétaire  
**Date de consolidation :** 12/10/2026  
**Phase :** Dashboard et rapports, complétée par les corrections post-audit  
**Branche :** `main`  
**Commit applicatif courant :** `97a9e3f` — `feat(phase5): corrections post-audit, moyens paiement, passation, roles cumulables`

Ce document réunit le bilan initial de Phase 5 et le bilan des corrections post-audit. Les rapports détaillés restent les références pour les scénarios, preuves et inventaires complets : [rapport initial](phase5-report.md) et [validation post-audit](phase5-post-audit-validation.md).

## 1. Résumé exécutif

Les sous-lots 5.A à 5.H sont déclarés terminés dans le rapport initial. Le lot apporte le dashboard, les rapports métier, les exports Excel/PDF, le profil des ventes par heure, la synthèse quotidienne et les mécanismes de clôture.

Le bilan post-audit recense **176 scénarios OK, 0 bug confirmé restant, 0 amélioration ouverte, 1 fonction non implémentée et 2 contrôles non exécutés**, sur 179 cas de référence. Il indique également **146/146 contrôles de régression demandés réussis**. Ces résultats couvrent les scénarios listés et les copies décrites dans le rapport ; ils ne constituent pas une certification exhaustive de tous les usages ou de la production.

Les résultats d’exploitation restent soumis à quelques limites connues : le SMTP réel est désactivé, les périphériques physiques ne sont pas vérifiés, les historiques BDT et les dettes historiques ne sont pas convertis, et les objectifs de sauvegarde/restauration sont reportés en Phase 7.

## 2. Périmètre livré

| Sous-lot | État consolidé | Résultat |
|---|---|---|
| 5.A — Cartographie et contrats | Terminé | Sources, dictionnaire, périodes, devises, permissions et clôtures documentés. |
| 5.B — Agrégations | Terminé et contrôlé | Calculs bornés, CMV et dettes datées, retours et coûts historisés. |
| 5.C — Dashboard | Terminé et contrôlé | Indicateurs, filtres, trésorerie, stock valorisé connu/inconnu, graphiques, FR/EN, thèmes et affichage mobile. |
| 5.D — Rapports | Terminé et contrôlé | Rapports vendeur, boutique, période, catégorie, produit, ventes, stock, péremptions, caisse, dépenses, résultat et historique séparé. |
| 5.E — Heures de pointe | Terminé et contrôlé | Histogramme 24 h, profil moyen, top 3 pics/creux et filtres. |
| 5.F — Excel/PDF | Terminé et contrôlé | Filtres et permissions cohérents avec les écrans, pagination et plafonds explicites, protections d’export. |
| 5.G — Synthèse et clôture | Livré ; SMTP à configurer | Clôtures distinctes, versions conservées, clôture manuelle, traitement automatique/correctif, suivi de livraison et réglages admin. |
| 5.H — Documentation et clôture | Terminé | Contrats, schéma, exploitation, roadmap et preuves référencés dans les rapports détaillés. |

## 3. Résultats métier et intégrité

- Le CMV utilise le coût des lots effectivement alloués aux ventes et aux retours ; un achat de la période n’est pas assimilé au CMV.
- Le résultat de gestion suit la formule **ventes nettes − CMV − dépenses**. Les coûts inconnus sont exclus du stock valorisé et signalés comme tels ; les marges concernées sont provisoires.
- Les modifications de prix ou de coûts courants ne réécrivent pas les ventes et coûts historisés (D37).
- XAF natif et historiques BDT/indéterminés restent séparés. Aucun historique n’a été converti ; une dette historique doit être réconciliée avant encaissement natif.
- Les périodes métier suivent `Africa/Douala`, avec bornes de fin exclusives. Les horodatages historiques ne sont pas réécrits.
- La clôture de journée ne ferme pas les caisses. Les sessions ouvertes sont signalées ; les versions de synthèse sont conservées et les livraisons suivies pour éviter les doublons silencieux.
- Les rapports et exports appliquent les mêmes restrictions de permission et de portée boutique. Les plafonds documentés sont de 10 000 lignes Excel et 1 000 lignes PDF, avec refus explicite au-delà.
- Les données de contrôle ont été écrites sur des copies restaurées. Aucun paiement ni email réel n’a été envoyé pendant les vérifications décrites.

## 4. Vérifications et performances rapportées

Le rapport initial recense **141 contrôles ciblés réussis**, une vérification de syntaxe PHP sur 39 fichiers, un build Vite, les caches Laravel et des contrôles navigateur sur copie (pages HTTP 200, 24 heures du profil, graphiques, FR/EN, thème sombre et vue 390 × 844 sans débordement horizontal).

Le rapport post-audit documente le détail des 179 cas, les preuves associées et les corrections postérieures : paiements mobiles et allocations, répartition par moyen, rôles cumulables, passation vendeur/caissière, supervision et filtres de rapports. Les cas sont détaillés dans [phase5-post-audit-validation.md](phase5-post-audit-validation.md).

La mesure locale combinée sur 10 002 ventes indique 13 requêtes SQL, 78 Mo de mémoire maximale et 7 126,96 ms. Le rapport précise que cette mesure n’est pas un SLA de production. Elle constitue une référence à conserver pour toute optimisation future.

## 5. Écarts, limites et reports

### Non implémenté — Phase 6

- **Filtres avancés du catalogue** (cas 4.1.11 de l’audit). La roadmap les affecte à la Phase 6 ; ils ne sont donc pas comptés comme bug Phase 5.

### Non exécuté — vérification physique

- Impression physique d’un ticket et usage d’un scanner/imprimante d’étiquettes (cas 4.9.4 et 4.9.15). Les rendus PDF ont été contrôlés ; le matériel réel n’était pas disponible.

### Exploitation

- **Email :** les paramètres et destinataires sont configurables, mais le transport SMTP est incomplet (Mailtrap port 2525 sans identifiants/URL dans le rapport). L’envoi est désactivé ; aucun envoi externe n’a été réalisé. Il faudra configurer le transport et l’expéditeur, puis activer l’envoi depuis l’interface.
- **Planification :** le rapport initial indique une tâche Windows `QPOS-Phase5` et un dernier passage observé le 08/10/2026 à 17:11:07 UTC. Son fonctionnement actuel après cette date n’est pas attesté par ce bilan.
- **Sauvegardes/reprise :** sauvegarde quotidienne, RPO 24 h, RTO 8 h et exercices de restauration restent affectés à la Phase 7. Les PDF privés de synthèse devront être inclus dans cette stratégie.
- **Prévision d’achats :** les bons de commande intelligents restent reportés en Phase 7.
- Fiscalité détaillée non configurée ; les résultats de gestion sont TTC. Les coûts, dates ou provenances inconnus restent visibles et ne sont pas inventés.

## 6. Correctif découvert après le bilan d’audit

Après le bilan post-audit, le compte Jean configuré avec le rôle `sales_associate` ne chargeait pas le POS : la liste des clients exige `customer_view`, permission absente de ce rôle. `customer_view` a été ajouté à `RolePermissionSeeder.php`, le seeder a été exécuté sur la base locale `qpos` et le cache des permissions a été réinitialisé.

Le vendeur ne reçoit pas `cash_session_manage`. Le message demandant d’ouvrir une session ou de confier l’encaissement à un caissier autorisé est donc attendu lorsqu’il n’a pas de session. Le parcours normal reste : le vendeur prépare/transmet la vente, puis une caissière autorisée l’encaisse. Le chargement du POS est corrigé ; la présence du bouton de transmission dans le navigateur du propriétaire reste à confirmer après reconnexion de Jean.

Ce cas est postérieur au décompte de 179 scénarios : il ne modifie pas rétroactivement leurs résultats. Il doit être ajouté comme contrôle de régression lors de la Phase 6.

## 7. État dépôt et base au 12/10/2026

- Le commit applicatif courant est `97a9e3f` sur `main`.
- Une lecture de `php artisan migrate:status` sur la base locale `qpos` confirme que les migrations `2026_10_08_160000_create_reporting_workflows` et `2026_10_09_230000_add_post_audit_workflows` sont au statut **Ran**.
- Après le correctif de permission, Git signale trois seeders modifiés localement : `CameroonDemoSeeder.php`, `DatabaseSeeder.php` et `RolePermissionSeeder.php`. Aucun commit n’a été fait pour ces modifications locales.
- Les rapports détaillés sont datés de leur campagne respective. Leur base Git d’origine n’est pas le HEAD courant ; `97a9e3f` inclut le lot post-audit.

## 8. Point de décision propriétaire avant clôture documentaire

Une divergence de format reste à arbitrer : le bilan post-audit rapporte un affichage XAF à deux décimales, tandis qu’une demande UX initiale indiquait un affichage sans décimale pour XAF. La précision stockée et les quantités fractionnaires sont distinctes de l’affichage monétaire. La règle d’affichage finale doit être confirmée et documentée avant de déclarer cette préférence UX définitivement close.

## 9. Conclusion

La Phase 5 est livrée et largement vérifiée selon les périmètres et preuves documentés. Aucun bug restant n’est confirmé dans les 179 cas de l’audit post-Phase 5. Les éléments non couverts ou reportés sont identifiés ci-dessus ; le SMTP réel, le matériel physique et la reprise après sinistre ne sont pas certifiés par les essais réalisés.

Ce bilan consolidé est proposé pour revue. Il complète les rapports historiques sans les remplacer. Aucun commit n’est créé par l’assistant ; le propriétaire conserve la responsabilité du commit et de la décision sur le format d’affichage XAF.

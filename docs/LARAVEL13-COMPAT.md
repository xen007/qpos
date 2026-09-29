# QPOS — compatibilité Laravel 13 et ordre de migration

**Relevé : 29/09/2026.** Analyse en lecture seule. Aucun changement Composer, runtime ou code.

## Résultat

**Laravel 13 est bloqué dans l’environnement actuel.** PHP CLI XAMPP est 8.2.12 ; Laravel 13 requiert PHP ^8.3. composer.json contraint aussi Laravel à ^10.8. Docker n’est pas installé sur ce poste.

La recommandation suit la décision du propriétaire : exécuter les passes verticales sans changer runtime ni dépendances Composer, puis traiter PHP 8.4 + Laravel 13 dans un lot final dédié après décision/runtime. Ce choix garde les passes S4/S5 sans migration d’infrastructure, mais prolonge temporairement l’usage d’un Laravel 10 qui n’est plus corrigé en sécurité ; ce n’est pas une approbation de mise en production.

## Diagnostic Composer exécuté

Commande : composer why-not --locked laravel/framework '^13.0' --no-interaction --no-cache

Environnement : Composer 2.10.3, PHP 8.2.12. Le solveur refuse Laravel 13.0.0 : PHP ^8.3 est requis et PHP 8.2.12 est actif. Le projet demande laravel/framework ^10.8. Plusieurs versions verrouillées limitent leurs contrats Illuminate à Laravel 10 ou 11. C’est un diagnostic du solveur, pas une simulation de migration ; aucun fichier n’a été écrit.

### Versions verrouillées qui bloquent le solveur 13

| Paquet verrouillé | Version | Blocage constaté | Piste finale |
|---|---:|---|---|
| laravel/framework | 10.48.22 / contrainte ^10.8 | contrainte majeure du projet | ^13.0 après préparation de PHP 8.3+. |
| barryvdh/laravel-dompdf | 2.2.0 | Illuminate jusqu’à 11 | wrapper 3.x annonce 9–13 et PHP 8.1+ ; confirmer version stable. |
| laravel/prompts | 0.1.25 | Illuminate Collections 10/11 | transitive ; résoudre la ligne ^0.3 requise par le framework 13. |
| laravel/sanctum | 3.3.3 | Illuminate 9/10 | Sanctum 4.x annonce 11–13 et PHP 8.2+ ; revoir config/migrations. |
| laravel/socialite | 5.16.0 | Illuminate jusqu’à 11 | 5.24.3+ annonce la compatibilité 13 ; tester le flux OAuth Google. |
| laravel/tinker | 2.10.0 | Illuminate jusqu’à 11 | Tinker 3.x annonce 8–13 et PHP 8.1+. |
| laravelcollective/html | 6.4.1 | Illuminate jusqu’à 10 ; paquet retiré par son mainteneur | l’analyse locale ne trouve plus d’appel Form/Html/Collective dans app, resources ou routes ; retirer si cette absence est confirmée au lot Composer. |
| maatwebsite/excel | 3.1.58 | cette version plafonne à Illuminate 11 | branche 3.1 annonce Laravel 13 mais CVE seulement ; 4.0 cible Laravel 12/13, PHP 8.3+, PhpSpreadsheet 5 et changements d’API. |
| spatie/laravel-permission | 5.11.1 | Illuminate jusqu’à 10 | versions 7.x–8.x pour Laravel 12/13, PHP 8.3+ ; revoir config, migrations, cache et Gates/Policies. |
| yajra/laravel-datatables et modules 10.x | core 10.0.0, oracle 10.11.4, buttons 10.0.9, HTML 10.12.0, fractal 10.0.0 | contrats des versions verrouillées jusqu’à Laravel 10 | série 13 pour Laravel 13, PHP 8.3+ ; vérifier Editor/Export et aligner les modules réellement utilisés. |
| barryvdh/laravel-debugbar | 3.14.6 | Illuminate jusqu’à 11 | le mainteneur publie fruitcake/laravel-debugbar 4.x pour Laravel 11–13, PHP 8.2+ ; dépendance de développement et changement de nom. |
| laravel/sail | 1.37.1 | Illuminate jusqu’à 11 | versions récentes annoncent Laravel 13 ; Docker reste indisponible localement. |
| nunomaduro/collision | 7.11.0 | conflit explicite avec Laravel >=11 | le squelette Laravel 13 utilise 8.x ; vérifier la version stable exacte avec PHPUnit au solveur final. |
| spatie/laravel-ignition / spatie/flare-client-php | 2.8.0 / 1.8.0 | Illuminate jusqu’à 11 | résoudre des versions compatibles 13 et séparer les outils dev de la production. |

Le solveur signale aussi des mises à niveau transitives : brick/math 0.12.1 vers au moins 0.14.2 ; laravel/serializable-closure 1.3.5 vers 2.0.10 ; league/commonmark 2.5.3 vers 2.8.1 ; Carbon 2.72.5 vers 3.8.4 ; Termwind 1.16 vers 2.x ; Symfony 6.4 vers 7.4 ou 8.x ; voku/portable-ascii 2.0.1 vers 2.0.2+. Résoudre ensemble au lot final et revoir les changements de dates/API.

## PHP : lock courant et branches cibles

- composer.json demande PHP ^8.1 ; le PHP CLI actif est 8.2.12 à C:\xampp\php\php.exe. Comparer la version Apache et ses extensions à la CLI.
- **Aucun paquet du composer.lock relevé n’impose un minimum PHP 8.3.** Laravel 13 est lui-même le blocage principal du runtime courant.
- Les lignes cibles modernes qui imposent PHP 8.3+ comprennent Laravel 13, Spatie Permission 7+, Yajra DataTables 13, Laravel Excel 4 et Intervention Image 4. Laravel 13 sélectionne aussi PHPUnit 12 ; confirmer la contrainte exacte du paquet dans le solveur final.
- Des contraintes supérieures du lock doivent aussi être contrôlées pour PHP 8.4 : HtmlPurifier ne déclare que les lignes PHP jusqu’à 8.3 et Sabberworm jusqu’à 8.4.
- Intervention Image 2.7.2 n’impose pas PHP 8.3 et la bibliothèque est framework-agnostic, mais son API diffère de la version 4 ; app/Trait/FileHandler.php l’utilise activement.
- Guzzle 7.9.2 satisfait la contrainte Laravel 13 ^7.8.2 || ^8.0 et reste compatible PHP 8.2. La contrainte racine ^7.2 est plus large que nécessaire.
- Les métadonnées Composer n’attestent pas les extensions du PHP Apache. Vérifier php -v et php -m pour les deux runtimes avant le lot final.

## Synthèse de compatibilité

**Compatible dans une ligne cible après mise à niveau :** Dompdf 3.x, Sanctum 4.x, Socialite 5.24.3+, Tinker 3.x et Debugbar Fruitcake 4.x déclarent la compatibilité Laravel 13. Guzzle 7.9.2 peut être conservé si le solveur le retient.

**Incompatible aux versions actuellement verrouillées :** Laravel 10, Laravel Collective, Sanctum 3, Spatie Permission 5, Yajra 10, Debugbar 3, Collision 7, Sail 1.37, ainsi que les versions verrouillées d’Excel, Dompdf, Socialite, Tinker, Prompts et leurs composants transitifs mentionnés plus haut.

**À confirmer au solveur final :**

- versions stables précises de Collision, Ignition, Excel, Yajra Editor/Export et modules associés ;
- extensions PHP, MySQL/MariaDB et différences entre CLI/Apache ;
- changements de config Sanctum, session, providers/bootstrap, commandes Artisan, migrations publiées et auto-discovery ;
- API Intervention Image et PhpSpreadsheet, Carbon 2→3, Symfony 6→7/8, exports, PDF, courriels, OAuth et imports ;
- build Vite/Node, à valider dans le lot final du projet.

## Recommandation d’ordre

1. **Pendant les passes verticales :** rester sur PHP 8.2.12 et les versions Composer actuelles, comme décidé. Ne pas modifier composer.json, composer.lock, Dockerfiles ni runtime. Ne pas introduire des APIs Laravel 13 en avance.
2. Garder ce rapport comme inventaire unique des bloqueurs ; actualiser la compatibilité seulement quand le lot final commence, car les versions upstream évoluent.
3. Après les passes : décider et préparer PHP 8.4 dans un environnement parallèle ou sauvegardé. Ne pas compter sur Docker tant qu’il n’est pas installé.
4. Faire ensuite une migration coordonnée PHP 8.4 + Laravel 13 + packages compatibles, examiner le lockfile, puis vérifier les neuf parcours au moyen de docs/REFERENCE-MAP.md.
5. **Pas de Laravel 12 intermédiaire par défaut :** il accepte PHP 8.2 mais ses corrections fonctionnelles ont cessé le 13/08/2026 et sa sécurité finit le 24/02/2027. Deux migrations majeures successives ne valent la courte fenêtre obtenue. Reconsidérer seulement si PHP 8.3+ reste durablement impossible.

### Risque de support actuel

Laravel 10 ne reçoit plus de correctifs de sécurité depuis le 04/02/2025. La branche PHP 8.2 reçoit des correctifs de sécurité jusqu’au 31/12/2026 ; PHP 8.2.12 est en retard sur cette branche (PHP 8.2.34 était annoncé le 24/09/2026). La décision de laisser le runtime en place pendant les passes est respectée, mais ce socle ne convient pas à une exposition publique. Réduire la durée du chantier sur Laravel 10 et préparer le runtime PHP 8.4 avant la fin de 2026.

## Sources

- [Prérequis et support Laravel](https://laravel.com/framework/docs/releases) ; [guide d’upgrade 13](https://laravel.com/framework/docs/13.x/upgrade).
- [Versions PHP supportées](https://www.php.net/supported-versions.php) ; [annonces PHP 2026](https://www.php.net/archive/2026.php).
- [Compatibilité Laravel DataTables](https://github.com/yajra/laravel-datatables) ; [upgrade Yajra 13](https://github.com/yajra/laravel-datatables-docs/blob/master/upgrade.md).
- [Prérequis Spatie Permission](https://github.com/spatie/laravel-permission/blob/main/docs/prerequisites.md).
- [Support Laravel Excel](https://github.com/SpartnerNL/Laravel-Excel) ; [upgrade Excel 4](https://github.com/SpartnerNL/Laravel-Excel/blob/4.x/UPGRADE-4.x.md).
- [Métadonnées Dompdf](https://github.com/barryvdh/laravel-dompdf/blob/master/composer.json) ; [Sanctum 4](https://github.com/laravel/sanctum/blob/4.x/composer.json) ; [compatibilité Socialite 13](https://github.com/laravel/socialite/blob/5.x/CHANGELOG.md) ; [métadonnées Tinker 3](https://github.com/laravel/tinker/blob/3.x/composer.json).
- [Prérequis Intervention Image](https://github.com/Intervention/image) ; [Debugbar et renommage](https://github.com/fruitcake/laravel-debugbar).

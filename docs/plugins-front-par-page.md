# QPOS — Plugins front par page (migration vers le layout Tailwind)

> **Mise à jour — 02/10/2026 :** tous les écrans métier existants utilisent le shell Tailwind commun. Les îles React POS/achat sont montées par ce shell ; `backend.master` est un alias. Bootstrap/AdminLTE/Font Awesome et les plugins globaux historiques ne sont plus chargés par ces écrans. Achat : date native, React Select ; POS : React Select/SweetAlert/Sonner. Les listes gardent leurs plugins DataTables locaux. Les paragraphes historiques ci-dessous décrivant ces deux pages en legacy sont remplacés par cette note et le [bilan de refonte](refonte-ui-complete.md).

Document de travail du Sprint 3 (lot A5). Il indique, pour chaque page du back-office,
les dépendances front à déclarer lorsqu'elle est migrée vers `backend.master-tailwind`.

> Documents liés : `docs/routes-permissions.md` (cartographie des routes et des
> permissions, Partie B) et `docs/modernization-roadmap.md` (feuille de route générale).

## Pourquoi déclarer les plugins

Finition regroupée du sous-lot 4 (01/10/2026) : voir
[ui-composants.md](ui-composants.md) pour les paramètres compatibles, nouveaux
composants, clavier, responsive et limites de vérification. Les icônes migrées
utilisent le sprite local `public/icons/lucide/sprite.svg`, dérivé du paquet
Lucide déjà installé ; les autres conservent Font Awesome. Sonner suit les
couleurs sémantiques partagées. Le raccordement des styles du shell AdminLTE
préserve ses plugins et les routes actuelles.

### Pages publiques — Lot A Phase 1, 01/10/2026

Les quatre vues `frontend/authentication` et la 404 partagent désormais
`frontend.authentication.layout` : tokens, Inter locale, CSS Tailwind, `theme.js`
et `frontend.js` (affichage du mot de passe, confirmation, saisie/collage OTP).
Aucun Bootstrap, tooltip, back-to-top, copyright.js ou police externe n'est
chargé par ce layout. Les messages et erreurs restent visibles dans le formulaire.

Les paramètres de style proposent trois palettes globales : `petrol`, `teal`,
`indigo`. `App\Support\SitePalette` fournit la liste autorisée et le défaut
Bleu pétrole si la valeur est absente/invalide. Les deux shells backend et le
layout public lisent la même configuration `system.site_palette`, enregistrée
uniquement lorsque le propriétaire soumet le formulaire protégé par
`style_settings`. Le thème clair/sombre reste une préférence navigateur.
La racine conserve sa redirection vers le login ; aucune vue welcome artificielle.

Le layout historique `backend.master` charge globalement AdminLTE, Bootstrap, jQuery et
tous les plugins (DataTables, select2, summernote, dropzone, moment, daterangepicker).
Le layout `backend.master-tailwind` ne charge que :

- les jetons de design (`public/css/qpos-tokens.css`) ;
- Inter variable locale (`public/fonts/inter/InterVariable.woff2`, version 4.1,
  licence SIL OFL dans le meme dossier), declaree dans les jetons et prechargee
  par le shell Tailwind ; aucun CDN ni nouveau package ;
- Font Awesome ;
- Tailwind compilé (`resources/css/app.css`) ;
- le thème (`resources/js/theme.js`), le shell (`resources/js/shell.js`) et, si besoin,
  les notifications Sonner.

Chaque page migrée déclare donc ses propres plugins via les partiels ci-dessous.

Lot A de Phase 1 (01/10/2026) : tokens Soft Modern clair/sombre, shell et bascule
de theme actualises ensemble. Les deux layouts partagent les tokens et
`theme.js` ; le shell Tailwind conserve les contrats `data-qpos-*` de `shell.js`.
Les utilitaires de couleur disposent de valeurs de repli ; le choix du theme
est valide et reste utilisable si le stockage navigateur est bloque.

## Déclaration dans une page

```blade
@extends('backend.master-tailwind')

@section('content')
    ...
@endsection

{{-- Plugins nécessaires à cette page --}}
@include('backend.layouts.tailwind.plugins.datatables')

@push('script')
    <script>/* initialisation propre à la page */</script>
@endpush
```

Partiels disponibles (dossier `resources/views/backend/layouts/tailwind/plugins/`) :

| Partiel | Contenu | Remarque |
|---|---|---|
| `jquery` | `plugins/jquery/jquery.min.js` | inclus automatiquement par les autres |
| `datatables` | CSS + JS DataTables, boutons, traduction FR/EN | `@include('...plugins.datatables')` |
| `select2` | CSS + JS select2 | l'initialisation reste à la page (l'ancien `custom-script.js` n'est pas chargé) |
| `daterangepicker` | CSS + moment + daterangepicker | le thème sombre du plugin vit dans `custom-style.css` (non chargé sur les pages migrées) |

`@once` garantit qu'un même partiel n'est chargé qu'une fois, même si plusieurs plugins
sont déclarés (jQuery n'est donc jamais chargé deux fois). Le partiel `datatables`
fournit aussi la **traduction** des tables : une page migrée n'a plus besoin de
redéfinir un bloc `language` (la liste des commandes le faisait en double).

## Matrice page → plugins (état au 29/09/2026)

| Page (vue Blade) | Plugins à déclarer | Migration |
|---|---|---|
| `backend/index.blade.php` (tableau de bord) | aucun (Chart.js via Vite, dates natives) | **migrée** |
| `backend/profile/index.blade.php` | aucun (`public/js/image-field.js` du projet) | **migrée** |
| `backend/brands/index.blade.php` | `datatables` | **migrée** |
| `backend/brands/create.blade.php` | aucun (`x-backend.image-field`) | **migrée** |
| `backend/brands/edit.blade.php` | aucun (`x-backend.image-field`) | **migrée** |
| `backend/categories/create.blade.php` | aucun (`x-backend.image-field`) | **migrée** |
| `backend/categories/edit.blade.php` | aucun (`x-backend.image-field`) | **migrée** |
| `backend/customers/create.blade.php` | aucun | **migrée** |
| `backend/customers/edit.blade.php` | aucun | **migrée** |
| `backend/suppliers/create.blade.php` | aucun | **migrée** |
| `backend/suppliers/edit.blade.php` | aucun | **migrée** |
| `backend/units/create.blade.php` | aucun (page sans champ image : le script `image-field.js` n'est plus chargé) | **migrée** |
| `backend/units/edit.blade.php` | aucun | **migrée** |
| `backend/settings/currencies/create.blade.php` | aucun (page sans champ image : le script `image-field.js` n'est plus chargé) | **migrée** |
| `backend/settings/currencies/edit.blade.php` | aucun | **migrée** |
| `backend/users/create.blade.php` | aucun (`x-backend.image-field`) | **migrée** |
| `backend/users/edit.blade.php` | aucun (`x-backend.image-field`) | **migrée** |
| `backend/categories/index.blade.php` | `datatables` | **migrée** |
| `backend/customers/index.blade.php` | `datatables` | **migrée** |
| `backend/orders/index.blade.php` | `datatables` | **migrée** |
| `backend/orders/print-invoice.blade.php` | aucun (`x-backend.invoice`) | **migrée** |
| `backend/orders/collection/invoice.blade.php` | aucun (`x-backend.invoice`) | **migrée** |
| `backend/orders/collection/create.blade.php` | aucun | **migrée** |
| `backend/orders/pos-invoice.blade.php` | aucun (ticket monospace, apparence claire forcée) | **migrée** |
| `backend/settings/role/permissions.blade.php` | aucun (cases à cocher, 63 permissions) | **migrée** |
| `backend/orders/collection/index.blade.php` | aucun (transactions d'une commande, rendues par le serveur) | **migrée** |
| `backend/products/index.blade.php` | `datatables` | **migrée** |
| `backend/purchase/index.blade.php` | `datatables` | **migrée** |
| `backend/purchase/products.blade.php` | aucun (lignes rendues par le serveur ; l'ancienne table portait un `id="datatables"` sans initialisation) | **migrée** |
| Achats 3.D, création et détail | Création React avec conditionnements, dates HTML natives et calcul décimal BigInt ; détail Blade, réceptions et règlements HTML/CSRF | 06/10/2026 : pas de Moment/datepicker pour ces nouvelles dates ; portée boutique conservée |
| `backend/reports/inventory.blade.php` | `datatables-export` (DataTables + boutons Excel/PDF/Impression, **fichiers locaux** au lieu des CDN) | **migrée** |
| `backend/settings/currencies/index.blade.php` | `datatables` | **migrée** |
| `backend/settings/role/index.blade.php` | aucun (modale native `<dialog>`) | **migrée** |
| `backend/settings/permission/index.blade.php` | aucun | **migrée** |
| `backend/suppliers/index.blade.php` | `datatables` | **migrée** |
| `backend/units/index.blade.php` | `datatables` | **migrée** |
| `backend/users/index.blade.php` | `datatables` | **migrée** |
| `backend/products/create.blade.php` | aucun (listes et date natives : plus de select2 ni de datepicker) | **migrée** |
| `backend/products/edit.blade.php` | aucun (listes et date natives) | **migrée** |
| `backend/products/import.blade.php` | aucun (page sans select2 ni image) | **migrée** |
| `backend/reports/sale-report.blade.php` | aucun (table rendue par le serveur, filtre de dates natif) | **migrée** |
| `backend/reports/sale-summery.blade.php` | aucun (filtre de dates natif : plus de daterangepicker ni de moment) | **migrée** |
| `backend/settings/website-settings/general.blade.php` | aucun (8 onglets `[data-qpos-tabs]`, 3 champs image, 7 interrupteurs) | **migrée** |
| toutes les autres pages | aucun | à migrer |

### Pages hors migration Blade

| Page | Nature | Décision |
|---|---|---|
| `backend/cart/index.blade.php` | île **React** (`<div id="cart">`, bundle `resources/js/app.jsx`) | reste sur le layout historique : le layout legacy est le seul à amorcer React (`@viteReactRefresh` + `app.jsx`, voir `backend/master.blade.php`) |
| `backend/purchase/create.blade.php` | île **React** (`<div id="purchase">`, même bundle, chargé aussi pour cette route) | idem : migrer la coquille casserait le montage React. La liste des achats et le détail sont, eux, migrés |

## Composants Blade du layout Tailwind

| Composant | Rôle |
|---|---|
| `x-backend.card` | carte de contenu (titre, sous-titre, slots `actions` et `footer`) |
| `x-backend.stat-card` | indicateur (libellé, valeur, icône, lien) |
| `x-backend.breadcrumbs` | fil d'Ariane (remplaçable via `@section('breadcrumb')`) |
| `x-backend.dropdown` | menu déroulant, comportement géré par `shell.js` |
| `x-backend.flash-toasts` | notifications flash Sonner (chargées seulement si nécessaire) |
| `x-backend.modal` | modale de formulaire sur `<dialog>` natif (Échap, focus et fond gérés par le navigateur), ouverte via `data-qpos-modal-open="#id"` |
| `x-backend.input` | champ de saisie (libellé, obligatoire, icône facultative, `old()` et erreur de validation) |
| `x-backend.textarea` | zone de texte, même comportement que `input` |
| `x-backend.radio-group` | choix exclusifs sur une ligne (`options` valeur => libellé, valeur retenue par `old()`) |
| `x-backend.button` | bouton ou lien (`variant` primary/ghost, `size`, `icon`) — mêmes classes que les boutons écrits à la main |
| `x-backend.switch` | interrupteur d'état : champ caché `0` puis case `1`, motif des formulaires existants |
| `x-backend.select` | liste déroulante (options `valeur => libellé`, option vide, valeur retenue par `old()`) |
| `x-backend.image-field` | champ d'image avec aperçu — **plusieurs par page** grâce à `field-id` (identifiants uniques) ; le script agit dans le conteneur `[data-qpos-image-field]` |
| `[data-qpos-tabs]` (comportement de `shell.js`, pas un composant) | onglets sans Bootstrap : `[data-qpos-tab]` + `[data-qpos-tab-panel]`, onglet initial repris du paramètre `?active-tab=` |
| `x-backend.invoice` | facture imprimable : en-tête, coordonnées, articles, note et totaux (slots `information` et `totals`), sans le CSS `.invoice` d'AdminLTE |
| `x-backend.adminlte.nav-item` / `x-backend.tailwind.nav-item` | rendu de navigation, piloté par `App\Support\BackendMenu` |

## Convention pour les listes DataTables migrées

Les contrôleurs génèrent aujourd'hui les cellules `action` et `status` en markup
Bootstrap (menu déroulant `data-toggle="dropdown"`, badges), que la page migrée ne peut
pas afficher : les pages migrées composent donc leurs cellules côté page.

1. Le contrôleur expose deux **colonnes neutres** en plus des colonnes historiques
   (qui restent inchangées pour les pages AdminLTE) :
   `->addColumn('id', fn ($data) => $data->id)` et
   `->addColumn('is_active', fn ($data) => (bool) $data->status)`.
2. La page migrée déclare ses colonnes avec les mêmes index que la page historique
   (la colonne `action` du serveur est ignorée, seul son index est conservé), puis
   remplace le rendu via `render` :
   `window.qposTableActions.inline({...})` pour les actions,
   `window.qposTableActions.statusBadge(...)` pour l'état.
3. Le helper `resources/js/table-actions.js` (entrée Vite dédiée) produit le markup
   Tailwind : deux actions inline (modifier, supprimer) et un badge d'état, sans
   Bootstrap ni jQuery, avec jeton CSRF et méthode `DELETE` spoofée.
   Pour les pages à actions supplémentaires (devises : « définir par défaut »), la
   fonction générique `window.qposTableActions.buttons({ csrf, items })` accepte une
   liste d'actions `{ type: 'link'|'form', url, method, label, icon, confirm }`.
   Deux options supplémentaires couvrent les listes métier :
   `disabled: true` désactive une action sur un enregistrement protégé (client
   « Walking Customer », fournisseur interne), comme le faisait le contrôleur ;
   et pour les clients, les actions disponibles sont calculées avec les mêmes
   permissions que dans le contrôleur et transmises dans `can`.
4. La page passe ses libellés, son jeton CSRF et ses gabarits de routes dans un bloc
   `<script type="application/json">` (même motif que le tableau de bord), puis les
   consomme dans son initialisation DataTables.

   **Attention aux types de paramètres de route** : un gabarit `:id` ne fonctionne que
   pour un paramètre de **chemin** (`/products/:id/edit`), inséré tel quel par le
   routeur. Pour un paramètre de **requête** (`?barcode=...`), le routeur encode le
   jeton en `%3A` et le remplacement échoue : l'URL complète doit alors être construite
   côté contrôleur, comme la colonne neutre `purchase_url` de la liste produits.

Le tableau de bord et la page marques suivent ce motif ; les 11 autres listes peuvent
être migrées à l'identique. Les colonnes Bootstrap des contrôleurs deviendront du code
mort une fois toutes les listes migrées et pourront être retirées dans un lot dédié.

4. **Impression** : le layout AdminLTE masquait la coquille (sidebar, barre, pied) grâce à
   ses propres règles `@media print`. Le layout Tailwind n'en a pas : ce sont les
   utilitaires `print:hidden` posés sur la sidebar, la barre supérieure, le pied de page,
   le fil d'Ariane et le titre de page qui garantissent qu'une facture s'imprime seule.
   Le bouton d'impression porte lui aussi `print:hidden` (il remplace `.no-print`
   d'AdminLTE). **À vérifier visuellement dans XAMPP** : les factures et le reçu, dont le
   rendu papier dépend de l'imprimante.
5. **Ticket de caisse** : le reçu force une apparence claire (noir sur blanc, monospace)
   au lieu des jetons de thème. C'est volontaire : le ticket doit rester lisible et
   économe en encre même si l'utilisateur travaille en thème sombre — le legacy gérait
   ce cas par des règles `.receipt-container` dans `custom-style.css`, non chargé sur
   les pages migrées.
6. **Contrôles natifs sur les formulaires produits** : les listes marque/catégorie et la
   date d'expiration utilisaient respectivement select2 et le datepicker Bootstrap
   (jQuery + moment + tempusdominus). Ils sont remplacés par des listes natives et un
   `<input type="date">` au même format `Y-m-d` : la page ne charge donc plus aucun
   plugin. Contrepartie assumée : la **recherche dans les longues listes** disparaît.
   Si le catalogue de marques ou de catégories devient volumineux, il suffit de déclarer
   `@include('backend.layouts.tailwind.plugins.select2')` sur ces pages et d'initialiser
   le champ (le partiel existe déjà).
7. **Filtre de période du résumé des ventes** : le bouton daterangepicker est remplacé
   par deux champs de date natifs, avec les **mêmes paramètres** `start_date` /
   `end_date` (donc les liens existants continuent de fonctionner). Le contrôleur
   transmet désormais les valeurs brutes `start_date_input` / `end_date_input` pour
   alimenter le filtre, les clés `start_date` / `end_date` restant les libellés affichés.
   **Robustesse à traiter séparément** : `ReportController::{saleSummery,saleReport}`
   analysent ces paramètres avec `Carbon::createFromFormat()` sans garde — une date
   invalide dans une URL écrite à la main provoque une erreur 500, comme le faisait le
   tableau de bord avant son correctif (`parseDate()` défensif).
8. **Message de fermeture du site** — *corrigé* : la page affichait un texte **codé en dur**
   au lieu du réglage, et `WebsiteSettingController::websiteStatusUpdate()` n'enregistrait
   que `is_live`, si bien que le message saisi était perdu. Le contrôleur enregistre
   désormais `close_msg` (avec validation de `is_live`), la vue affiche `readConfig('close_msg')`.
9. **Permissions obsolètes supprimées** : `sale_edit` (doublon de `sale_update`, contrôlée
   nulle part) et `product_purchase` (accordée au caissier, utilisée nulle part). Retirées du
   seeder, de `OrderPolicy` et de la base par la migration
   `2026_09_29_120000_remove_obsolete_permissions` — aucun changement d'accès (ces permissions
   ne donnaient rien), et le vendeur retrouvera la modification de ventes en une ligne s'il
   doit l'avoir un jour : lui accorder `sale_update`.
10. **Parcours de caisse protégé** : `/get/products`, `/cart`, `/cart/increment`,
    `/cart/decrement`, `/cart/delete`, `/cart/empty` et `/order/create` sont désormais groupés
    sous `permission:sale_create` (seuls `cart.empty` et `order/create` l'étaient). Les trois
    rôles détiennent `sale_create` : aucun changement d'accès aujourd'hui, mais la brèche est
    fermée pour les rôles à venir.
11. **Filtres de période consolidés** : `App\Support\DateRange` remplace les analyses
    dispersées du tableau de bord (`parseDate`) et des deux rapports (`Carbon::createFromFormat`
    sans garde, qui provoquait une erreur 500 sur une URL forgée). Le helper accepte
    `start_date`/`end_date`, `date_from`/`date_to` et l'ancien `daterange`, ignore les valeurs
    invalides, et **borne la période à la fin de journée** — les ventes du jour courant, jusque-là
    exclues du tableau de bord, sont maintenant comptées. Les libellés de période des rapports
    utilisent `translatedFormat` (dates en français quand l'interface est en français).
12. **`public/build` reste versionné** : la ligne `/public/build` est volontairement commentée
    dans `.gitignore`, ce qui permet un déploiement par `git` sans Node. Conséquence assumée :
    chaque `npm run build` apparaît dans le diff. Pour inverser ce choix :
    `git rm -r --cached public/build`, décommenter la ligne, puis `npm run build` sur le serveur.
13. **Écart mineur à connaître** : le réglage `site_url` vaut `http://localhost/QPOS/public`
    alors que `APP_URL` vaut `http://localhost/qpos/public`. Sans effet sous Windows (casse
    ignorée), à aligner avant tout déploiement Linux.
14. **Ressources legacy conservées** : `summernote` et `dropzone` sont toujours chargés par
    `backend.master` car `public/js/custom-script.js` et `page-builder-script.js` s'y réfèrent ;
    ils disparaîtront avec le layout historique. `config/system.php` ne contient **aucun secret**
    (site, réseaux sociaux, options de facture) : le stockage en clair n'appelle pas de refonte.

## Vérification finale (Sprint 3)

Audit global exécuté sur l'état réel du dépôt : **73 contrôles, 0 échec**, puis un second
passage après le lot de durcissement (**14 contrôles d'intégrité et 27 contrôles de
correctifs, 0 échec**).

- **Partie B** : 134 routes, **108 protégées** par `permission:xxx` (26 sans permission, toutes
  justifiées dans `docs/routes-permissions.md`), 60 permissions distinctes toutes existantes et
  détenues par au moins un rôle ; aucune méthode exposée par une route ne conserve de `abort_if` ;
  les 8 Form Requests existent et sont injectées ; les 3 Policies sont enregistrées et les
  contrôles `Gate` passent (dont les refus pour vendeur et caissier).
- **A4** : plus aucune règle morte du tableau de bord, jetons extraits (7 jetons × 3 thèmes),
  accolades équilibrées, layout Tailwind alimenté par `qpos-tokens.css`.
- **A5** : 42 pages migrées, seules les 2 îles React restent en legacy, aucune page migrée ne
  charge Bootstrap/AdminLTE/plugin, partiels de plugins présents, build produit, et 8 pages
  représentatives rendues en Tailwind pur sans exception.

## Points d'attention découverts

1. **Markup généré côté contrôleur** : les colonnes `action` et `status` des listes
   DataTables sont construites dans les contrôleurs avec des classes Bootstrap
   (`btn-group`, `dropdown-menu`, `data-toggle="dropdown"`, `badge`). Migrer une liste
   impose donc aussi de réécrire ces colonnes (ou d'exposer un rendu neutre), et de
   remplacer le menu déroulant Bootstrap par le composant `x-backend.dropdown` + `shell.js`.
2. **Assets chargés mais inutilisés** par le layout historique : `summernote` et
   `dropzone` (aucune vue ne les utilise) et `sweetalert2` (aucune vue Blade). Ils
   peuvent être retirés de `backend.master` pour alléger les pages non migrées.
3. **Thème sombre des plugins** : `custom-style.css` contient le thème sombre de
   daterangepicker, select2, tempusdominus et DataTables. Les pages migrées ne le
   chargent pas : tant qu'elles utilisent ces plugins, il faut soit reporter ces règles
   dans le layout Tailwind, soit remplacer le plugin par un contrôle natif (c'est le
   choix fait pour le tableau de bord, avec deux champs de date).
4. **Incohérences héritées, conservées telles quelles** (à trancher séparément) :
   les listes marques, catégories et unités trient sur `order: [[1, 'asc']]`, soit la
   colonne image quand elle existe, au lieu du nom (index 2) ; dans la liste
   utilisateurs, l'en-tête « # » affiche en réalité l'avatar, et la colonne « Role » ne
   renvoie que le premier rôle — et rien du tout si l'utilisateur n'a aucun rôle.

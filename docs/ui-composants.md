# QPOS — Composants Soft Modern

Date : 01/10/2026. Phase 1, sous-lot 4, finition regroupée après le commit
`b7306be`. Cette référence décrit le socle utilisable par les prochaines phases.

## Identité et surfaces

- Trois palettes globales : Bleu pétrole, Sarcelle, Indigo ; accent sauge.
- Sidebar profonde et colorée, texte clair, page active blanche avec repère sauge.
- Header teinté, profil identifié par initiale, actions et titres colorés.
- Fond de page et bordures teintés ; cartes claires en journée, surfaces adaptées
  en sombre. Les textes secondaires conservent une couleur discrète et lisible.
- Inter locale ; tokens uniques dans `public/css/qpos-tokens.css`.
- Le choix global de palette reste dans les paramètres de style ; le choix
  clair/sombre reste personnel. Aucun nouveau réglage ou stockage n'est ajouté.

## Contrats des composants Blade

Les composants existants conservent leurs paramètres ; les attributs HTML sont
transmis comme auparavant. Les pages historiques gardent AdminLTE et leurs
plugins. Les styles de leur navigation consomment les mêmes tokens.

| Composant | Paramètres et utilisation |
|---|---|
| `x-backend.button` | `type`, `href`, `icon`, `size` sm/md/lg ; variantes primary/secondary/ghost/danger ; `loading` sur bouton natif : désactivation et aria-busy |
| `x-backend.back-button` | `href` obligatoire, `label` facultatif ; destination explicite, pas de retour aveugle dans l'historique |
| `x-backend.card` | `title`, `subtitle`, `padded` ; slots actions/footer ; en-tête teinté |
| `x-backend.stat-card` | `label`, `value`, `icon`, `href`, `hint` ; aucun calcul métier dans le composant |
| `x-backend.input/select/textarea` | Paramètres existants et old() conservés ; erreur associée au champ par aria-describedby, aria-invalid |
| `x-backend.switch/radio-group` | Valeurs et champs existants conservés ; contrôle natif et choix accessibles au clavier |
| `x-backend.image-field` | Contrats d'IDs et aperçu conservés ; ouverture par clic, Entrée ou Espace |
| `x-backend.dropdown` | `align`, slot trigger et contenu ; aucune modification des actions ou permissions |
| `x-backend.modal` | `id`, `title`, `action`, `method`, `submitLabel` ; dialog natif, CSRF et simulation des méthodes non GET/POST |
| `x-backend.badge` | `variant` success/danger/warning/info/neutral ; `icon` facultatif |
| `x-backend.state` | `title`, `description`, `variant` empty/loading/error/success ; slot d'action ; rôles status/alert pour les états concernés |
| `x-backend.table` | `caption` facultative ; zone de défilement clavier, tableau natif, attributs et contenu fournis par la page |
| `x-backend.icon` | `name` Lucide ou classe Font Awesome connue ; alias progressifs, maintien des icônes non migrées |

Exemples pour les prochains écrans :

```blade
<x-backend.button icon="circle-plus">{{ __('Add New') }}</x-backend.button>
<x-backend.back-button :href="route('backend.admin.products.index')" />
<x-backend.badge variant="success" icon="check">{{ __('Active') }}</x-backend.badge>
<x-backend.state variant="loading" :title="__('Loading...')" />
```

Le paramètre loading ne déclenche aucune requête et ne transforme pas les
soumissions existantes. Chaque futur parcours gère son état réseau et ses règles
de concurrence/idempotence dans sa phase métier.

## Navigation et accessibilité

`BackendMenu` reste la source des routes et permissions. Aucun menu de fonction
future n'est ajouté. Les formulaires CRUD dont la route possède un index associé
reçoivent un retour vers cet index ; le serveur conserve ses contrôles d'accès.
Les autres pages peuvent utiliser le composant retour avec une destination précise.

Le tiroir mobile rend le contenu arrière inerte, garde le focus dans la
navigation et le rend au bouton après fermeture. Les onglets acceptent les
flèches, Home et End. Les modales gardent leur mécanisme natif de focus ; un
clic hors du rectangle ferme la modale. Les animations respectent la préférence
de réduction des mouvements.

Lucide est servi par un sprite SVG local de 35 symboles dérivés du paquet déjà
installé, avec sa licence dans `public/icons/lucide`. Aucune dépendance ajoutée.
Les notifications Sonner partagent les couleurs sémantiques et la police locale.

## Tableaux, plugins et impression

Les tableaux existants du shell Tailwind reçoivent des en-têtes teintés et des
lignes lisibles ; DataTables garde son chargement et sa pagination actuels.
Le composant table fournit ce contrat aux nouvelles pages.

Les blocs de facture et le ticket 80 mm portent `data-qpos-invoice` : leurs
tables sont exclues de ces styles. `data-qpos-plain-table` permet une exclusion
explicite pour un autre tableau particulier. Aucune refonte des documents,
aucune modification de leurs montants, formats ou déclencheurs d'impression.

## Vérifications et limites

- Build Vite réussi, assets et manifest actualisés.
- Syntaxe des scripts modifiés vérifiée ; 25 vues compilées et syntaxe PHP valide.
- Rendu HTML réel des composants effectué sans opération métier.
- GET login et forget-password : 200 ; URL inexistante : 404 ; palette Indigo
  et sprite Lucide présents dans le HTML. Sprite et tokens : HTTP 200.
- Contrastes calculés, sur les mélanges sRGB déclarés : texte de navigation
  secondaire 5,94:1 minimum ; texte blanc des boutons primaires 5,05:1 minimum,
  sur les trois palettes en clair/sombre. Contours des champs : 3,07:1 minimum.
  Ces calculs ne remplacent pas l'inspection du CSS effectivement affiché.
- Aucun test automatisé ajouté/exécuté, aucune migration ni modification des
  données métier, aucune nouvelle route ni permission.

Le navigateur intégré expire pendant l'inspection. Le propriétaire a choisi de
vérifier lui-même le rendu final : desktop/mobile, clair/sombre, trois palettes,
navigation/profil, formulaires, modales, tableaux et impression. Les interactions
au navigateur et les régressions visuelles ne sont donc pas déclarées vérifiées.

Le sous-lot 4 est implémenté en un ensemble cohérent à revue finale unique.
Sa validation visuelle reste du ressort du propriétaire avant clôture. Les
défauts 419, les règles et les fonctionnalités des prochaines phases suivent
la roadmap ; les composants sont prêts à être réutilisés.

### Chargement des styles

Les pages publiques chargent leur propre entrée Vite `resources/css/auth.css`, dont les sources Tailwind sont limitées aux vues et composants publics. Les vues du back-office utilisent `resources/css/app.css`. Apache conserve un an les assets Vite hachés via `public/.htaccess`; cette règle ne couvre pas le HTML.


### Livraison transversale du 02/10/2026

Le shell est désormais unique, même pour les deux îles React. Les contrôles et champs écrits directement dans les vues partagent la géométrie des composants ; une base CSS limitée au shell évite les marges/polices natives divergentes. Les icônes passent par `config/ui-icons.php` et le sprite local généré par `scripts/build-icons.mjs`. La refonte et ses limites de validation sont décrites dans [refonte-ui-complete.md](refonte-ui-complete.md).

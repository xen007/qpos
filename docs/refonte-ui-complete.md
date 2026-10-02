# QPOS — Refonte UI transversale

Date : 02/10/2026. Périmètre autorisé : harmoniser les écrans existants en une livraison intégrée, avec une seule revue finale du propriétaire.

Les sept étapes ci-dessous sont celles de la **refonte visuelle**. Elles ne remplacent pas les phases métier de la roadmap V4.2 : conversions, lots/FEFO, nouveaux paiements, caisse, notifications et exploitation conservent leurs prérequis et leurs décisions à valider. Aucun historique, taux de taxe ou donnée de démonstration n'a été ajouté aux données courantes.

## Couverture

| Étape visuelle | Implémentation |
|---|---|
| 1 — Shell et authentification | Un seul layout `backend.master-tailwind` ; `backend.master` devient un alias de compatibilité. Sidebar, header, retour et navigation communs. Inter locale, mêmes tailles/espacements, thèmes et trois palettes conservés. CSS public autonome avec règles mobiles. |
| 2 — Catalogue | Listes et formulaires produits, marques, catégories et unités : composants communs, champs/actions harmonisés, icônes Lucide, tableaux accessibles au clavier. Contrats métier actuels conservés. |
| 3 — Achats/stock | Le formulaire React d'achat rejoint le shell commun. Date native, fournisseur avec recherche et tokens, recherche produits temporisée/annulable, récapitulatif partagé. Lignes d'achat en cartes sur téléphone et tableau sur ordinateur. Listes fournisseurs/achats/inventaire utilisent le même langage visuel. |
| 4 — Vente/POS et documents | Galerie de produits à boutons, panier avec quantités tactiles, récapitulatif et encaissement. Sur mobile/tablette, onglets catalogue/panier ; sur grand écran, les deux panneaux. Pagination explicite, recherche temporisée, scan validé par Entrée. Factures/tickets restent HTML imprimables, sans prétendre ajouter un générateur PDF. |
| 5 — Dashboard/rapports | Cartes, filtres, tableaux et actions communs ; graphiques avec Inter et couleurs de palette/thème synchronisées. Aucun indicateur fictif ajouté. |
| 6 — Administration | Utilisateurs, rôles, permissions, profil, devises et paramètres : contrôles et actions partagés, onglets accessibles, icônes locales, en-tête/navigation uniques. Aucun élargissement des autorisations. |
| 7 — Vérification | Compilation Blade/PHP, rendu des composants, syntaxe JS, build Vite et HTTP. La vérification visuelle authentifiée n'est pas déclarée réussie : le navigateur intégré expire à l'inspection/capture, malgré l'autorisation renouvelée du propriétaire. |

## Principes communs

- Le shell fixe la navigation et la hiérarchie des titres sur chaque page. Les mises en page métier adaptent l'espace disponible sans changer de système visuel.
- Réinitialisation CSS limitée à `.qpos-shell`, dans la couche de base Tailwind : marges et polices natives ne divergent plus selon les balises ; les utilitaires et composants gardent la priorité.
- Champs et actions principales de 44 px minimum ; saisie en 16 px sur téléphone pour éviter le zoom automatique. Focus visible, lien d'accès au contenu, navigation clavier et régions de tableaux défilantes.
- Lucide SVG local sur les écrans actifs et dans les actions DataTables. Les noms historiques d'icônes restent des alias définis dans `config/ui-icons.php`. `scripts/build-icons.mjs` génère les 59 symboles pendant le build.
- Sonner suit les tokens et le thème. Le POS et les achats évitent un second conteneur de notifications de session.
- Montants, permissions, routes et champs métier existants sont conservés. Le montage React utilise l'URL de base Laravel et un jeton CSRF explicite ; images et retour achat utilisent les URL fournies par le shell.

## Chargement et maintenance

Le CSS du shell est chargé une seule fois dans son en-tête ; le module React ne le réimporte plus. Les sources Tailwind sont explicites et sa détection automatique est désactivée avec `source(none)` pour éviter l'inclusion des autres familles de vues dans la feuille publique.

Les deux anciens consommateurs du layout AdminLTE ont été migrés. Ils ne chargent plus sa collection globale de plugins (Bootstrap, Font Awesome, Tempus Dominus, Summernote, Dropzone, etc.). Les listes conservent DataTables et les ressources dont elles ont réellement besoin. Les anciens fichiers de plugins restent sur disque ; cela ne les rend pas consommateurs actifs.

Le cache immutable d'un an des assets Vite hachés se trouve maintenant dans `public/.htaccess`, limité au chemin `build/assets/`. Il survit ainsi au nettoyage du répertoire de sortie par Vite. Le HTML, le manifest et les fichiers non hachés ne reçoivent pas ce cache long. La compression Apache reste une action d'environnement distincte.

Mesure du build final : CSS public 9,86 Ko brut / 2,78 Ko gzip, CSS back-office 43,32 Ko / 8,57 Ko ; chunk achat 9,74 Ko / 2,97 Ko, contre environ 173,44 Ko auparavant. Ces chiffres concernent les fichiers/chunks propres, pas la totalité des dépendances React d'un écran. Les poids gzip du build ne prouvent pas une compression HTTP active.

## Vérifications et limites

Premier contrôle de compilation : **89 vues**, syntaxe PHP correcte, rendu HTML réel des composants communs correct. Build Vite réussi et nouvelles entrées générées. HEAD CSS public : 200, 9 866 octets, `Cache-Control: public, max-age=31536000, immutable`. HEAD tokens : 200, aucun cache immutable. GET récupération : 200 ; URL inconnue : 404 ; les deux chargent la CSS publique et conservent `no-cache, private`.

Le build final réussit ; les 16 entrées du manifest référencent des fichiers présents. Les 44 écrans backend et l'alias de compatibilité utilisent le shell commun. Le contrôle `git diff --check` réussit ; les scripts temporaires de préparation ont été supprimés.

Lors du premier contrôle du lot UI, un GET login a dépassé 30 secondes ; après le build il répondait en HTTP 200 en 9 526 ms. Le diagnostic runtime du 02/10/2026 a ensuite réduit ce délai : `.env` local `APP_DEBUG=false`, OPcache activé côté Apache, JIT désactivé après l'erreur Windows 87 ; les dernières réponses login sont de 2 426–2 802 ms. Les détails et la sauvegarde `php.ini` sont consignés dans [le rapport de lenteur](diagnostic-lenteur.md) et la roadmap.

Le navigateur intégré a été explicitement autorisé le 02/10/2026 après un refus initial du contrôle automatique fondé sur l'ancienne consigne de revue par le propriétaire. Sa connexion a pu être rétablie, mais l'inspection de l'onglet et la capture expirent. Aucune capture ni validation visuelle authentifiée n'est donc annoncée. Vente, achat, paiement, sauvegarde de paramètres et autres écritures métier n'ont pas été exécutés sur la base courante.

Cette livraison est un groupe de modifications en attente de revue et de commit par le propriétaire. Les phases métier 2–8 ne sont pas marquées terminées par cette refonte.

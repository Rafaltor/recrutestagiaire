# Fork Pannellum (asset `assets/pannellum.js`)

## Contexte

Pannellum place les **hot spots** en **HTML au-dessus du canvas WebGL**, avec un `transform` qui incluait historiquement **`translateZ(9999px)`** pour forcer l’empilement devant la scène. Ça renforce la sensation que les boutons sont sur **un autre plan** que le wallpaper 360°.

Ce dépôt embarque une **copie minifiée** de **Pannellum 2.5.6** avec un **patch minimal** sur ce comportement.

## Patch appliqué (à conserver lors des mises à jour)

| Fichier | Modification |
|---------|----------------|
| `assets/pannellum.js` | Dans la fonction interne qui positionne chaque hot spot (souvent notée **`Ca`** dans le bundle minifié), la chaîne **`translateZ(9999px)`** est remplacée par **`translateZ(0px)`**. |

**En-tête du fichier** : le commentaire en tête de `pannellum.js` rappelle la présence du fork et pointe vers ce document.

## Mettre à jour depuis upstream

1. Télécharger la version souhaitée depuis [mpetroff/pannellum](https://github.com/mpetroff/pannellum) (release ou build `build/pannellum.js`).
2. Remplacer `assets/pannellum.js` dans le thème.
3. **Rechercher** dans le nouveau bundle : `translateZ(9999px)` (ou équivalent si le moteur change).
4. **Réappliquer** le remplacement par `translateZ(0px)` (ou une valeur très petite type `1px` si un navigateur recolle les hot spots **derrière** le canvas — à valider sur Safari / iOS).
5. Mettre à jour la **ligne de version** en commentaire en tête de fichier.

## Si les hot spots passent derrière le canvas

- Augmenter légèrement le Z : `translateZ(1px)` … `translateZ(8px)` au lieu de `0px`.
- Vérifier le **z-index** déjà posé côté thème sur `#rs-panorama .pnlm-hotspot-base` dans `sections/rs-home-landing-next.liquid`.

## Réglages complémentaires (thème, sans toucher au fork)

- Option native **`scale: true`** sur les hot spots (déjà utilisée).
- Curseur section **`hotspot_plane_scale_pct`** + variable CSS **`--rs-hotspot-plane`**.

Ces réglages restent **indépendants** du patch `translateZ` et continuent de s’appliquer après une mise à jour du JS.

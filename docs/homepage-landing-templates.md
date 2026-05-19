# Accueil : deux landings (sauvegarde + brouillon)

## Fichiers

| Rôle | Section Liquid | Template JSON |
|------|----------------|---------------|
| **Sauvegarde** (comportement actuel figé côté code) | `sections/rs-home-landing-classic.liquid` | `templates/index.json` (défaut) |
| **Nouvelle landing** (360° Pannellum, même UX scroll / hotspots que le wallpaper) | `sections/rs-home-landing-next.liquid` | `templates/index.next.json` |

Les deux sections partagent les mêmes classes CSS / JS (`.rs-home-landing`, etc.) : une seule est affichée à la fois selon le template.

## Basculer sans toucher au code

1. **Prévisualiser la nouvelle landing**  
   Sur la boutique : ouvre la page d’accueil avec le paramètre  
   `?view=next`  
   (ex. `https://ta-boutique.myshopify.com/?view=next` ou ton domaine + `/?view=next`).

2. **Rendre la nouvelle landing visible pour tout le monde**  
   Éditeur de thème Shopify → **Modèles** (ou personnalisation de la page d’accueil) → choisir le modèle d’accueil **`next`** / « index next » selon l’intitulé affiché dans l’admin.  
   Pour revenir à l’ancienne : remettre le modèle par défaut **`Default`** (fichier `index.json`).

3. **Réglages par variante**  
   Chaque template garde ses propres réglages de section dans l’éditeur (images, textes, liens).

## Développement

- Modifier uniquement **`rs-home-landing-next.liquid`** pour itérer sur la nouvelle version.  
- La **classic** sert de référence / rollback : tu peux recopier son contenu vers `next` si besoin.
- **Next (360°)** : scroller large (yaw sur **360°** via scroll + geste **diagonal** yaw+pitch sur mobile), Pannellum plein écran, **CTAs + liste d’attente en hotspots Pannellum** (yaw/pitch) pour qu’ils suivent le fond.
- **Pannellum** : pour piloter yaw/pitch depuis le scroll ou le touch, utiliser **`setYaw(angle, 0)`** et **`setPitch(angle, 0)`**. Sans 2ᵉ argument, la lib tween sur **1000 ms** → latence et pitch « bloqué » sur mobile.
- **Pitch** : plage **±90°** en config (Pannellum resserre encore selon le FOV vertical). Le geste mobile suit le **même sens que le drag souris** (doigt vers le bas → pitch +).
- **Desktop** : yaw **complet** (-180° / 180°, comme le mobile au drag) ; **`hfov`** légèrement réduit (`RS_DESKTOP_HFOV`, ex. 72°) pour un peu plus de marge haut/bas. `applyPannellumViewportMode()` réapplique hfov au passage mobile/desktop. La track / scène transparente reste en `pointer-events: none` (≥768px) pour que la souris atteigne le canvas.
- **Suite prévue** : exposer yaw/pitch des hotspots en **réglages de section** pour caler sans toucher au JS.

## Ancien fichier

La section unique `rs-home-landing.liquid` a été remplacée par **classic** + **next** pour éviter trois entrées identiques dans l’éditeur.

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
- **Next (360°)** : même structure que le wallpaper (`rs-landing-scroller`, track 250vw sur mobile, hotspots qui se déplacent avec le scroll, hint « glisse »). Le fond est Pannellum plein écran ; sur mobile le **scroll horizontal** pilote le **yaw** ; sur desktop le **drag** se fait dans la sphère (Pannellum).

## Ancien fichier

La section unique `rs-home-landing.liquid` a été remplacée par **classic** + **next** pour éviter trois entrées identiques dans l’éditeur.

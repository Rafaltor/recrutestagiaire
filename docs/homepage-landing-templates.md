# Accueil : deux landings (sauvegarde + brouillon)

## Fichiers

| Rôle | Section Liquid | Template JSON |
|------|----------------|---------------|
| **Landing classique** (wallpaper / sans Pannellum) | `sections/rs-home-landing-classic.liquid` | Pas de fichier dédié dans le dépôt ; tu peux l’assigner en changeant le **type** de section dans l’éditeur de thème si besoin de rollback. |
| **Landing 360°** (Pannellum) | `sections/rs-home-landing-next.liquid` | **`templates/index.json`** (accueil par défaut) **et** **`templates/index.next.json`** (`?view=next`) — **mêmes `settings`** dans le dépôt. |

Les deux sections partagent les mêmes classes CSS / JS (`.rs-home-landing`, etc.).

**Dépôt** : quand tu modifies les réglages par défaut de la landing next dans le JSON, **garde `index.json` et `index.next.json` alignés** (même bloc `settings` pour `rs_home_landing_next`). Le code et le style restent dans **`rs-home-landing-next.liquid`** uniquement.

## Basculer sans toucher au code

**Pourquoi on parlait de `?view=next` ?**  
Shopify charge **`/`** avec le modèle **Default** (`index.json`) et **`/?view=next`** avec le modèle alternatif **`index.next.json`**. C’était utile quand les deux fichiers **n’avaient pas** les mêmes sections ou réglages : tu pouvais tester la variante « next » sans changer le modèle assigné à la page d’accueil.

**Aujourd’hui (dépôt)** : les deux JSON sont **alignés** sur la même section et les mêmes `settings`. Pour vérifier ce qui vient du thème dans Git, **l’URL d’accueil normale (`/`) suffit** ; `?view=next` ne montre **pas** une autre version tant que les fichiers restent identiques (sauf si l’éditeur Shopify a enregistré des **surcharges différentes** par modèle — chaque handle de modèle a son propre état dans l’admin).

1. **Tester en local / après push** : ouvre la **page d’accueil** (`/`) avec le modèle **Default** si c’est celui que tu utilises en prod.

2. **Changer le modèle assigné dans l’admin** (si tu en as besoin) : personnalisation de la page d’accueil → modèle **Default** (`index.json`) ou **next** (`index.next.json`). Tant que les deux JSON sont synchronisés dans le dépôt, le choix ne change pas le contenu **par défaut** issu des fichiers — seulement quel fichier Shopify met à jour quand tu modifies la page dans l’éditeur.

3. **`?view=next`** : garde-le comme raccourci pour **forcer l’affichage du modèle `next`** (debug, lien partagé, ou futur écart entre les deux JSON). Pas obligatoire si tu ne fais qu’itérer sur la même landing et que `index.json` est déjà à jour.

## Développement

- **`rs-home-landing-next.liquid`** : tout le HTML / CSS / JS de la landing 360°.  
- **`templates/index.json`** + **`templates/index.next.json`** : mêmes `sections` / `order` ; **mêmes `settings`** pour `rs_home_landing_next` (à synchroniser à chaque changement de défauts côté JSON).  
- La **classic** sert de référence / rollback : tu peux recopier son contenu vers `next` si besoin.
- **Next (360°)** : scroller large (yaw sur **360°** via scroll + geste **diagonal** yaw+pitch sur mobile), Pannellum plein écran, **CTAs + liste d’attente en hotspots Pannellum** pour qu’ils suivent le fond. **Au chargement**, la caméra et le scroll mobile sont alignés sur le **hotspot formulaire** (`RS_FORM_HS_YAW` / `RS_FORM_HS_PITCH` dans le JS). **Mobile** : le yaw horizontal est piloté par **deltas** de scroll avec **recentrage** du strip aux bords pour un **360° infini** sans butée.
- **Pannellum** : pour piloter yaw/pitch depuis le scroll ou le touch, utiliser **`setYaw(angle, 0)`** et **`setPitch(angle, 0)`**. Sans 2ᵉ argument, la lib tween sur **1000 ms** → latence et pitch « bloqué » sur mobile.
- **Pitch** : plage **±90°** en config (Pannellum resserre encore selon le FOV vertical). Le geste mobile suit le **même sens que le drag souris** (doigt vers le bas → pitch +).
- **Desktop** : yaw **complet** (-180° / 180°) ; **`hfov`** desktop (`RS_DESKTOP_HFOV`, ~**86°**) ; `applyPannellumViewportMode()` réapplique hfov au passage mobile/desktop. Hotspots : liste d’attente, bureau, **Cal.com** (`cal_booking_url`), **kit postal** (overlay sur l’accueil, texte + lien `kit_url`), **lookbook** — positions dans le JS (`registerRsPannellumHotspots`). La track / scène transparente reste en `pointer-events: none` (≥768px) pour que la souris atteigne le canvas.

### Hotspots Pannellum : doc officielle vs ce qu’on utilise

La [documentation Pannellum sur les hot spots](https://pannellum.org/documentation/examples/hot-spots/) décrit **beaucoup plus** que deux champs. En général un hotspot peut inclure entre autres :

| Champ (exemples) | Rôle |
|-------------------|------|
| **`yaw`**, **`pitch`** | Position sur la sphère (obligatoires pour placer le point). |
| **`type`** | `info`, `scene`, etc. — change le comportement / le style par défaut. |
| **`id`** | Identifiant stable (`removeHotSpot`, debug). |
| **`text`** | Infobulle texte intégrée à Pannellum. |
| **`URL`** / **`sceneId`** | Lien externe ou changement de scène. |
| **`cssClass`** | Classes sur le conteneur (on s’en sert pour le style + pointer). |
| **`createTooltipFunc`** / **`createTooltipArgs`** | Rendu custom du contenu dans le hotspot (notre cas : on **déplace** un nœud DOM Shopify dans le wrapper). |
| **`clickHandlerFunc`** | Clic custom. |
| **`targetYaw`**, **`targetPitch`**, **`targetHfov`** | Cibles pour les transitions de type `scene`. |
| **`scale`** (booléen) | Si **`true`**, Pannellum [met à l’échelle le hotspot](https://pannellum.org/documentation/reference/#hot-spots) quand le **hfov** change (mieux ancré visuellement dans la vue par rapport au zoom). |

**Dans ce thème** (`addMount` + tableau **`RS_HS_DEFS`** dans `rs-home-landing-next.liquid`), on fixe pour **tous** les hotspots : `type: 'info'`, **`scale: true`**, `id`, `yaw`, `pitch`, `cssClass`, `createTooltipFunc` (montage du slot Liquid + **`--rs-hotspot-plane`** sur le wrapper Pannellum). Le champ **`mul`** dans `RS_HS_DEFS` remplace l’ancien 6ᵉ argument `localPlaneMul` (échelle plan par hotspot). Chaque slot porte **`rs-landing-hotspot--prox-ui`** : la même **échelle dynamique** `scale( plane × --rs-hotspot-prox-scale )` (transition ~0,16 s) réagit à la **distance angulaire** caméra ↔ hotspot (intervalle JS ~90 ms) — pas seulement le formulaire.

**Réglage Shopify « profondeur »** : **`hotspot_plane_scale_pct`** (curseur **50–115 %**, défaut **82 %**) — facteur d’échelle sur le contenu du hotspot pour rapprocher visuellement le CTA du fond. **Fork Pannellum** : `assets/pannellum.js` repose sur la **2.5.6** avec un patch **`translateZ(9999px)` → `translateZ(14px)`** sur le positionnement des hot spots (voir **`docs/pannellum-fork.md`**). **`scale: true` + ce curseur** complètent le ressenti d’une sphère unique.

**Suite prévue** : exposer **`yaw` / `pitch`** (et éventuellement `localPlaneMul` ou des curseurs par hotspot) en **réglages de section** pour caler sans retoucher au JS à chaque itération.

## Ancien fichier

La section unique `rs-home-landing.liquid` a été remplacée par **classic** + **next** pour éviter trois entrées identiques dans l’éditeur.

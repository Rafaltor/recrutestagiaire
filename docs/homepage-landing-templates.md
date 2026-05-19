# Accueil : deux landings (sauvegarde + brouillon)

## Fichiers

| Rôle | Section Liquid | Template JSON |
|------|----------------|---------------|
| **Landing classique** (wallpaper / sans Pannellum) | `sections/rs-home-landing-classic.liquid` | **`templates/index.json`** et **`templates/index.next.json`** — **mêmes `settings`** (section `rs_home_landing_classic`) dans le dépôt actuel. |
| **Landing 360°** (Pannellum) | `sections/rs-home-landing-next.liquid` | Réactivable en remplaçant la section par `type: "rs-home-landing-next"` (et les `settings` de cette section) dans les deux JSON. |

Les deux sections partagent les mêmes classes CSS / JS (`.rs-home-landing`, etc.).

**Dépôt** : quand tu modifies les réglages par défaut de l’accueil dans le JSON, **garde `index.json` et `index.next.json` alignés** (même section / mêmes `settings`). La classic vit dans **`rs-home-landing-classic.liquid`** ; la next dans **`rs-home-landing-next.liquid`**.

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
- **Next (360°)** : scroller large (yaw **360°** + diagonal yaw/pitch sur mobile), Pannellum plein écran, CTAs + liste d’attente en hotspots. **Mobile** : yaw par deltas de scroll + recentrage → 360° infini. **Référentiel spawn** : **`RS_SPAWN_YAW` / `RS_SPAWN_PITCH`** — la caméra démarre dessus ; le **formulaire** suit **`RS_FORM_HS_YAW` / `RS_FORM_HS_PITCH`** (souvent alignés sur le spawn pour centrer le bloc au chargement). **Dépôt** : exemple actuel **spawn et form** **yaw −60°**, **pitch 30°** ; ajuste selon ta texture 360°. **iOS / Safari** : opacité du canvas WebGL légèrement réduite (sous 1) pour limiter le canvas au-dessus des hotspots ; le **menu burger** n’est plus masqué sur cette landing.
- **Pannellum** : pour piloter yaw/pitch depuis le scroll ou le touch, utiliser **`setYaw(angle, 0)`** et **`setPitch(angle, 0)`**. Sans 2ᵉ argument, la lib tween sur **1000 ms** → latence et pitch « bloqué » sur mobile.
- **Pitch** : plage **±90°** en config (Pannellum resserre encore selon le FOV vertical). Le geste mobile suit le **même sens que le drag souris** (doigt vers le bas → pitch +).
- **Desktop** : yaw **complet** (-180° / 180°) ; **`hfov`** ~**108°** desktop (dézoom) / **~90°** mobile (`RS_DESKTOP_HFOV` / `RS_MOBILE_HFOV`). Hotspots : **`RS_HS_DEFS`** ; **proximité** (`--rs-hotspot-prox-scale`, ~0,86–1,06). La track / scène transparente reste en `pointer-events: none` (≥768px) pour que la souris atteigne le canvas.

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
| **`scale`** (booléen) | Si **`true`**, Pannellum ajoute un facteur d’échelle lié à la géométrie de la vue — **peut diverger** quand l’angle tend vers certains cas (effet « zoom infini »). Ici on utilise **`scale: false`** et on gère l’échelle en **CSS** (`--rs-hotspot-plane` + `--rs-hotspot-prox-scale`). |

**Dans ce thème** (`addMount` + **`RS_HS_DEFS`**) : `type: 'info'`, **`scale: false`** (voir tableau ci-dessus), `id`, `yaw`, `pitch`, `cssClass`, `createTooltipFunc`, **`mul`**. Slots **`rs-landing-hotspot--prox-ui`** : `scale( plane × --rs-hotspot-prox-scale )` où **proximité** = plus grand quand la visée est **alignée** sur le hotspot, plus petit quand tu t’**éloignes** en yaw/pitch (plage ~0,86–1,06).

**Réglage Shopify « profondeur »** : **`hotspot_plane_scale_pct`** (curseur **50–115 %**, défaut **82 %**) — facteur d’échelle sur le contenu du hotspot pour rapprocher visuellement le CTA du fond. **`assets/pannellum.js`** est la **2.5.6** upstream. Hotspots en **`scale: false`** côté Pannellum ; l’échelle vient du **CSS** (`transform: scale(...)` avec **`--rs-hotspot-plane`** et **`--rs-hotspot-prox-scale`**).

**Suite prévue** : exposer **`yaw` / `pitch`** (et éventuellement `localPlaneMul` ou des curseurs par hotspot) en **réglages de section** pour caler sans retoucher au JS à chaque itération.

## Ancien fichier

La section unique `rs-home-landing.liquid` a été remplacée par **classic** + **next** pour éviter trois entrées identiques dans l’éditeur.

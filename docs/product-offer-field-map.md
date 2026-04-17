# Correspondance — Fiche offre (mockup) ↔ Shopify

Référence : `recrute_stagiaire_product_page_mockup.html` et section `sections/main-product.liquid`.

L’objectif est de **remplir la fiche produit** comme une offre (RH + e-commerce), sans dupliquer la logique métier ailleurs.

---

## 1. Blocs visuels & navigation

| Élément mockup | Source Shopify recommandée | Notes |
|----------------|---------------------------|--------|
| Logo / nav du mockup | Thème : `header`, pas la fiche produit | Le mockup simplifie ; en prod c’est votre en-tête actuel. |
| Fil d’Ariane « Offres › Collection › Poste » | `collection` + `product.title` / ref | Déjà possible : `collection.title`, lien collection, titre tronqué. Réf. : `product.id`, `variant.sku`. |
| Grande image + vignettes | `product.featured_image`, `product.images` | Déjà partiellement sur `main-product` (galerie si `images.size > 1`). |

---

## 2. Tags / pastilles (sous l’image)

| Pastille mockup | Implémentation | Détail |
|------------------|----------------|--------|
| **Candidatures ouvertes** (vert) | Dérivé | `product.selected_or_first_available_variant.available` — pas besoin de métachamp si la règle est toujours « dispo = ouvert ». |
| **Collection Printemps 26** (bleu) | Contexte ou tag | Idéal : **titre de la collection** d’où arrive le client (`collection.title`). Sinon tag produit ex. `collection-printemps-26` affiché avec un libellé lisible (metafield `custom.collection_badge` si besoin). |
| **Édition limitée** (ambre) | Tag ou métachamp | Tag produit (handle `edition-limitee` / `edition-limited`), **ou** métachamp booléen `custom.edition_limitee` — pastille affichée par le thème. |
| **Workwear**, **Unisexe**, etc. | `product.tags` | Pastilles en tête de fiche (max 8 tags, hors pastille « édition limitée »). Toujours la liste complète en bas si besoin. |
| **Collection sans contexte** | `custom.collection_badge` | Texte une ligne affiché comme pastille bleue quand le produit n’est pas vu depuis une collection (pas de `collection` Liquid). |

---

## 3. Titre & ligne « éditeur »

| Élément | Source | Détail |
|---------|--------|--------|
| Titre du poste | `product.title` | Natif. |
| Publié par | Réglage section ou boutique | `section.settings.employer_label` (+ `employer_url`) — déjà câblé. |
| Département (lien / libellé) | `product.type` + collection optionnelle | Déjà câblé : lien si collection du même **handle** que le type. |
| Référence | `product.id` + `variant.sku` | Déjà câblé (`RS-{id}` + SKU). |

---

## 4. Grille méta (2×2 dans le mockup)

| Libellé mockup | Clé métachamp suggérée (`namespace.key`) | Type Admin Shopify | Contenu exemple mockup |
|----------------|------------------------------------------|--------------------|-------------------------|
| Type de contrat | `custom.contract_type` | Texte une ligne | Pièce permanente |
| Disponibilité (texte libre) | `custom.availability_label` | Texte une ligne | *Optionnel* si vous voulez plus fin que « Immédiate / Indisponible » issu du stock. Sinon : uniquement logique variante. |
| Département (détail) | `custom.department_detail` | Texte une ligne | Opérations / Terrain (complète `product.type`) |
| Matière | `custom.matiere` | Texte une ligne (ou texte multi-lignes) | Déjà utilisé sur `main-product`. |

**Champs déjà couverts sans métachamp** : prix (`money`), disponibilité binaire (stock), type produit (département court).

---

## 5. Sections éditoriales (listes à puces)

| Section mockup | Clé métachamp suggérée | Type | Remplissage |
|----------------|------------------------|------|-------------|
| **Missions du poste** | `custom.missions` | Texte multi-lignes ou **contenu riche** | Une ligne = une puce (séparer par retours à la ligne) ou HTML liste en rich text. |
| **Profil recherché** | `custom.profil_recherche` | Idem | Idem. |
| **Avantages du poste** | `custom.avantages` | Idem | Idem. |

Aujourd’hui, une grande partie peut tenir dans **`product.description`** (HTML) ; les métachamps servent quand vous voulez **le même layout que le mockup** (3 blocs titrés) sans parser la description.

---

## 6. Colonne droite — Postuler

| Élément mockup | Source | Notes |
|----------------|--------|--------|
| Rémunération | `variant.price` | Déjà affiché. |
| « Sélectionner votre niveau… » | `product.options` + variantes | Libellé de l’option à renommer dans l’admin produit (ex. « Taille » → libellé marketing). |
| Bouton principal | Formulaire `cart/add` | Déjà en place. |
| Note sous prix (livraison / seuil) | `section.settings.free_shipping_text` | Déjà en place. |
| **Sauvegarder l’offre** | Hors natif | Liste de souhaits (app), favori compte, ou lien « Partager » — à trancher produit. |
| Puces type livraison / retour | `custom.reassurance_1` … `_3` | Texte une ligne chacune, **ou** un seul `custom.reassurance` multi-lignes ; le thème les affiche en liste. |

---

## 7. « Autres offres disponibles »

| Élément | Source | Notes |
|---------|--------|--------|
| Grille 3 cartes | Produits de la **même collection** (exclure l’actuel) | Déjà proche sur `main-product` (liste + lien « Voir toutes »). |

---

## 8. Création des définitions dans Shopify Admin

1. **Paramètres** → **Données personnalisées** → **Produits** → **Ajouter une définition**.
2. Créer les clés du tableau (namespace **`custom`**, noms ci-dessus), types indiqués.
3. Renseigner chaque **fiche produit** dans l’onglet Métachamps.

Cohérence des noms : gardez exactement les **clés** (`missions`, `matiere`, …) pour que le thème Liquid puisse les référencer de façon stable (`product.metafields.custom.missions`).

---

## 9. Implémentation thème (fait — layout mockup)

- **`snippets/rs-product-page-mockup.liquid`** : grille **1fr / 340px** (sticky carte postuler), **hero 4:3** + **vignettes** cliquables, **tag-row**, titre, ligne éditeur, **meta-grid 2×2** (contrat, disponibilité, département, matière), blocs **Missions / Profil / Avantages** (`metafield_tag`), **description** optionnelle, **3 cartes** offres similaires, colonne droite **prix + variantes + CTA + sauvegarder + puces** (métachamps `reassurance_*` / `reassurance` ou textes par défaut section).
- **`sections/main-product.liquid`** : fil d’Ariane type mockup + `render` du snippet + scripts (vignettes, variantes, prix, partage si pas d’URL sauvegarde).
- **CSS** : `assets/theme-overrides.css` (bloc `.rs-pro-mock__*`).

Ce fichier sert de **contrat éditorial / technique** entre contenu et thème.

---

## 10. Liste des clés `custom.*` lues par le thème

| Clé | Rôle |
|-----|------|
| `localisation`, `ville` | Localisation (déjà utilisé en tête + grille). |
| `contract_type` | Type de contrat (grille + optionnel). |
| `department_detail` | Sous-titre département sous le type. |
| `availability_label` | Texte disponibilité (remplace Immédiate / Indisponible si renseigné). |
| `collection_badge` | Pastille « collection » sans contexte `collection` Liquid. |
| `edition_limitee` | Booléen OU tags `edition-limitee` / `edition-limited`. |
| `missions`, `profil_recherche`, `avantages` | Blocs éditoriaux (`metafield_tag`). |
| `reassurance_1`, `reassurance_2`, `reassurance_3` | Puces sous le CTA. |
| `reassurance` | Texte multi-lignes ; une ligne = une puce. |
| `matiere` | Grille « Matière » (déjà en place). |

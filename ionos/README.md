# IONOS — Proxy Airtable (sécuriser le token)

## Pourquoi
Le token Airtable **ne doit pas** être utilisé depuis le navigateur (thème Shopify), sinon il est visible publiquement.

Ce proxy PHP fait les appels Airtable côté serveur IONOS et renvoie au thème uniquement les données utiles.

## Installation (IONOS hébergement web classique)

1. Sur ton FTP / espace web IONOS, crée un dossier, ex: `api/`
2. Upload :
   - `rs-airtable-proxy.php`
   - copie `rs-airtable-config.php.example` → `rs-airtable-config.php` puis renseigne :
     - `RS_AIRTABLE_TOKEN`
     - `RS_AIRTABLE_BASE`
     - `RS_AIRTABLE_TABLE`
     - `RS_ALLOWED_ORIGINS` (mets ton domaine Shopify au minimum)

URL finale typique :
`https://ton-domaine.com/api/rs-airtable-proxy.php`

## Endpoints

- **Compteur** (accueil)  
  `GET rs-airtable-proxy.php?action=count` → `{ "count": 123 }`

- **Liste profils (approuvés)**  
  `GET rs-airtable-proxy.php?action=profiles` → `{ "profiles": [ ... ] }`

- **Déposer candidature**  
  `POST rs-airtable-proxy.php?action=submit` JSON:

```json
{ "name":"...", "email":"...", "role":"...", "cv":"", "portfolio":"" }
```

## Sécurité

- **CORS** : restreins `RS_ALLOWED_ORIGINS` à `https://recrute-stagiaire.myshopify.com` (ou ton domaine custom).
- **Token** : si le token a déjà été exposé dans le thème, **révoque-le** dans Airtable et regénère un nouveau PAT.


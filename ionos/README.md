# IONOS — Proxy API (`rs-airtable-proxy.php`)

Le thème Shopify appelle ce script **HTTPS** (jamais la clé Supabase dans le navigateur).

## Fichiers

1. `rs-airtable-proxy.php` — à uploader sur Ionos (nom inchangé pour compatibilité des URLs Shopify).
2. `rs-airtable-config.php` — **créé sur le serveur** à partir de `rs-airtable-config.php.example` ; contient URL projet, **clé service_role**, nom de table et colonnes.  
   **`RS_SUPABASE_TABLE`** doit être le **nom exact** de la table dans l’éditeur Supabase (souvent `profiles` en anglais, pas `profils`).  
   Si la table n’a **pas** de colonne bool du type `approved` / `published`, mettez **`RS_SUPABASE_USE_APPROVED_FILTER`** à **`false`** (voir l’exemple de config) ; sinon PostgREST renverra « column … approved does not exist ».

## Endpoints

- `GET ?action=count` → `{ "count": N }`
- `GET ?action=profiles` → `{ "profiles": [ { id, name, email, role, professions, cv, portfolio, likes }, ... ] }`
- **`GET ?action=profil_top`** sur `https://api.recrutestagiaire.eu/rs-airtable-proxy.php` (sous-domaine **api**, Ionos) — ou **`GET /api/profils/top/`** si le dossier `api/profils/top/` est déployé sur ce même vhost. **Pas** sur `portail.*` (404). JSON public : profil publié avec le plus de likes ; colonnes en base par défaut : `likes`, `job_title`, `handle`, `full_name` (voir `RS_SUPABASE_COL_*`). Réponse : `{ "id", "name", "instagram", "metier", "likes", "rank", "token" }` ou `404` `no_published_profile`. CORS : `RS_ALLOWED_ORIGINS`.
- `POST ?action=parse_cv` — `multipart/form-data` avec le champ `file` (un CV) → appelle Affinda et retourne JSON : `name`, `email`, `role`, `portfolio`, `note`, `source: affinda`. Nécessite `RS_AFFINDA_API_KEY` dans `rs-airtable-config.php` (dossier `ionos/`, côté serveur uniquement).
- `POST ?action=submit`  
  - **Recommandé** (thème) : `multipart/form-data` avec champs `name`, `email`, `role`, `cv` (texte optionnel) et le même `file` que l’analyse. Le script **envoie le fichier dans Supabase Storage** (`RS_SUPABASE_STORAGE_BUCKET`) et enregistre l’**URL publique** `…/storage/v1/object/public/{bucket}/…` dans la colonne CV. Aucun dépôt de fichier sur IONOS.  
  - **Alternative** : JSON `{ "name", "email", "role", "cv", "portfolio" }` (sans envoi de fichier) si vous ne faites qu’un lien texte.  
  Insertion Supabase (`approved` = false, `likes` = 0 par défaut).  
  Si la table n’a pas de colonne `likes`, définir dans `rs-airtable-config.php` :  
  `define('RS_SUPABASE_SUBMIT_INCLUDE_LIKES', false);` (ou l’ancien `RS_SUPABASE_SUBMIT_INCLUDE_SCORE`)

## CORS

`RS_ALLOWED_ORIGINS` doit lister les origines exactes (ex. `https://recrute-stagiaire.myshopify.com`, votre domaine public).

Pour **`shopify theme dev`** (`http://127.0.0.1:9292`, etc.), soit vous ajoutez ces URLs dans `RS_ALLOWED_ORIGINS`, soit vous activez **`RS_CORS_ALLOW_LOCAL_DEV`** à `true` dans `rs-airtable-config.php` (et vous uploadez la version récente du proxy qui la prend en charge).

## Candidature par fichier CV (page thème « Candidatures »)

1. **Supabase Storage** : dans le [dashboard](https://supabase.com/dashboard) → Storage, créez un **bucket** (ex. `cvs`). Pour que le site puisse proposer le lien *Consulter le CV* côté vitrine, rendez le bucket **public** (policies / *Public bucket* selon l’UI). Renseignez le même nom dans **`RS_SUPABASE_STORAGE_BUCKET`** côté `rs-airtable-config.php`. L’API Storage est appelée avec la **même** clé `service_role` (jamais exposée au navigateur) ; IONOS ne sert qu’au script PHP, pas de stockage disque.
2. **`RS_AFFINDA_API_KEY`** (optionnel pour l’extraction) : [Affinda / EU](https://affinda.com) — le proxy utilise `https://api.eu1.affinda.com` par défaut (surcharge possible avec `RS_AFFINDA_BASE` dans la config).
3. Le thème appelle d’abord `?action=parse_cv` pour remplir les champs, puis `?action=submit` en **multipart** avec le même fichier : upload Storage + ligne dans la table.

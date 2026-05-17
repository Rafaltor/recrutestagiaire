<?php
/**
 * IONOS — renommer en : rs-airtable-config.php (même dossier que rs-airtable-proxy.php)
 * Puis remplacer les valeurs ci-dessous.
 */

define('RS_SUPABASE_URL', 'https://VOTRE_PROJECT_REF.supabase.co');
define('RS_SUPABASE_SERVICE_KEY', 'REMPLACER_PAR_LA_CLE_SERVICE_ROLE');
// Doit être identique au nom PostgreSQL (ex. profiles — pas profils)
define('RS_SUPABASE_TABLE', 'profiles');

define('RS_SUPABASE_USE_APPROVED_FILTER', false);
// define('RS_SUPABASE_COL_APPROVED', 'approved');
define('RS_SUPABASE_COL_LIKES', 'likes');
define('RS_SUPABASE_COL_METIER', 'job_title');
define('RS_SUPABASE_COL_INSTAGRAM', 'handle');
define('RS_SUPABASE_COUNT_MODE', 'all');

define('RS_SUPABASE_COL_NAME', 'full_name');
define('RS_SUPABASE_COL_EMAIL', 'email');
define('RS_SUPABASE_COL_ROLE', 'role');
define('RS_SUPABASE_COL_PROF', 'professions');
define('RS_SUPABASE_COL_CV', 'cv_url');
/** Bucket Supabase des CV (portail = `cvs`). Utilisé pour signer `cv_path` dans `?action=profil_top`. */
// define('RS_SUPABASE_CV_BUCKET', 'cvs');
define('RS_SUPABASE_COL_PORTF', 'portfolio_url');

define('RS_ALLOWED_ORIGINS', [
  'https://recrute-stagiaire.myshopify.com',
  'https://recrutestagiaire.eu',
  'https://www.recrutestagiaire.eu',
  'https://www.recrutestagiaire.fr',
]);

define('RS_CORS_ALLOW_LOCAL_DEV', true);

define('RS_RATE_LIMIT_PER_MIN', 60);

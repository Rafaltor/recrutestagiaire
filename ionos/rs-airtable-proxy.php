<?php
declare(strict_types=1);

/**
 * Proxy API Recrute Stagiaire — données Supabase uniquement (compteur, profils, dépôt).
 * Fichier conservé sous ce nom pour ne pas casser les URLs déjà configurées dans Shopify.
 */
$configPath = __DIR__ . '/rs-airtable-config.php';
if (!file_exists($configPath)) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'Missing config (rs-airtable-config.php)']);
  exit;
}
require $configPath;

function rs_json($data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function rs_origin_allowed(?string $origin): bool {
  if (!$origin) {
    return false;
  }
  if (defined('RS_CORS_ALLOW_LOCAL_DEV') && RS_CORS_ALLOW_LOCAL_DEV === true) {
    if (preg_match('#\Ahttps?://(127\.0\.0\.1|localhost)(:\d+)?\z#', $origin) === 1) {
      return true;
    }
  }
  $allowed = defined('RS_ALLOWED_ORIGINS') ? RS_ALLOWED_ORIGINS : [];
  if (in_array('*', $allowed, true)) {
    return true;
  }
  return in_array($origin, $allowed, true);
}

function rs_set_cors(): void {
  $origin = $_SERVER['HTTP_ORIGIN'] ?? null;
  if ($origin && rs_origin_allowed($origin)) {
    header('Access-Control-Allow-Origin: ' . $origin);
    header('Vary: Origin');
  } elseif (defined('RS_ALLOWED_ORIGINS') && in_array('*', RS_ALLOWED_ORIGINS, true)) {
    header('Access-Control-Allow-Origin: *');
  }
  header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, Accept, Origin, X-Requested-With');
  header('Access-Control-Max-Age: 86400');
}

function rs_rate_limit(): void {
  $limit = defined('RS_RATE_LIMIT_PER_MIN') ? (int)RS_RATE_LIMIT_PER_MIN : 0;
  if ($limit <= 0) {
    return;
  }
  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $bucket = 'rs_rl_' . md5($ip . '_' . (string)floor(time() / 60));
  if (!function_exists('apcu_inc') || !function_exists('apcu_add')) {
    return;
  }
  if (!apcu_add($bucket, 1, 70)) {
    $n = apcu_inc($bucket, 1);
    if ($n > $limit) {
      rs_json(['error' => 'rate_limited'], 429);
    }
  }
}

function rs_field(string $const, string $fallback): string {
  return defined($const) ? constant($const) : $fallback;
}

function rs_supabase_configured(): bool {
  return defined('RS_SUPABASE_URL') && RS_SUPABASE_URL !== ''
    && defined('RS_SUPABASE_SERVICE_KEY') && RS_SUPABASE_SERVICE_KEY !== ''
    && defined('RS_SUPABASE_TABLE') && RS_SUPABASE_TABLE !== '';
}

/**
 * @return array{ok:bool,code:int,data:mixed,headers:array<string,string>}
 */
function rs_supabase_request(string $method, string $pathQuery, array $extraHeaders = [], ?string $body = null): array {
  if (!rs_supabase_configured()) {
    return ['ok' => false, 'code' => 500, 'data' => ['error' => 'supabase_not_configured'], 'headers' => []];
  }
  $url = rtrim(RS_SUPABASE_URL, '/') . $pathQuery;
  $key = RS_SUPABASE_SERVICE_KEY;
  $baseHeaders = [
    'apikey: ' . $key,
    'Authorization: Bearer ' . $key,
  ];
  if ($body !== null) {
    $baseHeaders[] = 'Content-Type: application/json';
  }
  $headers = array_merge($baseHeaders, $extraHeaders);

  if (!function_exists('curl_init')) {
    return ['ok' => false, 'code' => 500, 'data' => ['error' => 'curl_required'], 'headers' => []];
  }

  $ch = curl_init($url);
  $respHeaders = [];
  curl_setopt_array($ch, [
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_HEADERFUNCTION => static function ($curl, $header) use (&$respHeaders) {
      $len = strlen($header);
      $parts = explode(':', $header, 2);
      if (count($parts) === 2) {
        $respHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
      }
      return $len;
    },
  ]);
  if ($body !== null) {
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
  }

  $respBody = curl_exec($ch);
  $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  $data = $respBody !== false && $respBody !== '' ? json_decode($respBody, true) : null;
  if ($code < 200 || $code >= 300) {
    return ['ok' => false, 'code' => $code, 'data' => $data ?: ['raw' => $respBody], 'headers' => $respHeaders];
  }
  return ['ok' => true, 'code' => $code, 'data' => $data, 'headers' => $respHeaders];
}

function rs_supabase_count(): int {
  $table = rawurlencode(RS_SUPABASE_TABLE);
  $colId = 'id';
  $mode = defined('RS_SUPABASE_COUNT_MODE') ? strtolower(trim((string)RS_SUPABASE_COUNT_MODE)) : 'approved';
  $q = "/rest/v1/{$table}?select={$colId}&limit=1";
  $appCol = rs_supabase_approved_column();
  if ($mode === 'all' || $appCol === null) {
    $path = $q;
  } else {
    $path = $q . '&' . rawurlencode($appCol) . '=eq.true';
  }

  $r = rs_supabase_request('GET', $path, ['Prefer: count=exact']);
  if (!$r['ok']) {
    rs_json(['error' => 'supabase', 'details' => $r['data']], 502);
  }
  $cr = $r['headers']['content-range'] ?? '';
  if (preg_match('/\/(\d+)\s*$/', $cr, $m)) {
    return (int)$m[1];
  }
  if (preg_match('/\*\/(\d+)\s*$/', $cr, $m2)) {
    return (int)$m2[1];
  }
  return 0;
}

function rs_supabase_col(string $const, string $fallback): string {
  return rs_field($const, $fallback);
}

/**
 * Colonne bool « visible / approuvé » pour filtrer count + liste. null = pas de colonne, pas de filtre.
 */
function rs_supabase_approved_column(): ?string {
  if (defined('RS_SUPABASE_USE_APPROVED_FILTER') && RS_SUPABASE_USE_APPROVED_FILTER === false) {
    return null;
  }
  $col = trim((string)rs_field('RS_SUPABASE_COL_APPROVED', 'approved'));
  if ($col === '' || strcasecmp($col, 'none') === 0) {
    return null;
  }
  return $col;
}

/**
 * Colonne numérique votes / classement (nom en base : `likes` par défaut).
 * Ne plus utiliser RS_SUPABASE_COL_SCORE : une ancienne config avec `score` provoquait « column … score does not exist »
 * si la table n’a que `likes`. Pour une base qui a encore une colonne `score`, définir RS_SUPABASE_COL_LIKES à `score`.
 */
function rs_supabase_likes_column(): string {
  if (defined('RS_SUPABASE_COL_LIKES')) {
    $c = trim((string) constant('RS_SUPABASE_COL_LIKES'));
    if ($c !== '') {
      return $c;
    }
  }
  return 'likes';
}

function rs_supabase_submit_include_likes(): bool {
  if (defined('RS_SUPABASE_SUBMIT_INCLUDE_LIKES')) {
    return (bool) RS_SUPABASE_SUBMIT_INCLUDE_LIKES;
  }
  if (defined('RS_SUPABASE_SUBMIT_INCLUDE_SCORE')) {
    return (bool) RS_SUPABASE_SUBMIT_INCLUDE_SCORE;
  }
  return true;
}

function rs_supabase_submit_initial_likes(): float {
  if (defined('RS_SUPABASE_SUBMIT_LIKES_VALUE')) {
    return (float) RS_SUPABASE_SUBMIT_LIKES_VALUE;
  }
  if (defined('RS_SUPABASE_SUBMIT_SCORE_VALUE')) {
    return (float) RS_SUPABASE_SUBMIT_SCORE_VALUE;
  }
  return 0.0;
}

function rs_supabase_row_to_profile(array $row): array {
  $n = rs_supabase_col('RS_SUPABASE_COL_NAME', 'full_name');
  $e = rs_supabase_col('RS_SUPABASE_COL_EMAIL', 'email');
  $r = rs_supabase_col('RS_SUPABASE_COL_ROLE', 'role');
  $p = rs_supabase_col('RS_SUPABASE_COL_PROF', 'professions');
  $cv = rs_supabase_col('RS_SUPABASE_COL_CV', 'cv_url');
  $po = rs_supabase_col('RS_SUPABASE_COL_PORTF', 'portfolio_url');
  $likesCol = rs_supabase_likes_column();

  $prof = $row[$p] ?? '';
  if (is_array($prof)) {
    $prof = implode(', ', $prof);
  } else {
    $prof = (string)$prof;
  }
  if ($prof === '') {
    $mCol = trim((string) rs_field('RS_SUPABASE_COL_METIER', 'job_title'));
    if ($mCol !== '' && isset($row[$mCol])) {
      $mv = $row[$mCol];
      $prof = is_array($mv) ? implode(', ', array_map('strval', $mv)) : (string) $mv;
    }
  }

  $likes = 0.0;
  if (isset($row[$likesCol]) && is_numeric($row[$likesCol])) {
    $likes = (float) $row[$likesCol];
  }

  $cvUrl = '';
  $rawCv = $row[$cv] ?? '';
  if (is_string($rawCv) && $rawCv !== '') {
    $cvUrl = $rawCv;
  } elseif (is_array($rawCv) && isset($rawCv[0]) && is_array($rawCv[0]) && !empty($rawCv[0]['url'])) {
    $cvUrl = (string)$rawCv[0]['url'];
  }

  return [
    'id' => (string)($row['id'] ?? ''),
    'name' => (string)($row[$n] ?? 'Candidat'),
    'email' => (string)($row[$e] ?? ''),
    'role' => (string)($row[$r] ?? ''),
    'professions' => $prof,
    'cv' => $cvUrl,
    'portfolio' => (string)($row[$po] ?? ''),
    'likes' => $likes,
  ];
}

function rs_supabase_profiles(): array {
  $table = rawurlencode(RS_SUPABASE_TABLE);
  $appCol = rs_supabase_approved_column();
  $sc = rawurlencode(rs_supabase_likes_column());
  $select = '*';
  $batch = 1000;
  $offset = 0;
  $out = [];

  while (true) {
    $path = "/rest/v1/{$table}?select={$select}";
    if ($appCol !== null) {
      $path .= '&' . rawurlencode($appCol) . '=eq.true';
    }
    $path .= "&order={$sc}.desc.nullslast&limit={$batch}&offset={$offset}";
    $r = rs_supabase_request('GET', $path, []);
    if (!$r['ok']) {
      rs_json(['error' => 'supabase', 'details' => $r['data']], 502);
    }
    $rows = $r['data'];
    if (!is_array($rows) || !count($rows)) {
      break;
    }
    foreach ($rows as $row) {
      if (!is_array($row)) {
        continue;
      }
      $out[] = rs_supabase_row_to_profile($row);
    }
    if (count($rows) < $batch) {
      break;
    }
    $offset += $batch;
  }

  usort($out, static function (array $a, array $b): int {
    return ($b['likes'] <=> $a['likes']);
  });

  return $out;
}

/**
 * Profils publiés triés par likes (tri côté Supabase).
 *
 * @return array<int, array<string,mixed>>
 */
function rs_supabase_top_published_rows_by_likes(int $limit): array {
  $table = rawurlencode(RS_SUPABASE_TABLE);
  $appCol = rs_supabase_approved_column();
  $likesEnc = rawurlencode(rs_supabase_likes_column());
  $lim = max(1, min(50, $limit));
  $path = "/rest/v1/{$table}?select=*";
  if ($appCol !== null) {
    $path .= '&' . rawurlencode($appCol) . '=eq.true';
  }
  $path .= "&order={$likesEnc}.desc.nullslast&limit={$lim}";
  $r = rs_supabase_request('GET', $path, []);
  if (!$r['ok']) {
    rs_json(['error' => 'supabase', 'details' => $r['data']], 502);
  }
  $rows = $r['data'];
  if (!is_array($rows)) {
    return [];
  }
  $out = [];
  foreach ($rows as $row) {
    if (is_array($row)) {
      $out[] = $row;
    }
  }
  return $out;
}

function rs_instagram_api_value(?string $raw): string {
  $s = trim((string) ($raw ?? ''));
  if ($s === '') {
    return '';
  }
  if ($s[0] !== '@') {
    $s = '@' . ltrim($s, '@');
  }
  return $s;
}

/**
 * Première valeur texte non vide parmi plusieurs noms de colonne possibles (schémas portail / Supabase variables).
 *
 * @param array<string,mixed> $row
 * @param array<int,string> $keysPriorité
 */
function rs_row_first_non_empty_scalar(array $row, array $keysPriorité): string {
  foreach ($keysPriorité as $k) {
    if (!array_key_exists($k, $row)) {
      continue;
    }
    $v = $row[$k];
    if ($v === null) {
      continue;
    }
    if (is_array($v)) {
      $s = implode(', ', array_map('strval', $v));
    } elseif (is_scalar($v)) {
      $s = trim((string) $v);
    } else {
      continue;
    }
    if ($s !== '') {
      return $s;
    }
  }
  return '';
}

/**
 * @param array<int,string> $extra
 * @return array<int,string>
 */
function rs_merge_unique_column_candidates(string $primary, array $extra): array {
  $out = [];
  $p = trim($primary);
  if ($p !== '') {
    $out[] = $p;
  }
  foreach ($extra as $k) {
    $k = trim((string) $k);
    if ($k !== '' && !in_array($k, $out, true)) {
      $out[] = $k;
    }
  }
  return $out;
}

/**
 * @param array<string,mixed> $row
 * @return array{id:string,name:string,instagram:string,metier:string,likes:int,rank:int,token:string,cv:string}
 */
function rs_supabase_row_to_top_public(array $row): array {
  $n = rs_supabase_col('RS_SUPABASE_COL_NAME', 'full_name');
  $likesCol = rs_supabase_likes_column();
  $tokPrimary = trim((string) rs_field('RS_SUPABASE_COL_PUBLIC_TOKEN', 'token'));
  $fcv = rs_supabase_col('RS_SUPABASE_COL_CV', 'cv_url');

  $likes = 0;
  if (isset($row[$likesCol]) && is_numeric($row[$likesCol])) {
    $likes = (int) round((float) $row[$likesCol]);
  }

  $igCandidates = rs_merge_unique_column_candidates(
    (string) rs_field('RS_SUPABASE_COL_INSTAGRAM', 'handle'),
    ['handle', 'instagram', 'ig_handle', 'instagram_handle', 'social_handle', 'social_instagram']
  );
  $igRaw = rs_row_first_non_empty_scalar($row, $igCandidates);

  $profCol = rs_supabase_col('RS_SUPABASE_COL_PROF', 'professions');
  $metierCandidates = rs_merge_unique_column_candidates(
    (string) rs_field('RS_SUPABASE_COL_METIER', 'job_title'),
    ['job_title', 'metier', 'professions', 'title', 'role', $profCol]
  );
  $metier = rs_row_first_non_empty_scalar($row, $metierCandidates);

  $tokCandidates = rs_merge_unique_column_candidates(
    $tokPrimary,
    ['token', 'public_token', 'slug', 'profile_slug', 'public_slug']
  );
  $token = rs_row_first_non_empty_scalar($row, $tokCandidates);

  $displayName = '';
  if (array_key_exists($n, $row)) {
    $nv = $row[$n];
    $displayName = is_string($nv) ? $nv : (is_scalar($nv) ? (string) $nv : '');
  }
  if ($displayName === '') {
    $displayName = rs_row_first_non_empty_scalar($row, rs_merge_unique_column_candidates('', ['full_name', 'name', 'display_name']));
  }

  $cvUrl = '';
  if ($fcv !== '' && array_key_exists($fcv, $row)) {
    $cvv = $row[$fcv];
    $cvUrl = is_string($cvv) ? trim($cvv) : (is_scalar($cvv) ? trim((string) $cvv) : '');
  }
  /* Portail : colonne `cv_path` + bucket privé `cvs` — signer côté serveur (clé service). */
  if ($cvUrl === '') {
    $cvPathRaw = rs_row_first_non_empty_scalar($row, ['cv_path', 'cvPath']);
    if ($cvPathRaw !== '') {
      $cvBucket = (defined('RS_SUPABASE_CV_BUCKET') && trim((string) constant('RS_SUPABASE_CV_BUCKET')) !== '')
        ? trim((string) constant('RS_SUPABASE_CV_BUCKET'))
        : 'cvs';
      $cvUrl = rs_supabase_storage_signed_url($cvBucket, $cvPathRaw, 900);
    }
  }

  return [
    'id' => (string) ($row['id'] ?? ''),
    'name' => $displayName,
    'instagram' => rs_instagram_api_value($igRaw),
    'metier' => $metier,
    'likes' => $likes,
    'rank' => 1,
    'token' => $token,
    'cv' => $cvUrl,
    'cv_url' => $cvUrl,
  ];
}

function rs_handle_profil_top(): void {
  $rows = rs_supabase_top_published_rows_by_likes(2);
  if (!count($rows)) {
    rs_json(['error' => 'no_published_profile'], 404);
  }
  $profiles = [];
  foreach ($rows as $i => $row) {
    $pub = rs_supabase_row_to_top_public($row);
    $n = $i + 1;
    $pub['rank'] = $n;
    $pub['rank_label'] = 'N°' . $n . ' — cette semaine';
    $profiles[] = $pub;
  }
  rs_json(['profiles' => $profiles]);
}

function rs_supabase_submit(array $payload): void {
  $name = trim((string)($payload['name'] ?? ''));
  $email = trim((string)($payload['email'] ?? ''));
  $role = trim((string)($payload['role'] ?? ''));
  $cv = trim((string)($payload['cv'] ?? ''));
  $portfolio = trim((string)($payload['portfolio'] ?? ''));

  if ($name === '' || $email === '' || $role === '') {
    rs_json(['error' => 'missing_fields'], 400);
  }

  $table = rawurlencode(RS_SUPABASE_TABLE);
  $n = rs_supabase_col('RS_SUPABASE_COL_NAME', 'full_name');
  $e = rs_supabase_col('RS_SUPABASE_COL_EMAIL', 'email');
  $r = rs_supabase_col('RS_SUPABASE_COL_ROLE', 'role');
  $fcv = rs_supabase_col('RS_SUPABASE_COL_CV', 'cv_url');
  $fpo = rs_supabase_col('RS_SUPABASE_COL_PORTF', 'portfolio_url');
  $fl = rs_supabase_likes_column();

  $row = [
    $n => $name,
    $e => $email,
    $r => $role,
  ];
  $appCol = rs_supabase_approved_column();
  if ($appCol !== null) {
    $row[$appCol] = false;
  }
  if (rs_supabase_submit_include_likes()) {
    $row[$fl] = rs_supabase_submit_initial_likes();
  }
  if ($cv !== '') {
    $row[$fcv] = $cv;
  }
  if ($portfolio !== '') {
    $row[$fpo] = $portfolio;
  }

  $body = json_encode([$row], JSON_UNESCAPED_UNICODE);
  $path = "/rest/v1/{$table}";
  $r = rs_supabase_request('POST', $path, ['Prefer: return=minimal'], $body);
  if (!$r['ok']) {
    rs_json(['error' => 'supabase', 'details' => $r['data']], 502);
  }
  rs_json(['ok' => true]);
}

// --- Supabase Storage (dépôt des fichiers CV) -----------------------------

function rs_supabase_storage_configured(): bool {
  return rs_supabase_configured()
    && defined('RS_SUPABASE_STORAGE_BUCKET')
    && (string) RS_SUPABASE_STORAGE_BUCKET !== '';
}

/**
 * @return string Path à l’intérieur du bucket, sans slash de tête, utilisable pour l’URL public.
 */
function rs_supabase_storage_object_key(string $ext): string {
  $ext = ltrim($ext, '.');
  $prefix = '';
  if (defined('RS_SUPABASE_STORAGE_PREFIX') && (string) constant('RS_SUPABASE_STORAGE_PREFIX') !== '') {
    $prefix = trim((string) constant('RS_SUPABASE_STORAGE_PREFIX'), '/') . '/';
  }
  return $prefix . 'cv-' . bin2hex(random_bytes(8)) . ($ext !== '' ? ('.' . $ext) : '');
}

/**
 * @return string URL pour bucket public
 */
function rs_supabase_storage_public_file_url(string $objectPathInBucket): string {
  if (defined('RS_SUPABASE_OBJECT_PUBLIC_BASE') && (string) constant('RS_SUPABASE_OBJECT_PUBLIC_BASE') !== '') {
    $base = rtrim((string) constant('RS_SUPABASE_OBJECT_PUBLIC_BASE'), '/');
  } else {
    $base = rtrim((string) RS_SUPABASE_URL, '/');
  }
  $b = (string) RS_SUPABASE_STORAGE_BUCKET;
  $p = trim(str_replace('\\', '/', $objectPathInBucket), '/');
  $encPath = '';
  if ($p !== '') {
    $segs = explode('/', $p);
    $encPath = implode('/', array_map('rawurlencode', $segs));
  }
  if ($encPath === '') {
    $encPath = '';
  } else {
    $encPath = '/' . $encPath;
  }
  return $base . '/storage/v1/object/public/' . rawurlencode($b) . $encPath;
}

/**
 * Clé d’objet dans le bucket CV (même logique que le portail Next : sans préfixe `cvs/`).
 */
function rs_normalize_cv_object_key(string $raw): string {
  $p = trim(str_replace('\\', '/', $raw));
  $p = ltrim($p, '/');
  if (stripos($p, 'cvs/') === 0) {
    $p = substr($p, 4);
  }
  return ltrim($p, '/');
}

/**
 * URL signée (GET) pour un objet dans un bucket privé Supabase Storage.
 */
function rs_supabase_storage_signed_url(string $bucket, string $objectPathInBucket, int $expiresIn = 900): string {
  if (!rs_supabase_configured()) {
    return '';
  }
  $b = trim(str_replace('\\', '/', $bucket), '/');
  $p = rs_normalize_cv_object_key($objectPathInBucket);
  if ($b === '' || $p === '') {
    return '';
  }
  $segs = explode('/', $p);
  $encPath = implode('/', array_map('rawurlencode', $segs));
  $pathQuery = '/storage/v1/object/sign/' . rawurlencode($b) . '/' . $encPath;
  $body = json_encode(['expiresIn' => $expiresIn]);
  if ($body === false) {
    return '';
  }
  $r = rs_supabase_request('POST', $pathQuery, [], $body);
  if (!$r['ok'] || !is_array($r['data'])) {
    return '';
  }
  $d = $r['data'];
  $rel = '';
  if (isset($d['signedURL']) && is_string($d['signedURL'])) {
    $rel = $d['signedURL'];
  } elseif (isset($d['signedUrl']) && is_string($d['signedUrl'])) {
    $rel = $d['signedUrl'];
  }
  if ($rel === '') {
    return '';
  }
  if (preg_match('#\Ahttps?://#i', $rel) === 1) {
    return $rel;
  }
  $base = rtrim((string) RS_SUPABASE_URL, '/') . '/storage/v1';
  return $base . ((isset($rel[0]) && $rel[0] === '/') ? '' : '/') . $rel;
}

/**
 * @return array{ok:bool,code:int,data:mixed}
 */
function rs_supabase_storage_put_object(string $objectPath, string $bytes, string $contentType): array {
  if (!rs_supabase_configured() || !rs_supabase_storage_configured()) {
    return ['ok' => false, 'code' => 500, 'data' => ['error' => 'storage_not_configured']];
  }
  if (!function_exists('curl_init')) {
    return ['ok' => false, 'code' => 500, 'data' => ['error' => 'curl_required']];
  }
  $b = rawurlencode((string) RS_SUPABASE_STORAGE_BUCKET);
  $p = trim(str_replace('\\', '/', $objectPath), '/');
  $segs = $p === '' ? [] : explode('/', $p);
  $encPath = implode('/', array_map('rawurlencode', $segs));
  $q = '/storage/v1/object/' . $b . '/' . $encPath;
  $url = rtrim((string) RS_SUPABASE_URL, '/') . $q;
  $key = RS_SUPABASE_SERVICE_KEY;
  $headers = [
    'apikey: ' . $key,
    'Authorization: Bearer ' . $key,
    'Content-Type: ' . $contentType,
    'x-upsert: true',
  ];
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => false,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $bytes,
    CURLOPT_HTTPHEADER => $headers,
  ]);
  $raw = (string) curl_exec($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  $j = $raw !== '' ? json_decode($raw, true) : null;
  if ($code < 200 || $code >= 300) {
    return ['ok' => false, 'code' => $code, 'data' => is_array($j) ? $j : ['raw' => $raw]];
  }
  return ['ok' => true, 'code' => $code, 'data' => $j];
}

/**
 * Valide l’upload $_FILES, envoie vers Supabase Storage, retourne l’URL publique du bucket.
 *
 * @return array{ok:bool,url?:string,error?:string,http?:int,data?:mixed}
 */
function rs_supabase_storage_upload_file(array $f): array {
  if (!rs_supabase_storage_configured()) {
    return ['ok' => false, 'error' => 'storage_not_configured'];
  }
  if ((int) ($f['error'] ?? 0) !== UPLOAD_ERR_OK) {
    return ['ok' => false, 'error' => 'upload'];
  }
  $max = defined('RS_CV_MAX_BYTES') ? (int) constant('RS_CV_MAX_BYTES') : 5242880;
  if ((int) $f['size'] > $max) {
    return ['ok' => false, 'error' => 'file_too_large', 'data' => ['max' => $max]];
  }
  $ext = strtolower((string) pathinfo((string) $f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'html', 'htm', 'png', 'jpg', 'jpeg', 'tiff', 'xls', 'xlsx'], true)) {
    return ['ok' => false, 'error' => 'file_type'];
  }
  $tmp = (string) $f['tmp_name'];
  if (!is_readable($tmp)) {
    return ['ok' => false, 'error' => 'upload_read'];
  }
  if (function_exists('is_uploaded_file') && is_uploaded_file($tmp) === false) {
    return ['ok' => false, 'error' => 'upload'];
  }
  $bytes = (string) file_get_contents($tmp);
  if ($bytes === '' && (int) ($f['size'] ?? 0) > 0) {
    return ['ok' => false, 'error' => 'file_empty'];
  }
  $mime = 'application/octet-stream';
  if (function_exists('finfo_open')) {
    $fi = @finfo_open(FILEINFO_MIME_TYPE);
    if (is_object($fi) || is_resource($fi)) {
      $g = @finfo_file($fi, $tmp);
      if (is_string($g) && $g !== '') {
        $mime = $g;
      }
      finfo_close($fi);
    }
  }
  $key = rs_supabase_storage_object_key($ext);
  $r = rs_supabase_storage_put_object($key, $bytes, $mime);
  if (empty($r['ok'])) {
    return [
      'ok' => false,
      'error' => 'upload_failed',
      'http' => (int) ($r['code'] ?? 0),
      'data' => $r['data'] ?? null,
    ];
  }
  return [
    'ok' => true,
    'url' => rs_supabase_storage_public_file_url($key),
  ];
}

// --- Affinda (extraction d’info depuis le CV) -----------------------------

function rs_affinda_configured(): bool {
  return defined('RS_AFFINDA_API_KEY') && (string) RS_AFFINDA_API_KEY !== '';
}

function rs_affinda_base(): string {
  if (defined('RS_AFFINDA_BASE') && (string) RS_AFFINDA_BASE !== '') {
    return rtrim((string) constant('RS_AFFINDA_BASE'), '/');
  }
  return 'https://api.eu1.affinda.com';
}

function rs_map_affinda_to_form(array $resume): array {
  $d = $resume['data'] ?? $resume;
  if (!is_array($d)) {
    $d = [];
  }
  $name = '';
  if (isset($d['name']) && is_array($d['name'])) {
    $n = $d['name'];
    if (!empty($n['raw']) && is_string($n['raw'])) {
      $name = (string) $n['raw'];
    } else {
      $a = [trim((string) ($n['title'] ?? '')), trim((string) ($n['first'] ?? '')), trim((string) ($n['last'] ?? ''))];
      $a = array_filter($a, static function ($x) { return (string) $x !== ''; });
      $name = count($a) ? implode(' ', $a) : '';
    }
  }
  $email = '';
  if (!empty($d['emails']) && is_array($d['emails']) && $d['emails'] !== []) {
    $fr = $d['emails'][0] ?? null;
    if (is_string($fr) && $fr !== '') {
      $email = $fr;
    } elseif (is_array($fr) && is_string($fr[0] ?? null)) {
      $email = (string) $fr[0];
    } elseif (is_array($fr) && !empty($fr['address'])) {
      $email = (string) $fr['address'];
    }
  }
  $role = '';
  if (isset($d['profession']) && is_string($d['profession']) && (string) $d['profession'] !== '') {
    $role = (string) $d['profession'];
  } elseif (isset($d['headline']) && is_string($d['headline'])) {
    $role = (string) $d['headline'];
  }
  if ($role === '' && !empty($d['workExperience']) && is_array($d['workExperience'])) {
    $wx0 = $d['workExperience'][0] ?? null;
    if (is_array($wx0) && !empty($wx0['jobTitle'])) {
      $role = (string) $wx0['jobTitle'];
    } elseif (is_array($wx0) && is_array($wx0['occupation'] ?? null) && !empty($wx0['occupation']['jobTitle'])) {
      $role = (string) $wx0['occupation']['jobTitle'];
    }
  }
  $portfolio = '';
  if (isset($d['linkedin']) && is_string($d['linkedin']) && (string) $d['linkedin'] !== '') {
    $portfolio = (string) $d['linkedin'];
  } elseif (!empty($d['websites']) && is_array($d['websites']) && $d['websites'] !== []) {
    $w0 = (string) $d['websites'][0];
    if (preg_match('/^https?:\/\//i', $w0) === 1) {
      $portfolio = $w0;
    } else {
      $portfolio = 'https://' . ltrim($w0, '/');
    }
  }
  return [
    'name' => $name,
    'email' => $email,
    'role' => $role,
    'cv' => '',
    'portfolio' => $portfolio,
    'source' => 'affinda',
    'note' => 'Vérifiez et complétez les champs. Les textes proviennent d’une analyse automatique du document.',
  ];
}

function rs_affinda_http_get_resume(string $identifier): array {
  $u = rs_affinda_base() . '/v2/resumes/' . rawurlencode($identifier);
  $ch = curl_init($u);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . (string) constant('RS_AFFINDA_API_KEY'),
    ],
  ]);
  $raw = (string) curl_exec($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  if ($code < 200 || $code >= 300) {
    return ['ok' => false, 'http' => $code, 'raw' => $raw];
  }
  $j = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
  if (!is_array($j)) {
    return ['ok' => false, 'http' => $code];
  }
  return ['ok' => true, 'body' => $j];
}

function rs_affinda_post_parse_file(string $tmp, string $origName, string $mime): array {
  $u = rs_affinda_base() . '/v2/resumes';
  if (!function_exists('curl_file_create')) {
    if (!@class_exists('CURLFile', false) && (PHP_VERSION_ID < 80000)) {
      return ['ok' => false, 'err' => 'curlfile'];
    }
  }
  $cfile = function_exists('curl_file_create')
    ? curl_file_create($tmp, $mime, $origName)
    : new \CURLFile($tmp, $mime, $origName);
  $ch = curl_init($u);
  $post = [
    'file' => $cfile,
    'wait' => 'true',
  ];
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 120,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $post,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer ' . (string) constant('RS_AFFINDA_API_KEY'),
    ],
  ]);
  $raw = (string) curl_exec($ch);
  $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
  $j = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
  if ($code < 200 || $code >= 300) {
    return ['ok' => false, 'err' => 'http_' . (string) $code, 'raw' => $raw, 'j' => $j];
  }
  if (is_array($j) && (!empty($j['data']['emails']) || !empty($j['data']['name']) || !empty($j['data']['name']['raw']))) {
    return ['ok' => true, 'resume' => $j];
  }
  if (is_array($j) && !empty($j['data'])) {
    $em = is_array($j['data']['emails'] ?? null) ? (count($j['data']['emails']) > 0) : false;
    if ($em || (isset($j['data']['name']) && (is_string($j['data']['name']) || is_array($j['data']['name'])))) {
      return ['ok' => true, 'resume' => $j];
    }
  }
  $id = null;
  if (is_array($j) && !empty($j['meta']['identifier'])) {
    $id = (string) $j['meta']['identifier'];
  } elseif (is_array($j) && !empty($j['meta']) && is_array($j['meta']) && !empty($j['identifier'])) {
    $id = (string) $j['identifier'];
  }
  if (is_string($id) && $id !== '') {
    for ($k = 0; $k < 25; $k++) {
      $pol = rs_affinda_http_get_resume($id);
      if (empty($pol['ok']) || !is_array($pol['body'] ?? null)) {
        usleep(500000);
        continue;
      }
      $b = $pol['body'];
      if (!empty($b['data']) && is_array($b['data'])) {
        if (!empty($b['data']['emails']) || (isset($b['data']['name']))) {
          return ['ok' => true, 'resume' => $b];
        }
        if (isset($b['meta']['ready']) && (bool) $b['meta']['ready']) {
          return ['ok' => true, 'resume' => $b];
        }
      }
      if (!empty($b['meta']['ready']) && (bool) $b['meta']['ready']) {
        return ['ok' => true, 'resume' => $b];
      }
      usleep(500000);
    }
  }
  if (is_array($j) && (isset($j['data']))) {
    return ['ok' => true, 'resume' => $j];
  }
  return ['ok' => false, 'err' => 'affinda_empty', 'raw' => $raw, 'j' => $j];
}

function rs_handle_parse_cv(): void {
  if (!rs_affinda_configured()) {
    rs_json(['error' => 'affinda_not_configured', 'info' => 'RS_AFFINDA_API_KEY dans rs-airtable-config.php'], 501);
  }
  if (empty($_FILES['file']) || (int) ($_FILES['file']['error'] ?? 0) !== UPLOAD_ERR_OK) {
    rs_json(['error' => 'file_required'], 400);
  }
  $f = $_FILES['file'];
  $max = defined('RS_CV_MAX_BYTES') ? (int) constant('RS_CV_MAX_BYTES') : 5242880;
  if ((int) ($f['size'] ?? 0) > $max) {
    rs_json(['error' => 'file_too_large', 'max' => $max], 400);
  }
  $ext = strtolower((string) pathinfo((string) $f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, ['pdf', 'doc', 'docx', 'txt', 'rtf', 'odt', 'html', 'htm', 'png', 'jpg', 'jpeg', 'tiff', 'xls', 'xlsx'], true)) {
    rs_json(['error' => 'file_type'], 400);
  }
  $tmp = (string) $f['tmp_name'];
  if (!is_readable($tmp) || (function_exists('is_uploaded_file') && !@is_uploaded_file($tmp))) {
    if (!is_readable($tmp)) {
      rs_json(['error' => 'upload'], 400);
    }
  }
  $mime = 'application/octet-stream';
  if (function_exists('finfo_open')) {
    $fi = @finfo_open(FILEINFO_MIME_TYPE);
    if (is_object($fi) || is_resource($fi)) {
      $g = @finfo_file($fi, $tmp);
      if (is_string($g) && $g !== '') {
        $mime = $g;
      }
      finfo_close($fi);
    }
  }
  $r = rs_affinda_post_parse_file($tmp, (string) $f['name'], $mime);
  if (empty($r['ok']) || !is_array($r['resume'] ?? null)) {
    $detail = (string) ($r['err'] ?? 'affinda');
    if (isset($r['j']) && is_array($r['j']) && isset($r['j']['error']['message'])) {
      $detail = (string) $r['j']['error']['message'];
    }
    rs_json(['error' => 'affinda', 'details' => $detail], 502);
  }
  $out = rs_map_affinda_to_form($r['resume']);
  rs_json($out, 200);
}

/**
 * Paramètre action (?action=…) — certains hébergeurs ne remplissent pas $_GET ; on relit QUERY_STRING.
 */
function rs_request_action(): string {
  $a = isset($_GET['action']) ? trim((string) $_GET['action']) : '';
  if ($a !== '') {
    return $a;
  }
  $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
  if ($qs !== '' && preg_match('/(?:^|&)action=([^&]*)/', $qs, $m)) {
    return trim(rawurldecode($m[1]));
  }
  return '';
}

// --- CORS preflight -------------------------------------------
rs_set_cors();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

$action = rs_request_action();
$reqUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
$uriPath = (string) (parse_url($reqUri, PHP_URL_PATH) ?: '');
$isProfilTop = ($action === 'profil_top' || $action === 'profil-top')
  || preg_match('#/api/profils/top/?$#', $uriPath) === 1;

rs_rate_limit();

if ($action === 'parse_cv' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  rs_handle_parse_cv();
}

if (!rs_supabase_configured()) {
  rs_json(['error' => 'supabase_not_configured'], 500);
}

if ($action === 'count') {
  rs_json(['count' => rs_supabase_count()]);
}

if ($action === 'profiles') {
  rs_json(['profiles' => rs_supabase_profiles()]);
}

if ($isProfilTop && $_SERVER['REQUEST_METHOD'] === 'GET') {
  rs_handle_profil_top();
}

if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $ct = (string) ($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
  if (preg_match('/multipart\/form-data/i', $ct) === 1) {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $role = trim((string) ($_POST['role'] ?? ''));
    $cv = trim((string) ($_POST['cv'] ?? ''));
    $portfolio = trim((string) ($_POST['portfolio'] ?? ''));
    if (!empty($_FILES['file']) && (int) ($_FILES['file']['error'] ?? 0) === UPLOAD_ERR_OK) {
      if (!rs_supabase_storage_configured()) {
        rs_json([
          'error' => 'storage_not_configured',
          'info' => 'Définir RS_SUPABASE_STORAGE_BUCKET (Supabase Storage) — voir ionos/README.md',
        ], 501);
      }
      $up = rs_supabase_storage_upload_file($_FILES['file']);
      if (empty($up['ok']) || (string) ($up['url'] ?? '') === '') {
        rs_json([
          'error' => 'storage_upload',
          'details' => (string) ($up['error'] ?? 'upload_failed'),
          'http' => $up['http'] ?? null,
          'data' => $up['data'] ?? null,
        ], 502);
      }
      $cv = (string) $up['url'];
    }
    rs_supabase_submit([
      'name' => $name,
      'email' => $email,
      'role' => $role,
      'cv' => $cv,
      'portfolio' => $portfolio,
    ]);
  } else {
    $raw = (string) file_get_contents('php://input');
    $payload = $raw !== '' ? json_decode($raw, true) : null;
    if (!is_array($payload)) {
      rs_json(['error' => 'invalid_json'], 400);
    }
    rs_supabase_submit($payload);
  }
}

rs_json(['error' => 'unknown_action'], 404);

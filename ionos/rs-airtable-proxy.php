<?php
declare(strict_types=1);

// --- Config ----------------------------------------------------
// In production, copy rs-airtable-config.php.example -> rs-airtable-config.php
$configPath = __DIR__ . '/rs-airtable-config.php';
if (!file_exists($configPath)) {
  http_response_code(500);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['error' => 'Missing config (rs-airtable-config.php)']);
  exit;
}
require $configPath;

// --- Helpers ---------------------------------------------------
function rs_json($data, int $code = 200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function rs_origin_allowed(?string $origin): bool {
  if (!$origin) return false;
  $allowed = defined('RS_ALLOWED_ORIGINS') ? RS_ALLOWED_ORIGINS : [];
  if (in_array('*', $allowed, true)) return true;
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
  header('Access-Control-Allow-Headers: Content-Type');
  header('Access-Control-Max-Age: 86400');
}

function rs_rate_limit(): void {
  $limit = defined('RS_RATE_LIMIT_PER_MIN') ? (int)RS_RATE_LIMIT_PER_MIN : 0;
  if ($limit <= 0) return;

  $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
  $bucket = 'rs_rl_' . md5($ip . '_' . (string)floor(time() / 60));

  // Use APCu if available; otherwise no rate limit.
  if (!function_exists('apcu_inc') || !function_exists('apcu_add')) return;
  if (!apcu_add($bucket, 1, 70)) {
    $n = apcu_inc($bucket, 1);
    if ($n > $limit) rs_json(['error' => 'rate_limited'], 429);
  }
}

function rs_airtable_request(string $method, string $path, ?array $body = null): array {
  $url = 'https://api.airtable.com/v0/' . rawurlencode(RS_AIRTABLE_BASE) . '/' . rawurlencode(RS_AIRTABLE_TABLE) . $path;
  $headers = [
    'Authorization: Bearer ' . RS_AIRTABLE_TOKEN,
    'Content-Type: application/json',
  ];

  $opts = [
    'http' => [
      'method' => $method,
      'header' => implode("\r\n", $headers),
      'ignore_errors' => true,
      'timeout' => 12,
    ],
  ];
  if ($body !== null) {
    $opts['http']['content'] = json_encode($body, JSON_UNESCAPED_UNICODE);
  }

  $ctx = stream_context_create($opts);
  $resp = @file_get_contents($url, false, $ctx);
  $statusLine = $http_response_header[0] ?? 'HTTP/1.1 500';
  if (!preg_match('/\s(\d{3})\s/', $statusLine, $m)) $code = 500;
  else $code = (int)$m[1];

  $data = $resp ? json_decode($resp, true) : null;
  if ($code < 200 || $code >= 300) {
    return ['ok' => false, 'code' => $code, 'data' => $data ?: ['raw' => $resp]];
  }
  return ['ok' => true, 'code' => $code, 'data' => $data];
}

function rs_field(string $const, string $fallback): string {
  return defined($const) ? constant($const) : $fallback;
}

// --- CORS preflight -------------------------------------------
rs_set_cors();
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

rs_rate_limit();

// --- Routing ---------------------------------------------------
$action = $_GET['action'] ?? '';

if ($action === 'count') {
  $filter = urlencode('{' . rs_field('RS_FIELD_OK', 'Approuvé') . '}=TRUE()');
  $path = '?filterByFormula=' . $filter . '&pageSize=100';
  $sum = 0;

  while (true) {
    $r = rs_airtable_request('GET', $path, null);
    if (!$r['ok']) rs_json(['error' => 'airtable', 'details' => $r['data']], 502);
    $records = $r['data']['records'] ?? [];
    $sum += is_array($records) ? count($records) : 0;
    $offset = $r['data']['offset'] ?? null;
    if (!$offset) break;
    $path = '?filterByFormula=' . $filter . '&pageSize=100&offset=' . rawurlencode((string)$offset);
  }

  rs_json(['count' => $sum]);
}

if ($action === 'profiles') {
  $filter = urlencode('{' . rs_field('RS_FIELD_OK', 'Approuvé') . '}=TRUE()');
  $sort = '&sort[0][field]=' . rawurlencode(rs_field('RS_FIELD_NAME', 'Nom Prénom')) . '&sort[0][direction]=asc';
  $path = '?filterByFormula=' . $filter . '&pageSize=100' . $sort;

  $all = [];
  while (true) {
    $r = rs_airtable_request('GET', $path, null);
    if (!$r['ok']) rs_json(['error' => 'airtable', 'details' => $r['data']], 502);
    $records = $r['data']['records'] ?? [];
    if (is_array($records)) $all = array_merge($all, $records);
    $offset = $r['data']['offset'] ?? null;
    if (!$offset) break;
    $path = '?filterByFormula=' . $filter . '&pageSize=100&offset=' . rawurlencode((string)$offset) . $sort;
  }

  $FN = rs_field('RS_FIELD_NAME', 'Nom Prénom');
  $FE = rs_field('RS_FIELD_EMAIL', 'Email');
  $FR = rs_field('RS_FIELD_ROLE', 'Poste recherché');
  $FP = rs_field('RS_FIELD_PROF', 'Professions');
  $FCV = rs_field('RS_FIELD_CV', 'CV');
  $FPO = rs_field('RS_FIELD_PORTF', 'Portfolio');

  $out = [];
  foreach ($all as $rec) {
    $id = $rec['id'] ?? '';
    $f = $rec['fields'] ?? [];
    $prof = $f[$FP] ?? '';
    if (is_array($prof)) $prof = implode(', ', $prof);
    $rawCv = $f[$FCV] ?? '';
    $cvUrl = '';
    if (is_string($rawCv) && $rawCv !== '') {
      $cvUrl = $rawCv;
    } elseif (is_array($rawCv) && isset($rawCv[0]) && is_array($rawCv[0]) && !empty($rawCv[0]['url'])) {
      $cvUrl = (string)$rawCv[0]['url'];
    }
    $out[] = [
      'id' => $id,
      'name' => $f[$FN] ?? ($f['Nom & Prénom'] ?? ($f['Nom'] ?? 'Candidat')),
      'email' => $f[$FE] ?? '',
      'role' => $f[$FR] ?? '',
      'professions' => $prof ?: '',
      'cv' => $cvUrl,
      'portfolio' => $f[$FPO] ?? '',
    ];
  }

  rs_json(['profiles' => $out]);
}

if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  $raw = file_get_contents('php://input');
  $payload = $raw ? json_decode($raw, true) : null;
  if (!is_array($payload)) rs_json(['error' => 'invalid_json'], 400);

  $name = trim((string)($payload['name'] ?? ''));
  $email = trim((string)($payload['email'] ?? ''));
  $role = trim((string)($payload['role'] ?? ''));
  $cv = trim((string)($payload['cv'] ?? ''));
  $portfolio = trim((string)($payload['portfolio'] ?? ''));

  if ($name === '' || $email === '' || $role === '') {
    rs_json(['error' => 'missing_fields'], 400);
  }

  $FN = rs_field('RS_FIELD_NAME', 'Nom Prénom');
  $FE = rs_field('RS_FIELD_EMAIL', 'Email');
  $FR = rs_field('RS_FIELD_ROLE', 'Poste recherché');
  $FCV = rs_field('RS_FIELD_CV', 'CV');
  $FPO = rs_field('RS_FIELD_PORTF', 'Portfolio');
  $FOK = rs_field('RS_FIELD_OK', 'Approuvé');

  $fields = [
    $FN => $name,
    $FE => $email,
    $FR => $role,
    $FOK => false,
  ];
  if ($cv !== '') $fields[$FCV] = $cv;
  if ($portfolio !== '') $fields[$FPO] = $portfolio;

  $r = rs_airtable_request('POST', '', ['records' => [['fields' => $fields]]]);
  if (!$r['ok']) rs_json(['error' => 'airtable', 'details' => $r['data']], 502);
  rs_json(['ok' => true]);
}

rs_json(['error' => 'unknown_action'], 404);


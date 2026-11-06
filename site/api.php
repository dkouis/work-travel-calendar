<?php
/* ============================================================
   Work & Travel Calendar — server API (PHP 7.4+)
   All data lives in the data/ folder next to this file.
   Data files are .php files that stop immediately when requested
   from a browser, so they can't be downloaded even on servers that
   ignore data/.htaccess.
   ============================================================ */
declare(strict_types=1);

const DATA_DIR      = __DIR__ . '/data';
const BACKUP_DIR    = DATA_DIR . '/backups';
const SESSION_DIR   = DATA_DIR . '/sessions';
const FILES_DIR     = DATA_DIR . '/files';    // attachments: one guarded .php file each (cannot be opened from a browser)
const FILE_MAX      = 50 * 1048576;           // largest attachment (50 MB)
const FILE_CHUNK    = 1048576;                // uploads arrive in 1 MB pieces, so PHP's upload limits don't matter
const FILE_GRACE    = 7 * 86400;              // unused attachments are deleted 7 days after they stop being used
const KEEP_BACKUPS  = 200;            // newest backups kept on the server
const MAX_BODY      = 3000000;        // 3 MB request limit
const REMEMBER_DAYS = 30;
const IDLE_HOURS    = 12;             // sign-out after this long without "keep me signed in"
// Password reset by file: put this file in data/ (e.g. by FTP), one line per account:
//   username  temporary-password  [editor|viewer]      (* instead of a username = every account)
// The next request applies it, deletes it, and writes a log without passwords.
const RESET_FILE    = DATA_DIR . '/reset-passwords.txt';
const RESET_LOG     = DATA_DIR . '/reset-passwords-log.txt';
const GUARD         = "<?php http_response_code(404); exit; __halt_compiler();\n";

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function out($val, int $code = 200): void { http_response_code($code); echo json_encode($val, JSON_UNESCAPED_UNICODE); exit; }
function fail(string $msg, int $code = 400, array $extra = []): void { out(['error' => $msg] + $extra, $code); }

/* ---------- storage ---------- */
function ensure_dirs(): void {
  foreach ([DATA_DIR, BACKUP_DIR, SESSION_DIR, FILES_DIR] as $d) {
    if (!is_dir($d) && !@mkdir($d, 0700, true)) fail('The server cannot create the folder "' . basename($d) . '". Make the data folder writable (permissions 755 or 775).', 500);
    if (!is_writable($d)) fail('The server cannot write to the folder "' . basename($d) . '". Make it writable (permissions 755 or 775).', 500);
  }
  if (!is_file(FILES_DIR . '/index.html')) @file_put_contents(FILES_DIR . '/index.html', '');
}

/* ---------- attachments ----------
   data/files/<32 hex>.php = GUARD line + the file's bytes (already encrypted by the browser for Personal items).
   data/files.php = index {id: {name, size, by, added, unused?}}. */
function file_id_ok($id): bool { return is_string($id) && preg_match('/^[a-f0-9]{32}$/', $id) === 1; }
function file_path(string $id, bool $part = false): string { return FILES_DIR . '/' . $id . ($part ? '.part' : '') . '.php'; }
function files_index(): array { $i = store_read('files'); return is_array($i) ? $i : []; }
function files_usage(array $idx): array {
  $n = 0; $b = 0;
  foreach ($idx as $f) if (empty($f['part'])) { $n++; $b += (int)($f['size'] ?? 0); }
  return ['count' => $n, 'bytes' => $b];
}
function file_new_id(): string { do { $id = bin2hex(random_bytes(16)); } while (is_file(file_path($id)) || is_file(file_path($id, true))); return $id; }
function file_remove(string $id, array &$idx): void { @unlink(file_path($id)); @unlink(file_path($id, true)); unset($idx[$id]); }
function store_path(string $name): string { return DATA_DIR . '/' . $name . '.php'; }
function read_file_json(string $f) {
  if (!is_file($f)) return null;
  $s = (string)file_get_contents($f);
  $p = strpos($s, "\n");
  $v = json_decode($p === false ? '' : substr($s, $p + 1), true);
  return is_array($v) ? $v : null;
}
function write_file_json(string $f, $val): void {
  // JSON_HEX_TAG turns < and > into < / >, so stored text can never form PHP tags.
  $json = json_encode($val, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_PRETTY_PRINT);
  if ($json === false) fail('Could not encode data.', 500);
  $tmp = $f . '.tmp' . bin2hex(random_bytes(4));
  if (file_put_contents($tmp, GUARD . $json, LOCK_EX) === false) fail('Could not write to the data folder.', 500);
  @chmod($tmp, 0600);
  if (!@rename($tmp, $f)) { @unlink($tmp); fail('Could not save the file.', 500); }
}
function store_read(string $name) { return read_file_json(store_path($name)); }
function store_write(string $name, $val): void { write_file_json(store_path($name), $val); }

/* One lock for everything that reads-then-writes. */
function with_lock(callable $fn) {
  $h = fopen(DATA_DIR . '/.lock', 'c');
  if (!$h) fail('Could not lock the data folder.', 500);
  flock($h, LOCK_EX);
  try { return $fn(); } finally { flock($h, LOCK_UN); fclose($h); }
}

function users(): array { $u = store_read('users'); return is_array($u) && isset($u['users']) ? $u['users'] : []; }
function find_user(array $list, string $name): ?array {
  foreach ($list as $u) if (strcasecmp($u['user'], $name) === 0) return $u;
  return null;
}
/* Apply data/reset-passwords.txt if present (see RESET_FILE above). Accounts reset this way
   sign in with the temporary password and must then choose a new one (flag must_change). */
function process_reset_file(): void {
  if (!is_file(RESET_FILE)) return;
  with_lock(function () {
    if (!is_file(RESET_FILE)) return;
    $lines = @file(RESET_FILE, FILE_IGNORE_NEW_LINES) ?: [];
    $list = users(); $log = []; $changed = false;
    foreach ($lines as $ln) {
      $ln = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string)$ln));   // ignore a UTF-8 BOM from Notepad
      if ($ln === '' || $ln[0] === '#') continue;
      $p = preg_split('/\s+/', $ln, 3);
      $name = $p[0]; $pass = $p[1] ?? ''; $role = strtolower(trim($p[2] ?? ''));
      if ($role !== '' && !in_array($role, ['editor', 'viewer'], true)) { $log[] = "SKIPPED  $name: the role must be editor or viewer"; continue; }
      if ($pass === '') { $log[] = "SKIPPED  $name: no temporary password on the line"; continue; }
      if (strlen($pass) < 6) { $log[] = "SKIPPED  $name: the temporary password needs at least 6 characters"; continue; }
      $hash = password_hash($pass, PASSWORD_DEFAULT);
      if ($name === '*') {
        foreach ($list as &$x) { $x['hash'] = $hash; $x['must_change'] = true; if ($role) $x['role'] = $role; }
        unset($x);
        $log[] = 'RESET    all ' . count($list) . ' accounts: they must choose a new password at the next sign-in';
        $changed = true; continue;
      }
      if (!preg_match('/^[\p{L}\p{N}._@-]{1,40}$/u', $name)) { $log[] = "SKIPPED  $name: not a valid username"; continue; }
      $found = false;
      foreach ($list as &$x) if (strcasecmp($x['user'], $name) === 0) {
        $x['hash'] = $hash; $x['must_change'] = true; if ($role) $x['role'] = $role;
        $found = true; $log[] = "RESET    {$x['user']} ({$x['role']}): must choose a new password at the next sign-in";
      }
      unset($x);
      if (!$found) { $list[] = ['user' => $name, 'role' => $role ?: 'viewer', 'hash' => $hash, 'must_change' => true];
        $log[] = "CREATED  $name (" . ($role ?: 'viewer') . '): must choose a new password at the next sign-in'; }
      $changed = true;
    }
    if ($changed) store_write('users', ['users' => array_values($list)]);
    // never leave temporary passwords on the server
    if (!@unlink(RESET_FILE)) { @file_put_contents(RESET_FILE, ''); $log[] = 'NOTE     could not delete reset-passwords.txt (it was emptied instead); please delete it'; }
    if (!$log) $log[] = 'NOTHING  the file had no account lines';
    @file_put_contents(RESET_LOG, '[' . gmdate('Y-m-d H:i') . " UTC]\n  " . implode("\n  ", $log) . "\n\n", FILE_APPEND | LOCK_EX);
    @chmod(RESET_LOG, 0600);
  });
}
function set_user_password(string $name, string $hash, bool $must): ?array {
  return with_lock(function () use ($name, $hash, $must) {
    $list = users(); $res = null;
    foreach ($list as &$x) if ($x['user'] === $name) { $x['hash'] = $hash; if ($must) $x['must_change'] = true; else unset($x['must_change']); $res = $x; }
    unset($x);
    if ($res) store_write('users', ['users' => $list]);
    return $res;
  });
}
function pw_stamp(array $u): string { return substr(hash('sha256', $u['hash']), 0, 16); }

function calendar(): array {
  $c = store_read('calendar');
  if (!$c) $c = ['rev' => 0, 'by' => '', 'saved' => '', 'data' => ['version' => 1, 'owner' => '', 'days' => new stdClass(), 'monthNotes' => new stdClass()]];
  return $c;
}
function backup_current(array $cal): void {
  if (empty($cal['rev'])) return;
  $name = 'cal-' . gmdate('Ymd-His') . '-r' . (int)$cal['rev'] . '.php';
  write_file_json(BACKUP_DIR . '/' . $name, $cal);
  $all = glob(BACKUP_DIR . '/cal-*.php') ?: [];
  rsort($all);
  foreach (array_slice($all, KEEP_BACKUPS) as $old) @unlink($old);
}

/* ---------- session ---------- */
function start_session(?bool $remember = null): void {
  if (session_status() === PHP_SESSION_ACTIVE) return;
  session_save_path(SESSION_DIR);
  ini_set('session.gc_maxlifetime', (string)(REMEMBER_DAYS * 86400));
  ini_set('session.use_strict_mode', '1');
  ini_set('session.gc_probability', '1');
  ini_set('session.gc_divisor', '100');
  session_name('WTCSESS');
  $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
  $path = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\') . '/';
  session_set_cookie_params(['lifetime' => $remember ? REMEMBER_DAYS * 86400 : 0, 'path' => $path, 'secure' => $https, 'httponly' => true, 'samesite' => 'Lax']);
  session_start();
}
function current_user(): ?array {
  start_session();
  if (empty($_SESSION['user'])) return null;
  $u = find_user(users(), $_SESSION['user']);
  $limit = !empty($_SESSION['remember']) ? REMEMBER_DAYS * 86400 : IDLE_HOURS * 3600;
  if (!$u || ($_SESSION['pw'] ?? '') !== pw_stamp($u) || time() - (int)($_SESSION['seen'] ?? 0) > $limit) {
    $_SESSION = []; session_destroy(); return null;      // removed, password changed elsewhere, or expired
  }
  $_SESSION['seen'] = time();
  return $u;
}
/* setcookie() accepts only expires/path/domain/secure/httponly/samesite. Passing
   session_get_cookie_params() directly also passes "lifetime", which PHP 8 rejects
   with a fatal error (HTTP 500 on sign-in with "keep me signed in", and on sign-out). */
function set_session_cookie(string $value, int $expires): void {
  $p = session_get_cookie_params();
  setcookie(session_name() ?: 'WTCSESS', $value, [
    'expires'  => $expires,
    'path'     => $p['path'] ?? '/',
    'domain'   => $p['domain'] ?? '',
    'secure'   => !empty($p['secure']),
    'httponly' => !empty($p['httponly']),
    'samesite' => $p['samesite'] ?? 'Lax',
  ]);
}
function sign_in(array $u, bool $remember): void {
  session_write_close();
  start_session($remember);
  session_regenerate_id(true);
  $_SESSION['user'] = $u['user'];
  $_SESSION['pw'] = pw_stamp($u);
  $_SESSION['remember'] = $remember;
  $_SESSION['seen'] = time();
  $_SESSION['token'] = bin2hex(random_bytes(16));
  if ($remember) set_session_cookie(session_id(), time() + REMEMBER_DAYS * 86400);
}
function me_info(?array $u): array {
  return $u ? ['user' => $u['user'], 'role' => $u['role'], 'token' => $_SESSION['token'] ?? ''] : ['user' => null];
}
function require_user(bool $editor = false): array {
  $u = current_user();
  if (!$u) fail('Please sign in again.', 401);
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && !hash_equals($_SESSION['token'] ?? '', $_SERVER['HTTP_X_WTC_TOKEN'] ?? '')) fail('Your session is out of date. Reload the page.', 403);
  if ($editor && $u['role'] !== 'editor') fail('Your account can only view the calendar.', 403);
  return $u;
}

/* ---------- login throttling (per IP) ---------- */
function throttle_check(): void {
  $t = store_read('throttle') ?: [];
  $ip = $_SERVER['REMOTE_ADDR'] ?? '?';
  $recent = array_filter($t[$ip] ?? [], fn($x) => $x > time() - 900);
  if (count($recent) >= 8) fail('Too many failed sign-in attempts. Please wait 15 minutes and try again.', 429);
}
function throttle_fail(): void {
  with_lock(function () {
    $t = store_read('throttle') ?: [];
    $ip = $_SERVER['REMOTE_ADDR'] ?? '?';
    foreach ($t as $k => $v) { $t[$k] = array_values(array_filter($v, fn($x) => $x > time() - 900)); if (!$t[$k]) unset($t[$k]); }
    $t[$ip][] = time();
    store_write('throttle', $t);
  });
  usleep(800000);
}

/* ---------- validation ---------- */
function clean_name($s): string {
  $s = trim((string)$s);
  if ($s === '' || mb_strlen($s) > 40 || !preg_match('/^[\p{L}\p{N}._@-]+$/u', $s)) fail('Usernames can use letters, numbers and . _ @ - (no spaces), up to 40 characters.');
  return $s;
}
function check_pass($p): string {
  $p = (string)$p;
  if (strlen($p) < 6) fail('Passwords need at least 6 characters.');
  if (strlen($p) > 200) fail('That password is too long.');
  return $p;
}
function clean_calendar($d): array {
  if (!is_array($d) || !isset($d['days']) || !is_array($d['days'])) fail('The calendar data is not valid.');
  unset($d['accounts']);
  $d['version'] = 1;
  $d['owner'] = mb_substr((string)($d['owner'] ?? ''), 0, 120);
  if (!$d['days']) $d['days'] = new stdClass();
  if (empty($d['monthNotes']) || !is_array($d['monthNotes'])) $d['monthNotes'] = new stdClass();
  return $d;
}
function body(): array {
  $raw = file_get_contents('php://input', false, null, 0, MAX_BODY + 1);
  if ($raw === false || strlen($raw) > MAX_BODY) fail('The request is too large.', 413);
  $j = json_decode($raw, true);
  if (!is_array($j)) fail('Invalid request.');
  return $j;
}

/* ============================================================ */
ensure_dirs();
process_reset_file();
$a = $_GET['a'] ?? '';
$post = $_SERVER['REQUEST_METHOD'] === 'POST';

switch ($a) {

  case 'me':
    if (!users()) out(['setup' => true, 'user' => null]);
    out(me_info(current_user()));

  case 'setup':                                   // first run only: create the first editor + viewer
    if (!$post) fail('Use POST.', 405);
    $b = body();
    $res = with_lock(function () use ($b) {
      if (users()) fail('Accounts already exist. Please sign in.', 409);
      $list = [];
      foreach (['editor', 'viewer'] as $role) {
        $name = clean_name($b[$role]['user'] ?? '');
        if (find_user($list, $name)) fail('The two usernames must be different.');
        $list[] = ['user' => $name, 'role' => $role, 'hash' => password_hash(check_pass($b[$role]['pass'] ?? ''), PASSWORD_DEFAULT)];
      }
      store_write('users', ['users' => $list]);
      return $list[0];
    });
    sign_in($res, false);
    out(me_info($res));

  case 'login':
    if (!$post) fail('Use POST.', 405);
    throttle_check();
    $b = body();
    $u = find_user(users(), trim((string)($b['user'] ?? '')));
    if (!$u || !password_verify((string)($b['pass'] ?? ''), $u['hash'])) { throttle_fail(); fail('Wrong username or password.', 401); }
    if (!empty($u['must_change'])) {                // temporary password (reset): a new one is required
      $new = (string)($b['newpass'] ?? '');
      if ($new === '') out(['change' => true, 'user' => $u['user']]);
      check_pass($new);
      if ($new === (string)($b['pass'] ?? '')) fail('Choose a new password that is different from the temporary one.');
      $u = set_user_password($u['user'], password_hash($new, PASSWORD_DEFAULT), false);
      if (!$u) fail('That account no longer exists.', 401);
    } elseif (password_needs_rehash($u['hash'], PASSWORD_DEFAULT)) {
      with_lock(function () use (&$u, $b) {
        $list = users();
        foreach ($list as &$x) if ($x['user'] === $u['user']) { $x['hash'] = password_hash((string)$b['pass'], PASSWORD_DEFAULT); $u = $x; }
        store_write('users', ['users' => $list]);
      });
    }
    sign_in($u, !empty($b['remember']));
    out(me_info($u));

  case 'logout':
    start_session();
    $_SESSION = [];
    if (session_status() === PHP_SESSION_ACTIVE) session_destroy();
    set_session_cookie('', time() - 3600);
    out(['ok' => true]);

  case 'load':                                    // ?since=REV returns {unchanged:true} if nothing new
    require_user();
    session_write_close();
    $c = calendar();
    if (isset($_GET['since']) && (int)$_GET['since'] === (int)$c['rev']) out(['rev' => (int)$c['rev'], 'unchanged' => true]);
    out(['rev' => (int)$c['rev'], 'by' => $c['by'], 'saved' => $c['saved'], 'data' => $c['data']]);

  case 'save':
    if (!$post) fail('Use POST.', 405);
    $u = require_user(true);
    session_write_close();
    $b = body();
    $data = clean_calendar($b['data'] ?? null);
    $base = (int)($b['rev'] ?? -1);
    $res = with_lock(function () use ($u, $data, $base) {
      $c = calendar();
      if ($base !== (int)$c['rev']) out(['error' => 'conflict', 'rev' => (int)$c['rev'], 'by' => $c['by'], 'saved' => $c['saved']], 409);
      // a new backup at most every 10 minutes per editor, or when a different person saves
      $last = glob(BACKUP_DIR . '/cal-*.php') ?: []; rsort($last);
      if (!$last || $c['by'] !== $u['user'] || time() - filemtime($last[0]) > 600) backup_current($c);
      $n = ['rev' => (int)$c['rev'] + 1, 'by' => $u['user'], 'saved' => gmdate('c'), 'data' => $data];
      store_write('calendar', $n);
      return $n;
    });
    out(['rev' => $res['rev'], 'saved' => $res['saved']]);

  case 'users':                                   // editor: list (GET) or replace (POST) accounts
    $me = require_user(true);
    if (!$post) out(['users' => array_map(fn($u) => ['user' => $u['user'], 'role' => $u['role'], 'must' => !empty($u['must_change'])], users())]);
    $b = body();
    $rows = $b['users'] ?? null;
    if (!is_array($rows) || !$rows) fail('Add at least one account.');
    $res = with_lock(function () use ($rows, $me) {
      $old = users(); $list = [];
      foreach ($rows as $r) {
        $name = clean_name($r['user'] ?? '');
        if (find_user($list, $name)) fail('Usernames must be different ("' . $name . '" is used twice).');
        $role = ($r['role'] ?? '') === 'editor' ? 'editor' : 'viewer';
        $prev = isset($r['orig']) ? find_user($old, (string)$r['orig']) : null;
        $pass = (string)($r['pass'] ?? '');
        if ($pass !== '') $hash = password_hash(check_pass($pass), PASSWORD_DEFAULT);
        elseif ($prev) $hash = $prev['hash'];
        else fail('The new account "' . $name . '" needs a password.');
        $row = ['user' => $name, 'role' => $role, 'hash' => $hash];
        // "must choose a new password at next sign-in": ticked in the dialog, or kept from before when unchanged
        $self = $prev && strcasecmp($prev['user'], $me['user']) === 0;
        if (!$self && (!empty($r['must']) || ($pass === '' && $prev && !empty($prev['must_change'])))) $row['must_change'] = true;
        $list[] = $row;
      }
      if (!array_filter($list, fn($u) => $u['role'] === 'editor')) fail('Keep at least one Editor account, otherwise nobody can change the calendar.');
      store_write('users', ['users' => $list]);
      // keep the person who made the change signed in (even if they renamed themselves or changed their password)
      foreach ($rows as $i => $r) if (isset($r['orig']) && strcasecmp((string)$r['orig'], $me['user']) === 0) return $list[$i];
      return null;
    });
    if ($res) { $_SESSION['user'] = $res['user']; $_SESSION['pw'] = pw_stamp($res); }
    out(['ok' => true, 'me' => $res ? me_info($res) : ['user' => null]]);

  case 'password':                                // any signed-in user: change own password
    if (!$post) fail('Use POST.', 405);
    $me = require_user(false);
    throttle_check();
    $b = body();
    if (!password_verify((string)($b['old'] ?? ''), $me['hash'])) { throttle_fail(); fail('Your current password is not correct.', 400); }
    $new = check_pass($b['new'] ?? '');
    if ($new === (string)$b['old']) fail('The new password is the same as the current one.');
    $u = set_user_password($me['user'], password_hash($new, PASSWORD_DEFAULT), false);
    $_SESSION['pw'] = pw_stamp($u);                // stay signed in here; other devices are signed out
    out(['ok' => true]);

  case 'history':                                 // editor: list server backups
    require_user(true);
    session_write_close();
    $files = glob(BACKUP_DIR . '/cal-*.php') ?: []; rsort($files);
    $list = [];
    foreach ($files as $f) {
      $c = read_file_json($f); if (!$c) continue;
      $list[] = ['id' => basename($f, '.php'), 'rev' => (int)$c['rev'], 'by' => $c['by'], 'saved' => $c['saved'], 'days' => count((array)($c['data']['days'] ?? []))];
    }
    out(['backups' => $list]);

  case 'restore':
    if (!$post) fail('Use POST.', 405);
    $u = require_user(true);
    session_write_close();
    $id = (string)(body()['id'] ?? '');
    if (!preg_match('/^cal-\d{8}-\d{6}-r\d+$/', $id)) fail('Unknown backup.');
    $old = read_file_json(BACKUP_DIR . '/' . $id . '.php');
    if (!$old) fail('That backup could not be read.', 404);
    $res = with_lock(function () use ($u, $old) {
      $c = calendar();
      backup_current($c);
      $n = ['rev' => (int)$c['rev'] + 1, 'by' => $u['user'], 'saved' => gmdate('c'), 'data' => clean_calendar($old['data'])];
      store_write('calendar', $n);
      return $n;
    });
    out(['rev' => $res['rev']]);

  case 'files_info':                              // limits and usage
    require_user();
    session_write_close();
    $free = @disk_free_space(FILES_DIR);
    out(['max' => FILE_MAX, 'chunk' => FILE_CHUNK, 'graceDays' => FILE_GRACE / 86400, 'free' => $free === false ? null : (float)$free] + files_usage(files_index()));

  case 'upload_start':                            // editor: {name, size} -> {id}
    if (!$post) fail('Use POST.', 405);
    $u = require_user(true);
    session_write_close();
    $b = body();
    $size = (int)($b['size'] ?? -1);
    if ($size < 0) fail('Invalid file.');
    if ($size > FILE_MAX) fail('That file is larger than ' . (FILE_MAX / 1048576) . ' MB.', 413);
    $free = @disk_free_space(FILES_DIR);
    if ($free !== false && $free < $size + 20 * 1048576) fail('The server has no room for that file.', 507);
    $name = mb_substr(preg_replace('/[\x00-\x1f\\\\\/]+/u', '_', (string)($b['name'] ?? 'file')), 0, 200);
    $id = with_lock(function () use ($u, $size, $name) {
      $idx = files_index();
      // forget uploads that were started more than a day ago and never finished
      foreach ($idx as $k => $f) if (!empty($f['part']) && strtotime($f['added']) < time() - 86400) file_remove((string)$k, $idx);
      $id = file_new_id();
      if (file_put_contents(file_path($id, true), GUARD, LOCK_EX) === false) fail('Could not write to the data folder.', 500);
      @chmod(file_path($id, true), 0600);
      $idx[$id] = ['name' => $name, 'size' => $size, 'by' => $u['user'], 'added' => gmdate('c'), 'part' => true];
      store_write('files', $idx);
      return $id;
    });
    out(['id' => $id, 'chunk' => FILE_CHUNK]);

  case 'upload_chunk':                            // editor: raw bytes, ?id=&off=
    if (!$post) fail('Use POST.', 405);
    require_user(true);
    session_write_close();
    $id = (string)($_GET['id'] ?? ''); $off = (int)($_GET['off'] ?? -1);
    if (!file_id_ok($id) || !is_file(file_path($id, true))) fail('That upload is not open any more. Try again.', 404);
    $raw = file_get_contents('php://input', false, null, 0, FILE_CHUNK + 1);
    if ($raw === false || strlen($raw) > FILE_CHUNK) fail('The request is too large.', 413);
    $f = file_path($id, true);
    $h = fopen($f, 'ab'); if (!$h) fail('Could not write to the data folder.', 500);
    flock($h, LOCK_EX); clearstatcache(true, $f);
    $have = filesize($f) - strlen(GUARD);
    if ($have !== $off) { flock($h, LOCK_UN); fclose($h); fail('Upload out of step.', 409, ['have' => $have]); }
    if ($have + strlen($raw) > FILE_MAX) { flock($h, LOCK_UN); fclose($h); fail('That file is too large.', 413); }
    fwrite($h, $raw); fflush($h); flock($h, LOCK_UN); fclose($h);
    out(['have' => $have + strlen($raw)]);

  case 'upload_end':                              // editor: {id} -> {id, size}
    if (!$post) fail('Use POST.', 405);
    require_user(true);
    session_write_close();
    $id = (string)(body()['id'] ?? '');
    if (!file_id_ok($id)) fail('Unknown file.');
    $res = with_lock(function () use ($id) {
      $idx = files_index(); $f = file_path($id, true);
      if (!isset($idx[$id]) || !is_file($f)) fail('That upload is not open any more. Try again.', 404);
      clearstatcache(true, $f);
      $size = filesize($f) - strlen(GUARD);
      if ($size !== (int)$idx[$id]['size']) fail('The file did not arrive completely. Try again.', 409);
      if (!@rename($f, file_path($id))) fail('Could not save the file.', 500);
      unset($idx[$id]['part']);
      store_write('files', $idx);
      return ['id' => $id, 'size' => $size];
    });
    out($res);

  case 'file':                                    // any signed-in user: ?id= -> the bytes (always as a download, never shown by the browser)
    require_user();
    session_write_close();
    $id = (string)($_GET['id'] ?? '');
    $f = file_id_ok($id) ? file_path($id) : '';
    if (!$f || !is_file($f)) fail('That file is no longer on the server.', 404);
    $size = filesize($f) - strlen(GUARD);
    header_remove('Content-Type');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="attachment.bin"');
    header('Content-Length: ' . $size);
    header('Content-Security-Policy: default-src \'none\'; sandbox');
    header('Cache-Control: private, no-store');
    $h = fopen($f, 'rb'); fseek($h, strlen(GUARD));
    while (!feof($h)) { echo fread($h, 65536); }
    fclose($h);
    exit;

  case 'file_delete':                             // editor: {ids:[...]} — only files uploaded and never saved (e.g. editor cancelled)
    if (!$post) fail('Use POST.', 405);
    require_user(true);
    session_write_close();
    $ids = array_values(array_filter((array)(body()['ids'] ?? []), 'file_id_ok'));
    $n = with_lock(function () use ($ids) {
      $idx = files_index(); $n = 0;
      foreach ($ids as $id) if (isset($idx[$id]) || is_file(file_path($id))) { file_remove($id, $idx); $n++; }
      store_write('files', $idx);
      return $n;
    });
    out(['deleted' => $n]);

  case 'files_gc':                                // editor: {keep:[ids in use]} — files not in use are deleted after FILE_GRACE
    if (!$post) fail('Use POST.', 405);
    require_user(true);
    session_write_close();
    $keep = array_flip(array_filter((array)(body()['keep'] ?? []), 'file_id_ok'));
    $res = with_lock(function () use ($keep) {
      $idx = files_index(); $del = 0; $marked = 0; $now = time();
      foreach (glob(FILES_DIR . '/*.php') ?: [] as $p) {          // files missing from the index (e.g. copied back by FTP)
        $k = basename($p, '.php');
        if (file_id_ok($k) && !isset($idx[$k])) $idx[$k] = ['name' => 'file', 'size' => filesize($p) - strlen(GUARD), 'by' => '', 'added' => gmdate('c')];
      }
      foreach ($idx as $id => $f) {
        $id = (string)$id;
        if (!empty($f['part'])) continue;
        if (isset($keep[$id])) { if (isset($f['unused'])) { unset($idx[$id]['unused']); } continue; }
        if (!is_file(file_path($id))) { unset($idx[$id]); continue; }
        if (!isset($f['unused'])) { $idx[$id]['unused'] = gmdate('c'); $marked++; }
        elseif (strtotime($f['unused']) < $now - FILE_GRACE) { file_remove($id, $idx); $del++; }
      }
      store_write('files', $idx);
      return ['deleted' => $del, 'unused' => $marked] + files_usage($idx);
    });
    out($res);

  default:
    fail('Unknown action.', 404);
}

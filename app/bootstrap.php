<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_exception_handler(function (Throwable $e): void {
    $reference = bin2hex(random_bytes(6));
    // Não registrar DSN, senha, parâmetros ou tokens em mensagens de erro.
    error_log('Calculadora Progress [' . $reference . '] ' . get_class($e) . ' code=' . $e->getCode());
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, "Falha de configuração/conexão. Referência: $reference\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: no-store');
    echo '<!doctype html><html lang="pt-BR"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Acesso indisponível</title><body><h1>Acesso temporariamente indisponível</h1><p>Tente novamente mais tarde. Se persistir, fale com o administrador: <a href="mailto:leoferrareto2013@gmail.com">leoferrareto2013@gmail.com</a>.</p><p>Referência: ' . $reference . '</p></body></html>';
    exit;
});

$configFile = dirname(__DIR__) . '/config/config.php';
$GLOBALS['calc_config'] = require (is_file($configFile) ? $configFile : dirname(__DIR__) . '/config/environment.php');
function cfg(string $key): mixed { return $GLOBALS['calc_config'][$key] ?? null; }
if (strlen((string) cfg('app_key')) < 32) { throw new RuntimeException('Chave ausente.'); }
$appUrl = parse_url((string) cfg('app_url'));
if (!$appUrl || empty($appUrl['host']) || isset($appUrl['query']) || isset($appUrl['fragment']) || isset($appUrl['user'])) {
    throw new RuntimeException('URL inválida.');
}
$isLocal = cfg('environment') === 'local';
if (($appUrl['scheme'] ?? '') !== 'https' && !($isLocal && in_array($appUrl['host'], ['127.0.0.1', 'localhost', '::1'], true))) {
    throw new RuntimeException('HTTPS obrigatório.');
}
function app_url(string $page = ''): string { return rtrim((string) cfg('app_url'), '/') . '/' . ltrim($page, '/'); }
function esc(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function go(string $page): never { header('Location: ' . app_url($page), true, 303); exit; }
function db(): PDO {
    static $pdo;
    if (!$pdo) {
        $dsn = (string) cfg('db_dsn');
        if (cfg('environment') !== 'local' && !str_contains($dsn, 'sslmode=verify-full')) {
            throw new RuntimeException('TLS com validação completa obrigatório.');
        }
        $pdo = new PDO($dsn, (string) cfg('db_user'), (string) cfg('db_password'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}
function posted(string $name): string { return isset($_POST[$name]) && is_string($_POST[$name]) ? $_POST[$name] : ''; }
function email_normalized(string $email): string { return strtolower(trim($email)); }
function valid_email(string $email): bool { return strlen($email) <= 254 && (bool) filter_var($email, FILTER_VALIDATE_EMAIL); }
function password_error(string $password, string $confirmation): string {
    if (mb_strlen($password, 'UTF-8') < 12 || strlen($password) > 72 || str_contains($password, "\0")) {
        return 'Use pelo menos 12 caracteres e no máximo 72 bytes na senha.';
    }
    return $password !== $confirmation ? 'As senhas não coincidem.' : '';
}
function hash_password(string $password): string {
    return password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT);
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . esc($_SESSION['csrf']) . '">'; }
function check_csrf(): void {
    if (!hash_equals($_SESSION['csrf'], posted('csrf'))) {
        http_response_code(403);
        exit('A página expirou. Volte, atualize a página e tente novamente.');
    }
}
function client_ip(): string {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (cfg('trust_cloudflare_headers') && in_array($remote, cfg('trusted_proxy_ips') ?? [], true)) {
        $cf = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '';
        if (filter_var($cf, FILTER_VALIDATE_IP)) { return $cf; }
    }
    return $remote;
}
// Contador atômico: requisições simultâneas não burlam o limite.
function throttle(string $scope, string $identity, int $limit, int $seconds): bool {
    $key = hash_hmac('sha256', $scope . ':' . $identity, (string) cfg('app_key'));
    $sql = "INSERT INTO calc_auth.rate_limits AS r (key_hash, hits, expires_at)
        VALUES (:key, 1, now() + CAST(:ttl AS integer) * interval '1 second')
        ON CONFLICT (key_hash) DO UPDATE SET
        hits = CASE WHEN r.expires_at <= now() THEN 1 ELSE LEAST(r.hits + 1, 1000000) END,
        expires_at = CASE WHEN r.expires_at <= now() THEN EXCLUDED.expires_at ELSE r.expires_at END
        RETURNING hits";
    $stmt = db()->prepare($sql);
    $stmt->execute(['key' => $key, 'ttl' => $seconds]);
    return (int) $stmt->fetchColumn() <= $limit;
}
function signed_user(): ?array {
    if (empty($_SESSION['uid'])) { return null; }
    if (time() - (int) ($_SESSION['started'] ?? 0) > 28800 || time() - (int) ($_SESSION['seen'] ?? 0) > 1800) {
        $_SESSION = ['csrf' => bin2hex(random_bytes(32))];
        session_regenerate_id(true);
        return null;
    }
    $stmt = db()->prepare('SELECT id, name, email, session_version FROM calc_auth.users WHERE id = :id AND active = TRUE');
    $stmt->execute(['id' => $_SESSION['uid']]);
    $user = $stmt->fetch();
    if (!$user || (int) $user['session_version'] !== (int) $_SESSION['version']) {
        $_SESSION = ['csrf' => bin2hex(random_bytes(32))];
        session_regenerate_id(true);
        return null;
    }
    $_SESSION['seen'] = time();
    return $user;
}
function require_user(): array {
    $user = signed_user();
    if (!$user) { go('login.php'); }
    return $user;
}
function sign_in(array $user): void {
    session_regenerate_id(true);
    $_SESSION = ['uid' => $user['id'], 'version' => (int) $user['session_version'],
        'started' => time(), 'seen' => time(), 'csrf' => bin2hex(random_bytes(32))];
    $stmt = db()->prepare('UPDATE calc_auth.users SET last_login_at = now() WHERE id = :id');
    $stmt->execute(['id' => $user['id']]);
}
if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/html; charset=UTF-8');
    header('Cache-Control: private, no-store, max-age=0');
    header('Pragma: no-cache');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self' 'unsafe-inline'; img-src 'self' data:; object-src 'none'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'");
    if (!$isLocal) { header('Strict-Transport-Security: max-age=31536000'); }
    $sessionDir = dirname(__DIR__) . '/storage/sessions';
    if (!is_dir($sessionDir) || !is_writable($sessionDir)) { throw new RuntimeException('Diretório de sessão indisponível.'); }
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '28800');
    session_save_path($sessionDir);
    session_name('calc_progress_session');
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => !$isLocal, 'httponly' => true, 'samesite' => 'Lax']);
    if (!session_start()) { throw new RuntimeException('Sessão indisponível.'); }
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

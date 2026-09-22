<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$ok = true;
foreach (['pdo_pgsql', 'mbstring', 'session'] as $extension) {
    $has = extension_loaded($extension); echo $extension . ': ' . ($has ? 'OK' : 'AUSENTE') . PHP_EOL; $ok = $ok && $has;
}
if (!$ok || version_compare(PHP_VERSION, '8.2', '<')) { fwrite(STDERR, "Necessário PHP 8.2+ com as extensões indicadas.\n"); exit(1); }
require dirname(__DIR__) . '/app/bootstrap.php';
echo 'Sessões: ', is_writable(dirname(__DIR__) . '/storage/sessions') ? 'OK' : 'SEM PERMISSÃO', PHP_EOL;
$stmt = db()->query('SELECT 1 FROM calc_auth.users LIMIT 1');
echo "Conexão e tabela de usuários: OK\n";
db()->query('SELECT 1 FROM calc_auth.rate_limits LIMIT 1');
db()->query('SELECT 1 FROM calc_auth.password_resets LIMIT 1');
echo "Tabelas de proteção e recuperação: OK\n";

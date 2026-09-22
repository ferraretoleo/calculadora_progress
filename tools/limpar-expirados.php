<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
echo 'Limites expirados: ', db()->exec('DELETE FROM calc_auth.rate_limits WHERE expires_at < now()'), PHP_EOL;
echo 'Links expirados: ', db()->exec('DELETE FROM calc_auth.password_resets WHERE expires_at < now() OR used_at IS NOT NULL'), PHP_EOL;

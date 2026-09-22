<?php
// Copie para config.php. Este diretório deve ficar FORA da raiz pública do IIS.
return [
    'app_url' => 'https://calculadora.seudominio.com',
    'environment' => 'production', // 'local' somente para teste em http://127.0.0.1
    'app_key' => '', // gere com: php tools/generate-key.php
    'admin_email' => 'leoferrareto2013@gmail.com',
    'db_dsn' => 'pgsql:host=SEU-HOST.neon.tech;port=5432;dbname=neondb;sslmode=verify-full;sslrootcert=C:/certs/cacert.pem;connect_timeout=10',
    'db_user' => 'SEU_USUARIO_NEON',
    'db_password' => '',
    // Só habilite após restringir a origem ao cloudflared, conforme INSTALACAO.md.
    'trust_cloudflare_headers' => false,
    'trusted_proxy_ips' => ['127.0.0.1', '::1'],
];

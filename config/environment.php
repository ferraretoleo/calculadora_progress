<?php
// Configuração Render/Docker: segredos somente nas variáveis de ambiente.
return [
    'app_url' => getenv('APP_URL') ?: '',
    'environment' => getenv('APP_ENV') ?: 'production',
    'app_key' => getenv('APP_KEY') ?: '',
    'admin_email' => 'leoferrareto2013@gmail.com',
    'db_dsn' => getenv('DB_DSN') ?: '',
    'db_user' => getenv('DB_USER') ?: '',
    'db_password' => getenv('DB_PASSWORD') ?: '',
    'trust_cloudflare_headers' => false,
    'trusted_proxy_ips' => [],
];

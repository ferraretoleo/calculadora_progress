<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/bootstrap.php';
$email = email_normalized($argv[1] ?? '');
if (!valid_email($email)) { fwrite(STDERR, "Uso: php tools/gerar-recuperacao.php usuario@empresa.com.br\n"); exit(1); }
$pdo = db(); $pdo->beginTransaction();
$stmt = $pdo->prepare('SELECT id FROM calc_auth.users WHERE email = :email AND active = TRUE FOR UPDATE');
$stmt->execute(['email' => $email]); $id = $stmt->fetchColumn();
if (!$id) { $pdo->rollBack(); fwrite(STDERR, "Conta ativa não encontrada.\n"); exit(1); }
$token = bin2hex(random_bytes(32));
$stmt = $pdo->prepare('DELETE FROM calc_auth.password_resets WHERE user_id = :id'); $stmt->execute(['id' => $id]);
$stmt = $pdo->prepare("INSERT INTO calc_auth.password_resets(token_hash, user_id, expires_at) VALUES (:hash, :id, now() + interval '30 minutes')");
$stmt->execute(['hash' => hash('sha256', $token), 'id' => $id]);
$pdo->commit();
echo "Após confirmar a identidade, envie ao titular este link de uso único (30 minutos):\n", app_url('redefinir.php?token=' . $token), PHP_EOL;

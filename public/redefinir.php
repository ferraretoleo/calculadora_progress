<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/view.php';
if (isset($_GET['token'])) {
    $_SESSION['reset_token'] = is_string($_GET['token']) && preg_match('/^[a-f0-9]{64}$/D', $_GET['token']) ? $_GET['token'] : '';
    go('redefinir.php');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    if (!throttle('reset-ip', client_ip(), 10, 900)) { http_response_code(429); $error = 'Muitas tentativas. Aguarde 15 minutos.'; }
    else { $error = password_error(posted('password'), posted('confirmation')); }
    if ($error === '') {
        $hash = hash('sha256', $_SESSION['reset_token'] ?? '');
        $newHash = hash_password(posted('password'));
        $pdo = db(); $pdo->beginTransaction();
        try {
            // Bloqueia usuário antes do token: serializa geração e resgate de links.
            $stmt = $pdo->prepare('SELECT u.id FROM calc_auth.users u JOIN calc_auth.password_resets r ON r.user_id = u.id WHERE r.token_hash = :hash AND u.active = TRUE FOR UPDATE OF u');
            $stmt->execute(['hash' => $hash]); $id = $stmt->fetchColumn();
            $stmt = $pdo->prepare('UPDATE calc_auth.password_resets SET used_at = now() WHERE token_hash = :hash AND used_at IS NULL AND expires_at > now() RETURNING user_id');
            $stmt->execute(['hash' => $hash]); $redeemed = $stmt->fetchColumn();
            if (!$id || !$redeemed || (string) $id !== (string) $redeemed) {
                $pdo->rollBack(); $error = 'Link inválido, expirado ou já utilizado. Solicite outro ao administrador.';
            } else {
                $stmt = $pdo->prepare('UPDATE calc_auth.users SET password_hash = :hash, session_version = session_version + 1 WHERE id = :id');
                $stmt->execute(['hash' => $newHash, 'id' => $id]);
                $stmt = $pdo->prepare('DELETE FROM calc_auth.password_resets WHERE user_id = :id'); $stmt->execute(['id' => $id]);
                $pdo->commit();
                $_SESSION = ['csrf' => bin2hex(random_bytes(32))]; session_regenerate_id(true);
                go('login.php?senha=ok');
            }
        } catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
    }
}
auth_start('Defina sua nova senha', 'O link enviado pelo administrador é válido por 30 minutos e pode ser usado uma única vez.'); notice($error);
if (empty($_SESSION['reset_token'])) { notice('Abra o link de recuperação enviado pelo administrador.'); }
else { ?>
<form method="post" class="auth-form"><?= csrf_field() ?>
<label for="password">Nova senha</label><div class="password-field"><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" required><button type="button" class="reveal" data-reveal="password" aria-controls="password" aria-pressed="false">Mostrar</button></div>
<p class="field-hint">Use pelo menos 12 caracteres.</p>
<label for="confirmation">Confirme a nova senha</label><input id="confirmation" name="confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
<button class="primary" type="submit">Salvar nova senha</button></form>
<?php } ?>
<p class="alternate"><a href="<?= esc(app_url('login.php')) ?>">← Voltar para o login</a></p>
<?php auth_end(); ?>

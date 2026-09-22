<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/view.php';
if (signed_user()) { go('index.php'); }
$error = ''; $email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $email = email_normalized(posted('email'));
    $password = posted('password');
    $ipAllowed = throttle('login-ip', client_ip(), 50, 900);
    $emailAllowed = throttle('login-email', $email, 10, 900);
    if (!$ipAllowed || !$emailAllowed) {
        http_response_code(429); header('Retry-After: 900');
        $error = 'Muitas tentativas. Aguarde 15 minutos antes de tentar novamente.';
    } elseif (!valid_email($email) || strlen($password) > 72 || str_contains($password, "\0")) {
        $error = 'E-mail ou senha incorretos.';
    } else {
        $stmt = db()->prepare('SELECT id, password_hash, session_version, active FROM calc_auth.users WHERE email = :email');
        $stmt->execute(['email' => $email]); $user = $stmt->fetch();
        $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
        $valid = password_verify($password, $hash);
        if ($user && $valid && in_array($user['active'], [true, 1, '1', 't'], true)) {
            sign_in($user); go('index.php');
        }
        $error = 'E-mail ou senha incorretos.';
    }
}
auth_start('Bem-vindo de volta', 'Entre com sua conta para acessar a calculadora.');
notice($error);
if (($_GET['cadastro'] ?? '') === 'ok') { notice('Cadastro realizado. Agora entre com seu e-mail e senha.', true); }
if (($_GET['senha'] ?? '') === 'ok') { notice('Senha atualizada. Entre com a nova senha.', true); }
?>
<form method="post" class="auth-form">
<?= csrf_field() ?>
<label for="email">E-mail</label><input id="email" name="email" type="email" maxlength="254" autocomplete="username" placeholder="voce@empresa.com.br" value="<?= esc($email) ?>" required>
<div class="label-row"><label for="password">Senha</label><a href="<?= esc(app_url('ajuda.php')) ?>">Perdi meu acesso</a></div>
<div class="password-field"><input id="password" name="password" type="password" maxlength="72" autocomplete="current-password" required><button type="button" class="reveal" data-reveal="password" aria-controls="password" aria-pressed="false">Mostrar</button></div>
<button class="primary" type="submit">Entrar na calculadora <span aria-hidden="true">→</span></button>
</form><p class="alternate">Ainda não tem uma conta? <a href="<?= esc(app_url('cadastro.php')) ?>">Criar cadastro</a></p>
<?php auth_end(); ?>

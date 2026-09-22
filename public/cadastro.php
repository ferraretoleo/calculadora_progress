<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/view.php';
if (signed_user()) { go('index.php'); }
$error = ''; $email = ''; $name = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf(); $name = trim(posted('name')); $email = email_normalized(posted('email'));
    if (!throttle('signup-ip', client_ip(), 5, 3600)) {
        http_response_code(429); header('Retry-After: 3600');
        $error = 'Limite de cadastros atingido. Tente novamente em uma hora.';
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 120 || preg_match('/[\x00-\x1F\x7F]/', $name)) {
        $error = 'Informe seu nome, com 2 a 120 caracteres.';
    } elseif (!valid_email($email)) { $error = 'Informe um e-mail válido.'; }
    else { $error = password_error(posted('password'), posted('confirmation')); }
    if ($error === '') {
        $stmt = db()->prepare('INSERT INTO calc_auth.users(name, email, password_hash) VALUES (:name, :email, :hash) ON CONFLICT (email) DO NOTHING RETURNING id');
        $stmt->execute(['name' => $name, 'email' => $email, 'hash' => hash_password(posted('password'))]);
        if ($stmt->fetchColumn()) { go('login.php?cadastro=ok'); }
        $error = 'Não foi possível cadastrar esse e-mail. Se já possui conta, entre ou solicite ajuda.';
    }
}
auth_start('Crie sua conta', 'Cadastre-se para usar a Calculadora Progress.'); notice($error);
?>
<form method="post" class="auth-form">
<?= csrf_field() ?>
<label for="name">Nome</label><input id="name" name="name" autocomplete="name" minlength="2" maxlength="120" value="<?= esc($name) ?>" required>
<label for="email">E-mail</label><input id="email" name="email" type="email" autocomplete="username" maxlength="254" value="<?= esc($email) ?>" required>
<label for="password">Senha</label><div class="password-field"><input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="72" aria-describedby="password-hint" required><button type="button" class="reveal" data-reveal="password" aria-controls="password" aria-pressed="false">Mostrar</button></div>
<p id="password-hint" class="field-hint">Use pelo menos 12 caracteres. Você pode usar uma frase.</p>
<label for="confirmation">Confirme a senha</label><input id="confirmation" name="confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="72" required>
<button type="submit" class="primary">Criar minha conta <span aria-hidden="true">→</span></button>
</form><p class="alternate">Já tem uma conta? <a href="<?= esc(app_url('login.php')) ?>">Entrar</a></p>
<?php auth_end(); ?>

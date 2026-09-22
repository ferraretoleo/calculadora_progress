<?php
require dirname(__DIR__) . '/app/bootstrap.php';
require dirname(__DIR__) . '/app/view.php';
auth_start('Vamos recuperar seu acesso', 'Envie sua solicitação ao administrador da calculadora.');
?>
<form class="auth-form" id="help-form" data-admin="<?= esc(cfg('admin_email')) ?>">
<label for="help-name">Seu nome</label><input id="help-name" autocomplete="name" maxlength="120" required>
<label for="help-email">E-mail do cadastro</label><input id="help-email" type="email" autocomplete="email" maxlength="254" required>
<label for="help-message">Como podemos ajudar?</label><textarea id="help-message" rows="4" maxlength="2000" placeholder="Ex.: Esqueci minha senha ou não consigo entrar." required></textarea>
<p class="field-hint">Não informe sua senha. Se perdeu acesso ao e-mail cadastrado, informe no texto um contato alternativo.</p>
<button class="primary" type="submit">Preparar e-mail de ajuda <span aria-hidden="true">↗</span></button>
<p class="field-hint">Seu aplicativo de e-mail será aberto. Revise a mensagem e confirme o envio por lá.</p>
</form>
<div class="contact-box">Administrador<a href="mailto:<?= esc(cfg('admin_email')) ?>"><?= esc(cfg('admin_email')) ?></a><p>Se o aplicativo não abrir, envie a solicitação diretamente para esse endereço.</p></div>
<noscript><p>Envie seu nome, e-mail de cadastro e descrição do problema ao endereço acima.</p></noscript>
<p class="alternate"><a href="<?= esc(app_url('login.php')) ?>">← Voltar para o login</a></p>
<?php auth_end(); ?>

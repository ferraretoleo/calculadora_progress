<?php
function auth_start(string $title, string $subtitle): void {
?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= esc($title) ?> · Calculadora Progress</title><link rel="stylesheet" href="<?= esc(app_url('assets/auth.css')) ?>"></head>
<body><main class="access-layout">
<aside class="brand-panel"><a class="brand" href="<?= esc(app_url('login.php')) ?>"><span class="brand-icon" aria-hidden="true">P</span> Progress<span class="brand-sub">/ Calculadora</span></a>
<div class="brand-copy"><div class="eyebrow">PROGRESS OPENEDGE</div><h1>Seu próximo<br>cálculo começa<br><span>aqui.</span></h1><p>Dimensione os parâmetros do banco e gere os arquivos de configuração em um só lugar.</p><div class="parameter-list"><span>-B</span><span>-L</span><span>-spin</span><span>APW</span></div></div>
<p class="brand-footer">Dimensionamento e configuração de bancos</p></aside>
<section class="form-panel"><div class="auth-card"><div class="eyebrow">CALCULADORA PROGRESS</div><h2><?= esc($title) ?></h2><p class="subtitle"><?= esc($subtitle) ?></p>
<?php
}
function notice(string $text, bool $success = false): void {
    if ($text !== '') { echo '<div class="notice ' . ($success ? 'success' : '') . '" role="' . ($success ? 'status' : 'alert') . '">' . esc($text) . '</div>'; }
}
function auth_end(): void { ?>
</div><footer class="support">Precisa de ajuda? <a href="<?= esc(app_url('ajuda.php')) ?>">Fale com o administrador</a></footer></section>
</main><script src="<?= esc(app_url('assets/auth.js')) ?>" defer></script></body></html>
<?php }

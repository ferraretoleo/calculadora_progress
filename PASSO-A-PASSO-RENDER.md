# Passo a passo — Render + Neon + Cloudflare

## 1. Criar o banco Neon

1. Acesse https://console.neon.tech e crie um projeto dedicado à calculadora.
2. No SQL Editor execute o conteúdo completo de `database/001_auth.sql`.
3. Em **Connect**, copie separadamente hostname, nome do banco, usuário e senha. Pode usar conexão direta. Guarde os dados; não os envie ao GitHub.

## 2. Preparar os arquivos no GitHub

1. Extraia o ZIP. Crie um repositório **privado** no GitHub, por exemplo `calculadora-progress`.
2. Envie o conteúdo da pasta `calculadora-progress` para a raiz do repositório. O `Dockerfile` deve aparecer na raiz, junto das pastas `app`, `public`, `config` e `database`.
3. Não envie `config/config.php`, arquivos `.env`, senhas ou sessões. Os arquivos de exemplo podem ser enviados.

## 3. Criar o serviço Render

1. Acesse https://dashboard.render.com e escolha **New → Web Service**.
2. Conecte o repositório privado e autorize o acesso solicitado pelo próprio Render.
3. Selecione **Docker** como linguagem/runtime. Dockerfile Path: `./Dockerfile`. Root Directory: vazio se os arquivos estão na raiz.
4. Escolha região e plano conforme seu uso. Confira valores e limitações no painel. Não é necessário instalar Docker no seu Windows para esse fluxo.
5. Em **Environment**, configure as variáveis abaixo antes de usar a aplicação.

| Variável | Valor |
|---|---|
| APP_URL | `https://NOME-DO-SERVICO.onrender.com` (substitua pelo endereço efetivamente atribuído) |
| APP_ENV | `production` |
| APP_KEY | Chave aleatória gerada abaixo, mantida fixa entre deploys |
| DB_DSN | `pgsql:host=SEU-HOST.neon.tech;port=5432;dbname=SEU_BANCO;sslmode=verify-full;sslrootcert=/etc/ssl/certs/ca-certificates.crt;connect_timeout=10` |
| DB_USER | Usuário do Neon |
| DB_PASSWORD | Senha do Neon, sem aspas adicionais |
| PORT | `10000` |

Para gerar APP_KEY no PowerShell, execute localmente:

```powershell
$bytes = New-Object byte[] 32
$rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
$rng.GetBytes($bytes)
[Convert]::ToBase64String($bytes)
$rng.Dispose()
```

Copie a saída para APP_KEY; não use uma senha inventada. A chave não é uma senha de login.

6. Clique em **Deploy Web Service**. O Docker instala PHP/Apache e as extensões necessárias. Não configure um Build Command ou Start Command adicional.
7. Quando souber a URL definitiva `onrender.com`, corrija APP_URL se necessário e salve as variáveis/reimplante.
8. Abra `/cadastro.php`, cadastre sua conta e entre. Não existe usuário/senha padrão.

O pacote usa uma única instância. As sessões ficam no disco temporário do contêiner: reinícios/deploys podem exigir novo login, mas as contas permanecem no Neon. Não escale para várias instâncias sem adaptar as sessões para armazenamento compartilhado. O limite por IP usa o endereço visto pelo Apache; em proxy ele pode ser compartilhado entre visitantes. Há também limite independente por e-mail. Não habilite confiança em cabeçalhos Cloudflare neste ambiente sem configurar e restringir os proxies confiáveis.

## 4. Conectar seu domínio no Cloudflare (opcional)

Você precisa de um domínio próprio configurado no Cloudflare. Sem domínio, use a URL HTTPS do Render normalmente.

1. No Render, serviço → **Settings → Custom Domains**, adicione `calculadora.seudominio.com`.
2. No Cloudflare, **DNS → Records → Add record**: tipo `CNAME`, nome `calculadora`, destino `NOME-DO-SERVICO.onrender.com` (sem `https://`). Inicialmente use **DNS only**, nuvem cinza.
3. Remova apenas registros conflitantes do mesmo hostname, se existirem. Preserve os demais serviços do domínio.
4. No Render, verifique o domínio e aguarde o certificado HTTPS ficar válido.
5. Troque APP_URL para `https://calculadora.seudominio.com` e reimplante. Faça novo login nesse endereço.
6. Pode manter DNS only. Se ativar o proxy Cloudflare, use SSL/TLS **Full (strict)** depois que o certificado Render estiver válido, redirecione HTTP para HTTPS e crie regra de **bypass de cache para todo o hostname**. Não use Cache Everything.

Neste caminho não precisa Cloudflare Tunnel, Workers ou Pages. O Render executa PHP; o Cloudflare cuida do DNS e, opcionalmente, do proxy.

## 5. Perdi meu acesso

O usuário clica no link da tela de login, informa nome, e-mail e problema. O botão abre uma mensagem para **leoferrareto2013@gmail.com**. O usuário revisa e confirma o envio no aplicativo de e-mail; se não abrir, o endereço está visível para envio manual. Não há envio SMTP automático.

Depois de confirmar a identidade, gere o link de recuperação:

- Se seu plano oferece **Shell** no Render, execute `php tools/gerar-recuperacao.php usuario@empresa.com.br` dentro de `/var/www/calculadora`.
- Se não oferece Shell, use localmente PHP 8.2+ com `pdo_pgsql` e `mbstring`, copie `config/config.example.php` para `config/config.php`, configure os mesmos dados Neon e APP_URL e gere a chave local. Para TLS no Windows, siga a seção de certificados de `INSTALACAO.md`. Execute `php tools/gerar-recuperacao.php usuario@empresa.com.br`. Essa ferramenta acessa o mesmo banco Neon; não requer o site rodando localmente.

Envie o link retornado ao titular por um canal confiável. Ele vale por 30 minutos, só pode ser usado uma vez e encerra as sessões anteriores quando a senha é redefinida. Gerar outro link invalida o anterior. O comando não envia e-mail. Não coloque tokens em tickets públicos.

## 6. Conferência após publicar

- Abrir `/index.php` sem login deve levar ao login.
- Cadastrar uma conta, entrar, executar um cálculo e sair.
- Tentar acessar a calculadora novamente deve pedir login.
- Abrir ajuda e conferir destinatário da mensagem.
- Testar um link de recuperação e sua expiração/uso único.
- Confirmar que `/config/config.php` e `/database/001_auth.sql` retornam 404.
- Retirar do ar qualquer URL antiga da calculadora sem login.

Se aparecer 503, confira as variáveis, certificado, tabelas criadas e logs do Render. Não exponha a senha do banco em prints. O login pode abrir mesmo com o banco indisponível: valide cadastro/login para testar a conexão completa.

## Referências oficiais

- https://render.com/docs/docker
- https://render.com/docs/configure-cloudflare-dns
- https://neon.com/docs/connect/connect-securely

Nenhum recurso foi criado nas suas contas. O deploy e a configuração externa serão realizados por você.

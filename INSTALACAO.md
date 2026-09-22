# Calculadora Progress — login, cadastro e Neon

## O que foi implementado

- Login com e-mail e senha; cadastro com nome, e-mail e confirmação de senha.
- Cadastro aberto: a conta pode acessar a calculadora imediatamente após entrar. Não há confirmação de e-mail nem aprovação administrativa nesta versão.
- Calculadora protegida no PHP, inclusive ao acessar diretamente `index.php` ou enviar POST.
- Botão Sair; sessão com 30 minutos de inatividade e duração máxima de 8 horas.
- Senhas com Argon2id quando disponível e fallback para PASSWORD_DEFAULT; mínimo de 12 caracteres, máximo de 72 bytes.
- Proteção CSRF nos POSTs; cookies HttpOnly, Secure em produção e SameSite=Lax.
- Limites de tentativas armazenados no PostgreSQL, por e-mail e IP.
- Ajuda que prepara um e-mail para **leoferrareto2013@gmail.com**. O usuário precisa confirmar o envio no aplicativo de e-mail. Não há SMTP nem envio automático pelo servidor.
- Ferramenta administrativa de linha de comando para gerar um link de recuperação válido por 30 minutos, de uso único. Redefinir a senha encerra as sessões anteriores da conta.
- O bloco original de cálculos e geração de scripts foi preservado; foram adicionados o controle de acesso, CSRF e a barra do usuário.

## Arquitetura escolhida

Navegador → HTTPS/Cloudflare → Cloudflare Tunnel → IIS/PHP → Neon/PostgreSQL com TLS.

O PHP continua no seu servidor. O Cloudflare publica o acesso pelo Tunnel; não executa o PHP. O Neon armazena as contas, limites de tentativas e links de recuperação. As sessões PHP ficam em `storage/sessions` no servidor. Esta entrega foi projetada para uma única instância PHP; vários servidores exigem armazenamento compartilhado de sessões.

## 1. Requisitos e pasta

Use PHP 8.2 ou superior com `pdo_pgsql`, `mbstring` e sessões habilitados. No Windows/IIS, mantenha o mapeamento FastCGI que já atende PHP; o `web.config` não instala nem substitui esse mapeamento.

Extraia o pacote, por exemplo, em:

`D:\dba\PHP\calculadora-progress`

A pasta pública do site deve ser **somente**:

`D:\dba\PHP\calculadora-progress\public`

Nunca aponte o site para a raiz do projeto. `config`, `app`, `database`, `tools` e `storage` ficam fora da raiz pública. Dê leitura à identidade do pool do IIS e escrita apenas em `storage\sessions`. Não dê escrita no código ou configuração à identidade web.

Use um novo diretório para esta implantação. Retire de circulação a URL antiga da calculadora sem login: deixá-la acessível permitiria contornar o novo acesso. Mantenha backups fora da raiz pública.

## 2. Criar o banco Neon

No console do Neon, crie ou escolha um projeto/banco dedicado à calculadora. No SQL Editor desse banco execute todo o arquivo `database/001_auth.sql`.

O script cria o schema `calc_auth` e três tabelas: `users`, `rate_limits` e `password_resets`. Pode ser executado novamente sem apagar as contas existentes.

Na opção de conexão do Neon obtenha host, banco, usuário e senha. Esta aplicação usa PDO, portanto a URL `postgresql://...` precisa ser separada nesses campos de configuração. Não exponha as credenciais no navegador.

## 3. Configurar a aplicação

No PowerShell, dentro da pasta do projeto:

```powershell
Copy-Item .\config\config.example.php .\config\config.php
php .\tools\generate-key.php
```

Se `php` não estiver no PATH, use o caminho completo do seu `php.exe`, por exemplo `& 'C:\PHP\php.exe' .\tools\generate-key.php`.

Copie a chave gerada para `app_key` em `config/config.php`. Preencha:

- `app_url`: endereço HTTPS definitivo, sem barra final. Ex.: `https://calculadora.seudominio.com`.
- `environment`: mantenha `production`.
- `db_dsn`: host, banco e caminho do certificado raiz.
- `db_user` e `db_password`: os dados do Neon.
- `admin_email`: já configurado com `leoferrareto2013@gmail.com`.

Exemplo de DSN:

```text
pgsql:host=ep-SEU-HOST.neon.tech;port=5432;dbname=neondb;sslmode=verify-full;sslrootcert=C:/certs/cacert.pem;connect_timeout=10
```

No Windows, obtenha o pacote de certificados CA do [projeto curl](https://curl.se/docs/caextract.html), salve em `C:\certs\cacert.pem` e permita leitura ao pool do IIS. No Linux, normalmente pode ser usado `/etc/ssl/certs/ca-certificates.crt`. Não desative a validação de certificado para resolver erros de conexão. O DSN exige `sslmode=verify-full` em produção.

A configuração é PHP: ao escrever uma senha entre aspas simples, escape apóstrofo como `\'` e barra invertida como `\\`. Proteja o arquivo com ACL do Windows; ele contém a senha do banco em texto e não deve ir para Git ou backups públicos.

Valide no servidor:

```powershell
php .\tools\verificar.php
```

A ferramenta verifica extensões, acesso à pasta de sessões e conexão às tabelas. O Neon pode levar alguns segundos para iniciar um compute suspenso. Em caso de falha, confira host, senha, nome do banco, certificados e saída TCP 5432 liberada.

## 4. IIS e Cloudflare Tunnel

1. Crie um site/aplicação IIS apontando para `public`, com PHP/FastCGI habilitado.
2. Para um site dedicado, use o binding HTTP `127.0.0.1:8088`, sem hostname. O conector cloudflared deve rodar no mesmo servidor.
3. No painel do Cloudflare, crie um Cloudflare Tunnel e siga o comando de instalação do conector como serviço Windows apresentado pelo painel. O token é secreto.
4. Cadastre o hostname público, por exemplo `calculadora.seudominio.com`, apontando para o serviço `http://127.0.0.1:8088`.
5. Ative o redirecionamento de HTTP para HTTPS no hostname público.
6. Configure regra de cache para **ignorar cache em todo o hostname da calculadora**. Não use “Cache Everything”. O PHP também envia `Cache-Control: private, no-store`.
7. Com o IIS restrito ao loopback e o cloudflared no mesmo servidor, ajuste `trust_cloudflare_headers` para `true` e mantenha os IPs de proxy `127.0.0.1` e `::1`. Isso permite que os limites usem o IP real do visitante.
8. Não exponha essa porta do IIS diretamente na Internet. Só confie no cabeçalho `CF-Connecting-IP` quando a origem estiver restrita ao conector. Sem esse ajuste, os visitantes do Tunnel compartilharão o limite do IP do proxy.
9. Acesse pelo endereço HTTPS configurado em `app_url` e faça o primeiro cadastro.

Se já usa um túnel, adicione apenas uma nova rota de hostname; não recrie o serviço existente. Há também um exemplo de configuração para túnel localmente gerenciado em `deploy/cloudflared.example.yml`.

O controle de acesso da aplicação é feito pelo login deste pacote. Uma política Cloudflare Access, se já aplicada ao hostname, representa uma segunda barreira e pode impedir que pessoas cheguem à tela de cadastro. Ajuste-a conforme o público pretendido; o pacote não muda políticas existentes.

## 5. Primeiro acesso

Abra `/cadastro.php`, informe nome, e-mail e senha e faça o login. Não existe senha padrão nem conta administrativa criada automaticamente. O endereço do administrador é um contato de suporte, não uma forma de ganhar privilégios cadastrando esse e-mail.

Para esta versão, operações administrativas ficam restritas ao servidor/console do banco, sob controle de quem tem acesso à infraestrutura. Não foi incluído painel administrativo web.

## 6. Atender uma perda de acesso

O usuário abre “Perdi meu acesso”, informa seus dados e clica em “Preparar e-mail de ajuda”. O aplicativo de e-mail é aberto, mas **nenhuma mensagem é enviada sem a ação do usuário**. Se ele usa somente webmail, pode enviar manualmente ao endereço exibido.

Depois que você receber a solicitação, confirme a identidade por um canal confiável e gere o link no servidor:

```powershell
php .\tools\gerar-recuperacao.php usuario@empresa.com.br
```

Envie ao titular o link retornado. O comando não envia e-mail. O link expira em 30 minutos; gerar outro invalida o anterior. A senha será definida pela própria pessoa, e todas as sessões anteriores serão invalidadas.

Se a pessoa também perdeu acesso ao e-mail, valide sua identidade antes de alterar o endereço no banco. Não altere dados só porque alguém preencheu o formulário de ajuda.

O token sai da barra de endereço após o primeiro carregamento, mas pode aparecer no log da requisição inicial. Configure os logs IIS/Cloudflare para não armazenar a query string de `/redefinir.php`, restrinja acesso a logs e não encaminhe links de recuperação a terceiros.

## 7. Manutenção

Agende diariamente no Agendador de Tarefas:

```powershell
php D:\dba\PHP\calculadora-progress\tools\limpar-expirados.php
```

Para bloquear uma conta pelo SQL Editor do Neon:

```sql
UPDATE calc_auth.users
SET active = FALSE, session_version = session_version + 1
WHERE email = 'usuario@empresa.com.br';
```

As sessões serão rejeitadas na próxima requisição. Nunca solicite ou envie a senha atual do usuário.

## Teste local antes da publicação

Somente para testes no próprio servidor, ajuste temporariamente `environment` para `local` e `app_url` para `http://127.0.0.1:8088`. O cookie não terá Secure nesse modo. Restaure `production` e a URL HTTPS antes de expor o hostname público.

## Estado da entrega

Os dados do seu Neon, domínio Cloudflare e configuração do servidor não foram fornecidos neste trabalho. O pacote não cria recursos na sua conta nem afirma que já está publicado. Consulte `VALIDACAO.md` para os testes executados no ambiente de desenvolvimento.

## Referências

- [Neon: conexão segura](https://neon.com/docs/connect/connect-securely)
- [Cloudflare Tunnel](https://developers.cloudflare.com/cloudflare-one/networks/connectors/cloudflare-tunnel/)
- [PHP: password_hash](https://www.php.net/manual/en/function.password-hash.php)

# Validação da entrega

- Todos os arquivos PHP passaram no verificador de sintaxe PHP 8.3.
- Testes HTTP locais com PHP e PostgreSQL compatível via PGlite: acesso protegido, CSRF, cadastro, duplicidade, hash de senha, login correto/incorreto, logout, limites, conta bloqueada, recuperação, token expirado e uso único, revogação de sessões.
- Os três blocos de saída da calculadora (PF, linha de script e script de carga) foram comparados com a versão anterior para a mesma entrada, sem diferenças.
- A comparação do fonte confirmou que os cálculos foram preservados; as mudanças no index são a proteção de acesso, token CSRF e barra do usuário.
- Docker não está disponível neste ambiente: a imagem e a implantação Render não foram executadas. A conexão TLS ao seu Neon e o domínio Cloudflare precisam ser conferidos após sua configuração.
- Configuração local de testes e sessões não estão no ZIP.

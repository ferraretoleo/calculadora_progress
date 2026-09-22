# Calculadora Progress — implantação manual

Caminho recomendado: **Render (PHP) + Neon (PostgreSQL) + Cloudflare (DNS do seu domínio)**.
Abra `PASSO-A-PASSO-RENDER.md` e siga na ordem. Não é necessário instalar o plugin Neon no ChatGPT.

O pacote inclui a calculadora existente com login, cadastro, sair, ajuda e redefinição de senha assistida pelo administrador. O cadastro libera acesso imediatamente; não exige confirmação de e-mail ou aprovação. O administrador é contatado em leoferrareto2013@gmail.com. A ajuda abre o aplicativo de e-mail; não envia automaticamente.

Para hospedar no seu Windows/IIS em vez do Render, consulte `INSTALACAO.md`.
Nunca disponibilize a cópia antiga sem login, nem publique a raiz inteira do projeto como pasta web. O Docker já publica apenas `public`.

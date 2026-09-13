# Deploy do PDVLoja em produção (VPS com Docker)

## O que você precisa ter no servidor
- Docker e Docker Compose instalados (`docker --version` e `docker compose version`).
- Uma pasta livre, por exemplo `/opt/pdvloja`.

## 1. Copiar o projeto para o servidor
Do seu PC, envie a pasta inteira (exceto `node_modules`, `vendor`, `admin/dist` — eles são gerados no próprio servidor):

```bash
rsync -av --exclude node_modules --exclude vendor --exclude admin/dist \
  "D:/Sistemas/PDVLoja/" usuario@SEU_SERVIDOR:/opt/pdvloja/
```

(Se preferir, pode zipar a pasta e subir por SFTP/painel do provedor — o importante é que `docker-compose.prod.yml`, a pasta `docker/`, `admin/`, `app/`, `database/`, `deploy/` e os arquivos `.env.production.example` cheguem no servidor.)

## 2. Configurar o `.env` do Laravel
No servidor, dentro da pasta do projeto:

```bash
cp .env.production.example .env
```

Edite o `.env` e ajuste:
- `APP_URL` → `http://SEU_DOMINIO_OU_IP:APP_PORT` (ou `https://...` se já tiver certificado e proxy na frente)
- `DB_PASSWORD` e `DB_ROOT_PASSWORD` → senhas fortes (troque os placeholders `TROQUE_ESTA_SENHA...`)
- `APP_PORT` → porta pública do Nginx. O exemplo já vem com uma porta alta pouco usada (`47281`) para evitar conflito com outras aplicações do servidor, mas confira antes se ela está livre: `sudo ss -ltnp | grep 47281`. Se preferir, escolha outra porta alta (acima de 10000) que não apareça na saída de `sudo ss -ltnp`.

> `db` (MariaDB) e `redis` **não** expõem porta nenhuma para fora do servidor no `docker-compose.prod.yml` — só são acessíveis entre os containers pela rede interna `pdvloja`. Só o Nginx (`webserver`) fica exposto, então é a única porta com risco de conflito.

Gere a chave da aplicação (não reaproveite a de desenvolvimento):

```bash
docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate
```

## 3. Buildar o painel (frontend)

```bash
sh deploy/build-frontend.sh
```

Isso gera `admin/dist/`, que o Nginx de produção serve diretamente (sem precisar do Vite dev server).

## 4. Subir os containers

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Isso sobe: `app` (PHP-FPM), `webserver` (Nginx, única porta exposta), `db` (MariaDB) e `redis` — todos só acessíveis entre si pela rede interna do Docker; nada além do Nginx fica exposto na internet.

## 5. Importar o banco inicial
O arquivo `deploy/pdvloja_dump_inicial.sql` já vem com as tabelas todas criadas e **só** a empresa + o usuário administrador (login `admin`, senha `@@123admin`) — nenhum dado de teste.

```bash
docker exec -i pdvloja_db mariadb -uroot -p"SUA_DB_ROOT_PASSWORD" pdvloja < deploy/pdvloja_dump_inicial.sql
```

(Troque `SUA_DB_ROOT_PASSWORD` pelo valor que você colocou em `DB_ROOT_PASSWORD` no `.env`.)

## 6. Conferir
Rode as migrations por garantia (não deve mudar nada, é só uma checagem de sincronismo):

```bash
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

Acesse `http://SEU_DOMINIO_OU_IP:APP_PORT` no navegador (a porta que você definiu no `.env`) — deve abrir a tela de login do NovaPDV.

## 7. IMPORTANTE — troque a senha do admin
A senha `@@123admin` é a mesma usada no ambiente de desenvolvimento. **Assim que logar pela primeira vez em produção, troque essa senha** (ainda não existe uma tela pronta para isso no painel — me avise que eu faço rapidinho, ou troque direto no banco por enquanto).

## Backups
O banco fica no volume Docker `pdvloja_db_data`. Faça backup periódico com:

```bash
docker exec pdvloja_db mariadb-dump -uroot -p"SUA_DB_ROOT_PASSWORD" pdvloja > backup_$(date +%Y%m%d).sql
```

## Próximos passos que ficaram de fora deste pacote
- **HTTPS**: hoje sobe em HTTP puro. Com um domínio apontado pro servidor, dá pra colocar um Certbot/Traefik na frente do Nginx depois.
- Emissão de nota fiscal (NFePHP) e as telas que ainda são só demonstração (Fornecedores, Usuários, Permissões, Categorias, Marcas, Movimentações, Certificado A1/Config Fiscal) continuam pendentes, como já conversamos.

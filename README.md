# NovaPDV

Sistema de PDV (ponto de venda) e gestão de loja: vendas, estoque, financeiro, fiscal (NFC-e/NF-e) e relatórios, multiempresa.

- **Backend/API:** Laravel 13 (PHP 8.3)
- **Painel (frontend):** Vue 3 + Vite + Tailwind CSS (pasta `admin/`)
- **Banco de dados:** MariaDB 11
- **Cache/Fila/Sessão:** Redis
- **Infra local:** Docker Compose

## Módulos

- **PDV** — nova venda, histórico de vendas, devoluções
- **Produtos** — cadastro, categorias, marcas, estoque, movimentações
- **Clientes** e **Fornecedores**
- **Financeiro** — contas a pagar/receber, fluxo de caixa
- **Fiscal** — NFC-e, NF-e, notas emitidas/canceladas, contingência
- **Relatórios** — vendas, produtos, estoque, financeiro, fiscal
- **Configurações** — empresa, usuários, vendedores, permissões, certificado A1, configurações fiscais

> Algumas telas listadas acima ainda são apenas demonstração (dados mockados no frontend) e a emissão fiscal via NFePHP ainda não está integrada. Consulte o histórico do projeto para o status atualizado de cada módulo.

## Rodando em desenvolvimento (Docker)

Pré-requisitos: Docker e Docker Compose.

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

O `db:seed` cria a empresa padrão e o usuário administrador inicial (veja as credenciais em [`DEPLOY.md`](./DEPLOY.md)).

> Troque a senha padrão assim que possível — ainda não há tela pronta para isso no painel, mas pode ser alterada direto no banco (tabela `users`).

Serviços expostos (portas padrão, ajustáveis no `.env`):

| Serviço          | URL                                            |
| ---------------- | ----------------------------------------------- |
| Aplicação (Nginx)| http://localhost:8090 (`APP_PORT`)               |
| Painel (Vite dev)| http://localhost:5180 (`FORWARD_FRONTEND_PORT`)  |
| Adminer          | http://localhost:8081 (`FORWARD_ADMINER_PORT`)   |
| Mailpit          | http://localhost:8025 (`FORWARD_MAILPIT_PORT`)   |

O container `frontend` já roda `npm install && npm run dev` para o painel Vue. Para buildar os assets do backend (Blade/Vite), rode `npm install && npm run dev` (ou `build`) na raiz do projeto quando necessário.

## Rodando sem Docker

```bash
composer install
cp .env.example .env
php artisan key:generate
# ajuste DB_* no .env para seu MySQL/MariaDB local
php artisan migrate --seed
npm install && npm run build   # assets do backend
cd admin && npm install && npm run dev   # painel Vue
```

## Testes

```bash
composer test
# ou
php artisan test
```

## Deploy em produção

Veja [`DEPLOY.md`](./DEPLOY.md) — inclui `docker-compose.prod.yml`, build do painel para produção e o dump inicial do banco (`deploy/pdvloja_dump_inicial.sql`, útil apenas quando as tabelas ainda não existem; se já rodou `migrate`, prefira `php artisan db:seed`).

## Estrutura do projeto

```
app/            Modelos, lógica de domínio (Laravel)
routes/         Rotas web/api/console
database/       Migrations, factories e seeders
admin/          Painel administrativo (Vue 3 + Vite)
docker/         Dockerfiles e configs (PHP, Nginx)
deploy/         Scripts e dump SQL para deploy em produção
tests/          Testes automatizados (PHPUnit)
```

<p align="center">
  <img src="./logo.png" alt="NovaPDV" width="320">
</p>

<h3 align="center">NovaPDV</h3>

<p align="center">
  Sistema de PDV (ponto de venda) e gestão de loja: vendas, estoque, financeiro, fiscal (NFC-e/NF-e) e relatórios, multiempresa.
</p>

<p align="center">
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white">
  <img alt="Vue" src="https://img.shields.io/badge/Vue-3-4FC08D?logo=vue.js&logoColor=white">
  <img alt="MariaDB" src="https://img.shields.io/badge/MariaDB-11-003545?logo=mariadb&logoColor=white">
  <img alt="Docker" src="https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white">
</p>

---

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

### Portas expostas e risco de conflito no servidor

O `docker-compose.yml` (desenvolvimento) e o `docker-compose.prod.yml` (produção) expõem coisas bem diferentes pro host. Isso importa quando você sobe a stack num servidor que já roda outras aplicações: só porta exposta pro host pode conflitar com algo que já está rodando lá.

**Em desenvolvimento** (`docker-compose.yml`) — pensado pra ambiente isolado na sua máquina, então expõe tudo pra facilitar debug/acesso direto:

| Serviço            | Container            | Porta padrão | Variável no `.env`       |
| ------------------ | --------------------- | ------------ | ------------------------- |
| Nginx (app)         | `pdvloja_webserver`    | 8090         | `APP_PORT`                |
| MariaDB             | `pdvloja_db`           | 3307         | `FORWARD_DB_PORT`         |
| Redis               | `pdvloja_redis`        | 6380         | `FORWARD_REDIS_PORT`      |
| Mailpit (SMTP fake) | `pdvloja_mailpit`      | 8025 / 1025  | `FORWARD_MAILPIT_PORT`    |
| Adminer             | `pdvloja_adminer`      | 8081         | `FORWARD_ADMINER_PORT`    |
| Painel (Vite dev)   | `pdvloja_frontend`     | 5180         | `FORWARD_FRONTEND_PORT`   |

**Em produção** (`docker-compose.prod.yml`) — deliberadamente fechado: **só o Nginx tem porta publicada pro host**. `db` e `redis` não têm nenhuma seção `ports:` no arquivo, então só são alcançáveis pelos outros containers, dentro da rede interna `pdvloja` — de fora do servidor (ou até por outros containers fora dessa rede) eles são invisíveis.

| Serviço  | Container           | Exposto pro host?             | Variável        |
| -------- | -------------------- | ------------------------------ | ---------------- |
| Nginx    | `pdvloja_webserver`   | Sim — única porta pública      | `APP_PORT` (padrão `47281`) |
| app (PHP)| `pdvloja_app`         | Não (fala com o Nginx via rede interna) | — |
| MariaDB  | `pdvloja_db`          | Não                             | — |
| Redis    | `pdvloja_redis`       | Não                             | — |

Ou seja: o único ponto de conflito possível é `APP_PORT`. O valor padrão já vem como uma porta alta pouco usada (`47281`) em vez de `80`, mas antes de subir vale checar se ela está mesmo livre no servidor:

```bash
sudo ss -ltnp | grep 47281
```

Se aparecer algo, escolha outra porta alta (acima de 10000, por exemplo) e defina `APP_PORT=<porta>` no `.env` do servidor — não precisa editar o `docker-compose.prod.yml`.

Se quiser confirmar isso você mesmo a qualquer momento, sem precisar ler o YAML:

```bash
docker compose -f docker-compose.prod.yml config   # mostra a config resolvida, incluindo ports:
docker port pdvloja_db                              # não deve imprimir nada
docker port pdvloja_redis                           # não deve imprimir nada
docker port pdvloja_webserver                        # deve imprimir só a porta do APP_PORT
```

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

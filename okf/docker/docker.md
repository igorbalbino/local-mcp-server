# Docker — Empacotamento e execução

## Contexto

Runtime oficial da v1: imagem PHP 8.4 CLI Alpine + servidor built-in apontando para `public/`, porta **8080**. Compose monta volumes de `storage/` e injeta `.env`.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [server](../server/server.md) | Entry `public/index.php`; health `/health` |
| [config](../config/config.md) | `env_file: .env` |
| [overview](../overview/overview.md) | Deployment alvo do produto |

## Arquivos

| Arquivo | Papel |
|---------|-------|
| `Dockerfile` | Multi-stage: `composer:2` (vendor) → `php:8.4-cli-alpine` |
| `docker-compose.yml` | Serviço `jarvis-mcp`, porta `${JARVIS_PORT:-8080}:8080`, healthcheck |
| `.dockerignore` | Exclui `.env`, `vendor/`, testes, caches |

## Comandos úteis

```bash
cp .env.example .env
docker compose up --build -d
curl http://localhost:8080/health
```

Build avulso:

```bash
docker build -t jarvis-mcp-server:latest .
docker run --env-file .env -p 8080:8080 jarvis-mcp-server:latest
```

## Detalhes do Dockerfile

| Stage | O que faz |
|-------|-----------|
| `vendor` | `composer install --no-dev`, depois `dump-autoload` com o código fonte |
| `runtime` | Copia `/app`, cria `storage/*`, user `www-data`, `CMD php -S 0.0.0.0:8080 -t public public/index.php` |

Healthcheck: `wget http://127.0.0.1:8080/health`.

## Volumes (compose)

- `./storage/logs` → logs Monolog
- `./storage/cache` → sessions MCP (`FileSessionStore`)

## Evolução futura

Path documentado no README para migrar de PHP built-in para **nginx + php-fpm** sem mudar a camada de aplicação (`public/index.php` permanece o front controller).

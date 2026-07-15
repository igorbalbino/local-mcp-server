# Jarvis MCP Server

Generic [Model Context Protocol](https://modelcontextprotocol.io) server that exposes modular tools to any AI agent (Cursor, Claude, Gemini, OpenAI, Ollama, llama.cpp, etc.). Credentials for external services stay on the server and are never sent to the model.

## Features

- Official PHP MCP SDK (`mcp/sdk`) with Streamable HTTP transport
- Bearer API Key authentication between MCP clients and Jarvis
- Pluggable tools: Home Assistant, SearXNG, Browserless, Meilisearch (RAG), LibreTranslate
- Configuration via environment variables
- Docker-ready (PHP 8.4)

## Quick start

```bash
cp .env.example .env
# set JARVIS_API_KEYS and enable the tools you need

composer install
docker compose up --build -d
```

Health check (no auth):

```bash
curl http://localhost:8080/health
```

MCP endpoint: `http://localhost:8080/` (or `/mcp` via the same entrypoint — all non-health routes go to MCP).

### Cursor / MCP client

```json
{
  "mcpServers": {
    "jarvis": {
      "url": "http://localhost:8080",
      "headers": {
        "Authorization": "Bearer change-me-to-a-secure-key"
      }
    }
  }
}
```

Use the same value as `JARVIS_API_KEYS` in `.env`.

Without Docker:

```bash
composer install
cp .env.example .env
php -S 0.0.0.0:8080 -t public public/index.php
```

## Environment variables

| Variable | Description |
|----------|-------------|
| `JARVIS_API_KEYS` | Comma-separated API keys for client auth |
| `MCP_SERVER_NAME` / `MCP_SERVER_VERSION` | Server identity |
| `LOG_LEVEL` | Monolog level (`info`, `debug`, …) |
| `ENABLE_HOME_ASSISTANT` + `HA_URL` + `HA_TOKEN` | Home Assistant |
| `ENABLE_SEARXNG` + `SEARXNG_URL` + `SEARXNG_API_KEY` | SearXNG |
| `ENABLE_BROWSERLESS` + `BROWSERLESS_URL` + `BROWSERLESS_TOKEN` | Browserless |
| `ENABLE_MEILISEARCH` + `MEILI_URL` + `MEILI_KEY` + `MEILI_INDEX` | Meilisearch RAG |
| `ENABLE_LIBRETRANSLATE` + `LIBRETRANSLATE_URL` + `LIBRETRANSLATE_API_KEY` | LibreTranslate |

## Tools

| Tool | Integration | Purpose |
|------|-------------|---------|
| `ha_list_states` | Home Assistant | List entity states |
| `ha_get_state` | Home Assistant | Get one entity |
| `ha_call_service` | Home Assistant | Call a service |
| `web_search` | SearXNG | Web search |
| `browser_screenshot` | Browserless | Screenshot as base64 |
| `browser_pdf` | Browserless | PDF as base64 |
| `browser_content` | Browserless | Rendered HTML |
| `rag_search` | Meilisearch | Search indexed docs |
| `rag_index_document` | Meilisearch | Index a document |
| `translate` | LibreTranslate | Translate text |

A tool is registered only when its `ENABLE_*` flag is true and required URL (and token where needed) is set.

## Adding a new tool

1. Create a Guzzle client in `src/Clients/` (secrets stay here).
2. Create a class in `src/Tools/YourService/` implementing `ToolInterface` (or extending `AbstractTool`).
3. Register the class in `config/tools.php`.
4. Wire construction in `src/Core/ServiceProvider.php`.
5. Document env vars in `.env.example`.

No changes to the MCP protocol bootstrap are required beyond the registry map.

## Project layout

```
src/
  Core/          Config, Container, ToolRegistry, ServiceProvider
  Auth/          API Key authenticator + PSR-15 middleware
  Clients/       HTTP clients for external services
  Tools/         MCP tool modules
  Contracts/     Interfaces
  Exceptions/    Typed errors
  Server.php     Facade (health + MCP)
public/index.php
config/tools.php
okf/             Knowledge docs by feature (see okf/README.md)
```

## Tests

```bash
composer install
composer test
```

## Docker image

```bash
docker build -t jarvis-mcp-server:latest .
docker run --env-file .env -p 8080:8080 jarvis-mcp-server:latest
```

For production behind a reverse proxy, terminate TLS at the proxy and forward to port 8080.

## License

MIT

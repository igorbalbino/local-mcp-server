# Local MCP Server

Generic [Model Context Protocol](https://modelcontextprotocol.io) server that exposes modular tools to any AI agent (Cursor, Claude, Gemini, OpenAI, Ollama, llama.cpp, etc.). Credentials for external services stay on the server and are never sent to the model.

**Image:** [`ghcr.io/igorbalbino/local-mcp`](https://github.com/igorbalbino/local-mcp-server/pkgs/container/local-mcp)

## Features

- Official PHP MCP SDK (`mcp/sdk`) with Streamable HTTP transport
- Bearer API Key authentication between MCP clients and the server
- Pluggable tools: Home Assistant, SearXNG, Browserless, Meilisearch (RAG), LibreTranslate
- Configuration via environment variables
- Published to GitHub Container Registry (GHCR)

## Quick start (published image)

```yaml
services:
  local-mcp:
    image: ghcr.io/igorbalbino/local-mcp:latest
    container_name: local-mcp
    restart: unless-stopped
    ports:
      - "8090:8080"
    env_file:
      - .env
    networks:
      - local-mcp

networks:
  local-mcp:
```

```bash
cp .env.example .env
# set LOCAL_MCP_API_KEYS and enable the tools you need
docker compose up -d
```

Or use the repo compose file:

```bash
docker compose up -d
```

### Local development (build)

```bash
docker compose -f compose.dev.yml up --build -d
```

Health check (no auth):

```bash
curl http://localhost:8090/health
# {"name":"Local MCP Server","version":"0.1.0"}
```

MCP endpoint: `http://localhost:8090/` with header `Authorization: Bearer <LOCAL_MCP_API_KEYS>`.

### Cursor / MCP client

```json
{
  "mcpServers": {
    "local-mcp": {
      "url": "http://localhost:8090",
      "headers": {
        "Authorization": "Bearer change-me-to-a-secure-key"
      }
    }
  }
}
```

Without Docker:

```bash
composer install
cp .env.example .env
php -S 0.0.0.0:8080 -t public public/index.php
```

## Versioning

The file [`VERSION`](VERSION) holds the semver (currently `0.1.0`). `/health` and the MCP `serverInfo` use it. GitHub Actions tags GHCR images with this value plus `latest` on the default branch.

## Environment variables

| Variable | Description |
|----------|-------------|
| `LOCAL_MCP_API_KEYS` | Comma-separated API keys for client auth |
| `MCP_SERVER_NAME` / `MCP_SERVER_VERSION` | Optional overrides (defaults: name + `VERSION` file) |
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

## Project layout

```
local-mcp-server/
├── src/
├── public/
├── config/
├── storage/
├── tests/
├── okf/
├── docker/
│   └── php/
│       └── Dockerfile
├── .github/workflows/docker.yml
├── docker-compose.yml
├── compose.dev.yml
├── VERSION
├── LICENSE
├── composer.json
└── README.md
```

Feature docs: [`okf/README.md`](okf/README.md).

## Tests

```bash
composer install
composer test
```

## Pull the image

```bash
docker pull ghcr.io/igorbalbino/local-mcp:latest
# or a pinned version
docker pull ghcr.io/igorbalbino/local-mcp:0.1.0
```

GHCR packages from this repo may be private until you make the package public in GitHub → Packages.

## License

[MIT](LICENSE)

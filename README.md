# Local MCP Server

Generic [Model Context Protocol](https://modelcontextprotocol.io) server for AI agents and Home Assistant. Credentials for external services stay on the server and are never sent to the model.

**Image:** [`ghcr.io/igorbalbino/local-mcp`](https://github.com/igorbalbino/local-mcp-server/pkgs/container/local-mcp)

**MCP endpoint:** `/mcp` (Streamable HTTP)

## Connect in 1 minute

### Home Assistant (MCP client)

Home Assistant only asks for a **URL** (no Bearer field). Put the API key in the path:

1. Set `LOCAL_MCP_API_KEYS` in `.env` (URL-safe: letters, numbers, `-`, `_`).
2. Settings → Devices & services → Add integration → **Model Context Protocol**.
3. Server URL:

```text
http://local-mcp:8080/mcp/YOUR_API_KEY
```

Use a hostname/IP that Home Assistant can reach (same Docker network → `http://local-mcp:8080/mcp/...`, or the host IP/port mapped, e.g. `http://192.168.x.x:8090/mcp/...`).

4. Enable the tools you want in `.env` (`ENABLE_SEARXNG=true`, etc.) and configure your conversation agent to use MCP tools.

If the server is on a fully trusted LAN and you want zero secrets in the URL:

```env
LOCAL_MCP_AUTH_MODE=none
```

Then use `http://local-mcp:8080/mcp`.

### Cursor / other agents (Bearer header)

```json
{
  "mcpServers": {
    "local-mcp": {
      "url": "http://localhost:8090/mcp",
      "headers": {
        "Authorization": "Bearer YOUR_API_KEY"
      }
    }
  }
}
```

### Auth cheat sheet

| Client | How to auth |
|--------|-------------|
| Home Assistant | `http://host:8090/mcp/<API_KEY>` |
| Cursor / Inspector | `Authorization: Bearer <API_KEY>` on `/mcp` |
| Any HTTP client | `?api_key=<API_KEY>` also works |

`LOCAL_MCP_AUTH_MODE`: `auto` (default) · `none` · `bearer`

## Quick start (Docker)

```bash
cp .env.example .env
# set LOCAL_MCP_API_KEYS + ENABLE_* tools
docker compose up -d
curl http://localhost:8090/health
```

Published image:

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

Dev build: `docker compose -f compose.dev.yml up --build -d`

## Features

- Streamable HTTP MCP (`mcp/sdk`) at `/mcp`
- Plug-and-play with Home Assistant via `/mcp/<api-key>`
- Bearer API key for Cursor and other clients
- Tools: Home Assistant REST, SearXNG, Browserless, Meilisearch (RAG), LibreTranslate
- Config via `.env`; secrets never returned to the model

## Environment variables

| Variable | Description |
|----------|-------------|
| `LOCAL_MCP_API_KEYS` | API keys (comma-separated, URL-safe) |
| `LOCAL_MCP_AUTH_MODE` | `auto` / `none` / `bearer` |
| `MCP_SERVER_NAME` / `MCP_SERVER_VERSION` | Optional identity overrides |
| `ENABLE_HOME_ASSISTANT` + `HA_URL` + `HA_TOKEN` | HA REST tools (`ha_*`) |
| `ENABLE_SEARXNG` + `SEARXNG_URL` + … | Web search |
| `ENABLE_BROWSERLESS` + … | Screenshots / PDF / HTML |
| `ENABLE_MEILISEARCH` + … | RAG |
| `ENABLE_LIBRETRANSLATE` + … | Translate |

`HA_*` is only for the **optional** tools that call Home Assistant’s REST API. Connecting HA as an **MCP client** to this server uses `LOCAL_MCP_API_KEYS` (path or open mode), not `HA_TOKEN`.

## Tools

| Tool | Integration |
|------|-------------|
| `ha_list_states`, `ha_get_state`, `ha_call_service` | Home Assistant REST |
| `web_search` | SearXNG |
| `browser_screenshot`, `browser_pdf`, `browser_content` | Browserless |
| `rag_search`, `rag_index_document` | Meilisearch |
| `translate` | LibreTranslate |

## Versioning

[`VERSION`](VERSION) → `/health` returns `{"name":"Local MCP Server","version":"0.1.0","mcp":"/mcp"}`.

## Docs

Feature knowledge base: [`okf/README.md`](okf/README.md) (see especially [`okf/auth/auth.md`](okf/auth/auth.md)).

## License

[MIT](LICENSE)

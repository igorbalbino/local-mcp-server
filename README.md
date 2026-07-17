# Local MCP Server

Generic [Model Context Protocol](https://modelcontextprotocol.io) server for AI agents and Home Assistant. Credentials for external services stay on the server and are never sent to the model.

**Image:** [`ghcr.io/igorbalbino/local-mcp`](https://github.com/igorbalbino/local-mcp-server/pkgs/container/local-mcp)

**MCP endpoint:** `/mcp` (Streamable HTTP — POST JSON-RPC + GET SSE)

## Connect in 1 minute

### Home Assistant (MCP client)

Home Assistant **≥ 2026.2** talks **Streamable HTTP** to `/mcp` first (the official docs still mention SSE; that is legacy fallback only). HA only asks for a **URL** (no Bearer field). Put the API key in the path, or leave OAuth Client ID/Secret empty.

1. Set `LOCAL_MCP_API_KEYS` in `.env` (URL-safe: letters, numbers, `-`, `_`), or use `LOCAL_MCP_AUTH_MODE=none` on a trusted LAN.
2. Settings → Devices & services → Add integration → **Model Context Protocol**.
3. Server URL (leave OAuth fields blank):

```text
http://local-mcp:8080/mcp/<YOUR_API_KEY>
```

Use a hostname/IP that Home Assistant can reach (same Docker network → `http://local-mcp:8080/mcp/...`, or the host IP/port mapped). Add that hostname to `LOCAL_MCP_ALLOWED_HOSTS` if it is not already covered (`localhost` and `local-mcp` are included by default).

4. Enable the tools you want in `.env` (`ENABLE_SEARXNG=true`, etc.).
5. Configure your **conversation agent** to use the MCP tools (adding the integration alone is not enough).

If the server is on a fully trusted LAN and you want zero secrets in the URL:

```env
LOCAL_MCP_AUTH_MODE=none
```

Then use `http://local-mcp:8080/mcp`.

Quick check that `initialize` returns a session (write JSON **without BOM** — on Windows PowerShell 5 use .NET, not `Set-Content -Encoding utf8`):

```powershell
[System.IO.File]::WriteAllText("$PWD\init.json", '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"curl","version":"0"}}}', [System.Text.UTF8Encoding]::new($false))
curl.exe -sS -D - -X POST "http://127.0.0.1:8090/mcp" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" --data-binary "@init.json"
```

Expect **200**, header **`Mcp-Session-Id`**, and an `InitializeResult` body (same JSON-RPC `id` as the request).

Then open the Streamable GET SSE channel (Home Assistant does this after initialize):

```powershell
curl.exe -N --max-time 3 -D - -X GET "http://127.0.0.1:8090/mcp" -H "Accept: text/event-stream" -H "Mcp-Session-Id: PASTE_SESSION_ID"
```

Expect **200** `text/event-stream` and a `: connected` comment (not **405**).

### Cursor / other agents (Bearer header)

```json
{
  "mcpServers": {
    "local-mcp": {
      "url": "http://localhost:8090/mcp",
      "headers": {
        "Authorization": "Bearer <YOUR_API_KEY>"
      }
    }
  }
}
```

### Auth cheat sheet

| Client | How to auth |
|--------|-------------|
| Home Assistant | `http://host:8090/mcp/<YOUR_API_KEY>` |
| Cursor / Inspector | `Authorization: Bearer <YOUR_API_KEY>` on `/mcp` |
| Any HTTP client | `?api_key=<YOUR_API_KEY>` also works when query location is enabled |

`LOCAL_MCP_AUTH_MODE`: `auto` (default) · `none` · `bearer`  
`LOCAL_MCP_AUTH_LOCATION`: `header,path,query` (default)

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
- Layered architecture: Transport · Protocol · Session · Middleware · Providers · Tools

## Environment variables

| Variable | Description |
|----------|-------------|
| `LOCAL_MCP_API_KEYS` | API keys (comma-separated, URL-safe) |
| `LOCAL_MCP_AUTH_MODE` | `auto` / `none` / `bearer` |
| `LOCAL_MCP_AUTH_LOCATION` | `header`, `path`, `query` (comma-separated) |
| `LOCAL_MCP_ALLOWED_HOSTS` | Extra Host/Origin allowlist (Docker DNS rebinding) |
| `LOCAL_MCP_CORS_ORIGINS` | CORS origins for MCP transport (`*` default) |
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

[`VERSION`](VERSION) → `/health` returns `{"status":"ok","name":"Local MCP Server","version":"0.1.0","mcp":"/mcp"}`.

## Docs

Feature knowledge base: [`okf/README.md`](okf/README.md) (see especially [`okf/auth/auth.md`](okf/auth/auth.md)).

## License

[MIT](LICENSE)

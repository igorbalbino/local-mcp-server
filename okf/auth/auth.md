# Auth — Autenticação cliente → Local MCP

## Contexto

Controla quem pode usar o endpoint MCP. Home Assistant (cliente MCP oficial) **não permite** configurar header Bearer na UI — só uma URL. Por isso o servidor aceita a API key de três jeitos (quando habilitados em `LOCAL_MCP_AUTH_LOCATION`).

## Formas de autenticar

| Método | Exemplo | Ideal para |
|--------|---------|------------|
| Bearer header | `Authorization: Bearer <key>` | Cursor, MCP Inspector, agents |
| Path token | `http://host:8090/mcp/<key>` | **Home Assistant** (colar URL) |
| Query | `http://host:8090/mcp?api_key=<key>` | proxies / fallback |

## Modos (`LOCAL_MCP_AUTH_MODE`)

| Modo | Comportamento |
|------|----------------|
| `auto` (default) | Sem keys → aberto; com keys → exige credencial |
| `none` | Sempre aberto (LAN confiável) |
| `bearer` | Sempre exige key (falha se não houver keys) |

## Locations (`LOCAL_MCP_AUTH_LOCATION`)

Default: `header,path,query`. Se `path` não estiver na lista, `/mcp/<segment>` responde **404** (não trata o segmento como key).

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [server](../server/server.md) | Pipeline Logging → Authentication → Transport |
| [config](../config/config.md) | `LOCAL_MCP_API_KEYS`, `LOCAL_MCP_AUTH_MODE`, `LOCAL_MCP_AUTH_LOCATION` |
| [contracts](../contracts/contracts.md) | `AuthenticatorInterface` |

## Arquivos, classes e funções

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Contracts/AuthenticatorInterface.php` | interface | `hasKeys()`, `isValidKey()`, `authenticate(?header)` |
| `src/Auth/ApiKeyAuthenticator.php` | `ApiKeyAuthenticator` | validação `hash_equals` das keys |
| `src/Auth/RequestAuthenticator.php` | `RequestAuthenticator` | `isRequired()`, `authorize(request, pathToken?)` |
| `src/Middleware/AuthenticationMiddleware.php` | `AuthenticationMiddleware` | PSR-15 gate (Bearer / path / query) |

OAuth fica como extension point futuro via `AuthenticatorInterface` — sem alterar o pipeline.

## Variáveis

- `LOCAL_MCP_API_KEYS` — keys URL-safe (`a-zA-Z0-9-_`)
- `LOCAL_MCP_AUTH_MODE` — `auto` \| `none` \| `bearer`
- `LOCAL_MCP_AUTH_LOCATION` — `header`, `path`, `query`

## Home Assistant (cliente MCP)

Na integração **Model Context Protocol** (HA ≥ 2026.2), informe só a URL Streamable em `/mcp`. Deixe OAuth Client ID/Secret **vazios**.

```text
http://local-mcp:8080/mcp/<YOUR_API_KEY>
```

(use o hostname/IP alcançável pelo HA e a mesma key do `.env`)

Depois de conectar, habilite as tools no **conversation agent**.

Teste Windows (JSON **sem BOM** — no PowerShell 5 use .NET):

```powershell
[System.IO.File]::WriteAllText("$PWD\init.json", '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25","capabilities":{},"clientInfo":{"name":"curl","version":"0"}}}', [System.Text.UTF8Encoding]::new($false))
curl.exe -sS -D - -X POST "http://127.0.0.1:8090/mcp" -H "Content-Type: application/json" -H "Accept: application/json, text/event-stream" --data-binary "@init.json"
```

Deve retornar header `Mcp-Session-Id`.

## Cursor

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

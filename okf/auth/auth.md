# Auth — Autenticação cliente → Local MCP

## Contexto

Controla quem pode usar o endpoint MCP. Home Assistant (cliente MCP oficial) **não permite** configurar header Bearer na UI — só uma URL. Por isso o servidor aceita a API key de três jeitos.

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

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [server](../server/server.md) | `Server::handle()` chama `RequestAuthenticator` |
| [config](../config/config.md) | `LOCAL_MCP_API_KEYS`, `LOCAL_MCP_AUTH_MODE` |
| [contracts](../contracts/contracts.md) | `AuthenticatorInterface` |

## Arquivos, classes e funções

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Contracts/AuthenticatorInterface.php` | interface | `hasKeys()`, `isValidKey()`, `authenticate(?header)` |
| `src/Auth/ApiKeyAuthenticator.php` | `ApiKeyAuthenticator` | validação `hash_equals` das keys |
| `src/Auth/RequestAuthenticator.php` | `RequestAuthenticator` | `isRequired()`, `authorize(request, pathToken?)` |
| `src/Auth/AuthMiddleware.php` | `AuthMiddleware` | PSR-15 (Bearer header) |

## Variáveis

- `LOCAL_MCP_API_KEYS` — keys URL-safe (`a-zA-Z0-9-_`)
- `LOCAL_MCP_AUTH_MODE` — `auto` \| `none` \| `bearer`

## Home Assistant (cliente MCP)

Na integração **Model Context Protocol** (HA ≥ 2026.2), informe só a URL Streamable em `/mcp`. A doc oficial ainda cita SSE; o core tenta Streamable primeiro. Deixe OAuth Client ID/Secret **vazios** (este server usa API key no path ou `LOCAL_MCP_AUTH_MODE=none`, não OAuth).

```text
http://local-mcp:8080/mcp/change-me-to-a-secure-key
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
        "Authorization": "Bearer change-me-to-a-secure-key"
      }
    }
  }
}
```

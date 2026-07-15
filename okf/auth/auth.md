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

Na integração **Model Context Protocol**, informe só a URL:

```text
http://local-mcp:8080/mcp/change-me-to-a-secure-key
```

(use o hostname/IP alcançável pelo HA e a mesma key do `.env`)

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

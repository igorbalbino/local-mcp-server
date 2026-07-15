# Overview — Arquitetura Jarvis MCP Server

## Contexto

O **Jarvis MCP Server** é um servidor [Model Context Protocol](https://modelcontextprotocol.io) genérico, independente do modelo de IA (Cursor, OpenAI, Gemini, Ollama, etc.). Ele expõe **tools** HTTP-authenticated para agentes, encapsulando integrações externas sem vazar secrets ao LLM.

Decisões centrais:

- SDK oficial `mcp/sdk` com transport **Streamable HTTP**
- Auth cliente→Jarvis por **API Key** (`Authorization: Bearer`)
- PHP 8.4+, Composer, Guzzle, sem framework pesado
- Módulos de tool independente (SOLID)

## Relacionamentos

| Depende de | Relação |
|------------|---------|
| [auth](../auth/auth.md) | Gate de entrada para rotas MCP |
| [core](../core/core.md) | Bootstrap, DI, config, registry |
| [server](../server/server.md) | Orquestra HTTP, health e MCP |
| [tools](../tools/tools.md) | Capacidades expostas ao agente |
| [clients](../clients/clients.md) | Chamadas autenticadas a serviços |
| [config](../config/config.md) | Feature flags e secrets via `.env` |
| [docker](../docker/docker.md) | Runtime de produção/local |

## Mapa de pastas principais

| Pasta / arquivo | Papel |
|-----------------|-------|
| `public/index.php` | Entry point HTTP (SAPI) |
| `src/Server.php` | Facade do servidor |
| `src/Core/` | Config, Container, ToolRegistry, ServiceProvider, Logger |
| `src/Auth/` | API Key + middleware PSR-15 |
| `src/Clients/` | HTTP clients por integração |
| `src/Tools/` | Tools MCP por domínio |
| `src/Contracts/` | Interfaces |
| `src/Exceptions/` | Erros tipados |
| `config/tools.php` | Lista de classes de tools |
| `.env` / `.env.example` | Configuração |

## Diagrama

```
AI Agent
   │  Bearer API Key
   ▼
public/index.php
   ▼
Jarvis\McpServer\Server
   ├─ /health          (sem auth)
   ├─ OPTIONS          (CORS)
   ├─ Auth             → ApiKeyAuthenticator
   └─ MCP Streamable HTTP
         └─ ToolRegistry → Tools → Clients → APIs externas
```

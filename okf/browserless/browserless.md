# Browserless

## Contexto

Renderização headless: screenshot, PDF e HTML de URLs. Token Browserless fica no client (`?token=` ou equivalente); tools retornam base64/HTML sem expor a URL autenticada.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [providers](../providers/providers.md) | `BrowserlessProvider` |
| [tools](../tools/tools.md) | Três tools browser_* |
| [config](../config/config.md) | `ENABLE_BROWSERLESS`, `BROWSERLESS_URL`, `BROWSERLESS_TOKEN` |

## Variáveis de ambiente

| Var | Uso |
|-----|-----|
| `ENABLE_BROWSERLESS` | Feature flag |
| `BROWSERLESS_URL` | Base URL (ex.: `http://browserless:3000`) |
| `BROWSERLESS_TOKEN` | Token da API |

Timeout do client: **60s** (render pode ser lento).

## Arquivos, classes e funções

### Provider

| Arquivo | Classe | Métodos |
|---------|--------|---------|
| `src/Providers/Browserless/BrowserlessProvider.php` | `BrowserlessProvider` | `screenshot(url, width?, height?)`, `pdf(url)`, `content(url)`, `tokenizedPath(path)` (privado) |

### Tools

| Arquivo | Classe | Nome MCP |
|---------|--------|----------|
| `src/Tools/Browserless/BrowserScreenshotTool.php` | `BrowserScreenshotTool` | `browser_screenshot` |
| `src/Tools/Browserless/BrowserPdfTool.php` | `BrowserPdfTool` | `browser_pdf` |
| `src/Tools/Browserless/BrowserContentTool.php` | `BrowserContentTool` | `browser_content` |

Respostas típicas:

- screenshot/pdf: `{ content_type, data_base64 }`
- content: `{ html }`

# LibreTranslate

## Contexto

Tradução de texto via API LibreTranslate. API key (se existir) é enviada no JSON como `api_key` pelo client — nunca aparece na resposta da tool ao modelo.

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [clients](../clients/clients.md) | `LibreTranslateClient` |
| [tools](../tools/tools.md) | `TranslateTool` |
| [config](../config/config.md) | `ENABLE_LIBRETRANSLATE`, `LIBRETRANSLATE_URL`, `LIBRETRANSLATE_API_KEY` |

## Variáveis de ambiente

| Var | Uso |
|-----|-----|
| `ENABLE_LIBRETRANSLATE` | Feature flag |
| `LIBRETRANSLATE_URL` | Base URL |
| `LIBRETRANSLATE_API_KEY` | Opcional |

## Arquivos, classes e funções

| Arquivo | Classe | Métodos / nome MCP |
|---------|--------|-------------------|
| `src/Clients/LibreTranslateClient.php` | `LibreTranslateClient` | `translate(text, source, target)` → `POST translate` |
| `src/Tools/LibreTranslate/TranslateTool.php` | `TranslateTool` | `translate` — args: `text`, `target`, `source` (default `auto`) |

Resposta tipada ao agente: `translatedText`, `detectedLanguage`, `source`, `target`.

# Meilisearch (RAG)

## Contexto

Camada de retrieval/indexação para RAG: buscar documentos (`rag_search`) e indexar (`rag_index_document`) em um índice Meilisearch (`MEILI_INDEX` por padrão).

## Relacionamentos

| Assunto | Relação |
|---------|---------|
| [providers](../providers/providers.md) | `MeilisearchProvider` |
| [tools](../tools/tools.md) | Duas tools RAG |
| [config](../config/config.md) | `ENABLE_MEILISEARCH`, `MEILI_URL`, `MEILI_KEY`, `MEILI_INDEX` |

## Variáveis de ambiente

| Var | Uso |
|-----|-----|
| `ENABLE_MEILISEARCH` | Feature flag |
| `MEILI_URL` | Base URL |
| `MEILI_KEY` | API key (Bearer) |
| `MEILI_INDEX` | Índice padrão (ex.: `documents`) |

`isConfigured()` exige URL e índice não vazio (key pode ser vazia em instâncias abertas de dev).

## Arquivos, classes e funções

| Arquivo | Classe | Métodos / nome MCP |
|---------|--------|-------------------|
| `src/Providers/Meilisearch/MeilisearchProvider.php` | `MeilisearchProvider` | `search()`, `indexDocument()`, `getDefaultIndex()`, `authHeaders()` |
| `src/Tools/Meilisearch/RagSearchTool.php` | `RagSearchTool` | `rag_search` — `query`, `index?`, `limit?` |
| `src/Tools/Meilisearch/RagIndexDocumentTool.php` | `RagIndexDocumentTool` | `rag_index_document` — `document` (object), `index?` |

Resposta de indexação resume `taskUid` / `status` sem dumpar documentos sensíveis de volta desnecessariamente.

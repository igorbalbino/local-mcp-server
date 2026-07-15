<?php

declare(strict_types=1);

use Jarvis\McpServer\Tools\Browserless\BrowserContentTool;
use Jarvis\McpServer\Tools\Browserless\BrowserPdfTool;
use Jarvis\McpServer\Tools\Browserless\BrowserScreenshotTool;
use Jarvis\McpServer\Tools\HomeAssistant\HaCallServiceTool;
use Jarvis\McpServer\Tools\HomeAssistant\HaGetStateTool;
use Jarvis\McpServer\Tools\HomeAssistant\HaListStatesTool;
use Jarvis\McpServer\Tools\LibreTranslate\TranslateTool;
use Jarvis\McpServer\Tools\Meilisearch\RagIndexDocumentTool;
use Jarvis\McpServer\Tools\Meilisearch\RagSearchTool;
use Jarvis\McpServer\Tools\Searxng\WebSearchTool;

/**
 * Tool class map. Each class is resolved from the container and registered
 * only when ToolInterface::isEnabled() returns true.
 *
 * @return list<class-string<\Jarvis\McpServer\Contracts\ToolInterface>>
 */
return [
    HaListStatesTool::class,
    HaGetStateTool::class,
    HaCallServiceTool::class,
    WebSearchTool::class,
    BrowserScreenshotTool::class,
    BrowserPdfTool::class,
    BrowserContentTool::class,
    RagSearchTool::class,
    RagIndexDocumentTool::class,
    TranslateTool::class,
];

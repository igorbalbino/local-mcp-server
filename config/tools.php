<?php

declare(strict_types=1);

use LocalMcp\Tools\Browserless\BrowserContentTool;
use LocalMcp\Tools\Browserless\BrowserPdfTool;
use LocalMcp\Tools\Browserless\BrowserScreenshotTool;
use LocalMcp\Tools\HomeAssistant\HaCallServiceTool;
use LocalMcp\Tools\HomeAssistant\HaGetStateTool;
use LocalMcp\Tools\HomeAssistant\HaListStatesTool;
use LocalMcp\Tools\LibreTranslate\TranslateTool;
use LocalMcp\Tools\Meilisearch\RagIndexDocumentTool;
use LocalMcp\Tools\Meilisearch\RagSearchTool;
use LocalMcp\Tools\Searxng\WebSearchTool;

/**
 * Tool class map. Each class is resolved from the container and registered
 * only when ToolInterface::isEnabled() returns true.
 *
 * @return list<class-string<\LocalMcp\Contracts\ToolInterface>>
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

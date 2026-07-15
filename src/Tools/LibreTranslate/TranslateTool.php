<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Tools\LibreTranslate;

use Jarvis\McpServer\Clients\LibreTranslateClient;
use Jarvis\McpServer\Core\Config;
use Jarvis\McpServer\Tools\AbstractTool;

final class TranslateTool extends AbstractTool
{
    public function __construct(Config $config, LibreTranslateClient $client)
    {
        parent::__construct($config, $client, 'ENABLE_LIBRETRANSLATE');
    }

    public function name(): string
    {
        return 'translate';
    }

    public function description(): string
    {
        return 'Translate text using LibreTranslate. Use source=auto to auto-detect the source language.';
    }

    public function inputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'text' => [
                    'type' => 'string',
                    'description' => 'Text to translate',
                ],
                'source' => [
                    'type' => 'string',
                    'description' => 'Source language code (e.g. en, pt) or auto',
                    'default' => 'auto',
                ],
                'target' => [
                    'type' => 'string',
                    'description' => 'Target language code (e.g. en, pt, es)',
                ],
            ],
            'required' => ['text', 'target'],
        ];
    }

    public function handle(array $arguments): string|array
    {
        /** @var LibreTranslateClient $client */
        $client = $this->client;
        $text = $this->requireString($arguments, 'text');
        $target = $this->requireString($arguments, 'target');
        $source = $this->optionalString($arguments, 'source') ?? 'auto';

        $result = $client->translate($text, $source, $target);

        return $this->json([
            'translatedText' => $result['translatedText'] ?? null,
            'detectedLanguage' => $result['detectedLanguage'] ?? null,
            'source' => $source,
            'target' => $target,
        ]);
    }
}

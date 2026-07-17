<?php

declare(strict_types=1);

namespace LocalMcp\Tools\LibreTranslate;

use LocalMcp\Providers\LibreTranslate\LibreTranslateProvider;
use LocalMcp\Core\Config;
use LocalMcp\Tools\AbstractTool;

final class TranslateTool extends AbstractTool
{
    public function __construct(Config $config, LibreTranslateProvider $provider)
    {
        parent::__construct($config, $provider, 'ENABLE_LIBRETRANSLATE');
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
        /** @var LibreTranslateProvider $provider */
        $provider = $this->provider;
        $text = $this->requireString($arguments, 'text');
        $target = $this->requireString($arguments, 'target');
        $source = $this->optionalString($arguments, 'source') ?? 'auto';

        $result = $provider->translate($text, $source, $target);

        return $this->json([
            'translatedText' => $result['translatedText'] ?? null,
            'detectedLanguage' => $result['detectedLanguage'] ?? null,
            'source' => $source,
            'target' => $target,
        ]);
    }
}

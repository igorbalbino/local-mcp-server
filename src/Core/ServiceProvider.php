<?php

declare(strict_types=1);

namespace Jarvis\McpServer\Core;

use Jarvis\McpServer\Auth\ApiKeyAuthenticator;
use Jarvis\McpServer\Clients\BrowserlessClient;
use Jarvis\McpServer\Clients\HomeAssistantClient;
use Jarvis\McpServer\Clients\LibreTranslateClient;
use Jarvis\McpServer\Clients\MeilisearchClient;
use Jarvis\McpServer\Clients\SearxngClient;
use Jarvis\McpServer\Contracts\AuthenticatorInterface;
use Jarvis\McpServer\Contracts\ToolInterface;
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
use Psr\Log\LoggerInterface;

final class ServiceProvider
{
    public function __construct(
        private readonly string $basePath,
    ) {
    }

    public function register(): Container
    {
        $container = new Container();
        $config = Config::fromEnv();

        $container->set(Config::class, static fn (): Config => $config);
        $container->set(LoggerInterface::class, fn (): LoggerInterface => LoggerFactory::create($config, $this->basePath));
        $container->set(AuthenticatorInterface::class, static fn (Container $c): AuthenticatorInterface => new ApiKeyAuthenticator($c->get(Config::class)));
        $container->set(ApiKeyAuthenticator::class, static fn (Container $c): ApiKeyAuthenticator => $c->get(AuthenticatorInterface::class));

        $container->set(HomeAssistantClient::class, static fn (Container $c): HomeAssistantClient => new HomeAssistantClient($c->get(Config::class)));
        $container->set(SearxngClient::class, static fn (Container $c): SearxngClient => new SearxngClient($c->get(Config::class)));
        $container->set(BrowserlessClient::class, static fn (Container $c): BrowserlessClient => new BrowserlessClient($c->get(Config::class)));
        $container->set(MeilisearchClient::class, static fn (Container $c): MeilisearchClient => new MeilisearchClient($c->get(Config::class)));
        $container->set(LibreTranslateClient::class, static fn (Container $c): LibreTranslateClient => new LibreTranslateClient($c->get(Config::class)));

        $toolClasses = require $this->basePath . '/config/tools.php';

        $container->set(ToolRegistry::class, function (Container $c) use ($toolClasses): ToolRegistry {
            $tools = [];
            foreach ($toolClasses as $class) {
                $tools[] = $this->resolveTool($c, $class);
            }

            return new ToolRegistry($tools);
        });

        return $container;
    }

    /**
     * @param class-string<ToolInterface> $class
     */
    private function resolveTool(Container $container, string $class): ToolInterface
    {
        $config = $container->get(Config::class);

        return match ($class) {
            HaListStatesTool::class => new HaListStatesTool($config, $container->get(HomeAssistantClient::class)),
            HaGetStateTool::class => new HaGetStateTool($config, $container->get(HomeAssistantClient::class)),
            HaCallServiceTool::class => new HaCallServiceTool($config, $container->get(HomeAssistantClient::class)),
            WebSearchTool::class => new WebSearchTool($config, $container->get(SearxngClient::class)),
            BrowserScreenshotTool::class => new BrowserScreenshotTool($config, $container->get(BrowserlessClient::class)),
            BrowserPdfTool::class => new BrowserPdfTool($config, $container->get(BrowserlessClient::class)),
            BrowserContentTool::class => new BrowserContentTool($config, $container->get(BrowserlessClient::class)),
            RagSearchTool::class => new RagSearchTool($config, $container->get(MeilisearchClient::class)),
            RagIndexDocumentTool::class => new RagIndexDocumentTool($config, $container->get(MeilisearchClient::class)),
            TranslateTool::class => new TranslateTool($config, $container->get(LibreTranslateClient::class)),
            default => throw new \InvalidArgumentException(sprintf('Unknown tool class: %s', $class)),
        };
    }
}

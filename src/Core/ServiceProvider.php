<?php

declare(strict_types=1);

namespace LocalMcp\Core;

use LocalMcp\Auth\ApiKeyAuthenticator;
use LocalMcp\Contracts\AuthenticatorInterface;
use LocalMcp\Contracts\ToolInterface;
use LocalMcp\Protocol\McpServerFacade;
use LocalMcp\Providers\Browserless\BrowserlessProvider;
use LocalMcp\Providers\HomeAssistant\HomeAssistantProvider;
use LocalMcp\Providers\LibreTranslate\LibreTranslateProvider;
use LocalMcp\Providers\Meilisearch\MeilisearchProvider;
use LocalMcp\Providers\SearXNG\SearXNGProvider;
use LocalMcp\Session\FileSessionStoreAdapter;
use LocalMcp\Session\SessionStoreInterface;
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
use LocalMcp\Transport\GetSseHandler;
use LocalMcp\Transport\TransportFactory;
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
        $container->set(ApiKeyAuthenticator::class, static function (Container $c): ApiKeyAuthenticator {
            $authenticator = $c->get(AuthenticatorInterface::class);
            if (!$authenticator instanceof ApiKeyAuthenticator) {
                throw new \RuntimeException('AuthenticatorInterface must be ApiKeyAuthenticator');
            }

            return $authenticator;
        });

        $container->set(SessionStoreInterface::class, fn (): SessionStoreInterface => FileSessionStoreAdapter::forBasePath($this->basePath));

        $container->set(HomeAssistantProvider::class, static fn (Container $c): HomeAssistantProvider => new HomeAssistantProvider($c->get(Config::class)));
        $container->set(SearXNGProvider::class, static fn (Container $c): SearXNGProvider => new SearXNGProvider($c->get(Config::class)));
        $container->set(BrowserlessProvider::class, static fn (Container $c): BrowserlessProvider => new BrowserlessProvider($c->get(Config::class)));
        $container->set(MeilisearchProvider::class, static fn (Container $c): MeilisearchProvider => new MeilisearchProvider($c->get(Config::class)));
        $container->set(LibreTranslateProvider::class, static fn (Container $c): LibreTranslateProvider => new LibreTranslateProvider($c->get(Config::class)));

        $toolClasses = require $this->basePath . '/config/tools.php';

        $container->set(ToolRegistry::class, function (Container $c) use ($toolClasses): ToolRegistry {
            $tools = [];
            foreach ($toolClasses as $class) {
                $tools[] = $this->resolveTool($c, $class);
            }

            return new ToolRegistry($tools);
        });

        $container->set(McpServerFacade::class, fn (Container $c): McpServerFacade => new McpServerFacade(
            $c->get(Config::class),
            $c->get(LoggerInterface::class),
            $c->get(ToolRegistry::class),
            $c->get(SessionStoreInterface::class),
            $c,
            $this->basePath,
        ));

        $container->set(GetSseHandler::class, static fn (Container $c): GetSseHandler => new GetSseHandler(
            $c->get(SessionStoreInterface::class),
            $c->get(LoggerInterface::class),
        ));

        $container->set(TransportFactory::class, static fn (Container $c): TransportFactory => new TransportFactory(
            $c->get(Config::class),
            $c->get(LoggerInterface::class),
            $c->get(McpServerFacade::class),
            $c->get(GetSseHandler::class),
        ));

        return $container;
    }

    /**
     * @param class-string<ToolInterface> $class
     */
    private function resolveTool(Container $container, string $class): ToolInterface
    {
        $config = $container->get(Config::class);

        return match ($class) {
            HaListStatesTool::class => new HaListStatesTool($config, $container->get(HomeAssistantProvider::class)),
            HaGetStateTool::class => new HaGetStateTool($config, $container->get(HomeAssistantProvider::class)),
            HaCallServiceTool::class => new HaCallServiceTool($config, $container->get(HomeAssistantProvider::class)),
            WebSearchTool::class => new WebSearchTool($config, $container->get(SearXNGProvider::class)),
            BrowserScreenshotTool::class => new BrowserScreenshotTool($config, $container->get(BrowserlessProvider::class)),
            BrowserPdfTool::class => new BrowserPdfTool($config, $container->get(BrowserlessProvider::class)),
            BrowserContentTool::class => new BrowserContentTool($config, $container->get(BrowserlessProvider::class)),
            RagSearchTool::class => new RagSearchTool($config, $container->get(MeilisearchProvider::class)),
            RagIndexDocumentTool::class => new RagIndexDocumentTool($config, $container->get(MeilisearchProvider::class)),
            TranslateTool::class => new TranslateTool($config, $container->get(LibreTranslateProvider::class)),
            default => throw new \InvalidArgumentException(sprintf('Unknown tool class: %s', $class)),
        };
    }
}

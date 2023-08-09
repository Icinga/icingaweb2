<?php

namespace Icinga\Application;

use DirectoryIterator;
use Icinga\Web\Request;
use Icinga\Web\Response;

require_once __DIR__ . '/Cli.php';

class Test extends Cli
{
    protected $isCli = false;

    /** @var Request */
    private $request;

    /** @var Response */
    private $response;

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        assert(isset($this->request), 'BaseTestCase should have set the request');

        return $this->request;
    }

    public function setResponse(Response $response): void
    {
        $this->response = $response;
    }

    public function getResponse(): Response
    {
        assert(isset($this->request), 'BaseTestCase should have set the response');

        return $this->response;
    }

    public function getFrontController()
    {
        return $this; // Callers are expected to only call getRequest or getResponse, hence the app should suffice
    }

    protected function bootstrap()
    {
        $this->assertRunningOnCli();
        $this->setupLogging()
            ->setupErrorHandling()
            ->loadLibraries()
            ->setupComposerAutoload()
            ->loadConfig()
            ->setupModuleAutoloaders()
            ->setupTimezone()
            ->prepareInternationalization()
            ->setupInternationalization()
            ->parseBasicParams()
            ->setupLogger()
            ->setupModuleManager()
            ->setupUserBackendFactory()
            ->setupFakeAuthentication();
    }

    public function setupAutoloader()
    {
        parent::setupAutoloader();

        if (($icingaLibDir = getenv('ICINGAWEB_ICINGA_LIB')) !== false) {
            $this->getLoader()->registerNamespace('Icinga', $icingaLibDir);
        }

        // Conflicts with `Tests\Icinga\Module\...\Lib`. But it seems it's not needed anyway...
        //$this->getLoader()->registerNamespace('Tests', $this->getBaseDir('test/php/library'));

        return $this;
    }

    protected function detectTimezone()
    {
        return 'UTC';
    }

    private function setupModuleAutoloaders(): self
    {
        $modulePaths = getenv('ICINGAWEB_MODULE_DIRS');

        if ($modulePaths) {
            $modulePaths = preg_split('/:/', $modulePaths, -1, PREG_SPLIT_NO_EMPTY);
        }

        if (! $modulePaths) {
            $modulePaths = $this->getAvailableModulePaths();
        }

        foreach ($modulePaths as $path) {
            foreach (new DirectoryIterator($path) as $item) {
                if ($item->isDot() || $item->isFile() || ! $item->isReadable()) {
                    continue;
                }

                $modulePath = $item->getPathname();
                $module = $item->getFilename();

                $moduleNamespace = 'Icinga\\Module\\' . ucfirst($module);
                $moduleLibraryPath = "$modulePath/library/" . ucfirst($module);

                if (is_dir($moduleLibraryPath)) {
                    $this
                        ->getLoader()
                        ->registerNamespace($moduleNamespace, $moduleLibraryPath, "$modulePath/application");
                }

                $moduleTestPath = "$modulePath/test/php/Lib";
                if (is_dir($moduleTestPath)) {
                    $this->getLoader()->registerNamespace('Tests\\' . $moduleNamespace . '\\Lib', $moduleTestPath);
                }
            }
        }

        return $this;
    }

    private function setupComposerAutoload(): self
    {
        $vendorAutoload = $this->getBaseDir('/vendor/autoload.php');
        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        }

        return $this;
    }
}

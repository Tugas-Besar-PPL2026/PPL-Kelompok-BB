<?php

namespace Tests;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Artisan;
use Laravel\Dusk\TestCase as BaseTestCase;
use PHPUnit\Framework\Attributes\BeforeClass;
use Symfony\Component\Process\Process;

abstract class DuskTestCase extends BaseTestCase
{
    protected static ?Process $serveProcess = null;

    protected static bool $migrated = false;

    /**
     * Prepare for Dusk test execution.
     */
    #[BeforeClass]
    public static function prepare(): void
    {
        static::configureDatabaseFallback();

        if (! static::runningInSail()) {
            static::startChromeDriver(['--port=9515']);
            static::ensureLocalServerStarted();
        }
    }

    protected function setUp(): void
    {
        static::configureDatabaseFallback();

        parent::setUp();

        if (! static::$migrated) {
            Artisan::call('migrate', ['--force' => true]);
            static::$migrated = true;
        }
    }

    protected static function configureDatabaseFallback(): void
    {
        $basePath = dirname(__DIR__);
        $sqlitePath = $basePath.DIRECTORY_SEPARATOR.'database'.DIRECTORY_SEPARATOR.'database.sqlite';

        $socket = @fsockopen('127.0.0.1', 3306, $errno, $errstr, 0.3);
        if (is_resource($socket)) {
            fclose($socket);

            return;
        }

        if (! file_exists($sqlitePath)) {
            @touch($sqlitePath);
        }

        $overrides = [
            'APP_URL' => 'http://127.0.0.1:8000',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $sqlitePath,
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
        ];

        foreach ($overrides as $key => $value) {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    protected static function ensureLocalServerStarted(): void
    {
        if (static::$serveProcess !== null && static::$serveProcess->isRunning()) {
            return;
        }

        $socket = @fsockopen('127.0.0.1', 8000, $errno, $errstr, 0.3);
        if (is_resource($socket)) {
            fclose($socket);

            return;
        }

        $basePath = dirname(__DIR__);
        $publicPath = $basePath.DIRECTORY_SEPARATOR.'public';
        $serverScript = $basePath.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'laravel'
            .DIRECTORY_SEPARATOR.'framework'.DIRECTORY_SEPARATOR.'src'.DIRECTORY_SEPARATOR.'Illuminate'
            .DIRECTORY_SEPARATOR.'Foundation'.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'server.php';

        static::$serveProcess = new Process(
            [PHP_BINARY, '-d', 'variables_order=EGPCS', '-S', '127.0.0.1:8000', $serverScript],
            $publicPath
        );
        static::$serveProcess->start();

        for ($i = 0; $i < 50; $i++) {
            $check = @fsockopen('127.0.0.1', 8000, $errno, $errstr, 0.1);
            if (is_resource($check)) {
                fclose($check);
                break;
            }
            usleep(100_000);
        }

        register_shutdown_function(static function (): void {
            if (static::$serveProcess !== null && static::$serveProcess->isRunning()) {
                static::$serveProcess->stop(1);
                static::$serveProcess = null;
            }
        });
    }

    /**
     * Create the RemoteWebDriver instance.
     */
    protected function driver(): RemoteWebDriver
    {
        $options = (new ChromeOptions)->addArguments(collect([
            $this->shouldStartMaximized() ? '--start-maximized' : '--window-size=1920,1600',
            '--disable-search-engine-choice-screen',
            '--disable-smooth-scrolling',
        ])->unless($this->hasHeadlessDisabled(), function (Collection $items) {
            return $items->merge([
                '--disable-gpu',
                '--headless=new',
            ]);
        })->all());

        if ($binary = static::resolveBrowserBinary()) {
            $options->setBinary($binary);
        }

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? env('DUSK_DRIVER_URL') ?? 'http://localhost:9515',
            DesiredCapabilities::chrome()->setCapability(
                ChromeOptions::CAPABILITY, $options
            )
        );
    }

    protected static function resolveBrowserBinary(): ?string
    {
        $candidates = array_filter([
            $_ENV['DUSK_CHROME_BINARY'] ?? env('DUSK_CHROME_BINARY'),
            'C:\\Program Files\\BraveSoftware\\Brave-Browser\\Application\\brave.exe',
            'C:\\Program Files (x86)\\BraveSoftware\\Brave-Browser\\Application\\brave.exe',
            getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA').'\\BraveSoftware\\Brave-Browser\\Application\\brave.exe' : null,
            'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'C:\\Program Files (x86)\\Google\\Chrome\\Application\\chrome.exe',
            getenv('LOCALAPPDATA') ? getenv('LOCALAPPDATA').'\\Google\\Chrome\\Application\\chrome.exe' : null,
        ]);

        foreach ($candidates as $path) {
            if (is_string($path) && file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}

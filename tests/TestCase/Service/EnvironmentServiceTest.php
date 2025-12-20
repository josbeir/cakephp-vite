<?php
declare(strict_types=1);

namespace CakeVite\Test\TestCase\Service;

use Cake\Http\ServerRequest;
use CakeVite\Enum\Environment;
use CakeVite\Service\EnvironmentService;
use CakeVite\ValueObject\ViteConfig;
use PHPUnit\Framework\TestCase;

/**
 * EnvironmentService Test
 *
 * Following TDD principles - this test is written BEFORE the service exists.
 */
class EnvironmentServiceTest extends TestCase
{
    /**
     * Test detect returns Production when forceProductionMode is true
     */
    public function testDetectReturnsProductionWhenForced(): void
    {
        $config = ViteConfig::fromArray(['forceProductionMode' => true]);
        $request = new ServerRequest();

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Production, $result);
    }

    /**
     * Test detect returns Production when production hint cookie exists
     */
    public function testDetectReturnsProductionWhenHintCookieExists(): void
    {
        $config = ViteConfig::fromArray(['productionModeHint' => 'vprod']);
        $request = new ServerRequest([
            'environment' => ['HTTP_COOKIE' => 'vprod=1'],
        ]);

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Production, $result);
    }

    /**
     * Test detect returns Production when production hint query param exists
     */
    public function testDetectReturnsProductionWhenHintQueryExists(): void
    {
        $config = ViteConfig::fromArray(['productionModeHint' => 'vprod']);
        $request = new ServerRequest(['url' => '/?vprod=1']);

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Production, $result);
    }

    /**
     * Test detect returns Development when host contains localhost
     */
    public function testDetectReturnsDevelopmentForLocalhostHost(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => ['hostHints' => ['localhost', '.test']],
        ]);
        $request = new ServerRequest([
            'environment' => ['HTTP_HOST' => 'localhost:3000'],
        ]);

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Development, $result);
    }

    /**
     * Test detect returns Development when host contains .test
     */
    public function testDetectReturnsDevelopmentForTestDomain(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => ['hostHints' => ['localhost', '.test']],
        ]);
        $request = new ServerRequest([
            'environment' => ['HTTP_HOST' => 'myapp.test'],
        ]);

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Development, $result);
    }

    /**
     * Test detect returns Development when host contains .local
     */
    public function testDetectReturnsDevelopmentForLocalDomain(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => ['hostHints' => ['.local']],
        ]);
        $request = new ServerRequest([
            'environment' => ['HTTP_HOST' => 'dev.local'],
        ]);

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Development, $result);
    }

    /**
     * Test detect returns Production by default for production host
     */
    public function testDetectReturnsProductionByDefaultForProductionHost(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => ['hostHints' => ['localhost', '.test']],
        ]);
        $request = new ServerRequest([
            'environment' => ['HTTP_HOST' => 'example.com'],
        ]);

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Production, $result);
    }

    /**
     * Test forceProductionMode takes precedence over host hints
     */
    public function testForceProductionModeTakesPrecedenceOverHostHints(): void
    {
        $config = ViteConfig::fromArray([
            'forceProductionMode' => true,
            'devServer' => ['hostHints' => ['localhost']],
        ]);
        $request = new ServerRequest([
            'environment' => ['HTTP_HOST' => 'localhost'],
        ]);

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Production, $result);
    }

    /**
     * Test production hint takes precedence over host hints
     */
    public function testProductionHintTakesPrecedenceOverHostHints(): void
    {
        $config = ViteConfig::fromArray([
            'productionModeHint' => 'vprod',
            'devServer' => ['hostHints' => ['localhost']],
        ]);
        $request = new ServerRequest([
            'environment' => ['HTTP_HOST' => 'localhost'],
            'url' => '/?vprod=1',
        ]);

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Production, $result);
    }

    /**
     * Test detect returns Production when no host information available
     */
    public function testDetectReturnsProductionWhenNoHostAvailable(): void
    {
        $config = ViteConfig::fromArray([]);
        $request = new ServerRequest();

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Production, $result);
    }

    /**
     * Test detect is case-insensitive for host hints
     */
    public function testDetectIsCaseInsensitiveForHostHints(): void
    {
        $config = ViteConfig::fromArray([
            'devServer' => ['hostHints' => ['localhost']],
        ]);
        $request = new ServerRequest([
            'environment' => ['HTTP_HOST' => 'LOCALHOST'],
        ]);

        $service = new EnvironmentService($request);
        $result = $service->detect($config);

        $this->assertSame(Environment::Development, $result);
    }
}

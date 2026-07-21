<?php

namespace RickSelby\Tests;

use Illuminate\Contracts\Auth\Access\Gate as GateContract;
use Illuminate\Contracts\Auth\Authenticatable;
use RickSelby\Laravel\GateCache\GateCache;
use RickSelby\Laravel\GateCache\GateCacheProvider;

class ProviderTest extends AbstractPackageTestCase
{
    public function test_service_provider_boots_with_the_application(): void
    {
        $this->assertTrue($this->app->isBooted());
        $this->assertInstanceOf(
            GateCacheProvider::class,
            $this->app->getProvider(GateCacheProvider::class)
        );
    }

    public function test_gate_contract_resolves_to_the_gate_cache_singleton(): void
    {
        $gate = $this->app->make(GateContract::class);

        $this->assertInstanceOf(GateCache::class, $gate);
        $this->assertSame($gate, $this->app->make(GateContract::class));
    }

    public function test_a_fresh_application_lifecycle_gets_a_fresh_gate_cache(): void
    {
        $calls = 0;
        $callback = function (?Authenticatable $user = null) use (&$calls): bool {
            $calls++;

            return true;
        };
        $firstApplication = $this->app;
        $firstGate = $firstApplication->make(GateContract::class);
        $firstGate->define('view', $callback);

        $this->assertTrue($firstGate->allows('view'));
        $this->assertTrue($firstGate->allows('view'));

        $this->refreshApplication();

        $secondGate = $this->app->make(GateContract::class);
        $secondGate->define('view', $callback);

        $this->assertNotSame($firstApplication, $this->app);
        $this->assertNotSame($firstGate, $secondGate);
        $this->assertTrue($secondGate->allows('view'));
        $this->assertTrue($secondGate->allows('view'));
        $this->assertSame(2, $calls);
    }
}

<?php

namespace RickSelby\Tests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Authenticatable;
use RickSelby\Laravel\GateCache\GateCache;

class RawTest extends AbstractPackageTestCase
{
    public function test_authorization_callback_is_called_once_for_the_same_ability_and_arguments(): void
    {
        $calls = 0;
        $gate = $this->gateForUser();
        $gate->define('update', function (Authenticatable $user, string $post) use (&$calls): string {
            $calls++;

            return 'allowed-'.$post;
        });

        $this->assertSame('allowed-post-1', $gate->raw('update', ['post-1']));
        $this->assertSame('allowed-post-1', $gate->raw('update', ['post-1']));
        $this->assertSame(1, $calls);
    }

    public function test_false_results_are_cached(): void
    {
        $calls = 0;
        $gate = $this->gateForUser();
        $gate->define('delete', function (Authenticatable $user) use (&$calls): bool {
            $calls++;

            return false;
        });

        $this->assertFalse($gate->raw('delete'));
        $this->assertFalse($gate->raw('delete'));
        $this->assertSame(1, $calls);
    }

    public function test_different_abilities_do_not_share_a_cache_entry(): void
    {
        $calls = 0;
        $gate = $this->gateForUser();
        $callback = function (Authenticatable $user) use (&$calls): bool {
            $calls++;

            return true;
        };
        $gate->define('create', $callback);
        $gate->define('update', $callback);

        $this->assertTrue($gate->raw('create'));
        $this->assertTrue($gate->raw('update'));
        $this->assertSame(2, $calls);
    }

    public function test_different_arguments_do_not_share_a_cache_entry(): void
    {
        $calls = 0;
        $gate = $this->gateForUser();
        $gate->define('update', function (Authenticatable $user, string $post) use (&$calls): bool {
            $calls++;

            return true;
        });

        $this->assertTrue($gate->raw('update', ['post-1']));
        $this->assertTrue($gate->raw('update', ['post-2']));
        $this->assertSame(2, $calls);
    }

    public function test_common_gate_methods_keep_their_laravel_behavior(): void
    {
        $calls = 0;
        $gate = $this->gateForUser();
        $gate->define('publish', function (Authenticatable $user) use (&$calls): Response {
            $calls++;

            return Response::allow('Publication allowed');
        });

        $this->assertTrue($gate->allows('publish'));
        $this->assertFalse($gate->denies('publish'));

        $response = $gate->inspect('publish');

        $this->assertTrue($response->allowed());
        $this->assertSame('Publication allowed', $response->message());
        $this->assertSame(1, $calls);
    }

    public function test_raw_cache_is_isolated_between_gate_instances(): void
    {
        $calls = 0;
        $callback = function (Authenticatable $user) use (&$calls): bool {
            $calls++;

            return true;
        };

        $firstGate = $this->gateForUser();
        $firstGate->define('view', $callback);
        $secondGate = $this->gateForUser();
        $secondGate->define('view', $callback);

        $this->assertTrue($firstGate->raw('view'));
        $this->assertTrue($firstGate->raw('view'));
        $this->assertTrue($secondGate->raw('view'));
        $this->assertTrue($secondGate->raw('view'));
        $this->assertSame(2, $calls);
    }

    private function gateForUser(): GateCache
    {
        $user = $this->createStub(Authenticatable::class);

        return new GateCache($this->app, fn (): Authenticatable => $user);
    }
}

<?php

namespace RickSelby\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use RickSelby\Laravel\GateCache\GateCache;

class ForUserTest extends AbstractPackageTestCase
{
    protected GateCache $gateCache;

    protected Authenticatable $user;

    public function test_the_same_user_gets_the_same_gate_instance(): void
    {
        $this->assertSame(
            $this->gateCache->forUser($this->user),
            $this->gateCache->forUser($this->user)
        );
    }

    public function test_different_users_get_different_gate_instances(): void
    {
        $altUser = $this->createStub(Authenticatable::class);
        $altUser->method('getAuthIdentifier')->willReturn(2);

        $this->assertNotSame(
            $this->gateCache->forUser($this->user),
            $this->gateCache->forUser($altUser)
        );
    }

    public function test_policy_name_guesser_is_preserved_for_a_user_gate(): void
    {
        $guessedFor = [];
        $this->gateCache->guessPolicyNamesUsing(function (string $class) use (&$guessedFor): string {
            $guessedFor[] = $class;

            return GuessedPolicy::class;
        });

        $gate = $this->gateCache->forUser($this->user);

        $this->assertTrue($gate->allows('view', new PolicySubject));
        $this->assertSame([PolicySubject::class], $guessedFor);
    }

    public function test_user_gate_caches_are_isolated_from_each_other(): void
    {
        $calls = 0;
        $this->gateCache->define('view', function (Authenticatable $user) use (&$calls): bool {
            $calls++;

            return true;
        });
        $altUser = $this->createStub(Authenticatable::class);
        $altUser->method('getAuthIdentifier')->willReturn(2);

        $firstGate = $this->gateCache->forUser($this->user);
        $secondGate = $this->gateCache->forUser($altUser);

        $this->assertTrue($firstGate->allows('view'));
        $this->assertTrue($firstGate->allows('view'));
        $this->assertTrue($secondGate->allows('view'));
        $this->assertTrue($secondGate->allows('view'));
        $this->assertSame(2, $calls);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createStub(Authenticatable::class);
        $this->user->method('getAuthIdentifier')->willReturn(1);
        $this->gateCache = new GateCache($this->app, fn (): Authenticatable => $this->user);
    }
}

class PolicySubject
{
    public int $id = 1;
}

class GuessedPolicy
{
    public function view(Authenticatable $user, PolicySubject $subject): bool
    {
        return true;
    }
}

<?php

namespace Tests;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        DB::statement('PRAGMA foreign_keys=on;');

        $this->withoutExceptionHandling();
    }

    /**
     * Sign in with a user.
     *
     * @param  \App\User|null $user
     * @return $this
     */
    protected function signIn(?User $user = null)
    {
        $user = $user ?? create(User::class);

        $this->be($user);

        return $this;
    }

    /**
     * Sign in with a user and give it a permission.
     *
     * @param  string  $permission
     * @return $this
     */
    protected function signInWithPermission(string $permission)
    {
        return $this->signIn(
            create(User::class)
                ->givePermissionTo($permission)
        );
    }

    /**
     * Sign in with an admin user.
     *
     * @return $this
     */
    protected function signInGod()
    {
        return $this->signIn(
            factory(User::class)->states('god')->create(['email' => 'john@example.com'])
        );
    }
}

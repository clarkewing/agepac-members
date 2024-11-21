<?php

namespace Tests\Unit;

use App\Models\Channel;
use App\Models\User;
use App\Traits\RestrictedChannels;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class RestrictedChannelsTest extends TestCase
{
    /**
     * @var \App\Models\Channel
     */
    public $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = Channel::factory()->create();
    }

    /** @test */
    public function testExceptionThrownIfPermissionsArrayIsNotSet()
    {
        $this->expectExceptionMessage('Permissions array missing on model.');

        $channelWithoutPermissionsArray = new class extends Model {
            use RestrictedChannels;
        };
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function testNoExceptionThrownIfPermissionsArrayIsSet()
    {
        $channelWithPermissionsArray = new class extends Model {
            use RestrictedChannels;

            protected static $permissions = ['view'];
        };
    }

    /** @test */
    public function testKnowsIfAnyRestrictionsExist()
    {
        $this->assertFalse($this->channel->isRestricted());

        $this->channel->createPermission('post');

        $this->assertTrue($this->channel->isRestricted());

        $this->assertFalse($this->channel->isRestricted('view'));
        $this->assertTrue($this->channel->isRestricted('post'));
    }

    /** @test */
    public function testCanCreateAndDeletePermissionsForChannel()
    {
        $this->channel->createPermission('view');

        $this->assertDatabaseHas('permissions', ['name' => "channels.view.{$this->channel->slug}"]);

        $this->channel->deletePermission('view');

        $this->assertDatabaseMissing('permissions', ['name' => "channels.view.{$this->channel->slug}"]);
    }

    /** @test */
    public function testCanGetAssociatedPermission()
    {
        $this->assertNull($this->channel->vote_permission);
        $this->assertNull($this->channel->votePermission);

        $this->channel->createPermission('vote');

        $this->assertInstanceOf(Permission::class, $this->channel->vote_permission);
        $this->assertInstanceOf(Permission::class, $this->channel->votePermission);
    }

    /** @test */
    public function testCanScopeUnrestrictedChannels()
    {
        $restrictedChannel = $this->channel->createPermission('view');
        $unrestrictedChannel = Channel::factory()->create();

        $this->assertEquals(1, Channel::unrestricted()->count());
        $this->assertTrue($unrestrictedChannel->is(Channel::unrestricted()->first()));
    }

    /** @test */
    public function testCanScopeChannelsWithPermission()
    {
        $restrictedChannel = tap($this->channel)->createPermission('view');
        $unrestrictedChannel = Channel::factory()->create();

        $user = User::factory()->create();

        $this->assertEquals(1, Channel::withPermission('view', $user)->count());
        $this->assertTrue($unrestrictedChannel->is(Channel::withPermission('view', $user)->first()));

        $user->givePermissionTo($restrictedChannel->viewPermission);

        $this->assertEquals(2, Channel::withPermission('view', $user)->count());
    }

    /** @test */
    public function testWithPermissionScopeIncludesChannelsWhereSpecifiedPermissionIsNotSet()
    {
        $restrictedChannel = tap($this->channel)->createPermission('post');

        $user = User::factory()->create();

        $this->assertEquals(1, Channel::withPermission('view', $user)->count());
    }

    /** @test */
    public function testCanScopeChannelsWithPermissionForLoggedInUser()
    {
        $this->signIn();

        $restrictedChannel = tap($this->channel)->createPermission('view');
        $unrestrictedChannel = Channel::factory()->create();

        $this->assertEquals(1, Channel::withPermission('view')->count());
        $this->assertTrue($unrestrictedChannel->is(Channel::withPermission('view')->first()));

        Auth::user()->givePermissionTo($restrictedChannel->viewPermission);

        $this->assertEquals(2, Channel::withPermission('view')->count());
    }

    /** @test */
    public function testCanFilterChannelsCollectionWithPermission()
    {
        $restrictedChannel = tap($this->channel)->createPermission('view');
        $unrestrictedChannel = Channel::factory()->create();

        $allChannels = Channel::all();

        $user = User::factory()->create();

        $this->assertEquals(1, $allChannels->withPermission('view', $user)->count());
        $this->assertTrue($unrestrictedChannel->is($allChannels->withPermission('view', $user)->first()));

        $user->givePermissionTo($restrictedChannel->viewPermission);

        $this->assertEquals(2, $allChannels->withPermission('view', $user)->count());
    }

    /** @test */
    public function testCanFilterChannelsCollectionWithPermissionForLoggedInUser()
    {
        $this->signIn();

        $restrictedChannel = tap($this->channel)->createPermission('view');
        $unrestrictedChannel = Channel::factory()->create();

        $allChannels = Channel::all();

        $this->assertEquals(1, $allChannels->withPermission('view')->count());
        $this->assertTrue($unrestrictedChannel->is($allChannels->withPermission('view')->first()));

        Auth::user()->givePermissionTo($restrictedChannel->viewPermission);

        $this->assertEquals(2, $allChannels->withPermission('view')->count());
    }
}

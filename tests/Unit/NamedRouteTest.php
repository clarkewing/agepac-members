<?php

namespace Tests\Unit;

use App\Attachment;
use App\Channel;
use App\Post;
use App\Thread;
use App\User;
use Illuminate\Support\Str;
use Tests\TestCase;

class NamedRouteTest extends TestCase
{
    /* @test */
    public function testHome()
    {
        $this->assertRoutePathIs('/home', 'home');
    }

    /* @test */
    public function testThreadIndex()
    {
        $this->assertRoutePathIs('/threads', 'threads.index');
    }

    /* @test */
    public function testThreadIndexWithChannel()
    {
        $channel = make(Channel::class);

        $this->assertRoutePathIs("/threads/{$channel->slug}", 'threads.index', $channel);
    }

    /* @test */
    public function testThreadCreate()
    {
        $this->assertRoutePathIs('/threads/create', 'threads.create');
    }

    /* @test */
    public function testThreadStore()
    {
        $this->assertRoutePathIs('/threads', 'threads.store');
    }

    /* @test */
    public function testThreadSearch()
    {
        $this->assertRoutePathIs('/threads/search', 'threads.search');
    }

    /* @test */
    public function testThreadShow()
    {
        $thread = create(Thread::class); // Create required to generate slug

        $this->assertRoutePathIs(
            "/threads/{$thread->channel->slug}/{$thread->slug}",
            'threads.show', [$thread->channel, $thread]
        );
    }

    /* @test */
    public function testThreadUpdate()
    {
        $thread = create(Thread::class);

        $this->assertRoutePathIs(
            "/threads/{$thread->channel->slug}/{$thread->slug}",
            'threads.update', [$thread->channel, $thread]
        );
    }

    /* @test */
    public function testThreadDestroy()
    {
        $thread = create(Thread::class);

        $this->assertRoutePathIs(
            "/threads/{$thread->channel->slug}/{$thread->slug}",
            'threads.destroy', [$thread->channel, $thread]
        );
    }

    /* @test */
    public function testLockThread()
    {
        $thread = create(Thread::class);

        $this->assertRoutePathIs(
            "/locked-threads/{$thread->slug}",
            'threads.lock', $thread
        );
    }

    /* @test */
    public function testUnlockThread()
    {
        $thread = create(Thread::class);

        $this->assertRoutePathIs(
            "/locked-threads/{$thread->slug}",
            'threads.unlock', $thread
        );
    }

    /* @test */
    public function testThreadSubscriptionsStore()
    {
        $thread = create(Thread::class);

        $this->assertRoutePathIs(
            "/threads/{$thread->channel->slug}/{$thread->slug}/subscriptions",
            'threads.subscribe', [$thread->channel, $thread]
        );
    }

    /* @test */
    public function testThreadSubscriptionsDestroy()
    {
        $thread = create(Thread::class);

        $this->assertRoutePathIs(
            "/threads/{$thread->channel->slug}/{$thread->slug}/subscriptions",
            'threads.unsubscribe', [$thread->channel, $thread]
        );
    }

    /* @test */
    public function testPostsIndex()
    {
        $thread = create(Thread::class);

        $this->assertRoutePathIs(
            "/threads/{$thread->channel->slug}/{$thread->slug}/posts",
            'posts.index', [$thread->channel, $thread]
        );
    }

    /* @test */
    public function testPostsStore()
    {
        $thread = create(Thread::class);

        $this->assertRoutePathIs(
            "/threads/{$thread->channel->slug}/{$thread->slug}/posts",
            'posts.store', [$thread->channel, $thread]
        );
    }

    /* @test */
    public function testPostsUpdate()
    {
        $post = create(Post::class);

        $this->assertRoutePathIs(
            "/posts/{$post->id}",
            'posts.update', $post
        );
    }

    /* @test */
    public function testPostsDestroy()
    {
        $post = create(Post::class);

        $this->assertRoutePathIs(
            "/posts/{$post->id}",
            'posts.destroy', $post
        );
    }

    /* @test */
    public function testPostsMarkBest()
    {
        $post = create(Post::class);

        $this->assertRoutePathIs(
            "/posts/{$post->id}/best",
            'posts.mark_best', $post
        );
    }

    /* @test */
    public function testPostsUnmarkBest()
    {
        $posts = create(Post::class);

        $this->assertRoutePathIs(
            "/posts/{$posts->id}/best",
            'posts.unmark_best', $posts
        );
    }

    /* @test */
    public function testPostsFavorite()
    {
        $post = create(Post::class);

        $this->assertRoutePathIs(
            "/posts/{$post->id}/favorites",
            'posts.favorite', $post
        );
    }

    /* @test */
    public function testPostsUnfavorite()
    {
        $post = create(Post::class);

        $this->assertRoutePathIs(
            "/posts/{$post->id}/favorites",
            'posts.unfavorite', $post
        );
    }

    /* @test */
    public function testAttachmentsStore()
    {
        $this->assertRoutePathIs(
            '/attachments',
            'attachments.store'
        );
    }

    /* @test */
    public function testAttachmentsDestroy()
    {
        $attachment = create(Attachment::class);

        $this->assertRoutePathIs(
            "/attachments/{$attachment->id}",
            'attachments.destroy', $attachment
        );
    }

    /* @test */
    public function testProfilesShow()
    {
        $user = make(User::class);

        $this->assertRoutePathIs(
            '/profiles/' . $user->username,
            'profiles.show', $user
        );
    }

    /* @test */
    public function testNotificationsIndex()
    {
        $this->assertRoutePathIs('/notifications', 'notifications.index');
    }

    /* @test */
    public function testNotificationsDestroy()
    {
        $notificationId = Str::orderedUuid();

        $this->assertRoutePathIs(
            "/notifications/{$notificationId}",
            'notifications.destroy', $notificationId
        );
    }

    /* @test */
    public function testApiUsersIndex()
    {
        $this->assertRoutePathIs('/api/users', 'api.users.index');
    }

    /* @test */
    public function testApiUsersAvatarStore()
    {
        $user = make(User::class);

        $this->assertRoutePathIs(
            '/api/users/' . $user->username . '/avatar',
            'api.users.avatar.store', $user
        );
    }

    public function assertRoutePathIs(string $expectedPath, string $routeName, $routeParameters = null)
    {
        $this->assertEquals(
            config('app.url') . $expectedPath,
            route($routeName, $routeParameters)
        );
    }
}

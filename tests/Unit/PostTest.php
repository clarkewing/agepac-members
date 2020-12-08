<?php

namespace Tests\Unit;

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PostTest extends TestCase
{
    /** @test */
    public function testHasAnOwner()
    {
        $post = Post::factory()->create();

        $this->assertInstanceOf(User::class, $post->owner);
    }

    /** @test */
    public function testKnowsIfItWasJustPublished()
    {
        $post = Post::factory()->create();

        $this->assertTrue($post->wasJustPublished());

        $post->created_at = now()->subMonth();

        $this->assertFalse($post->wasJustPublished());
    }

    /** @test */
    public function testDetectsAllMentionedUsersInTheBody()
    {
        $jane = User::factory()->create(['username' => 'jane.doe']);
        $john = User::factory()->create(['username' => 'john.doe']);

        $post = new Post([
            'body' => '@jane.doe wants to talk to @john.doe but not @fake.user',
        ]);

        $this->assertCount(2, $post->mentionedUsers());
        $this->assertTrue($post->mentionedUsers()->contains('id', $jane->id));
        $this->assertTrue($post->mentionedUsers()->contains('id', $john->id));
    }

    /** @test */
    public function testWrapsMentionedUsernamesInTheBodyWithinAnchorTags()
    {
        $post = new Post([
            'body' => 'Hello @jane.doe.',
        ]);

        $this->assertEquals(
            'Hello <a href="'.route('profiles.show', 'jane.doe', false).'">@jane.doe</a>.',
            $post->body
        );
    }

    /** @test */
    public function testBodyPreservesAnyAllowedHtmlTags()
    {
        Event::fake();

        $post = Post::factory()->create(['body' => '<h1>Header 1</h1>']);
        $this->assertEquals('<h1>Header 1</h1>', $post->body);

        $post = Post::factory()->create(['body' => '<h2>Header 2</h2>']);
        $this->assertEquals('<h2>Header 2</h2>', $post->body);

        $post = Post::factory()->create(['body' => '<h3>Header 3</h3>']);
        $this->assertEquals('<h3>Header 3</h3>', $post->body);

        $post = Post::factory()->create(['body' => '<h4>Header 4</h4>']);
        $this->assertEquals('<h4>Header 4</h4>', $post->body);

        $post = Post::factory()->create(['body' => '<h5>Header 5</h5>']);
        $this->assertEquals('<h5>Header 5</h5>', $post->body);

        $post = Post::factory()->create(['body' => '<h6>Header 6</h6>']);
        $this->assertEquals('<h6>Header 6</h6>', $post->body);

        $post = Post::factory()->create(['body' => '<b>Bold text</b>']);
        $this->assertEquals('<b>Bold text</b>', $post->body);

        $post = Post::factory()->create(['body' => '<strong>Strong text</strong>']);
        $this->assertEquals('<strong>Strong text</strong>', $post->body);

        $post = Post::factory()->create(['body' => '<i>Italic text</i>']);
        $this->assertEquals('<i>Italic text</i>', $post->body);

        $post = Post::factory()->create(['body' => '<em>Emphasis text</em>']);
        $this->assertEquals('<em>Emphasis text</em>', $post->body);

        $post = Post::factory()->create(['body' => '<del>Strikethrough text</del>']);
        $this->assertEquals('<del>Strikethrough text</del>', $post->body);

        $post = Post::factory()->create(['body' => '<a href="/foobar" title="Baz">Link with href and title</a>']);
        $this->assertEquals('<a href="/foobar" title="Baz">Link with href and title</a>', $post->body);

        $post = Post::factory()->create(['body' => '<ul><li>Unordered list item</li></ul>']);
        $this->assertEquals('<ul><li>Unordered list item</li></ul>', $post->body);

        $post = Post::factory()->create(['body' => '<ol><li>Ordered list item</li></ol>']);
        $this->assertEquals('<ol><li>Ordered list item</li></ol>', $post->body);

        $post = Post::factory()->create(['body' => '<p style="color:#0000FF;">Paragraph with style</p>']);
        $this->assertEquals('<p style="color:#0000FF;">Paragraph with style</p>', $post->body);

        $post = Post::factory()->create(['body' => '<span style="color:#0000FF;">Span with style</span>']);
        $this->assertEquals('<span style="color:#0000FF;">Span with style</span>', $post->body);

        $post = Post::factory()->create(['body' => 'Line 1<br>Line 2']);
        $this->assertEquals('Line 1<br>Line 2', $post->body);

        $post = Post::factory()->create([
            'body' => '<figure class="attachment" data-trix-attachment="foobar" data-trix-attributes="baz" data-trix-content-type="foo/bar">Content</figure>',
        ]);
        $this->assertEquals(
            '<figure class="attachment" data-trix-attachment="foobar" data-trix-attributes="baz" data-trix-content-type="foo/bar">Content</figure>',
            $post->body
        );

        $post = Post::factory()->create([
            'body' => '<figcaption class="attachment__caption">This is a caption</figcaption>',
        ]);
        $this->assertEquals(
            '<figcaption class="attachment__caption">This is a caption</figcaption>',
            $post->body
        );

        $post = Post::factory()->create([
            'body' => '<img src="/greatimage.jpg" alt="Image with src, alt, width, and height" width="12" height="34">',
        ]);
        $this->assertEquals(
            '<img src="/greatimage.jpg" alt="Image with src, alt, width, and height" width="12" height="34">',
            $post->body
        );
    }

    /** @test */
    public function testBodyIsPurifiedOfForbiddenTags()
    {
        $post = Post::factory()->create(['body' => '<script>alert("Do something fishy.");</script>']);
        $this->assertStringNotContainsString('<script>', $post->body);
    }

    /** @test */
    public function testKnowsIfItIsTheBestPost()
    {
        $post = Post::factory()->create();

        $this->assertFalse($post->isBest());

        $post->thread->update(['best_post_id' => $post->id]);

        $this->assertTrue($post->fresh()->isBest());
    }

    /** @test */
    public function testBodyIsSanitizedAutomatically()
    {
        $post = Post::factory()->make(['body' => '<script>alert("bad");</script><p>This is okay.</p>']);

        $this->assertEquals($post->body, '<p>This is okay.</p>');
    }
}

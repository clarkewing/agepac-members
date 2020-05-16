<?php

namespace Tests\Unit;

use App\Attachment;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttachmentTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    /** @test */
    public function testGetsUuidOnCreation()
    {
        $attachment = make(Attachment::class);

        $this->assertEquals('', $attachment->id);

        $attachment->save();

        $this->assertTrue(Str::isUuid((string) $attachment->id));
    }

    /** @test */
    public function testDeletingDeletesAssociatedFileInStorage()
    {
        $attachment = create(Attachment::class);

        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertExists($attachment->path);

        $attachment->delete();

        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        Storage::disk('public')->assertMissing($attachment->path);
    }
}

<?php

namespace App\Models;

use App\Events\PostCreated;
use App\Events\PostUpdated;
use App\Traits\Favoritable;
use App\Traits\MentionsUsers;
use App\Traits\RecordsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Post extends Model
{
    use HasFactory, Favoritable, MentionsUsers, Searchable, SoftDeletes;
    use RecordsActivity {
        recordActivity as protected traitRecordActivity;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'thread_id',
        'user_id',
        'body',
        'is_thread_initiator',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'user_id' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['is_best'];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['owner', 'thread'];

    /**
     * All of the relationships to be touched.
     *
     * @var array
     */
    protected $touches = ['thread'];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($post) {
            if (! $post->is_thread_initiator) {
                $post->owner->gainReputation('reply_posted');
            }
        });

        static::deleting(function ($post) {
            if ($post->isBest()) {
                $post->thread->unmarkBestPost();
            }
        });

        static::deleted(function ($post) {
            if (! $post->is_thread_initiator) {
                $post->owner->loseReputation('reply_posted');
            }

            $post->attachments->each->delete();
        });
    }

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => PostCreated::class,
        'updated' => PostUpdated::class,
    ];

    /**
     * Don't record activity for thread initiator,
     * as it is already handled by the thread.
     *
     * @param  \Symfony\Contracts\EventDispatcher\Event  $event
     * @return void
     */
    protected function recordActivity($event): void
    {
        if ($this->is_thread_initiator) {
            return;
        }

        $this->traitRecordActivity($event);
    }

    /**
     * Get the user that owns the post.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the thread that the post belongs to.
     */
    public function thread()
    {
        return $this->belongsTo(Thread::class);
    }

    /**
     * Get the attachments for the post.
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    /**
     * Determines whether the post was just published.
     *
     * @return bool
     */
    public function wasJustPublished(): bool
    {
        return $this->created_at->greaterThan(now()->subMinute());
    }

    /**
     * Returns the URL of the post.
     *
     * @return string
     */
    public function path()
    {
        return $this->thread->path() . "#post-{$this->id}";
    }

    /**
     * Determines whether the post is marked as the best one on its thread.
     *
     * @return bool
     */
    public function isBest(): bool
    {
        return $this->thread->best_post_id == $this->id;
    }

    /**
     * Get the best post flag for the post.
     *
     * @return bool
     */
    public function getIsBestAttribute(): bool
    {
        return $this->isBest();
    }

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $searchableData = [
            'id' => $this->id,
            'body' => $this->body,
            'is_thread_initiator' => $this->is_thread_initiator,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'favorites_count' => $this->favorites_count,
            'is_best' => $this->is_best,

            'owner' => [
                'name' => $this->owner->name,
                'username' => $this->owner->username,
                'avatar_path' => $this->owner->avatar_path,
                'reputation' => $this->owner->reputation,
            ],

            'thread' => [
                'id' => $this->thread->id,
                'title' => $this->thread->title,
                'replies_count' => $this->thread->replies_count,
                'path' => $this->thread->path,
                'pinned' => $this->thread->pinned,
                'created_at' => $this->thread->created_at,
                'updated_at' => $this->thread->updated_at,

                'creator' => [
                    'name' => $this->thread->creator->name,
                    'username' => $this->thread->creator->username,
                    'avatar_path' => $this->thread->creator->avatar_path,
                ],

                'channel' => [
                    'name' => $this->thread->channel->name,
                    'slug' => $this->thread->channel->slug,
                ],
            ],
        ];

        if (is_null($this->thread->channel->parent)) {
            $searchableData['thread']['channel']['lvl0'] = $this->thread->channel->name;
        } else {
            $searchableData['thread']['channel']['lvl0'] = $this->thread->channel->parent->name;
            $searchableData['thread']['channel']['lvl1'] = $this->thread->channel->parent->name . ' > ' . $this->thread->channel->name;
        }

        return $searchableData;
    }
}

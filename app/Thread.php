<?php

namespace App;

use App\Events\ThreadPublished;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Stevebauman\Purify\Facades\Purify;

class Thread extends Model
{
    use MentionsUsers, RecordsActivity, Searchable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'channel_id',
        'title',
        'body',
        'slug',
        'best_reply_id',
        'locked',
    ];

    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = ['creator', 'channel'];
    protected $withCount = ['replies'];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['isSubscribedTo'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'locked' => 'boolean',
    ];

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($thread) {
            $thread->slug = $thread->title;
        });

        static::created(function ($thread) {
            $thread->creator->gainReputation('thread_published');
        });

        // Cascade deleting of thread.
        static::deleting(function ($thread) {
            $thread->replies->each->delete();

            $thread->creator->loseReputation('thread_published');
        });
    }

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'created' => ThreadPublished::class,
    ];

    /**
     * Returns the URL of the thread.
     *
     * @return string
     */
    public function path()
    {
        return route('threads.show', [$this->channel, $this]);
    }

    /**
     * Get the replies for the thread.
     */
    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    /**
     * Get the thread's best reply.
     */
    public function bestReply()
    {
        return $this->hasOne(Reply::class, 'id', 'best_reply_id');
    }

    /**
     * Get the channel that the thread belongs to.
     */
    public function channel()
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the user that created the thread.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Add a reply to the thread.
     *
     * @param  array $reply
     * @return \App\Reply
     */
    public function addReply(array $reply)
    {
        $reply = $this->replies()->create($reply);

        return $reply;
    }

    /**
     * Scope a query to filter.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param   $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFilter($query, $filters)
    {
        return $filters->apply($query);
    }

    /**
     * Creates a subscription to this thread for the given user.
     *
     * @param  int|null $userId
     * @return $this
     */
    public function subscribe(?int $userId = null)
    {
        $this->subscriptions()->create([
            'user_id' => $userId ?? Auth::id(),
        ]);

        return $this;
    }

    /**
     * Removes a subscription from this thread for the given user.
     *
     * @param  int|null $userId
     * @return $this
     */
    public function unsubscribe(?int $userId = null)
    {
        $this->subscriptions()
            ->where('user_id', $userId ?? Auth::id())
            ->delete();

        return $this;
    }

    /**
     * Get the subscriptions for the thread.
     */
    public function subscriptions()
    {
        return $this->hasMany(ThreadSubscription::class);
    }

    /**
     * Get if the authenticated user is subscribed to the thread.
     *
     * @return bool
     */
    public function getIsSubscribedToAttribute(): bool
    {
        return $this->subscriptions()
            ->where('user_id', Auth::id())
            ->exists();
    }

    /**
     * Determine whether the thread has updates for the user.
     *
     * @param  \App\User $user
     * @return bool
     */
    public function hasUpdatesFor(User $user): bool
    {
        $key = $user->visitedThreadCacheKey($this);

        return $this->updated_at > cache($key);
    }

    /**
     * Get visits for the thread.
     *
     * @return \App\Visits
     */
    public function visits(): Visits
    {
        return new Visits($this);
    }

    /**
     * Sets a unique slug for the thread.
     *
     * @param  string $title
     * @return void
     */
    public function setSlugAttribute($title): void
    {
        $slug = Str::slug($title);

        if (static::where('slug', $slug)->exists()) {
            $slug = $slug . '-' . ($this->created_at ?? now())->timestamp;
        }

        $this->attributes['slug'] = $slug;
    }

    /**
     * Set the thread's best reply.
     *
     * @param  \App\Reply $reply
     * @return void
     */
    public function markBestReply(Reply $reply): void
    {
        if ($this->hasBestReply()) {
            $this->bestReply->owner->loseReputation('best_reply_awarded');
        }

        $this->update(['best_reply_id' => $reply->id]);

        $reply->owner->gainReputation('best_reply_awarded');
    }

    /**
     * Get the indexable data array for the thread.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return $this->toArray() + ['path' => $this->path()];
    }

    /**
     * Get the sanitized body.
     *
     * @param  string  $value
     * @return string
     */
    public function getBodyAttribute($value)
    {
        return Purify::clean($value);
    }

    /**
     * Determine if the thread has a best reply.
     *
     * @return bool
     */
    public function hasBestReply(): bool
    {
        return ! is_null($this->best_reply_id);
    }
}

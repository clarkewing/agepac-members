<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Cashier\Billable;
use Propaganistas\LaravelPhone\PhoneNumber;
use Spatie\Permission\Traits\HasRoles;
use Torann\GeoIP\Facades\GeoIP;
use URLify;

class User extends Authenticatable implements MustVerifyEmail
{
    use Billable, HasReputation, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'class_course',
        'class_year',
        'gender',
        'birthdate',
        'phone',
        'avatar_path',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'email', 'password', 'remember_token', 'email_verified_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['name'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'birthdate' => 'date:Y-m-d',
        'email_verified_at' => 'datetime',
        'flight_hours' => 'integer',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::created(function ($user) {
            $user->activity()->create([
                'type' => 'created_user',
                'user_id' => $user->id,
                'subject_id' => $user->id,
                'subject_type' => get_class($user),
            ]);
        });
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'username';
    }

    /**
     * Get the activity for the user.
     */
    public function activity()
    {
        return $this->hasMany(Activity::class);
    }

    /**
     * Get the associated profile.
     */
    public function profile()
    {
        return $this->hasOne(Profile::class, 'id');
    }

    /**
     * Get the threads for the user.
     */
    public function threads()
    {
        return $this->hasMany(Thread::class)->latest();
    }

    /**
     * Get the latest post posted by the user.
     */
    public function lastPost()
    {
        return $this->hasOne(Post::class)->latest();
    }

    /**
     * Get the cache key for visited threads.
     *
     * @param  \App\Thread $thread
     * @return string
     */
    public function read(Thread $thread)
    {
        Cache::forever(
            $this->visitedThreadCacheKey($thread),
            now()
        );
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getNameAttribute(): string
    {
        return $this->first_name.' '.$this->last_name;
    }

    /**
     * Get the user's class.
     *
     * @return string
     */
    public function getClassAttribute(): string
    {
        return $this->class_course.' '.$this->class_year;
    }

    /**
     * Set the user's phone number.
     *
     * @param  string  $value
     * @return void
     */
    public function setPhoneAttribute($value): void
    {
        if (is_null($value)) {
            $this->attributes['phone'] = null;
        } else {
            $this->attributes['phone'] = PhoneNumber::make($value)
                ->ofCountry('AUTO')
                ->ofCountry('FR')
                ->ofCountry(GeoIP::getLocation(request()->ip())->iso_code);
        }
    }

    /**
     * Get the user's phone number.
     *
     * @param  string  $value
     * @return \Propaganistas\LaravelPhone\PhoneNumber|null
     */
    public function getPhoneAttribute($value)
    {
        if (is_null($value)) {
            return;
        }

        return PhoneNumber::make($value);
    }

    /**
     * Get the path of the User's avatar.
     *
     * @param  string $avatar
     * @return string
     */
    public function getAvatarPathAttribute($avatar): string
    {
        return $avatar
            ? Storage::url($avatar)
            : asset('images/avatars/default.jpg');
    }

    /**
     * Get the cache key for visited threads.
     *
     * @param  \App\Thread $thread
     * @return string
     */
    public function visitedThreadCacheKey(Thread $thread)
    {
        return sprintf('users.%s.visits.%s', $this->id, $thread->id);
    }

    /**
     * Make a username string from a first and last name.
     *
     * @param  string  $firstName
     * @param  string  $lastName
     * @return string
     */
    public static function makeUsername(string $firstName, string $lastName): string
    {
        return strtolower(URLify::filter($firstName).'.'.URLify::filter($lastName));
    }
}

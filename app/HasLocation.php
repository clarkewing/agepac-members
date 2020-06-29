<?php

namespace App;

trait HasLocation
{
    /**
     * The "boot" method of the trait.
     *
     * @return void
     */
    protected static function bootHasLocation()
    {
        static::deleting(function ($model) {
            $model->location->delete();
        });
    }

    /**
     * Get the model's location relationship.
     */
    public function location()
    {
        return $this->morphOne(Location::class, 'locatable');
    }

    /**
     * Set the model's location.
     *
     * @param  mixed  $value
     * @return \App\Location|null
     */
    public function setLocation($value)
    {
        if (is_null($value)) {
            $this->location()->delete();

            return null;
        }

        return tap($this->location()->updateOrCreate([], $value), function ($newLocation) {
            $this->location = $newLocation;
        });
    }
}

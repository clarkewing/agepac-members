<?php

namespace App;

trait HasReputation
{
    public function initializeHasReputation()
    {
        $this->fillable[] = 'reputation';
    }

    /**
     * Reset user reputation.
     *
     * @return void
     */
    public function resetReputation(): void
    {
        $this->update(['reputation' => 0]);
    }

    /**
     * Award reputation points to the model.
     *
     * @param  int|string  $points
     * @return void
     */
    public function gainReputation($points): void
    {
        if (is_string($points)) {
            $points = config("council.reputation.{$points}");
        }

        $this->increment('reputation', $points);
    }

    /**
     * Reduce reputation points for the model.
     *
     * @param  int|string  $points
     * @return void
     */
    public function loseReputation($points): void
    {
        if (is_string($points)) {
            $points = config("council.reputation.{$points}");
        }

        $this->decrement('reputation', $points);
    }
}

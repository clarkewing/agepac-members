<?php

namespace Tests\Feature;

use App\Thread;
use App\User;
use Tests\TestCase;

class ProfilesTest extends TestCase
{
    protected $user;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = create(User::class);
    }

    /** @test */
    public function testUserHasAProfile()
    {
        $this->getProfile()
            ->assertSee($this->user->name);
    }

    /** @test */
    public function testProfileShowsAssociatedUsersReputation()
    {
        $this->getProfile()
            ->assertSee($this->user->reputation . ' XP');
    }

    /** @test */
    public function testProfileShowsAssociatedUsersClass()
    {
        $this->getProfile()
            ->assertSee($this->user->class);
    }

    /** @test */
    public function testProfileShowsAssociatedUsersEmail()
    {
        $this->getProfile()
            ->assertSee($this->user->email);
    }

    /** @test */
    public function testProfileShowsAssociatedUsersPhoneNumber()
    {
        $this->getProfile()
            ->assertSee($this->user->phone->formatInternational());
    }

    /** @test */
    public function testProfileHidesLocationIfUnknown()
    {
        $this->getProfile()
            ->assertDontSee('Lieu :');
    }

    /** @test */
    public function testProfileShowsAssociatedUsersLocation()
    {
        $this->getProfile(
            $user = factory(User::class)->states('with_location')->create()
        )->assertSee("{$user->location->municipality}, {$user->location->country}");
    }

    /** @test */
    public function testProfileHidesFlightHoursIfUnknown()
    {
        $this->getProfile(create(User::class, ['flight_hours' => null]))
            ->assertDontSee('heures de vol');
    }

    /** @test */
    public function testProfileShowsAssociatedUsersFlightHours()
    {
        $this->getProfile(create(User::class, ['flight_hours' => 150]))
            ->assertSee('150 heures de vol');
    }

    /** @test */
    public function testProfileShowsAssociatedUsersBio()
    {
        $this->getProfile()
            ->assertSee($this->user->bio);
    }

    /** @test */
    public function testProfilesDisplayAllThreadsCreatedByAssociatedUser()
    {
        $this->signIn($this->user);

        $thread = create(Thread::class, ['user_id' => $this->user->id]);

        $this->getProfile()
            ->assertSee($thread->title)
            ->assertSee($thread->body);
    }

    /**
     * Get the user's profile.
     *
     * @param  \App\User|null  $user
     * @return \Illuminate\Testing\TestResponse
     */
    protected function getProfile(User $user = null): \Illuminate\Testing\TestResponse
    {
        return $this->get(route('profiles.show', $user ?? $this->user));
    }
}

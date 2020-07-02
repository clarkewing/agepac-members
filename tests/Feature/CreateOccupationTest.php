<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CreateOccupationTest extends TestCase
{
    /**
     * @var array The data to create a new occupation
     */
    protected $data = [
        'position' => 'FO',
        'aircraft_id' => 32, // DHC8 Q400
        'company' => 'Flybe',
        'status_code' => 1,
        'description' => 'Awesome description, even though the company went bust.',
        'start_date' => '2019-08-01',
        'end_date' => '2020-03-05',
        'is_primary' => false,
        'location' => [
            'type' => 'city',
            'name' => 'Belfast, Royaume-Uni',
            'street_line_1' => null,
            'street_line_2' => null,
            'municipality' => 'Belfast',
            'administrative_area' => 'Northern Ireland',
            'sub_administrative_area' => 'Antrim',
            'postal_code' => 'BT3',
            'country' => 'Royaume-Uni',
            'country_code' => 'GB',
        ],
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->withExceptionHandling()->signIn();
    }

    /** @test */
    public function testGuestCannotStoreOccupation()
    {
        Auth::logout();

        $this->storeOccupation()
            ->assertUnauthorized();
    }

    /** @test */
    public function testPositionIsRequired()
    {
        $this->storeOccupation(['position' => null])
            ->assertJsonValidationErrors('position');
    }

    /** @test */
    public function testPositionMustBeString()
    {
        $this->storeOccupation(['position' => 12345])
            ->assertJsonValidationErrors('position');
    }

    /** @test */
    public function testPositionCannotBeLongerThan255Characters()
    {
        $this->storeOccupation(['position' => str_repeat('*', 256)])
            ->assertJsonValidationErrors('position');
    }

    /** @test */
    public function testAircraftIdCanBeNull()
    {
        $this->storeOccupation(['aircraft_id' => null])
            ->assertJsonMissingValidationErrors('aircraft_id');
    }

    /** @test */
    public function testAircraftIdMustBeInteger()
    {
        $this->storeOccupation(['aircraft_id' => 'foo'])
            ->assertJsonValidationErrors('aircraft_id');
    }

    /** @test */
    public function testAircraftIdMustExist()
    {
        $this->storeOccupation(['aircraft_id' => 9999])
            ->assertJsonValidationErrors('aircraft_id');

        $this->storeOccupation(['aircraft_id' => 1])
            ->assertJsonMissingValidationErrors('aircraft_id');
    }

    /** @test */
    public function testCompanyIsRequired()
    {
        $this->storeOccupation(['company' => null])
            ->assertJsonValidationErrors('company');
    }

    /** @test */
    public function testCompanyMustBeString()
    {
        $this->storeOccupation(['company' => 12345])
            ->assertJsonValidationErrors('company');
    }

    /** @test */
    public function testCompanyCannotBeLongerThan255Characters()
    {
        $this->storeOccupation(['company' => str_repeat('*', 256)])
            ->assertJsonValidationErrors('company');
    }

    /** @test */
    public function testLocationIsRequired()
    {
        $this->storeOccupation(['location' => null])
            ->assertJsonValidationErrors('location');
    }

    /** @test */
    public function testLocationMustBeValid()
    {
        $this->storeOccupation(['location' => 'foobar'])
            ->assertJsonValidationErrors('location');

        $this->storeOccupation(['location' => []])
            ->assertJsonValidationErrors('location');
    }

    /** @test */
    public function testStatusCodeIsRequired()
    {
        $this->storeOccupation(['status_code' => null])
            ->assertJsonValidationErrors('status_code');
    }

    /** @test */
    public function testStatusCodeMustExist()
    {
        $this->storeOccupation(['status_code' => 999])
            ->assertJsonValidationErrors('status_code');
    }

    /** @test */
    public function testStartDateIsRequired()
    {
        $this->storeOccupation(['start_date' => null])
            ->assertJsonValidationErrors('start_date');
    }

    /** @test */
    public function testStartDateMustBeDateInIsoFormat()
    {
        $this->storeOccupation(['start_date' => 'foobar'])
            ->assertJsonValidationErrors('start_date');

        $this->storeOccupation(['start_date' => 12345678])
            ->assertJsonValidationErrors('start_date');

        $this->storeOccupation(['start_date' => '01/01/2020'])
            ->assertJsonValidationErrors('start_date');
    }

    /** @test */
    public function testEndDateCanBeNull()
    {
        $this->storeOccupation(['end_date' => null])
            ->assertJsonMissingValidationErrors('end_date');
    }

    /** @test */
    public function testEndDateMustBeDateInIsoFormat()
    {
        $this->storeOccupation(['end_date' => 'foobar'])
            ->assertJsonValidationErrors('end_date');

        $this->storeOccupation(['end_date' => 12345678])
            ->assertJsonValidationErrors('end_date');

        $this->storeOccupation(['end_date' => '01/01/2020'])
            ->assertJsonValidationErrors('end_date');
    }

    /** @test */
    public function testEndDateMustBeInPast()
    {
        $this->storeOccupation(['end_date' => '2099-12-31'])
            ->assertJsonValidationErrors('end_date');
    }

    /** @test */
    public function testStartAndEndDatesMustBeChronological()
    {
        $this->storeOccupation([
            'start_date' => '2000-01-01',
            'end_date' => '1999-12-31',
        ])->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /** @test */
    public function testNullEndDateDoesNotLimitStartDate()
    {
        $this->storeOccupation([
            'start_date' => '2000-01-01',
            'end_date' => null,
        ])->assertJsonMissingValidationErrors('start_date');
    }

    /** @test */
    public function testDescriptionCanBeNull()
    {
        $this->storeOccupation(['description' => null])
            ->assertJsonMissingValidationErrors('description');
    }

    /** @test */
    public function testDescriptionMustBeString()
    {
        $this->storeOccupation(['description' => 12345])
            ->assertJsonValidationErrors('description');
    }

    /** @test */
    public function testDescriptionCannotBeLongerThan65535Characters()
    {
        $this->storeOccupation(['description' => str_repeat('*', 65536)])
            ->assertJsonValidationErrors('description');
    }

    /** @test */
    public function testCanStoreOccupation()
    {
        $this->storeOccupation()
            ->assertJsonMissingValidationErrors()
            ->assertCreated()
            ->assertJson($this->data);
    }

    /**
     * Send a request to store the occupation.
     *
     * @param  array  $overrides
     * @return \Illuminate\Testing\TestResponse
     */
    protected function storeOccupation(array $overrides = [])
    {
        return $this->postJson(
            route('occupations.store'),
            array_merge($this->data, $overrides)
        );
    }
}

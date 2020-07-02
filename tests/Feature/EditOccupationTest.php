<?php

namespace Tests\Feature;

use App\Occupation;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class EditOccupationTest extends TestCase
{
    /**
     * @var \App\Occupation
     */
    protected $occupation;

    public function setUp(): void
    {
        parent::setUp();

        $this->withExceptionHandling()->signIn();

        $this->occupation = create(Occupation::class, ['user_id' => Auth::id()]);
    }

    /** @test */
    public function testGuestCannotUpdateOccupation()
    {
        Auth::logout();

        $this->updateOccupation()
            ->assertUnauthorized();
    }

    /** @test */
    public function testOnlyAuthorizedUserCanUpdateOccupation()
    {
        $this->updateOccupation()
            ->assertOk();

        $this->signIn(); // Other user

        $this->updateOccupation()
            ->assertForbidden();
    }

    /** @test */
    public function testPositionIsRequiredIfSet()
    {
        $this->updateOccupation(['position' => null])
            ->assertJsonValidationErrors('position');
    }

    /** @test */
    public function testPositionMustBeString()
    {
        $this->updateOccupation(['position' => 12345])
            ->assertJsonValidationErrors('position');
    }

    /** @test */
    public function testPositionCannotBeLongerThan255Characters()
    {
        $this->updateOccupation(['position' => str_repeat('*', 256)])
            ->assertJsonValidationErrors('position');
    }

    /** @test */
    public function testAircraftIdCanBeNull()
    {
        $this->updateOccupation(['aircraft_id' => null])
            ->assertJsonMissingValidationErrors('aircraft_id');
    }

    /** @test */
    public function testAircraftIdMustBeInteger()
    {
        $this->updateOccupation(['aircraft_id' => 'foo'])
            ->assertJsonValidationErrors('aircraft_id');
    }

    /** @test */
    public function testAircraftIdMustExist()
    {
        $this->updateOccupation(['aircraft_id' => 9999])
            ->assertJsonValidationErrors('aircraft_id');

        $this->updateOccupation(['aircraft_id' => 1])
            ->assertJsonMissingValidationErrors('aircraft_id');
    }

    /** @test */
    public function testCompanyIsRequiredIfSet()
    {
        $this->updateOccupation(['company' => null])
            ->assertJsonValidationErrors('company');
    }

    /** @test */
    public function testCompanyMustBeString()
    {
        $this->updateOccupation(['company' => 12345])
            ->assertJsonValidationErrors('company');
    }

    /** @test */
    public function testCompanyCannotBeLongerThan255Characters()
    {
        $this->updateOccupation(['company' => str_repeat('*', 256)])
            ->assertJsonValidationErrors('company');
    }

    /** @test */
    public function testLocationIsRequiredIfSet()
    {
        $this->updateOccupation(['location' => null])
            ->assertJsonValidationErrors('location');
    }

    /** @test */
    public function testLocationMustBeValid()
    {
        $this->updateOccupation(['location' => 'foobar'])
            ->assertJsonValidationErrors('location');

        $this->updateOccupation(['location' => []])
            ->assertJsonValidationErrors('location');
    }

    /** @test */
    public function testStatusCodeIsRequiredIfSet()
    {
        $this->updateOccupation(['status_code' => null])
            ->assertJsonValidationErrors('status_code');
    }

    /** @test */
    public function testStatusCodeMustExist()
    {
        $this->updateOccupation(['status_code' => 999])
            ->assertJsonValidationErrors('status_code');
    }

    /** @test */
    public function testStartDateIsRequiredIfSet()
    {
        $this->updateOccupation(['start_date' => null])
            ->assertJsonValidationErrors('start_date');
    }

    /** @test */
    public function testStartDateMustBeDateInIsoFormat()
    {
        // Set end_date to null to prevent conflicts.
        $this->occupation->update(['end_date' => null]);

        $this->updateOccupation(['start_date' => 'foobar'])
            ->assertJsonValidationErrors('start_date');

        $this->updateOccupation(['start_date' => 12345678])
            ->assertJsonValidationErrors('start_date');

        $this->updateOccupation(['start_date' => '01/01/2020'])
            ->assertJsonValidationErrors('start_date');
    }

    /** @test */
    public function testEndDateCanBeNull()
    {
        $this->updateOccupation(['end_date' => null])
            ->assertJsonMissingValidationErrors('end_date');
    }

    /** @test */
    public function testEndDateMustBeDateInIsoFormat()
    {
        $this->updateOccupation(['end_date' => 'foobar'])
            ->assertJsonValidationErrors('end_date');

        $this->updateOccupation(['end_date' => 12345678])
            ->assertJsonValidationErrors('end_date');

        $this->updateOccupation(['end_date' => '01/01/2020'])
            ->assertJsonValidationErrors('end_date');
    }

    /** @test */
    public function testStartAndEndDatesMustBeChronological()
    {
        $this->updateOccupation([
            'start_date' => '2000-01-01',
            'end_date' => '1999-12-31',
        ])->assertJsonValidationErrors(['start_date', 'end_date']);
    }

    /** @test */
    public function testStartDateMustBeBeforeExistingEndDate()
    {
        $this->occupation->update(['end_date' => '1999-12-31']);

        $this->updateOccupation(['start_date' => '2000-01-01'])
            ->assertJsonValidationErrors('start_date');
    }

    /** @test */
    public function testEndDateMustBeAfterExistingStartDate()
    {
        $this->occupation->update(['start_date' => '2000-01-01']);

        $this->updateOccupation(['end_date' => '1999-12-31'])
            ->assertJsonValidationErrors('end_date');
    }

    /** @test */
    public function testEndDateMustBeInPast()
    {
        $this->updateOccupation(['end_date' => '2099-12-31'])
            ->assertJsonValidationErrors('end_date');
    }

    /** @test */
    public function testNullEndDateDoesNotLimitStartDate()
    {
        $this->updateOccupation([
            'start_date' => '2000-01-01',
            'end_date' => null,
        ])
            ->assertJsonMissingValidationErrors('start_date')
            ->assertOk();

        $this->updateOccupation(['start_date' => '2000-01-01'])
            ->assertJsonMissingValidationErrors('start_date');
    }

    /** @test */
    public function testDescriptionCanBeNull()
    {
        $this->updateOccupation(['description' => null])
            ->assertJsonMissingValidationErrors('description');
    }

    /** @test */
    public function testDescriptionMustBeString()
    {
        $this->updateOccupation(['description' => 12345])
            ->assertJsonValidationErrors('description');
    }

    /** @test */
    public function testDescriptionCannotBeLongerThan65535Characters()
    {
        $this->updateOccupation(['description' => str_repeat('*', 65536)])
            ->assertJsonValidationErrors('description');
    }

    /** @test */
    public function testIsPrimaryMustBeBoolean()
    {
        $this->updateOccupation(['is_primary' => 'foo'])
            ->assertJsonValidationErrors('is_primary');

        $this->updateOccupation(['is_primary' => 123])
            ->assertJsonValidationErrors('is_primary');
    }

    /** @test */
    public function testCannotBeSetToPrimaryIfEndDateIsInPast()
    {
        // End date being updated in request.
        $this->updateOccupation([
            'end_date' => '2000-01-01',
            'is_primary' => true,
        ])->assertJsonValidationErrors('is_primary');

        // End date not being updated in request.
        $occupation = create(Occupation::class, [
            'user_id' => Auth::id(),
            'end_date' => '2000-01-01',
        ]);

        $this->updateOccupation(['is_primary' => true], $occupation)
            ->assertJsonValidationErrors('is_primary');
    }

    /** @test */
    public function testCanBeSetToPrimary()
    {
        $this->occupation->update(['end_date' => null]);

        $this->updateOccupation(['is_primary' => true])
            ->assertJsonMissingValidationErrors('is_primary');
    }

    /** @test */
    public function testCanUnsetPrimary()
    {
        $this->occupation->update(['end_date' => null]);

        $this->updateOccupation(['is_primary' => false])
            ->assertJsonMissingValidationErrors('is_primary');

        $this->occupation->update(['end_date' => '2020-01-01']);

        $this->updateOccupation(['is_primary' => false])
            ->assertJsonMissingValidationErrors('is_primary');
    }

    /** @test */
    public function testCanUpdateOccupation()
    {
        $data = [
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
                'sub_administrative_area' => 'Royaume-Uni',
                'postal_code' => 'BT1',
                'country' => 'Royaume-Uni',
                'country_code' => 'GB',
            ],
        ];

        $this->updateOccupation($data)
            ->assertJsonMissingValidationErrors()
            ->assertOk()
            ->assertJson($data);
    }

    /**
     * Send a request to update the occupation.
     *
     * @param  array  $data
     * @param  null  $occupation
     * @return \Illuminate\Testing\TestResponse
     */
    protected function updateOccupation(array $data = [], $occupation = null)
    {
        return $this->patchJson(
            route(
                'occupations.update',
                $occupation ?? $this->occupation
            ),
            $data
        );
    }
}

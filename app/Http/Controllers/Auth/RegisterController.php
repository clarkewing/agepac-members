<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use App\UserInvitation;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Torann\GeoIP\Facades\GeoIP;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @return string
     */
    protected function redirectTo()
    {
        return route('home');
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'class_course' => ['required', Rule::in(config('council.courses'))],
            'class_year' => ['required', 'digits:4'],
            'gender' => ['required', Rule::in(array_keys(config('council.genders')))],
            'birthdate' => ['required', 'date_format:Y-m-d', 'before:13 years ago'],
            'phone' => [
                'required',
                Rule::phone()->detect() // Auto-detect country if country code supplied
                    ->country(['FR', GeoIP::getLocation(request()->ip())->iso_code]), // Fallback to France then GeoIP if unable to auto-detect
            ],
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return \App\User
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function create(array $data)
    {
        $userInvitation = $this->validateInvited($data);

        $user = User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'username' => User::makeUsername($data['first_name'], $data['last_name']),
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'class_course' => $data['class_course'],
            'class_year' => $data['class_year'],
            'gender' => $data['gender'],
            'birthdate' => $data['birthdate'],
            'phone' => $data['phone'],
        ]);

        $userInvitation->delete();

        return $user;
    }

    /**
     * Validate that the attempted registration is for an invited user.
     *
     * @param  array  $data
     * @return \App\UserInvitation
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateInvited(array $data): UserInvitation
    {
        $userInvitation = UserInvitation::where([
            ['first_name', $data['first_name']],
            ['last_name', $data['last_name']],
            ['class_course', $data['class_course']],
            ['class_year', $data['class_year']],
        ])->first();

        if (is_null($userInvitation)) {
            throw ValidationException::withMessages([
                'invitation' => 'Impossible de créer un compte sans invitation',
            ]);
        }

        return $userInvitation;
    }
}

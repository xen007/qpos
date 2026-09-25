<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\PasswordReset;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Rules\ValidImageType;
use App\Models\ForgetPassword;
use App\Trait\FileHandler;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public $fileHandler;

    public function __construct(FileHandler $fileHandler)
    {
        $this->fileHandler = $fileHandler;
    }

    public function login(Request $request)
    {
        if ($request->isMethod('post')) {

            $request->validate(
                [
                    'email' => 'required',
                    'password' => 'required'
                ]
            );

            if (!Auth::validate($request->only('email', 'password'))) {
                return redirect()->back()->with('error', __('Incorrect email or password'));
            }

            $user = User::where('email', $request->email)->first();
            if ($user->is_suspended == 1) {
                return redirect()->back()->with('error', __('Your account is temporarily suspended'));
            }

            $remember = $request->remember_me ? true : false;
            $credentials['email'] = $request->email;
            $credentials['password'] = $request->password;
            $credentials['remember'] = $remember;
            $credentials['previous_url'] = $request->previous_url;

            $valid = Arr::only($credentials, ['email', 'password']);

            if (Auth::attempt($valid, $credentials['remember'])) {
                session()->regenerate();

                return $this->redirectUser();
            } else {
                return redirect()->route('login')->with('error', __('Incorrect email or password'));
            }
        } else {
            if (auth()->user()) {
                return $this->redirectUser();
            } else {
                return view('frontend.authentication.login');
            }
        }
    }

    public function forgetPassword(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'email' => 'email|required',
            ]);
            $request->session()->forget([
                'user_id',
                'reset-email',
                'password-reset-verified-user',
            ]);
            $findUser = User::where('email', $request->email)->first();

            $otp = random_int(10000, 99999);

            if ($findUser) {
                ForgetPassword::updateOrCreate(
                    [
                        'user_id' => $findUser->id
                    ],
                    [
                        'otp' => $otp,
                        'email' => $findUser->email,
                        'suspend_duration' => now()->addMinutes(5)
                    ]
                );

                session([
                    'user_id' => $findUser->id,
                    'reset-email' => $findUser->email
                ]);

                $mailData = [
                    'title' => readConfig('site_name'),
                    'otp' => $otp,
                    'name' => $findUser->name,
                ];

                Mail::to($findUser->email)->send(new PasswordReset($mailData));

                return redirect()->route('password.reset')->with('success', __('Check your inbox for the one-time code.'));
            } else {
                return back()->with('error', __('User not found'));
            }
        } else {
            return view('frontend.authentication.forget-password');
        }
    }

    public function resendOtp()
    {
        $findUser = ForgetPassword::where('user_id', session('user_id'))
            ->where('email', session('reset-email'))
            ->first();

        if ($findUser) {
            $user = User::find(session('user_id'));
            if (!$user) {
                $findUser->delete();
                return back()->with('error', __('Something went wrong. Please try again.'));
            }
            $otp = random_int(10000, 99999);

            $findUser->otp = $otp;
            $findUser->failed_attempt = 0;
            $findUser->resent_count++;
            $findUser->suspend_duration = now()->addMinutes(5);
            $findUser->save();

            $mailData = [
                'title' => readConfig('site_name'),
                'otp' => $otp,
                'name' => $user->name,
            ];
            Mail::to($findUser->email)->send(new PasswordReset($mailData));

            return back()->with('success', __('A new one-time code was sent.'));
        } else {
            return back()->with('error', __('Something went wrong. Please try again.'));
        }
    }

    public function newPassword(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'password' => 'required|confirmed|min:6',
            ]);

            $verifiedUserId = $request->session()->get('password-reset-verified-user');
            $user = $verifiedUserId ? User::find($verifiedUserId) : null;

            if ($user && hash_equals((string) $user->email, (string) $request->session()->get('reset-email'))) {
                $user->password = Hash::make($request->password);
                $user->remember_token = Str::random(60);
                $user->save();

                ForgetPassword::where('user_id', $user->id)->delete();
                $request->session()->forget([
                    'user_id',
                    'reset-email',
                    'password-reset-verified-user',
                ]);
                $request->session()->regenerate();

                return redirect()->route('login')->with('success', __('Password reset successfully'));
            } else {
                return redirect()->route('forget.password')->with('error', __('Something went wrong. Please try again.'));
            }
        } else {
            $verifiedUserId = $request->session()->get('password-reset-verified-user');
            $user = $verifiedUserId ? User::find($verifiedUserId) : null;

            if (!$user || !hash_equals((string) $user->email, (string) $request->session()->get('reset-email'))) {
                return redirect()->route('forget.password');
            }

            return view('frontend.authentication.new-password');
        }
    }

    public function resetPassword(Request $request)
    {
        $resetEmail = $request->session()->get('reset-email');
        if (!$resetEmail) {
            return redirect()->route('forget.password');
        }

        if ($request->isMethod('post')) {
            $request->validate([
                'number_1' => 'required|digits:1',
                'number_2' => 'required|digits:1',
                'number_3' => 'required|digits:1',
                'number_4' => 'required|digits:1',
                'number_5' => 'required|digits:1',
            ]);

            $otp = $request->number_1 . $request->number_2 . $request->number_3 . $request->number_4 . $request->number_5;
            $record = ForgetPassword::where('email', $resetEmail)->first();

            if (!$record) {
                return redirect()->route('forget.password')->with('error', __('Request a new password reset code.'));
            }

            if (now()->greaterThan(Carbon::parse($record->suspend_duration))) {
                $record->delete();
                return redirect()->route('forget.password')->with('error', __('The one-time code has expired.'));
            }

            if ($record->failed_attempt >= 5) {
                $record->delete();
                return redirect()->route('forget.password')->with('error', __('Too many attempts. Request a new code.'));
            }

            if (!hash_equals((string) $record->otp, (string) $otp)) {
                $record->failed_attempt++;

                if ($record->failed_attempt >= 5) {
                    $record->delete();
                    return redirect()->route('forget.password')->with('error', __('Too many attempts. Request a new code.'));
                }

                $record->save();
                return back()->with('error', __('The one-time code is invalid.'));
            }

            $request->session()->put('password-reset-verified-user', $record->user_id);
            $record->delete();
            $request->session()->regenerate();

            return redirect()->route('new.password');
        }

        return view('frontend.authentication.reset');
    }
    public function logout(Request $request)
    {
        if (auth()->user()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        }

        return back()->with('error', __('You are not logged in'));
    }
    public function update(Request $request)
    {
        $user = User::find(auth()->id());

        if (demoUserCheck($user->email)) {
            return back()->with('error', __('Cannot update details of demo user'));
        }
        // dd($request->all());
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'profile_image' => ['file', new ValidImageType]
        ]);

        if ($request->name !== $user->name) {
            $user->name = $request->name;
        }

        if ($request->email !== $user->email) {
            $user->email = $request->email;
            $user->google_id = null;
        }

        if ($request->hasFile("profile_image")) {
            $user->profile_image = $this->fileHandler->fileUploadAndGetPath($request->file("profile_image"), "/public/media/users");
        }

        if ($request->current_password || $request->new_password || $request->confirm_password) {

            $request->validate([
                'new_password' => 'required|min:6|confirmed',
            ]);

            if ($user->is_google_registered) {
                $user->is_google_registered = false;
            } else {
                $request->validate([
                    'current_password' => 'required',
                ]);

                $currentPassword = $request->current_password;

                if (!Hash::check($currentPassword, $user->password)) {
                    throw ValidationException::withMessages([
                        'current_password' => 'The current password is incorrect',
                    ]);
                }
            }

            $user->password = bcrypt($request->new_password);
        }

        $user->save();

        return back()->with('success', __('Updated Successfully'));
    }

    public function redirectUser()
    {
        if (Auth::check()) {
            return redirect()->route('backend.admin.dashboard');
        } else {
            return redirect()->route('login')->with('error', __('You are not logged in'));
        }
    }
}

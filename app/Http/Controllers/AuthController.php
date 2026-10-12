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
use Illuminate\Support\Facades\DB;
use App\Services\AdministrationAudit;
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
                    'email' => 'required|string|email|max:254',
                    'password' => 'required|string|max:255',
                ]
            );

            if (!Auth::validate($request->only('email', 'password'))) {
                return redirect()->back()->with('error', __('Incorrect email or password'));
            }

            $user = User::where('email', $request->email)->firstOrFail();
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
                'password' => 'required|string|confirmed|min:8|max:255',
            ]);

            $verifiedUserId = $request->session()->get('password-reset-verified-user');
            $user = $verifiedUserId ? User::find($verifiedUserId) : null;

            if ($user && hash_equals((string) $user->email, (string) $request->session()->get('reset-email'))) {
                DB::transaction(function () use ($user,$request) {
                    $user=User::whereKey($user->id)->lockForUpdate()->firstOrFail();
                    $user->password = Hash::make($request->password);
                    $user->remember_token = Str::random(60);
                    $user->save();
                    ForgetPassword::where('user_id',$user->id)->delete();
                    app(AdministrationAudit::class)->record('user.password_reset','user',$user->id,[],['password_changed'=>true]);
                });
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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:254|unique:users,email,' . $user->id,
            'profile_image' => ['file', new ValidImageType, 'max:2048']
        ]);

        $changePassword=$request->filled('current_password') || $request->filled('new_password') || $request->filled('new_password_confirmation');
        if ($changePassword) {
            $request->validate([
                'new_password' => 'required|string|min:8|max:255|confirmed',
                'current_password' => 'required|string|max:255',
            ]);
        }
        $oldImage=null;$newImage=null;
        try {
            DB::transaction(function () use ($request,$changePassword,&$oldImage,&$newImage) {
                $user=User::whereKey(auth()->id())->lockForUpdate()->firstOrFail();
                if($changePassword && !Hash::check($request->current_password,$user->password)) {
                    throw ValidationException::withMessages(['current_password'=>__('The current password is incorrect')]);
                }
                abort_unless(\Illuminate\Support\Facades\Schema::hasTable('audit_logs'),503,__('administration.migration_required'));
                $before=['name'=>$user->name,'email'=>$user->email];
                $user->name=$request->name;$user->email=$request->email;
                if($request->hasFile('profile_image')) {
                    $oldImage=$user->profile_image;
                    $newImage=$this->fileHandler->fileUploadAndGetPath($request->file('profile_image'),'/public/media/users');
                    $user->profile_image=$newImage;
                }
                if($changePassword){$user->password=Hash::make($request->new_password);$user->is_google_registered=false;}
                $user->save();
                app(AdministrationAudit::class)->record('user.profile_updated','user',$user->id,$before,['name'=>$user->name,'email'=>$user->email,'password_changed'=>$changePassword,'profile_image_changed'=>$newImage!==null],\App\Support\UserAdministration::auditShop($user,$user));
            });
        } catch (\Throwable $e) {if($newImage)$this->fileHandler->secureUnlink($newImage);throw $e;}
        if($oldImage)$this->fileHandler->secureUnlink($oldImage);

        return back()->with('success', __('Updated Successfully'));
    }

    public function redirectUser()
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->can('dashboard_view')) {
                return redirect()->route('backend.admin.dashboard');
            }

            if ($user->hasAnyPermission(['sale_create','pending_sale_prepare'])) {
                return redirect()->route('backend.admin.cart.index');
            }

            if ($user->can('cash_session_manage')) {
                return redirect()->route('backend.admin.cash.index');
            }

            return redirect()->route('backend.admin.profile');
        } else {
            return redirect()->route('login')->with('error', __('You are not logged in'));
        }
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\StrongPasswordPattern;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'security_question_1' => trim((string) $request->security_question_1),
            'security_answer_1' => trim((string) $request->security_answer_1),
            'security_question_2' => trim((string) $request->security_question_2),
            'security_answer_2' => trim((string) $request->security_answer_2),
            'security_question_3' => trim((string) $request->security_question_3),
            'security_answer_3' => trim((string) $request->security_answer_3),
        ]);

        $uppercaseAnswer = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value !== mb_strtoupper($value, 'UTF-8')) {
                $fail('Security answers must be entered in CAPITAL LETTERS only.');
            }
        };

        $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults(), new StrongPasswordPattern()],
            'security_question_1' => ['required', 'string', 'max:255'],
            'security_answer_1' => ['required', 'string', 'max:255', $uppercaseAnswer],
            'security_question_2' => ['required', 'string', 'max:255'],
            'security_answer_2' => ['required', 'string', 'max:255', $uppercaseAnswer],
            'security_question_3' => ['required', 'string', 'max:255'],
            'security_answer_3' => ['required', 'string', 'max:255', $uppercaseAnswer],
        ], [
            'username.unique' => 'Username already exists.',
        ]);

        $user = User::create([
            'name' => $request->username,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'security_question_1' => $request->security_question_1,
            'security_answer_1' => Hash::make($request->security_answer_1),
            'security_question_2' => $request->security_question_2,
            'security_answer_2' => Hash::make($request->security_answer_2),
            'security_question_3' => $request->security_question_3,
            'security_answer_3' => Hash::make($request->security_answer_3),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('two-factor.setup', absolute: false));
    }
}

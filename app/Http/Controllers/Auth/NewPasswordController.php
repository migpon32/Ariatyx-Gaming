<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View|RedirectResponse
    {
        $user = $this->passwordResetUser($request);

        if (! $user) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password', [
            'email' => $user->email,
            'questions' => [
                $user->security_question_1,
                $user->security_question_2,
                $user->security_question_3,
            ],
        ]);
    }

    /**
     * Handle an incoming new password request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'security_answer_1' => trim((string) $request->security_answer_1),
            'security_answer_2' => trim((string) $request->security_answer_2),
            'security_answer_3' => trim((string) $request->security_answer_3),
        ]);

        $uppercaseAnswer = function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value !== mb_strtoupper($value, 'UTF-8')) {
                $fail('Security answers must be entered in CAPITAL LETTERS only.');
            }
        };

        $request->validate([
            'security_answer_1' => ['required', 'string', 'max:255', $uppercaseAnswer],
            'security_answer_2' => ['required', 'string', 'max:255', $uppercaseAnswer],
            'security_answer_3' => ['required', 'string', 'max:255', $uppercaseAnswer],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $this->passwordResetUser($request);

        if (! $user) {
            return redirect()->route('password.request');
        }

        if (! $this->answersMatch($request, $user)) {
            throw ValidationException::withMessages([
                'security_answer_1' => 'The security answers do not match our records.',
            ]);
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        event(new PasswordReset($user));

        $request->session()->forget('password_reset_user_id');

        return redirect()
            ->route('login')
            ->with('status', 'Your password has been reset. You can now sign in.');
    }

    private function passwordResetUser(Request $request): ?User
    {
        $userId = $request->session()->get('password_reset_user_id');

        return $userId ? User::find($userId) : null;
    }

    private function answersMatch(Request $request, User $user): bool
    {
        return Hash::check($request->security_answer_1, $user->security_answer_1)
            && Hash::check($request->security_answer_2, $user->security_answer_2)
            && Hash::check($request->security_answer_3, $user->security_answer_3);
    }
}

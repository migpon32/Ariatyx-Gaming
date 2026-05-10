<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'We could not find an account for that Gmail address.',
            ]);
        }

        if ($this->missingSecurityQuestions($user)) {
            throw ValidationException::withMessages([
                'email' => 'This account does not have security questions configured. Please contact support.',
            ]);
        }

        $request->session()->put('password_reset_user_id', $user->id);

        return redirect()
            ->route('password.reset')
            ->with('status', 'Answer your security questions to create a new password.');
    }

    private function missingSecurityQuestions(User $user): bool
    {
        return blank($user->security_question_1)
            || blank($user->security_answer_1)
            || blank($user->security_question_2)
            || blank($user->security_answer_2)
            || blank($user->security_question_3)
            || blank($user->security_answer_3);
    }
}

<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('reset password request screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('security question reset screen can be requested', function () {
    $user = User::factory()->create();

    $response = $this->post('/forgot-password', ['email' => $user->email]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('password.reset'));

    $this->withSession(['password_reset_user_id' => $user->id])
        ->get('/reset-password')
        ->assertStatus(200)
        ->assertSee($user->security_question_1)
        ->assertSee($user->security_question_2)
        ->assertSee($user->security_question_3);
});

test('password can be reset with valid security answers', function () {
    $user = User::factory()->create();

    $this->withSession(['password_reset_user_id' => $user->id])
        ->post('/reset-password', [
            'security_answer_1' => 'MANILA',
            'security_answer_2' => 'BULLET DROP',
            'security_answer_3' => 'ARIATYX',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login'));

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('password cannot be reset with lowercase security answers', function () {
    $user = User::factory()->create();

    $this->withSession(['password_reset_user_id' => $user->id])
        ->post('/reset-password', [
            'security_answer_1' => 'manila',
            'security_answer_2' => 'BULLET DROP',
            'security_answer_3' => 'ARIATYX',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])
        ->assertSessionHasErrors('security_answer_1');
});

test('password cannot be reset without matching confirmation', function () {
    $user = User::factory()->create();

    $this->withSession(['password_reset_user_id' => $user->id])
        ->post('/reset-password', [
            'security_answer_1' => 'MANILA',
            'security_answer_2' => 'BULLET DROP',
            'security_answer_3' => 'ARIATYX',
            'password' => 'new-password',
            'password_confirmation' => 'different-password',
        ])
        ->assertSessionHasErrors('password');
});

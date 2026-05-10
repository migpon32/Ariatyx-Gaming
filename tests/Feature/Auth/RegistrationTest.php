<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'security_question_1' => 'What city were you born in?',
        'security_answer_1' => 'MANILA',
        'security_question_2' => 'What is your favorite game?',
        'security_answer_2' => 'BULLET DROP',
        'security_question_3' => 'What is your first school?',
        'security_answer_3' => 'ARIATYX',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('security answers must be capital letters during registration', function () {
    $response = $this->post('/register', [
        'username' => 'testuser',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'security_question_1' => 'What city were you born in?',
        'security_answer_1' => 'Manila',
        'security_question_2' => 'What is your favorite game?',
        'security_answer_2' => 'BULLET DROP',
        'security_question_3' => 'What is your first school?',
        'security_answer_3' => 'ARIATYX',
    ]);

    $response->assertSessionHasErrors('security_answer_1');
    $this->assertGuest();
});

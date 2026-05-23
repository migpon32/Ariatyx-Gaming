<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;

class StrongPasswordPattern implements DataAwareRule, ValidationRule
{
    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $password = Str::lower((string) $value);

        if ($this->containsCommonWord($password)) {
            $fail('The :attribute must not contain common words such as password, admin, qwerty, player, or Ariatyx game names.');
        }

        if ($this->containsPersonalInfo($password)) {
            $fail('The :attribute must not contain your username, name, or email.');
        }

        if ($this->containsKeyboardPattern($password)) {
            $fail('The :attribute must not contain predictable keyboard or number patterns.');
        }

        if (preg_match('/(.)\1{3,}/', $password)) {
            $fail('The :attribute must not contain the same character repeated four or more times.');
        }
    }

    private function containsCommonWord(string $password): bool
    {
        $blockedWords = [
            'password',
            'passw0rd',
            'admin',
            'administrator',
            'qwerty',
            'welcome',
            'letmein',
            'login',
            'user',
            'player',
            'gamer',
            'ariatyx',
            'gaming',
            'bulletdrop',
        ];

        foreach ($blockedWords as $word) {
            if (str_contains($password, $word)) {
                return true;
            }
        }

        return false;
    }

    private function containsPersonalInfo(string $password): bool
    {
        $personalValues = [
            $this->data['username'] ?? null,
            $this->data['name'] ?? null,
            $this->data['email'] ?? null,
        ];

        foreach ($personalValues as $value) {
            if (! is_string($value) || trim($value) === '') {
                continue;
            }

            $value = Str::lower($value);
            $emailName = Str::before($value, '@');

            foreach (array_unique([$value, $emailName]) as $part) {
                if (strlen($part) >= 4 && str_contains($password, $part)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function containsKeyboardPattern(string $password): bool
    {
        $patterns = [
            'abcdefghijklmnopqrstuvwxyz',
            'qwertyuiop',
            'asdfghjkl',
            'zxcvbnm',
            '0123456789',
        ];

        foreach ($patterns as $pattern) {
            if ($this->containsSequence($password, $pattern) || $this->containsSequence($password, strrev($pattern))) {
                return true;
            }
        }

        return false;
    }

    private function containsSequence(string $password, string $pattern): bool
    {
        for ($length = 4; $length <= 6; $length++) {
            for ($index = 0; $index <= strlen($pattern) - $length; $index++) {
                if (str_contains($password, substr($pattern, $index, $length))) {
                    return true;
                }
            }
        }

        return false;
    }
}

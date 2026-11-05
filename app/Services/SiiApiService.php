<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SiiApiService
{
    private function base()
    {
        return Http::baseUrl(config('services.sii.url'))
            ->acceptJson()
            ->timeout(15);
    }

    public function login(string $email, string $password): ?string
    {
        $res = $this->base()->post('/login', [
            'email' => $email,
            'password' => $password,
        ]);

        return $res->json('message.login.token');
    }

    public function get(string $path, string $token)
    {
        return $this->base()->withToken($token)->get($path);
    }
}
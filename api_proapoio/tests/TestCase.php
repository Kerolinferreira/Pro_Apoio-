<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Helpers\JwtHelper;
use App\Models\User;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Autentica como usuário específico usando JWT
     */
    public function actingAs($user, $guard = null)
    {
        if ($user instanceof User) {
            $token = JwtHelper::generateToken($user);
            $this->withHeader('Authorization', 'Bearer ' . $token);
        }

        return parent::actingAs($user, $guard);
    }
}

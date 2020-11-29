<?php

namespace tests\user;

use ApiClient\exception\NotFoundException;
use ApiClient\model\User;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    public function correctAuthProvider(): array
    {
        return [
            ['test', '12345'],
        ];
    }

    public function notCorrectAuthProvider(): array
    {
        return [
            ['tst', '12345'],
            ['test', '1245'],
            ['tet', '1245'],
        ];
    }

    /**
     * @dataProvider correctAuthProvider
     */
    public function testCorrectAuth(string $login, string $pass): void
    {
        $isCorrectAuth = true;
        try {
            $user = new User($login, $pass);
        } catch (\Throwable $e) {
            $isCorrectAuth = false;
        }
        $this->assertTrue($isCorrectAuth);
    }

    /**
     * @dataProvider notCorrectAuthProvider
     */
    public function testNotCorrectAuth(string $login, string $pass): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Error: {"status":"Not found"}');
        $this->expectExceptionCode(404);
        new User($login, $pass);
    }
}

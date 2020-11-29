<?php

namespace tests\user;

use ApiClient\exception\NotFoundException;
use ApiClient\model\User;
use PHPUnit\Framework\TestCase;

class UpdateTest extends TestCase
{
    public function correctUpdateProvider(): array
    {
        return [
            ['test', '12345', 23, [
                'active' => '1',
                'blocked' => true,
                'name' => 'Petr Petrovich',
                'permissions' => [
                    'id' => 1,
                    'permission' => 'comment',
                ],
            ]],
        ];
    }

    public function notCorrectUidProvider(): array
    {
        return [
            ['test', '12345', 1, [
                'active' => '1',
                'blocked' => true,
                'name' => 'Petr Petrovich',
                'permissions' => [
                    'id' => 1,
                    'permission' => 'comment',
                ],
            ]],
        ];
    }

    /**
     * @dataProvider correctUpdateProvider
     */
    public function testCorrectUpdate(string $login, string $pass, int $uid, array $updateParams): void
    {
        $user = new User($login, $pass);
        $isCorrectUpdate = true;
        try {
            $user->updateData($uid, $updateParams);
        } catch (\Throwable $e) {
            $isCorrectUpdate = false;
        }
        $this->assertTrue($isCorrectUpdate);
    }

    /**
     * @dataProvider notCorrectUidProvider
     */
    public function testNotCorrectUid(string $login, string $pass, int $uid, array $updateParams): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Error: {"status":"Not found"}');
        $this->expectExceptionCode(404);
        $user = new User($login, $pass);
        $user->updateData($uid, $updateParams);
    }
}

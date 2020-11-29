<?php

namespace tests\user;

use ApiClient\component\ApiClient;
use ApiClient\exception\NotAuthException;
use ApiClient\exception\NotFoundException;
use ApiClient\model\User;
use PHPUnit\Framework\TestCase;

class GetDataTest extends TestCase
{
    public function correctUsernameProvider(): array
    {
        return [
            [
                'test', '12345', 'ivanov',
                [
                    'status' => 'Ok',
                    'active' => '1',
                    'blocked' => false,
                    'created_at' => 1587457590,
                    'id' => 23,
                    'name' => 'Ivanov Ivan',
                    'permissions' => [
                        [
                            'id' => 1,
                            'permission' => 'comment',
                        ],
                        [
                            'id' => 2,
                            'permission' => 'upload photo',
                        ],
                        [
                            'id' => 3,
                            'permission' => 'add event',
                        ],
                    ]
                ],
            ]
        ];
    }

    public function notCorrectUsernameProvider(): array
    {
        return [
            ['test', '12345', 'ivaov'],
        ];
    }

    public function notAuthTokenProvider(): array
    {
        return [
            ['ivanov'],
            ['ivaov'],
        ];
    }

    public function notUsernameProvider(): array
    {
        return [
            ['test', '12345'],
        ];
    }

    /**
     * @dataProvider correctUsernameProvider
     */
    public function testCorrectUsername(string $login, string $pass, string $username, array $data): void
    {
        $user = new User($login, $pass);
        $user->setUsername($username);
        $this->assertEquals($user->getData(), $data);
    }

    /**
     * @dataProvider notCorrectUsernameProvider
     */
    public function testNotCorrectUsername(string $login, string $pass, string $username): void
    {
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Error: {"status":"Not found"}');
        $this->expectExceptionCode(404);
        $user = new User($login, $pass);
        $user->setUsername($username);
        $user->getData();
    }

    /**
     * @dataProvider notAuthTokenProvider
     */
    public function testNotAuthToken(string $username): void
    {
        $this->expectException(NotAuthException::class);
        (new ApiClient())->getData($username);
    }

    /**
     * @dataProvider notUsernameProvider
     */
    public function testNotUsername(string $login, string $pass): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Params username cannot be empty.');
        $user = new User($login, $pass);
        $user->getData();
    }
}

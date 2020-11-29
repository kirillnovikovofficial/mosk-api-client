<?php

require_once 'vendor/autoload.php';

const CONTENT_TYPE = 'application/json';

const STATUS_OK = 'Ok';
const STATUS_NOT_FOUND = 'Not found';
const STATUS_ERROR = 'Error';

const SUPPORTED_METHOD_GET = ['auth', 'get-user'];
const SUPPORTED_METHOD_POST = ['user/update'];

$loop = React\EventLoop\Factory::create();

$functions = [
    'auth' => function(array $params) {
        $login = $params['login'] ?? null;
        $pass = $params['pass'] ?? null;
        if (!isset($login, $pass)) {
            throw new InvalidArgumentException();
        }
        if (!($login === 'test' && $pass === '12345')) {
            throw new NotFoundException();
        }
        return ['token' => 'dsfd79843r32d1d3dx23d32d'];
    },
    'get-user' => function(array $params) {
        $username = $params['username'] ?? null;
        $token = $params['token'] ?? null;
        if (!isset($username, $token)) {
            throw new InvalidArgumentException();
        }
        if (!($username === 'ivanov' && $token === 'dsfd79843r32d1d3dx23d32d')) {
            throw new NotFoundException();
        }
        return [
	        'active' => '1',
	        'blocked' => false,
	        'created_at' => 1587457590,
	        'id' => 23,
	        'name' => 'Ivanov Ivan',
	        'permissions' => [
	            [
                    'id' => 1,
                    'permission' => 'comment'
                ],
                [
                    'id' => 2,
                    'permission' => 'upload photo'
                ],
                [
                    'id' => 3,
                    'permission' => 'add event'
                ],
	        ]

        ];
    },
    'user/update' => function(array $params) {
        $userId = $params['user_id'] ?? null;
        $token = $params['token'] ?? null;
        if (!isset($userId, $token)) {
            throw new InvalidArgumentException();
        }
        if (!($userId == 23 && $token === 'dsfd79843r32d1d3dx23d32d')) {
            throw new NotFoundException();
        }
        return [];
    },
];


$server = new React\Http\Server($loop, function (Psr\Http\Message\ServerRequestInterface $request) use ($functions) {
    $requestMethod = $request->getMethod();
    $params = $request->getQueryParams();
    $path = $request->getUri()->getPath();

    $error400 = new React\Http\Message\Response(
        400,
        [
            'Content-Type' => CONTENT_TYPE,
        ],
        json_encode(['status' => STATUS_ERROR])
    );

    $pathSplit = explode('/', $path);
    $pathSplitCount = count($pathSplit);
    if ($pathSplitCount < 2 || $pathSplitCount > 4) {
        return $error400;
    }

    switch ($requestMethod) {
        case 'GET':
            $callMethod = $pathSplit[1];
            if (!in_array($callMethod, SUPPORTED_METHOD_GET)) {
                return $error400;
            }
            if ($callMethod === 'get-user') {
                if (!isset($pathSplit[2])) {
                    return $error400;
                }
                $params += ['username' => $pathSplit[2]];
            }
            return processResponseGet($functions[$callMethod], $params);
        case 'POST':
            if (!isset($pathSplit[3])) {
                return $error400;
            }
            $callMethod = $pathSplit[1] . '/' . $pathSplit[3];
            if (!in_array($callMethod, SUPPORTED_METHOD_POST)) {
                return $error400;
            }
            $params += ['user_id' => $pathSplit[2]];
            return processResponsePost($functions[$callMethod], $params);
        default:
            return new React\Http\Message\Response(
                405,
                [
                    'Content-Type' => CONTENT_TYPE,
                ],
                json_encode(['status' => STATUS_ERROR])
            );
    }

});

class NotFoundException extends RuntimeException
{

}

function processResponseGet(callable $func, array $params): \Psr\Http\Message\ResponseInterface
{
    return processResponse($func, $params, 200);
}

function processResponsePost(callable $func, array $params): \Psr\Http\Message\ResponseInterface
{
    return processResponse($func, $params, 201);
}

function processResponse(callable $func, array $params, int $resultStatus): \Psr\Http\Message\ResponseInterface
{
    try {
        $result = $func($params);
    } catch (NotFoundException $e) {
        return new React\Http\Message\Response(
            404,
            [
                'Content-Type' => CONTENT_TYPE,
            ],
            json_encode(['status' => STATUS_NOT_FOUND])
        );
    } catch (\Throwable $e) {
        return new React\Http\Message\Response(
            500,
            [
                'Content-Type' => CONTENT_TYPE,
            ],
            json_encode(['status' => STATUS_ERROR])
        );
    }
    return new React\Http\Message\Response(
        $resultStatus,
        [
            'Content-Type' => CONTENT_TYPE,
        ],
        json_encode([
                'status' => STATUS_OK
            ] + $result)
    );
}

$socket = new React\Socket\Server(80, $loop);
$server->listen($socket);

$loop->run();
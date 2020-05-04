<?php

namespace Example;

// Подключение автозагрузки через composer
require __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use function Example\Validator\validate;
use function Example\Form\parse;
use function Example\Form\write;

$container = new Container();
AppFactory::setContainer($container);
$container->set('renderer', function () {
    // Параметром передается базовая директория в которой будут храниться шаблоны
    //return new \Slim\Views\PhpRenderer(__DIR__ . '/templates');
    return Twig::create(__DIR__ . '/templates');
});

$app = AppFactory::create();
//$app->add(TwigMiddleware::createFromContainer($app));
$app->addErrorMiddleware(true, true, true);
$router = $app->getRouteCollector()->getRouteParser();
$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});
$app->get('/deploy', function ($request, $response) {
    return $response->write(__DIR__ . '/../templates');
});

$app->get('/users_redirect', function ($request, $response) {
    $newResponse = $response->withStatus(302);
    if ($newResponse == $response) {
        echo "true";
    } else {
        echo 'false';
    }
    return $response->withStatus(302);
});
$app->get('/courses/{id}', function ($request, $response, array $args) {
    $id = htmlspecialchars($args['id']);
    return $response->write("Course id: {$id}");
})->setName('course');

$app->get('/users', function ($request, $response) use ($router) {
    $pathDB = 'db/users.json';
    $users = parse($pathDB);
    $usersData = get_object_vars($users);
    $term = $request->getQueryParam('term', null);
    $message = $term === '' ? 'Type something' : '';
    $users = array_map(function ($user) {
        return ['name' => $user->name, 'email' => $user->email];
    }, $usersData);
    $filteredUsers = array_filter($users, function ($user) use ($term) {
        return mb_substr($user['name'], 0, mb_strlen($term)) === (string) $term;
    });
    $params = [
        'users' => $filteredUsers,
        'term' => $term,
        'message' => $message,
        'routname' => $router->urlFor('course', ['id' => 200])
    ];
    return $this->get('renderer')->render($response, 'users.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) use ($router) {
    $params = [
        'user' => ['name' => '','email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'newUser.phtml', $params);
})->setName('addUser');

$app->post('/users/new', function ($request, $response) use ($router) {

    $user = $request->getParsedBodyParam('user');
    $pathDB = 'db/users.json';
    $users = parse($pathDB);
    $usersData = get_object_vars($users);
    $lastId = array_pop(array_keys($usersData));
    $newId = $lastId + 1;

    $users->$newId = (object) ['name' => $user['name'],'email' => $user['email']];
    $errors = validate($user);
    if (count($errors) === 0) {
        write($users, $pathDB);
        return $response->withRedirect($router->urlFor('users'));
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, 'newUser.phtml', $params);
});
$app->run();

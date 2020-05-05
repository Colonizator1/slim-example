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

session_start();
// Create Container
$container = new Container();
AppFactory::setContainer($container);

// Set view in Container
$container->set('renderer', function () {
    return Twig::create('templates', []);
});
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});
// Create App
$app = AppFactory::create();

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app, 'renderer'));
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
$app->get('/course/{id}', function ($request, $response, array $args) {
    $id = htmlspecialchars($args['id']);
    $params = [
        'id' => $id,
        'name' => 'test Sasha',
    ];
    return $this->get('renderer')->render($response, 'user.phtml', $params);
})->setName('course');

$app->get('/users[/{id:[0-9]+}]', function ($request, $response, array $args) use ($router) {
    $pathDB = 'db/users.json';
    $users = parse($pathDB);
    $usersData = get_object_vars($users);
    $term = $request->getQueryParam('term', null);
    $message = $term === '' ? 'Type something' : '';
    print_r($this->get('flash')->getFirstMessage('success'));
    $users = array_map(function ($user) {
        return ['name' => $user->name, 'email' => $user->email];
    }, $usersData);
    $filteredUsers = array_filter($users, function ($user) use ($term) {
        return mb_substr(mb_strtolower($user['name']), 0, mb_strlen($term)) === (string) mb_strtolower($term);
    });
    $id = isset($args['id']) ? $args['id'] : null;
    $params = [
        'users' => $filteredUsers,
        'term' => $term,
        'message' => $message,
        'routname' => $router,
        'id' => $id
    ];
    if (array_key_exists($id, $users)) {
        return $this->get('renderer')->render($response, 'user.phtml', $params);
    } elseif ($id !== null) {
        return $response->write('User not find')->withStatus(404);
    }
    return $this->get('renderer')->render($response, 'users.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) use ($router) {
    $params = [
        'user' => ['name' => '','email' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'newUser.phtml', $params);
})->setName('adduser');

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
        $this->get('flash')->addMessage('success', 'User ' . $user['name'] . ' successfully added');
        return $response->withRedirect($router->urlFor('users'));
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, 'newUser.phtml', $params);
});
$app->run();

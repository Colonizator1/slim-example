<?php

namespace Example;

// Подключение автозагрузки через composer
require __DIR__ . '/../../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Slim\Middleware\MethodOverrideMiddleware;
use DI\Container;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

use function Example\Form\parse;
use function Example\Form\write;

session_start();

// Users repository on the json file
//$repo = new UserRepository('db/users.json');

//Repo on the cookie
$repo = new UserRepositoryCookie('users');
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
$app->add(MethodOverrideMiddleware::class);

// Add Twig-View Middleware
$app->add(TwigMiddleware::createFromContainer($app, 'renderer'));
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $response->write('Welcome to Slim!');
});

$app->get('/thumb', function ($request, $response) {
    return $this->get('renderer')->render($response, 'thumb.phtml');
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
$app->get('/users/{id:[0-9]+}', function ($request, $response, array $args) use ($repo, $router) {
    $id = $args['id'];
    $user = $repo->find($id);
    if (!$user) {
        return $response->write('User not find')->withStatus(404);
    }
    $authUser = $_SESSION['_user'] ?? null;
    $params = [
        'authUser' => $authUser,
        'user' => $user,
        'id' => $id
    ];
    return $this->get('renderer')->render($response, 'user.phtml', $params);
})->setName('user');

$app->get('/users', function ($request, $response) use ($repo, $router) {
    $users = get_object_vars($repo->all());

    $term = $request->getQueryParam('term', null);
    $message = $term === '' ? 'Type something' : '';
    echo $this->get('flash')->getFirstMessage('success');

    $filteredUsers = array_filter($users, function ($user) use ($term) {
        return mb_substr(mb_strtolower($user->name), 0, mb_strlen($term)) === (string) mb_strtolower($term);
    });

    $params = [
        'users' => $filteredUsers,
        'term' => $term,
        'message' => $message
    ];
    return $this->get('renderer')->render($response, 'users.phtml', $params);
})->setName('users');

$app->get('/users/auth', function ($request, $response) use ($repo, $router) {
    print_r("<pre>");
    print_r($repo->all());
    print_r("</pre>");
    $params = [
        'user' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'auth.phtml', $params);
})->setName('auth');

$app->post('/users/auth', function ($request, $response) use ($repo, $router) {
    $userAuthData = $request->getParsedBodyParam('user');
    print_r($userAuthData['email']);
    $users = $repo->all();
    if ($repo->auth($userAuthData['email'])) {
        $user = $repo->auth($userAuthData['email']);
        $_SESSION['_auth_status'] = 'ok';
        $_SESSION['_user'] = $user;
        $this->get('flash')->addMessage('success', 'Successfully authorized');
        return $response->withRedirect($router->urlFor('user', ['id' => $user->id]));
    }
    $errors = 'User not found';
    $params = [
        'user' => (object) $userAuthData,
        'errors' => $errors
    ];
    $response = $response->withStatus(401);
    return $this->get('renderer')->render($response, 'auth.phtml', $params);
});

$app->get('/users/new', function ($request, $response) use ($router) {
    $params = [
        'user' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'newUser.phtml', $params);
})->setName('adduser');

$app->post('/users', function ($request, $response) use ($repo, $router) {
    $newUser = $request->getParsedBodyParam('user');
    $validator = new Validator();
    $errors = $validator->validate($newUser);
    if (count($errors) === 0) {
        $repo->save((object) $newUser);
        $this->get('flash')->addMessage('success', 'User ' . $newUser['name'] . ' successfully added');
        return $response->withRedirect($router->urlFor('users'));
    }
    $params = [
        'user' => (object) $newUser,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'newUser.phtml', $params);
});

$app->get('/users/{id:[0-9]+}/edit', function ($request, $response, $args) use ($repo, $router) {
    $id = $args['id'];
    $user = $repo->find($id);
    $params = [
        'user' => $user,
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'editUser.phtml', $params);
})->setName('edituser');

$app->patch('/users/{id:[0-9]+}', function ($request, $response, $args) use ($repo, $router) {
    $id = $args['id'];
    $user = $repo->find($id);
    $newUserData = $request->getParsedBodyParam('user');

    $validator = new Validator();
    $errors = $validator->validate($newUserData);

    if (count($errors) === 0) {
        $user->name = $newUserData['name'];
        $user->lastname = $newUserData['lastname'];
        $user->email = $newUserData['email'];

        $repo->save($user);
        $this->get('flash')->addMessage('success', 'User ' . $newUserData['name'] . ' successfully edited');
        return $response->withRedirect($router->urlFor('users'));
    }
    $params = [
        'user' => $user,
        'data' => $newUserData,
        'errors' => $errors
    ];
    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'editUser.phtml', $params);
});

$app->delete('/users/{id:[0-9]+}', function ($request, $response, $args) use ($repo, $router) {
    $id = $args['id'];
    $repo->delete($id);
    $this->get('flash')->addMessage('success', 'User ' . $user->name . ' successfully deleted');
    return $response->withRedirect($router->urlFor('users'));
});

$app->run();

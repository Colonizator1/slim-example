<?php

namespace Example\Validator;

// Подключение автозагрузки через composer
function validate($user)
{
    $errors = [];
    if (empty($user['name'])) {
        $errors['name'] = "Name can't be blank";
    } elseif (mb_strlen($user['name']) > 30) {
        $errors['name'] = "Name can't to be more than 30 chars";
    }
    if (empty($user['email'])) {
        $errors['email'] = "Email can't be blank";
    } elseif (mb_strlen($user['email']) > 30) {
        $errors['email'] = "Email can't to be more than 30 chars";
    }
    return $errors;
}

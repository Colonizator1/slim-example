<?php

namespace Example;

class UserRepositoryCookie
{
    public $db;
    public function __construct(string $cookieName)
    {
        $this->db = $cookieName;
    }

    public function all()
    {
        if (array_key_exists($this->db, $_COOKIE)) {
            $usersData = stripslashes($_COOKIE[$this->db]);
        } else {
            return '';
        }

        $result = json_decode($usersData);
        if (json_last_error() !== JSON_ERROR_NONE) {
            echo "json error: " . json_last_error();
            throw new \Exception("json error: " . json_last_error());
        }
        return $result;
    }

    public function find(string $id)
    {
        return $this->all()->$id;
    }

    public function save(object $item)
    {
        $users = $this->all();
        if (empty($item->name) || empty($item->email)) {
            $json = json_encode($item);
            throw new \Exception("Wrong data: {$json}");
        }
        if (!isset($item->id)) {
            $item->id = time();
        }
        $id = $item->id;
        $users->$id = (object) $item;
        $content = json_encode($users);
        $result = setcookie($this->db, $content, time() + 60 * 60 * 160, "/users");
        if ($result === false) {
            throw new \Exception("Cookies doesn't send");
        }
    }
    public function delete(string $id)
    {
        $users = $this->all();
        unset($users->$id);
        $content = json_encode($users);
        $result = setcookie($this->db, $content);
        if ($result === false) {
            throw new \Exception("Cookies doesn't send");
        }
    }

    public function auth(string $email)
    {
        $users = $this->all();
        foreach ($users as $id => $user) {
            if ($user->email === $email) {
                return $user;
            }
        }
        return false;
    }
}

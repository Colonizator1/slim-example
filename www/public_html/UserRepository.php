<?php

namespace Example;

class UserRepository
{
    public $db;
    public function __construct(string $filePath)
    {
        $this->db = $filePath;
    }

    public function all()
    {
        $userData = file_get_contents($this->db);
        if ($userData === false) {
            throw new \Exception("Can't read file by: {$this->db}");
        } elseif ($userData === '') {
            return [];
        }
        $result = json_decode($userData);
        if (json_last_error() !== JSON_ERROR_NONE) {
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
        $result = file_put_contents($this->db, $content);
        if ($result === false) {
            throw new \Exception("Can't write file by: {$filePath}");
        }
    }
    public function delete(string $id)
    {
        $users = $this->all();
        unset($users->$id);
        $content = json_encode($users);
        $result = file_put_contents($this->db, $content);
        if ($result === false) {
            throw new \Exception("Can't write file by: {$filePath}");
        }
    }
    
    public function auth(string $email)
    {
        $users = $this->all();
        foreach ($users as $id => $user) {
            if ($user['email'] === $email) {
                return true;
            }
        }
        return false;
    }
}

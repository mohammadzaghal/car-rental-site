<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once 'userStorage.php';

class Auth {
    private $user_storage;
    private $user = NULL;

    public function __construct(IStorage $user_storage) {
        $this->user_storage = $user_storage;

        if (isset($_SESSION["user"])) {
            $this->user = $_SESSION["user"];
        }
    }

    public function register($data) {
        $user = [
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'fullname' => $data['fullname'],
            "role" => "user",
        ];

        return $this->user_storage->add($user);
    }

    public function user_exists($email) {
        $user = $this->user_storage->findOne(['email' => $email]);
        return !is_null($user);
    }

    public function authenticate($email, $password) {
        $user = $this->user_storage->findOne(['email' => $email]);
    
        if (!$user) {
            return NULL;
        }

        if (password_verify($password, $user['password'])) {
            return $user;
        }
        return NULL;
    }
    
    public function is_authenticated() {
        return isset($_SESSION["user"]);
    }

    public function authorize($roles = []) {
        if (!$this->is_authenticated()) {
            return FALSE;
        }
        if (!isset($this->user["role"])) {
            return FALSE;
        }
        foreach ($roles as $role) {
            if ($this->user["role"] === $role) {
                return TRUE;
            }
        }
        return FALSE;
    }


    public function login($user) {
        $this->user = $user;
        $_SESSION["user"] = $user;
    }

    public function logout() {
        $this->user = NULL;
        unset($_SESSION["user"]);
    }

    public function authenticated_user() {
        return $this->user;
    }
}

<?php 
require_once 'IStorage.php';

class UserStorage implements IStorage {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function load() {
        $stmt = $this->pdo->query("SELECT * FROM users");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function save($data) {
        if (!is_array($data)) {
            throw new Exception("Expected array in save(), got: " . gettype($data));
        }

        if (!isset($data['email']) || !isset($data['password'])) {
            throw new Exception("Missing required fields: email or password");
        }

        $id = uniqid();
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (id, email, password, fullname, role) 
            VALUES (:id, :email, :password, :fullname, :role)"
        );
        $stmt->execute([
            'id' => $id,
            'email' => $data['email'],
            'password' => $data['password'],
            'fullname' => $data['fullname'] ?? null,
            'role' => $data['role'] ?? 'user'
        ]);
        return $id;
    }

    public function add($user): string {
        return $this->save($user);
    }

    public function findById(string $id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findAll(array $params = []) {
        $sql = "SELECT * FROM users";
        if ($params) {
            $clauses = [];
            foreach ($params as $key => $value) {
                $clauses[] = "$key = :$key";
            }
            $sql .= " WHERE " . implode(" AND ", $clauses);
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findOne(array $params = []) {
        $results = $this->findAll($params);
        return $results[0] ?? null;
    }

    public function update(string $id, $user) {
        $stmt = $this->pdo->prepare(
            "UPDATE users SET 
                email = :email,
                password = :password,
                fullname = :fullname,
                role = :role
             WHERE id = :id"
        );
        $stmt->execute([
            'id' => $id,
            'email' => $user['email'],
            'password' => $user['password'],
            'fullname' => $user['fullname'],
            'role' => $user['role']
        ]);
    }

    public function delete(string $id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);
    }
}

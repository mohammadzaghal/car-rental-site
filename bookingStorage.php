<?php
class BookingStorage {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findByUserId($user_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM bookings WHERE user_id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add($data) {
        $stmt = $this->pdo->prepare("INSERT INTO bookings (user_id, car_id, start_date, end_date) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            $data['user_id'],
            $data['car_id'],
            $data['start_date'],
            $data['end_date']
        ]);
    }
    
    public function findByCarId($car_id) {
        $stmt = $this->pdo->prepare("SELECT * FROM bookings WHERE car_id = ?");
        $stmt->execute([$car_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

}


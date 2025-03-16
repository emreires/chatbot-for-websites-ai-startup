<?php

namespace App\Models;

use App\Config\Database;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    public function create($data) {
        $sql = "INSERT INTO users (email, password, role, plan_type, api_key, created_at) 
                VALUES (:email, :password, :role, :plan_type, :api_key, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'role' => $data['role'] ?? 'user',
            'plan_type' => $data['plan_type'] ?? 'small',
            'api_key' => bin2hex(random_bytes(32))
        ]);
    }

    public function findByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    public function updatePlan($userId, $planType) {
        $sql = "UPDATE users SET plan_type = :plan_type WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'plan_type' => $planType,
            'id' => $userId
        ]);
    }

    public function getApiUsage($userId) {
        $sql = "SELECT COUNT(*) as count FROM api_calls 
                WHERE user_id = :user_id 
                AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetch()['count'];
    }
} 
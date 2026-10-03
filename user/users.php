<?php
class User {
    private $db;

    public function __construct($dbconnection) {
        $this->db = $dbconnection;
    }

    public function login($username, $password) {
        if (empty($username) || empty($password)) {
            return new Response(false, "Username dan password harus diisi!");
        }

        $query = "SELECT * FROM users WHERE username = $1";
        $result = $this->db->send_query($query, [$username]);

        if (!$result->status || empty($result->data)) {
            return new Response(false, "Username atau password salah!");
        }

        $user = $result->data[0];
        $storedPassword = $user['password'] ?? '';

        if ($password === $storedPassword || password_verify($password, $storedPassword)) {
            return new Response(true, "Login berhasil!", $user);
        }

        return new Response(false, "Username atau password salah!");
    }
    
}
?>
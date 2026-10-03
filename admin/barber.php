<?php
class Barber {
    private DBconnection $db;

    public function __construct(DBconnection $dbconnection) {
        $this->db = $dbconnection;
    }

    public function getAll(): Response {
        $query = "SELECT * FROM barbers WHERE status = 'active' ORDER BY name ASC";
        return $this->db->send_query($query, []);
    }

    public function getById(int|string $id): Response {
        $query = "SELECT * FROM barbers WHERE id = $1";
        return $this->db->send_query($query, [$id]);
    }

    public function add(string $name, ?string $specialization, string $phone, ?string $email): Response {
        if (empty($name) || empty($phone)) {
            return new Response(false, "Nama dan telepon wajib diisi.");
        }

        $query = "INSERT INTO barbers (name, specialization, phone, email, status) VALUES ($1, $2, $3, $4, 'active')";
        $result = $this->db->send_query($query, [$name, $specialization, $phone, $email]);
        
        if ($result->status) {
            return new Response(true, "Barber berhasil ditambahkan.");
        } else {
            return new Response(false, $result->message);
        }
    }

    public function edit(int|string $id, string $name, ?string $specialization, string $phone, ?string $email): Response {
        if (empty($name) || empty($phone)) {
            return new Response(false, "Nama dan telepon wajib diisi.");
        }

        $query = "UPDATE barbers SET name = $1, specialization = $2, phone = $3, email = $4 WHERE id = $5";
        $result = $this->db->send_query($query, [$name, $specialization, $phone, $email, $id]);
        
        if ($result->status) {
            return new Response(true, "Barber berhasil diubah.");
        } else {
            return new Response(false, $result->message);
        }
    }

    public function delete(int|string $id): Response {
        $query = "DELETE FROM barbers WHERE id = $1";
        $result = $this->db->send_query($query, [$id]);
        
        if ($result->status) {
            return new Response(true, "Barber berhasil dihapus.");
        } else {
            return new Response(false, $result->message);
        }
    }
}
?>
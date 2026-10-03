<?php
class BarberSession {
    private $db;

    public function __construct($dbconnection) {
        $this->db = $dbconnection;
    }

    public function addSession($session_name, $day_of_week, $start_time, $end_time, $capacity, $description = null) {
        if (empty($session_name) || empty($day_of_week) || empty($start_time) || empty($end_time) || empty($capacity)) {
            return ["status" => false, "message" => "Semua field harus diisi!"];
        }

        $query = "INSERT INTO barber_sessions (session_name, day_of_week, start_time, end_time, capacity, available_slots, description) 
                  VALUES ($1, $2, $3, $4, $5, $5, $6)";
        $result = $this->db->send_query($query, [$session_name, $day_of_week, $start_time, $end_time, $capacity, $description]);

        if ($result->status) {
            return ["status" => true, "message" => "Sesi berhasil ditambahkan!"];
        } else {
            return ["status" => false, "message" => $result->message];
        }
    }

    public function getAllSessions() {
        $query = "SELECT * FROM barber_sessions ORDER BY day_of_week, start_time";
        $result = $this->db->send_query($query, []);

        if ($result->status) {
            return ["status" => true, "data" => $result->data];
        } else {
            return ["status" => false, "message" => $result->message];
        }
    }

    public function getSessionById($session_id) {
        $query = "SELECT * FROM barber_sessions WHERE id = $1";
        $result = $this->db->send_query($query, [$session_id]);

        if ($result->status && !empty($result->data)) {
            return ["status" => true, "data" => $result->data[0]];
        } else {
            return ["status" => false, "message" => "Sesi tidak ditemukan!"];
        }
    }

    public function updateSession($session_id, $session_name, $day_of_week, $start_time, $end_time, $capacity) {
        $query = "UPDATE barber_sessions SET session_name = $1, day_of_week = $2, start_time = $3, end_time = $4, capacity = $5 WHERE id = $6";
        $result = $this->db->send_query($query, [$session_name, $day_of_week, $start_time, $end_time, $capacity, $session_id]);

        if ($result->status) {
            return ["status" => true, "message" => "✅ Sesi berhasil diupdate!"];
        } else {
            return ["status" => false, "message" => $result->message];
        }
    }

    public function deleteSession($session_id) {
        $query = "DELETE FROM barber_sessions WHERE id = $1";
        $result = $this->db->send_query($query, [$session_id]);

        if ($result->status) {
            return ["status" => true, "message" => "✅ Sesi berhasil dihapus!"];
        } else {
            return ["status" => false, "message" => $result->message];
        }
    }
}
?>
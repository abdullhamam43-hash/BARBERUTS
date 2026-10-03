<?php
class BarberSchedule {
    private $db;

    public function __construct($dbconnection) {
        $this->db = $dbconnection;
    }

    public function getAll() {
        $query = "SELECT bs.*, b.name FROM barber_schedules bs 
                  JOIN barbers b ON bs.barber_id = b.id 
                  WHERE bs.is_active = TRUE 
                  ORDER BY b.name, bs.day_of_week";
        return $this->db->send_query($query, []);
    }

    public function getById($id) {
        $query = "SELECT bs.*, b.name FROM barber_schedules bs 
                  JOIN barbers b ON bs.barber_id = b.id 
                  WHERE bs.id = $1";
        return $this->db->send_query($query, [$id]);
    }

    public function getByBarber($barber_id) {
        $query = "SELECT * FROM barber_schedules 
                  WHERE barber_id = $1 AND is_active = TRUE 
                  ORDER BY day_of_week";
        return $this->db->send_query($query, [$barber_id]);
    }

    public function add($barber_id, $day_of_week, $start_time, $end_time, $slot_quota) {
        if (empty($barber_id) || empty($day_of_week) || empty($start_time) || empty($end_time) || empty($slot_quota)) {
            return new Response(false, "Semua field wajib diisi.");
        }

        $checkQuery = "SELECT id FROM barber_schedules WHERE barber_id = $1 AND day_of_week = $2";
        $checkResult = $this->db->send_query($checkQuery, [$barber_id, $day_of_week]);

        if (!empty($checkResult->data)) {
            return new Response(false, "Jadwal untuk hari ini sudah ada.");
        }

        $query = "INSERT INTO barber_schedules (barber_id, day_of_week, start_time, end_time, slot_quota, is_active) 
                  VALUES ($1, $2, $3, $4, $5, TRUE)";
        $result = $this->db->send_query($query, [$barber_id, $day_of_week, $start_time, $end_time, $slot_quota]);
        
        if ($result->status) {
            return new Response(true, "Jadwal berhasil ditambahkan.");
        } else {
            return new Response(false, $result->message);
        }
    }

    public function edit($id, $start_time, $end_time, $slot_quota) {
        if (empty($start_time) || empty($end_time) || empty($slot_quota)) {
            return new Response(false, "Semua field wajib diisi.");
        }

        $query = "UPDATE barber_schedules SET start_time = $1, end_time = $2, slot_quota = $3 WHERE id = $4";
        $result = $this->db->send_query($query, [$start_time, $end_time, $slot_quota, $id]);
        
        if ($result->status) {
            return new Response(true, "Jadwal berhasil diubah.");
        } else {
            return new Response(false, $result->message);
        }
    }

    public function delete($id) {
        $query = "DELETE FROM barber_schedules WHERE id = $1";
        $result = $this->db->send_query($query, [$id]);
        
        if ($result->status) {
            return new Response(true, "Jadwal berhasil dihapus.");
        } else {
            return new Response(false, $result->message);
        }
    }
}
?>
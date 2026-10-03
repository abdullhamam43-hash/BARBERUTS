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
                  ORDER BY bs.schedule_date ASC, bs.start_time ASC";
        return $this->db->send_query($query, []);
    }

    public function getById($id) {
        $query = "SELECT bs.*, b.name FROM barber_schedules bs 
                  JOIN barbers b ON bs.barber_id = b.id 
                  WHERE bs.id = $1";
        return $this->db->send_query($query, [$id]);
    }

    public function getByBarber($barber_id, $days = 30) {
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime("+$days days"));
        
        $query = "SELECT * FROM barber_schedules 
                  WHERE barber_id = $1 AND is_active = TRUE 
                  AND schedule_date BETWEEN $2 AND $3
                  ORDER BY schedule_date ASC, start_time ASC";
        return $this->db->send_query($query, [$barber_id, $start_date, $end_date]);
    }

    public function add($barber_id, $schedule_date, $start_time, $end_time, $slot_quota) {
        if (empty($barber_id) || empty($schedule_date) || empty($start_time) || empty($end_time) || empty($slot_quota)) {
            return new Response(false, "Semua field wajib diisi.");
        }

        $checkQuery = "SELECT id FROM barber_schedules WHERE barber_id = $1 AND schedule_date = $2";
        $checkResult = $this->db->send_query($checkQuery, [$barber_id, $schedule_date]);

        if (!empty($checkResult->data)) {
            return new Response(false, "Jadwal pada tanggal ini sudah ada.");
        }

        $query = "INSERT INTO barber_schedules (barber_id, schedule_date, start_time, end_time, slot_quota, is_active) 
                  VALUES ($1, $2, $3, $4, $5, TRUE)";
        $result = $this->db->send_query($query, [$barber_id, $schedule_date, $start_time, $end_time, $slot_quota]);
        
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
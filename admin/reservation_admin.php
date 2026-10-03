<?php 
class Reservation { 
    private DBconnection $db;

    public function __construct(DBconnection $dbconnection) {
        $this->db = $dbconnection;
    }

    public function add(int|string $user_id, int|string $barber_id, string $reservation_date, string $time_slot, ?string $notes = null): Response {
        if (empty($user_id) || empty($barber_id) || empty($reservation_date) || empty($time_slot)) {
            return new Response(false, "Semua field wajib diisi.");
        }

        $checkQuery = "SELECT id FROM reservations WHERE barber_id = $1 AND reservation_date = $2 AND time_slot = $3 AND status != 'cancelled'";
        $checkResult = $this->db->send_query($checkQuery, [$barber_id, $reservation_date, $time_slot]);

        if (!empty($checkResult->data)) {
            return new Response(false, "Slot waktu sudah dipesan.");
        }

        $query = "INSERT INTO reservations (user_id, barber_id, reservation_date, time_slot, status, notes) 
                  VALUES ($1, $2, $3, $4, 'pending', $5)";
        $result = $this->db->send_query($query, [$user_id, $barber_id, $reservation_date, $time_slot, $notes]);

        if ($result->status) {
            return new Response(true, "Reservasi berhasil dibuat!");
        } else {
            return new Response(false, $result->message);
        }
    }

    public function getUserReservations(int|string $user_id): Response {
        $query = "SELECT r.*, b.name as barber_name FROM reservations r 
                  JOIN barbers b ON r.barber_id = b.id 
                  WHERE r.user_id = $1 
                  ORDER BY r.reservation_date DESC";
        return $this->db->send_query($query, [$user_id]);
    }
    public function delete(int|string $id): Response {
        $query = "DELETE FROM reservations WHERE id = $1";
        $result = $this->db->send_query($query, [$id]);

        if ($result->status) {
            return new Response(true, "Reservasi berhasil dihapus!");
        } else {
            return new Response(false, $result->message);
        }
    }
    public function getAll(): Response {
        $query = "SELECT r.*, b.name as barber_name, u.username FROM reservations r 
                  JOIN barbers b ON r.barber_id = b.id 
                  JOIN users u ON r.user_id = u.id 
                  ORDER BY r.reservation_date DESC";
        return $this->db->send_query($query, []);
    }

    public function updateStatus(int|string $id, string $status): Response {
        if (!in_array($status, ['pending', 'confirmed', 'cancelled'], true)) {
            return new Response(false, "Status tidak valid.");
        }

        $query = "UPDATE reservations SET status = $1 WHERE id = $2";
        $result = $this->db->send_query($query, [$status, $id]);

        if ($result->status) {
            return new Response(true, "Status reservasi diubah!");
        } else {
            return new Response(false, $result->message);
        }
    }

    public function confirm(int|string $id): Response {
        return $this->updateStatus($id, 'confirmed');
    }

    public function cancel(int|string $id): Response {
        return $this->updateStatus($id, 'cancelled');
    }
}
?>
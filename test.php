<?php

require_once '../dbconnection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


$user_id = null;

if (isset($_SESSION['user_id'])) {

    $user_id = $_SESSION['user_id'];

} elseif (isset($_SESSION['id'])) {

    $user_id = $_SESSION['id'];

}

if ($user_id === null) {

    header("location: logout.php");
    exit();

}


try {

    $db = new DBconnection();

} catch (Exception $e) {

    die(
        "Koneksi database gagal: " .
        htmlspecialchars($e->getMessage())
    );

}


$query = "
    SELECT
        r.id,
        r.reservation_date,
        r.time_slot,
        r.status,
        r.notes,
        b.name AS barber_name
    FROM reservations r
    JOIN barbers b
        ON r.barber_id = b.id
    WHERE r.user_id = $1
    ORDER BY
        r.reservation_date DESC,
        r.time_slot DESC
";


$reservationResult = $db->send_query(
    $query,
    [$user_id]
);


if (
    isset($_GET['batal']) &&
    !empty($_GET['batal'])
) {

    $reservation_id = $_GET['batal'];


    $cancelQuery = "
        UPDATE reservations
        SET status = 'cancelled'
        WHERE id = $1
        AND user_id = $2
        AND status = 'pending'
    ";


    $cancelResult = $db->send_query(
        $cancelQuery,
        [
            $reservation_id,
            $user_id
        ]
    );


    if ($cancelResult->status) {

        header(
            "Location: user_dashboard.php?cancel=success"
        );

        exit;

    }


    header(
        "Location: user_dashboard.php?cancel=failed"
    );

    exit;
}


function getStatusText(string $status): string
{
    switch ($status) {

        case 'pending':
            return 'Menunggu';

        case 'confirmed':
            return 'Selesai';

        case 'cancelled':
            return 'Dibatalkan';

        default:
            return ucfirst($status);
    }
}


function getStatusClass(string $status): string
{
    switch ($status) {

        case 'pending':
            return 'status-menunggu';

        case 'confirmed':
            return 'status-selesai';

        case 'cancelled':
            return 'status-batal';

        default:
            return 'status-menunggu';
    }
}

?>


<!DOCTYPE html>

<html lang="id">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Riwayat Reservasi - Barbershop
    </title>


    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {

            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background: #202020;

            color: #222222;

        }


        .page {

            width: 97%;

            max-width: 1200px;

            min-height: 90vh;

            margin: 22px auto;

            background: #fafafa;

        }


        .header {

            height: 64px;

            background: #ffffff;

            border-bottom:
                1px solid #e5e5e5;

            padding:
                0 85px;

            display: flex;

            align-items: center;

            justify-content: space-between;

        }


        .brand {

            display: flex;

            align-items: center;

            gap: 10px;

        }


        .brand-logo {

            width: 28px;

            height: 28px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 22px;

        }


        .brand-name {

            font-size: 13px;

            font-weight: bold;

            letter-spacing: 0.3px;

        }


        .navigation {

            display: flex;

            background: #f2f2f2;

            border:
                1px solid #dddddd;

            border-radius: 3px;

            overflow: hidden;

        }


        .navigation a {

            text-decoration: none;

            color: #444444;

            font-size: 11px;

            padding:
                8px 12px;

            border-right:
                1px solid #dddddd;

        }


        .navigation a:last-child {

            border-right: none;

        }


        .navigation a:hover {

            background: #e8e8e8;

        }


        .navigation a.active {

            background: #ffffff;

            color: #111111;

        }


        .content {

            width: 70%;

            margin:
                24px auto;

        }


        .alert {

            margin-bottom: 15px;

            padding:
                10px 14px;

            background: #eeeeee;

            border:
                1px solid #dddddd;

            font-size: 11px;

        }


        .title-box {

            background: #ffffff;

            padding: 20px;

            display: flex;

            align-items: center;

            justify-content: space-between;

        }


        .title-box h1 {

            font-size: 20px;

            font-weight: 600;

            margin-bottom: 6px;

        }


        .title-box p {

            font-size: 11px;

            color: #666666;

        }


        .logout {

            text-decoration: none;

            color: #222222;

            font-size: 11px;

            padding:
                8px 11px;

        }


        .logout:hover {

            background: #f1f1f1;

        }


        .table-box {

            margin-top: 18px;

            background: #ffffff;

            overflow: hidden;

        }


        table {

            width: 100%;

            border-collapse: collapse;

        }


        thead {

            background: #f0f0f0;

        }


        th {

            text-align: left;

            padding:
                11px 14px;

            font-size: 9px;

            font-weight: bold;

            color: #333333;

        }


        td {

            padding:
                13px 14px;

            font-size: 11px;

            color: #333333;

            border-bottom:
                1px solid #eeeeee;

        }


        tbody tr:last-child td {

            border-bottom: none;

        }


        tbody tr:hover {

            background: #fafafa;

        }


        .status {

            display: inline-block;

            padding:
                4px 8px;

            border-radius: 3px;

            font-size: 9px;

            white-space: nowrap;

        }


        .status-menunggu {

            background: #e5e5e5;

            color: #222222;

        }


        .status-selesai {

            background: #000000;

            color: #ffffff;

        }


        .status-batal {

            background: #eeeeee;

            color: #777777;

        }


        .action {

            color: #333333;

            font-size: 10px;

            text-decoration: underline;

            cursor: pointer;

        }


        .action:hover {

            color: #000000;

        }


        .action-disabled {

            color: #aaaaaa;

            font-size: 10px;

        }


        .empty {

            text-align: center;

            padding:
                35px 20px;

            color: #777777;

            font-size: 11px;

        }


        .footer {

            margin-top: 60px;

            padding: 12px;

            text-align: center;

            border-top:
                1px solid #eeeeee;

            font-size: 9px;

            color: #777777;

        }


        @media (max-width: 800px) {

            .header {

                height: auto;

                padding: 15px;

                flex-direction: column;

                gap: 15px;

            }


            .content {

                width: 92%;

            }


            .navigation {

                width: 100%;

                overflow-x: auto;

            }


            .table-box {

                overflow-x: auto;

            }


            table {

                min-width: 700px;

            }

        }

    </style>

</head>


<body>


<div class="page">


    <header class="header">


        <div class="brand">

            <div class="brand-logo">
                💈
            </div>


            <div class="brand-name">
                BARBERSHOP
            </div>

        </div>


        <nav class="navigation">


            <a
                href="About.php"
                class="active"
            >
                About
            </a>


            <a href="BarberSchedule.php">
                Reservasi
            </a>


            <a href="#riwayat">
                Riwayat
            </a>


            <a href="logout.php">
                Logout
            </a>


        </nav>


    </header>


    <main class="content">


        <?php

        if (
            isset($_GET['cancel']) &&
            $_GET['cancel'] === 'success'
        ) {

        ?>

            <div class="alert">

                Reservasi berhasil dibatalkan.

            </div>

        <?php

        }


        if (
            isset($_GET['cancel']) &&
            $_GET['cancel'] === 'failed'
        ) {

        ?>

            <div class="alert">

                Reservasi tidak dapat dibatalkan.

            </div>

        <?php

        }

        ?>


        <section class="title-box">


            <div>

                <h1>
                    Riwayat Reservasi Saya
                </h1>


                <p>
                    Daftar riwayat pemesanan jadwal cukur Anda
                </p>

            </div>


            <a
                href="logout.php"
                class="logout"
            >

                ↪ Logout

            </a>


        </section>


        <section
            class="table-box"
            id="riwayat"
        >


            <table>


                <thead>

                    <tr>

                        <th>
                            NO
                        </th>

                        <th>
                            BARBER
                        </th>

                        <th>
                            TANGGAL
                        </th>

                        <th>
                            JAM
                        </th>

                        <th>
                            STATUS
                        </th>

                        <th>
                            AKSI
                        </th>

                    </tr>

                </thead>


                <tbody>


                <?php


                if (
                    $reservationResult->status &&
                    !empty($reservationResult->data)
                ) {

                    $no = 1;


                    foreach (
                        $reservationResult->data
                        as $row
                    ) {

                        $status = strtolower(
                            $row['status']
                        );

                ?>


                    <tr>



                        <td>

                            <?= $no ?>

                        </td>



                        <td>

                            <?= htmlspecialchars(
                                $row['barber_name']
                            ) ?>

                        </td>


                        <td>

                            <?php

                            if (
                                !empty(
                                    $row['reservation_date']
                                )
                            ) {

                                echo date(
                                    'd F Y',
                                    strtotime(
                                        $row['reservation_date']
                                    )
                                );

                            } else {

                                echo '-';

                            }

                            ?>

                        </td>



                        <td>

                            <?php

                            if (
                                !empty(
                                    $row['time_slot']
                                )
                            ) {

                                echo htmlspecialchars(
                                    $row['time_slot']
                                );

                                echo " WIB";

                            } else {

                                echo '-';

                            }

                            ?>

                        </td>



                        <td>

                            <span
                                class="status <?= getStatusClass($status) ?>"
                            >

                                <?= htmlspecialchars(
                                    getStatusText($status)
                                ) ?>

                            </span>

                        </td>


                        <td>


                            <?php

                            if (
                                $status === 'pending'
                            ) {

                            ?>


                                <a
                                    href="user_dashboard.php?batal=<?= urlencode($row['id']) ?>"
                                    class="action"
                                    onclick="return confirm('Yakin ingin membatalkan reservasi ini?');"
                                >

                                    Batal

                                </a>


                            <?php

                            } elseif (
                                $status === 'confirmed'
                            ) {

                            ?>


                                <a
                                    href="#"
                                    class="action"
                                    onclick="alert('Reservasi dengan barber <?= htmlspecialchars($row['barber_name']) ?>'); return false;"
                                >

                                    Detail

                                </a>


                            <?php

                            } else {

                            ?>


                                <span
                                    class="action-disabled"
                                >

                                    -

                                </span>


                            <?php

                            }

                            ?>


                        </td>


                    </tr>


                <?php

                        $no++;

                    }


                } else {

                ?>


                    <tr>


                        <td
                            colspan="6"
                            class="empty"
                        >

                            Belum ada riwayat reservasi.

                        </td>


                    </tr>


                <?php

                }

                ?>


                </tbody>


            </table>


        </section>


    </main>


    <footer class="footer">

        BARBERSHOP

    </footer>


</div>


</body>

</html>
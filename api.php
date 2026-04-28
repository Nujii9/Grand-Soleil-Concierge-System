<?php
// ============================================================
//  api.php — Full CRUD Backend API
//  Handles: guests, rooms, reservations, services, orders,
//           payments, and the consolidated invoice
// ============================================================

require_once __DIR__ . '/config.php';

$method   = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';
$id       = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Parse JSON body for PUT/POST
$body = [];
if (in_array($method, ['POST', 'PUT'])) {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw, true) ?? [];
    // Also accept form-encoded POST
    if (empty($body) && !empty($_POST)) {
        $body = $_POST;
    }
}

try {
    $db = getDB();

    switch ($resource) {

        // ========================================================
        //  ROOMS
        // ========================================================
        case 'rooms':
            if ($method === 'GET') {
                if ($id) {
                    $stmt = $db->prepare("SELECT * FROM rooms WHERE room_id = ?");
                    $stmt->execute([$id]);
                    $room = $stmt->fetch();
                    $room ? jsonResponse(true, 'OK', ['room' => $room])
                          : jsonResponse(false, 'Room not found');
                } else {
                    $status = $_GET['status'] ?? null;
                    if ($status) {
                        $stmt = $db->prepare("SELECT * FROM rooms WHERE status = ? ORDER BY room_number");
                        $stmt->execute([$status]);
                    } else {
                        $stmt = $db->query("SELECT * FROM rooms ORDER BY room_number");
                    }
                    jsonResponse(true, 'OK', ['rooms' => $stmt->fetchAll()]);
                }
            }

            if ($method === 'POST') {
                $stmt = $db->prepare("
                    INSERT INTO rooms (room_number, room_type, floor, capacity, rate_per_night, status, amenities)
                    VALUES (:room_number,:room_type,:floor,:capacity,:rate_per_night,:status,:amenities)
                ");
                $stmt->execute([
                    ':room_number'    => clean($body['room_number']),
                    ':room_type'      => clean($body['room_type']),
                    ':floor'          => (int)($body['floor'] ?? 1),
                    ':capacity'       => (int)($body['capacity'] ?? 2),
                    ':rate_per_night' => (float)($body['rate_per_night'] ?? 0),
                    ':status'         => clean($body['status'] ?? 'Available'),
                    ':amenities'      => clean($body['amenities'] ?? ''),
                ]);
                jsonResponse(true, 'Room created', ['room_id' => $db->lastInsertId()]);
            }

            if ($method === 'PUT' && $id) {
                $stmt = $db->prepare("
                    UPDATE rooms SET room_number=:rn, room_type=:rt, floor=:fl,
                    capacity=:cap, rate_per_night=:rate, status=:st, amenities=:am
                    WHERE room_id=:id
                ");
                $stmt->execute([
                    ':rn'   => clean($body['room_number']),
                    ':rt'   => clean($body['room_type']),
                    ':fl'   => (int)($body['floor'] ?? 1),
                    ':cap'  => (int)($body['capacity'] ?? 2),
                    ':rate' => (float)($body['rate_per_night'] ?? 0),
                    ':st'   => clean($body['status'] ?? 'Available'),
                    ':am'   => clean($body['amenities'] ?? ''),
                    ':id'   => $id,
                ]);
                jsonResponse(true, 'Room updated');
            }

            if ($method === 'DELETE' && $id) {
                $stmt = $db->prepare("DELETE FROM rooms WHERE room_id = ?");
                $stmt->execute([$id]);
                jsonResponse(true, 'Room deleted');
            }
            break;

        // ========================================================
        //  GUESTS
        // ========================================================
        case 'guests':
            if ($method === 'GET') {
                if ($id) {
                    $stmt = $db->prepare("SELECT * FROM guests WHERE guest_id = ?");
                    $stmt->execute([$id]);
                    $guest = $stmt->fetch();
                    $guest ? jsonResponse(true, 'OK', ['guest' => $guest])
                           : jsonResponse(false, 'Guest not found');
                } else {
                    $search = $_GET['search'] ?? '';
                    if ($search) {
                        $like = '%' . $search . '%';
                        $stmt = $db->prepare("
                            SELECT * FROM guests
                            WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ?
                            ORDER BY last_name, first_name
                        ");
                        $stmt->execute([$like, $like, $like, $like]);
                    } else {
                        $stmt = $db->query("SELECT * FROM guests ORDER BY last_name, first_name");
                    }
                    jsonResponse(true, 'OK', ['guests' => $stmt->fetchAll()]);
                }
            }

            if ($method === 'POST') {
                $stmt = $db->prepare("
                    INSERT INTO guests (first_name,last_name,email,phone,id_type,id_number,nationality,address,vip_status)
                    VALUES (:fn,:ln,:em,:ph,:it,:in_,:na,:ad,:vip)
                ");
                $stmt->execute([
                    ':fn'  => clean($body['first_name']),
                    ':ln'  => clean($body['last_name']),
                    ':em'  => clean($body['email'] ?? ''),
                    ':ph'  => clean($body['phone'] ?? ''),
                    ':it'  => clean($body['id_type'] ?? 'Passport'),
                    ':in_' => clean($body['id_number'] ?? ''),
                    ':na'  => clean($body['nationality'] ?? ''),
                    ':ad'  => clean($body['address'] ?? ''),
                    ':vip' => (int)(!empty($body['vip_status'])),
                ]);
                jsonResponse(true, 'Guest created', ['guest_id' => $db->lastInsertId()]);
            }

            if ($method === 'PUT' && $id) {
                $stmt = $db->prepare("
                    UPDATE guests SET first_name=:fn,last_name=:ln,email=:em,phone=:ph,
                    id_type=:it,id_number=:in_,nationality=:na,address=:ad,vip_status=:vip
                    WHERE guest_id=:id
                ");
                $stmt->execute([
                    ':fn'  => clean($body['first_name']),
                    ':ln'  => clean($body['last_name']),
                    ':em'  => clean($body['email'] ?? ''),
                    ':ph'  => clean($body['phone'] ?? ''),
                    ':it'  => clean($body['id_type'] ?? 'Passport'),
                    ':in_' => clean($body['id_number'] ?? ''),
                    ':na'  => clean($body['nationality'] ?? ''),
                    ':ad'  => clean($body['address'] ?? ''),
                    ':vip' => (int)(!empty($body['vip_status'])),
                    ':id'  => $id,
                ]);
                jsonResponse(true, 'Guest updated');
            }

            if ($method === 'DELETE' && $id) {
                $stmt = $db->prepare("DELETE FROM guests WHERE guest_id = ?");
                $stmt->execute([$id]);
                jsonResponse(true, 'Guest deleted');
            }
            break;

        // ========================================================
        //  RESERVATIONS
        // ========================================================
        case 'reservations':
            if ($method === 'GET') {
                if ($id) {
                    $stmt = $db->prepare("
                        SELECT r.*,
                               CONCAT(g.first_name,' ',g.last_name) AS guest_name,
                               g.email, g.phone, g.vip_status,
                               rm.room_number, rm.room_type, rm.rate_per_night,
                               DATEDIFF(r.check_out_date, r.check_in_date) AS nights
                        FROM reservations r
                        JOIN guests g  ON g.guest_id = r.guest_id
                        JOIN rooms  rm ON rm.room_id  = r.room_id
                        WHERE r.reservation_id = ?
                    ");
                    $stmt->execute([$id]);
                    $res = $stmt->fetch();
                    $res ? jsonResponse(true, 'OK', ['reservation' => $res])
                         : jsonResponse(false, 'Reservation not found');
                } else {
                    $status = $_GET['status'] ?? null;
                    $sql = "
                        SELECT r.*,
                               CONCAT(g.first_name,' ',g.last_name) AS guest_name,
                               g.phone, g.vip_status,
                               rm.room_number, rm.room_type, rm.rate_per_night,
                               DATEDIFF(r.check_out_date, r.check_in_date) AS nights
                        FROM reservations r
                        JOIN guests g  ON g.guest_id = r.guest_id
                        JOIN rooms  rm ON rm.room_id  = r.room_id
                    ";
                    if ($status) {
                        $stmt = $db->prepare($sql . " WHERE r.status = ? ORDER BY r.check_in_date DESC");
                        $stmt->execute([$status]);
                    } else {
                        $stmt = $db->query($sql . " ORDER BY r.check_in_date DESC");
                    }
                    jsonResponse(true, 'OK', ['reservations' => $stmt->fetchAll()]);
                }
            }

            if ($method === 'POST') {
                // Validate room availability
                $checkStmt = $db->prepare("
                    SELECT COUNT(*) FROM reservations
                    WHERE room_id = :room_id
                      AND status NOT IN ('Checked-Out','Cancelled','No-Show')
                      AND check_in_date  < :co
                      AND check_out_date > :ci
                ");
                $checkStmt->execute([
                    ':room_id' => (int)$body['room_id'],
                    ':ci'      => $body['check_in_date'],
                    ':co'      => $body['check_out_date'],
                ]);
                if ($checkStmt->fetchColumn() > 0) {
                    jsonResponse(false, 'Room is not available for selected dates');
                }

                $stmt = $db->prepare("
                    INSERT INTO reservations
                    (guest_id,room_id,check_in_date,check_out_date,adults,children,status,special_requests)
                    VALUES (:gi,:ri,:ci,:co,:ad,:ch,:st,:sr)
                ");
                $stmt->execute([
                    ':gi' => (int)$body['guest_id'],
                    ':ri' => (int)$body['room_id'],
                    ':ci' => $body['check_in_date'],
                    ':co' => $body['check_out_date'],
                    ':ad' => (int)($body['adults'] ?? 1),
                    ':ch' => (int)($body['children'] ?? 0),
                    ':st' => clean($body['status'] ?? 'Confirmed'),
                    ':sr' => clean($body['special_requests'] ?? ''),
                ]);
                $resId = $db->lastInsertId();

                // Mark room as Reserved
                $db->prepare("UPDATE rooms SET status='Reserved' WHERE room_id=?")->execute([$body['room_id']]);

                jsonResponse(true, 'Reservation created', ['reservation_id' => $resId]);
            }

            if ($method === 'PUT' && $id) {
                $action = $body['action'] ?? 'update';

                if ($action === 'check_in') {
                    $stmt = $db->prepare("
                        UPDATE reservations
                        SET status='Checked-In', actual_check_in=NOW()
                        WHERE reservation_id=?
                    ");
                    $stmt->execute([$id]);
                    // Mark room Occupied
                    $res = $db->prepare("SELECT room_id FROM reservations WHERE reservation_id=?");
                    $res->execute([$id]);
                    $row = $res->fetch();
                    $db->prepare("UPDATE rooms SET status='Occupied' WHERE room_id=?")->execute([$row['room_id']]);
                    jsonResponse(true, 'Guest checked in');
                }

                if ($action === 'check_out') {
                    $stmt = $db->prepare("
                        UPDATE reservations
                        SET status='Checked-Out', actual_check_out=NOW()
                        WHERE reservation_id=?
                    ");
                    $stmt->execute([$id]);
                    $res = $db->prepare("SELECT room_id FROM reservations WHERE reservation_id=?");
                    $res->execute([$id]);
                    $row = $res->fetch();
                    $db->prepare("UPDATE rooms SET status='Available' WHERE room_id=?")->execute([$row['room_id']]);
                    jsonResponse(true, 'Guest checked out');
                }

                if ($action === 'cancel') {
                    $stmt = $db->prepare("UPDATE reservations SET status='Cancelled' WHERE reservation_id=?");
                    $stmt->execute([$id]);
                    $res = $db->prepare("SELECT room_id FROM reservations WHERE reservation_id=?");
                    $res->execute([$id]);
                    $row = $res->fetch();
                    $db->prepare("UPDATE rooms SET status='Available' WHERE room_id=?")->execute([$row['room_id']]);
                    jsonResponse(true, 'Reservation cancelled');
                }

                // Generic update
                $stmt = $db->prepare("
                    UPDATE reservations
                    SET guest_id=:gi,room_id=:ri,check_in_date=:ci,check_out_date=:co,
                        adults=:ad,children=:ch,status=:st,special_requests=:sr
                    WHERE reservation_id=:id
                ");
                $stmt->execute([
                    ':gi' => (int)$body['guest_id'],
                    ':ri' => (int)$body['room_id'],
                    ':ci' => $body['check_in_date'],
                    ':co' => $body['check_out_date'],
                    ':ad' => (int)($body['adults'] ?? 1),
                    ':ch' => (int)($body['children'] ?? 0),
                    ':st' => clean($body['status'] ?? 'Confirmed'),
                    ':sr' => clean($body['special_requests'] ?? ''),
                    ':id' => $id,
                ]);
                jsonResponse(true, 'Reservation updated');
            }

            if ($method === 'DELETE' && $id) {
                $res = $db->prepare("SELECT room_id, status FROM reservations WHERE reservation_id=?");
                $res->execute([$id]);
                $row = $res->fetch();
                if ($row && in_array($row['status'], ['Confirmed','Reserved'])) {
                    $db->prepare("UPDATE rooms SET status='Available' WHERE room_id=?")->execute([$row['room_id']]);
                }
                $db->prepare("DELETE FROM reservations WHERE reservation_id=?")->execute([$id]);
                jsonResponse(true, 'Reservation deleted');
            }
            break;

        // ========================================================
        //  ROOM SERVICE ORDERS
        // ========================================================
        case 'orders':
            if ($method === 'GET') {
                if ($id) {
                    // Get all orders for a reservation
                    $stmt = $db->prepare("
                        SELECT o.*, s.service_name, sc.category_name,
                               (o.quantity * o.unit_price * (1 - o.discount_pct/100)) AS line_total
                        FROM room_service_orders o
                        JOIN services s          ON s.service_id   = o.service_id
                        JOIN service_categories sc ON sc.category_id = s.category_id
                        WHERE o.reservation_id = ?
                        ORDER BY o.ordered_at DESC
                    ");
                    $stmt->execute([$id]);
                    jsonResponse(true, 'OK', ['orders' => $stmt->fetchAll()]);
                }
            }

            if ($method === 'POST') {
                // Fetch current unit price
                $svc = $db->prepare("SELECT unit_price FROM services WHERE service_id=?");
                $svc->execute([$body['service_id']]);
                $row = $svc->fetch();
                if (!$row) jsonResponse(false, 'Service not found');

                $stmt = $db->prepare("
                    INSERT INTO room_service_orders
                    (reservation_id,service_id,quantity,unit_price,discount_pct,notes,status)
                    VALUES (:ri,:si,:qty,:up,:dp,:notes,:st)
                ");
                $stmt->execute([
                    ':ri'    => (int)$body['reservation_id'],
                    ':si'    => (int)$body['service_id'],
                    ':qty'   => (float)($body['quantity'] ?? 1),
                    ':up'    => (float)$row['unit_price'],
                    ':dp'    => (float)($body['discount_pct'] ?? 0),
                    ':notes' => clean($body['notes'] ?? ''),
                    ':st'    => 'Pending',
                ]);
                jsonResponse(true, 'Order placed', ['order_id' => $db->lastInsertId()]);
            }

            if ($method === 'PUT' && $id) {
                $stmt = $db->prepare("UPDATE room_service_orders SET status=:st WHERE order_id=:id");
                $stmt->execute([':st' => clean($body['status']), ':id' => $id]);
                jsonResponse(true, 'Order updated');
            }

            if ($method === 'DELETE' && $id) {
                $db->prepare("DELETE FROM room_service_orders WHERE order_id=?")->execute([$id]);
                jsonResponse(true, 'Order deleted');
            }
            break;

        // ========================================================
        //  PAYMENTS
        // ========================================================
        case 'payments':
            if ($method === 'GET' && $id) {
                $stmt = $db->prepare("SELECT * FROM payments WHERE reservation_id=? ORDER BY paid_at DESC");
                $stmt->execute([$id]);
                jsonResponse(true, 'OK', ['payments' => $stmt->fetchAll()]);
            }

            if ($method === 'POST') {
                $stmt = $db->prepare("
                    INSERT INTO payments (reservation_id,amount,payment_method,reference_no,notes)
                    VALUES (:ri,:am,:pm,:ref,:nt)
                ");
                $stmt->execute([
                    ':ri'  => (int)$body['reservation_id'],
                    ':am'  => (float)$body['amount'],
                    ':pm'  => clean($body['payment_method']),
                    ':ref' => clean($body['reference_no'] ?? ''),
                    ':nt'  => clean($body['notes'] ?? ''),
                ]);
                jsonResponse(true, 'Payment recorded', ['payment_id' => $db->lastInsertId()]);
            }

            if ($method === 'DELETE' && $id) {
                $db->prepare("DELETE FROM payments WHERE payment_id=?")->execute([$id]);
                jsonResponse(true, 'Payment deleted');
            }
            break;

        // ========================================================
        //  CONSOLIDATED INVOICE  (Complex SQL JOIN)
        // ========================================================
        case 'invoice':
            if ($method === 'GET' && $id) {
                // — Reservation + Guest + Room details
                $resStmt = $db->prepare("
                    SELECT
                        r.reservation_id,
                        r.check_in_date,
                        r.check_out_date,
                        r.actual_check_in,
                        r.actual_check_out,
                        r.status           AS reservation_status,
                        r.adults,
                        r.children,
                        r.special_requests,
                        DATEDIFF(r.check_out_date, r.check_in_date) AS nights,
                        g.guest_id,
                        CONCAT(g.first_name,' ',g.last_name) AS guest_name,
                        g.email,
                        g.phone,
                        g.nationality,
                        g.vip_status,
                        g.id_type,
                        g.id_number,
                        rm.room_number,
                        rm.room_type,
                        rm.floor,
                        rm.rate_per_night,
                        DATEDIFF(r.check_out_date, r.check_in_date) * rm.rate_per_night AS room_subtotal
                    FROM reservations r
                    JOIN guests g  ON g.guest_id = r.guest_id
                    JOIN rooms  rm ON rm.room_id  = r.room_id
                    WHERE r.reservation_id = :id
                ");
                $resStmt->execute([':id' => $id]);
                $res = $resStmt->fetch();
                if (!$res) jsonResponse(false, 'Reservation not found');

                // — Line items from room service orders grouped by category
                $ordStmt = $db->prepare("
                    SELECT
                        o.order_id,
                        o.ordered_at,
                        o.quantity,
                        o.unit_price,
                        o.discount_pct,
                        o.notes,
                        o.status           AS order_status,
                        s.service_name,
                        s.unit_label,
                        sc.category_name,
                        ROUND(o.quantity * o.unit_price, 2)                              AS gross_amount,
                        ROUND(o.quantity * o.unit_price * (o.discount_pct / 100), 2)    AS discount_amount,
                        ROUND(o.quantity * o.unit_price * (1 - o.discount_pct / 100), 2) AS net_amount
                    FROM room_service_orders o
                    JOIN services          s  ON s.service_id   = o.service_id
                    JOIN service_categories sc ON sc.category_id = s.category_id
                    WHERE o.reservation_id = :id AND o.status != 'Cancelled'
                    ORDER BY sc.category_name, o.ordered_at
                ");
                $ordStmt->execute([':id' => $id]);
                $orders = $ordStmt->fetchAll();

                // — Service totals by category
                $catStmt = $db->prepare("
                    SELECT
                        sc.category_name,
                        COUNT(o.order_id)  AS item_count,
                        SUM(ROUND(o.quantity * o.unit_price * (1 - o.discount_pct/100), 2)) AS category_total
                    FROM room_service_orders o
                    JOIN services          s  ON s.service_id   = o.service_id
                    JOIN service_categories sc ON sc.category_id = s.category_id
                    WHERE o.reservation_id = :id AND o.status != 'Cancelled'
                    GROUP BY sc.category_id, sc.category_name
                    ORDER BY category_total DESC
                ");
                $catStmt->execute([':id' => $id]);
                $categories = $catStmt->fetchAll();

                // — Payments
                $payStmt = $db->prepare("
                    SELECT payment_id, amount, payment_method, reference_no, paid_at, notes
                    FROM payments
                    WHERE reservation_id = :id
                    ORDER BY paid_at
                ");
                $payStmt->execute([':id' => $id]);
                $payments = $payStmt->fetchAll();

                // — Totals calculation
                $serviceSubtotal = array_sum(array_column($orders, 'net_amount'));
                $roomSubtotal    = (float)$res['room_subtotal'];
                $subtotal        = $roomSubtotal + $serviceSubtotal;
                $tax             = round($subtotal * TAX_RATE, 2);
                $serviceCharge   = round($subtotal * SERVICE_CHARGE, 2);
                $grandTotal      = round($subtotal + $tax + $serviceCharge, 2);
                $totalPaid       = array_sum(array_column($payments, 'amount'));
                $balance         = round($grandTotal - $totalPaid, 2);

                jsonResponse(true, 'OK', [
                    'invoice' => [
                        'reservation'      => $res,
                        'line_items'       => $orders,
                        'category_summary' => $categories,
                        'payments'         => $payments,
                        'totals' => [
                            'room_subtotal'    => $roomSubtotal,
                            'service_subtotal' => $serviceSubtotal,
                            'subtotal'         => $subtotal,
                            'tax'              => $tax,
                            'service_charge'   => $serviceCharge,
                            'grand_total'      => $grandTotal,
                            'total_paid'       => $totalPaid,
                            'balance_due'      => $balance,
                        ],
                        'hotel' => [
                            'name'    => HOTEL_NAME,
                            'address' => HOTEL_ADDRESS,
                            'phone'   => HOTEL_PHONE,
                            'email'   => HOTEL_EMAIL,
                        ],
                    ]
                ]);
            }
            break;

        // ========================================================
        //  SERVICES CATALOG
        // ========================================================
        case 'services':
            if ($method === 'GET') {
                $stmt = $db->query("
                    SELECT s.*, sc.category_name
                    FROM services s
                    JOIN service_categories sc ON sc.category_id = s.category_id
                    WHERE s.is_active = 1
                    ORDER BY sc.category_name, s.service_name
                ");
                jsonResponse(true, 'OK', ['services' => $stmt->fetchAll()]);
            }
            break;

        // ========================================================
        //  DASHBOARD STATS
        // ========================================================
        case 'dashboard':
            if ($method === 'GET') {
                $stats = [];

                // Room occupancy counts
                $stmt = $db->query("SELECT status, COUNT(*) AS cnt FROM rooms GROUP BY status");
                $stats['room_status'] = $stmt->fetchAll();

                // Today's check-ins
                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM reservations
                    WHERE check_in_date = CURDATE() AND status IN ('Confirmed','Checked-In')
                ");
                $stmt->execute();
                $stats['checkins_today'] = (int)$stmt->fetchColumn();

                // Today's check-outs
                $stmt = $db->prepare("
                    SELECT COUNT(*) FROM reservations
                    WHERE check_out_date = CURDATE() AND status = 'Checked-In'
                ");
                $stmt->execute();
                $stats['checkouts_today'] = (int)$stmt->fetchColumn();

                // Pending room service orders
                $stmt = $db->prepare("SELECT COUNT(*) FROM room_service_orders WHERE status='Pending'");
                $stmt->execute();
                $stats['pending_orders'] = (int)$stmt->fetchColumn();

                // Revenue this month (room + services)
                $stmt = $db->prepare("
                    SELECT
                        COALESCE(SUM(DATEDIFF(r.check_out_date, r.check_in_date) * rm.rate_per_night), 0) AS room_revenue
                    FROM reservations r
                    JOIN rooms rm ON rm.room_id = r.room_id
                    WHERE MONTH(r.check_in_date) = MONTH(CURDATE())
                      AND YEAR(r.check_in_date)  = YEAR(CURDATE())
                      AND r.status NOT IN ('Cancelled','No-Show')
                ");
                $stmt->execute();
                $stats['room_revenue_month'] = (float)$stmt->fetchColumn();

                $stmt = $db->prepare("
                    SELECT COALESCE(SUM(o.quantity * o.unit_price * (1 - o.discount_pct/100)), 0)
                    FROM room_service_orders o
                    JOIN reservations r ON r.reservation_id = o.reservation_id
                    WHERE MONTH(o.ordered_at)=MONTH(CURDATE())
                      AND YEAR(o.ordered_at)=YEAR(CURDATE())
                      AND o.status != 'Cancelled'
                ");
                $stmt->execute();
                $stats['service_revenue_month'] = (float)$stmt->fetchColumn();

                // Currently checked-in guests
                $stmt = $db->prepare("
                    SELECT r.reservation_id,
                           CONCAT(g.first_name,' ',g.last_name) AS guest_name,
                           rm.room_number, rm.room_type,
                           r.check_out_date,
                           DATEDIFF(r.check_out_date, CURDATE()) AS nights_remaining
                    FROM reservations r
                    JOIN guests g  ON g.guest_id = r.guest_id
                    JOIN rooms  rm ON rm.room_id  = r.room_id
                    WHERE r.status = 'Checked-In'
                    ORDER BY r.check_out_date
                ");
                $stmt->execute();
                $stats['in_house_guests'] = $stmt->fetchAll();

                jsonResponse(true, 'OK', ['stats' => $stats]);
            }
            break;

        default:
            jsonResponse(false, 'Unknown resource: ' . $resource);
    }

} catch (PDOException $e) {
    jsonResponse(false, 'Database error: ' . $e->getMessage());
} catch (Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage());
}

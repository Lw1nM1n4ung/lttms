<?php
require_once __DIR__ . '/../config/database.php';

function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function formatPrice(float $amount): string {
    return number_format($amount, 0, '.', ',') . ' MMK';
}

function getMyanmarLocations(): array {
    return [
        'Yangon Region',
        'Mandalay Region',
        'Sagaing Region',
        'Bago Region',
        'Magway Region',
        'Ayeyarwady Region',
        'Tanintharyi Region',
        'Naypyidaw',
        'Shan State',
        'Kachin State',
        'Kayin State',
        'Kayah State',
        'Chin State',
        'Mon State',
        'Rakhine State',
    ];
}

function getActiveDestinations(): array {
    $pdo = getDBConnection();
    $stmt = $pdo->query("SELECT * FROM destinations WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
    return $stmt->fetchAll();
}

function formatDate(string $date): string {
    return date('M d, Y', strtotime($date));
}

function getFlashMessage(): ?string {
    if (isset($_SESSION['flash'])) {
        $msg = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $msg;
    }
    return null;
}

function setFlash(string $message): void {
    $_SESSION['flash'] = $message;
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function getPackages(array $filters = []): array {
    $pdo = getDBConnection();
    $sql = "SELECT p.*, h.name as hotel_name, t.type as transport_type, u.full_name as agent_name
            FROM packages p
            LEFT JOIN hotels h ON p.hotel_id = h.id
            LEFT JOIN transportation t ON p.transportation_id = t.id
            LEFT JOIN users u ON p.agent_id = u.id
            WHERE p.status = 'active'";
    $params = [];

    if (!empty($filters['destination'])) {
        $sql .= " AND p.destination LIKE ?";
        $params[] = '%' . $filters['destination'] . '%';
    }
    if (!empty($filters['min_price'])) {
        $sql .= " AND p.price_per_person >= ?";
        $params[] = $filters['min_price'];
    }
    if (!empty($filters['max_price'])) {
        $sql .= " AND p.price_per_person <= ?";
        $params[] = $filters['max_price'];
    }
    if (!empty($filters['min_rating'])) {
        $sql .= " AND p.rating_avg >= ?";
        $params[] = $filters['min_rating'];
    }

    $sql .= " ORDER BY p.created_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getPackageById(int $id): ?array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT p.*, h.name as hotel_name, h.location as hotel_location, h.price_per_night,
                t.type as transport_type, t.company_name, t.price as transport_price,
                u.full_name as agent_name, u.kbz_phone as agent_kbz_phone, u.kbz_name as agent_kbz_name
         FROM packages p
         LEFT JOIN hotels h ON p.hotel_id = h.id
         LEFT JOIN transportation t ON p.transportation_id = t.id
         LEFT JOIN users u ON p.agent_id = u.id
         WHERE p.id = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function getPackageFeedback(int $packageId): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare(
        "SELECT f.*, u.full_name as customer_name
         FROM feedback f
         JOIN users u ON f.customer_id = u.id
         WHERE f.package_id = ?
         ORDER BY f.created_at DESC"
    );
    $stmt->execute([$packageId]);
    return $stmt->fetchAll();
}

function getRatingDistribution(int $packageId): array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT rating, COUNT(*) as count FROM feedback WHERE package_id = ? GROUP BY rating ORDER BY rating DESC");
    $stmt->execute([$packageId]);
    $rows = $stmt->fetchAll();

    $dist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $total = 0;
    foreach ($rows as $r) {
        $dist[(int)$r['rating']] = (int)$r['count'];
        $total += (int)$r['count'];
    }
    return ['distribution' => $dist, 'total' => $total];
}

function renderStars(float $rating): string {
    $full = (int)floor($rating);
    $half = ($rating - $full) >= 0.25 && ($rating - $full) < 0.75 ? 1 : 0;
    $extra = ($rating - $full) >= 0.75 ? 1 : 0;
    $full += $extra;
    $empty = 5 - $full - $half;

    $html = '<span class="stars-display">';
    for ($i = 0; $i < $full; $i++) $html .= '<span class="star star-full">&#9733;</span>';
    if ($half) $html .= '<span class="star star-half">&#9733;</span>';
    for ($i = 0; $i < $empty; $i++) $html .= '<span class="star star-empty">&#9733;</span>';
    $html .= '</span>';
    return $html;
}

function updatePackageRating(int $packageId): void {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE packages SET rating_avg = (SELECT AVG(rating) FROM feedback WHERE package_id = ?) WHERE id = ?");
    $stmt->execute([$packageId, $packageId]);
}

function handleNRCUpload(array $file): ?string {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) {
        return null;
    }

    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info['mime'], $allowed)) {
        return null;
    }

    $ext = match($info['mime']) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/nrc/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    return $filename;
}

function handlePackageImageUpload(array $file): ?string {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) {
        return null;
    }

    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info['mime'], $allowed)) {
        return null;
    }

    $ext = match($info['mime']) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/packages/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    return $filename;
}

function handleDestinationImageUpload(array $file): ?string {
    $allowed = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > $maxSize) {
        return null;
    }

    $info = @getimagesize($file['tmp_name']);
    if (!$info || !in_array($info['mime'], $allowed)) {
        return null;
    }

    $ext = match($info['mime']) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        default      => 'jpg',
    };

    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $dest = __DIR__ . '/../uploads/destinations/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return null;
    }

    return $filename;
}

function getDashboardStats(): array {
    $pdo = getDBConnection();
    $stats = [];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM bookings");
    $stats['total_bookings'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COALESCE(SUM(total_price), 0) as total FROM bookings WHERE status = 'confirmed'");
    $stats['total_revenue'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT destination, COUNT(*) as count FROM packages p JOIN bookings b ON p.id = b.package_id GROUP BY destination ORDER BY count DESC LIMIT 5");
    $stats['popular_destinations'] = $stmt->fetchAll();

    $stmt = $pdo->query("SELECT COALESCE(AVG(rating), 0) as avg FROM feedback");
    $stats['avg_rating'] = round($stmt->fetch()['avg'], 1);

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $stats['total_customers'] = $stmt->fetch()['total'];

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE role = 'agent'");
    $stats['total_agents'] = $stmt->fetch()['total'];

    return $stats;
}

<?php
// Memanggil file koneksi dan header
require_once 'db_config.php';
header('Content-Type: application/json');

// Inisialisasi array respons dengan struktur yang benar
$response = array(
    'error' => true,
    'message' => 'Parameter user_id tidak ada.',
    'campaigns' => [],
    'counts' => [
        'all' => 0,
        'active' => 0, // 'active' akan digunakan untuk "Ongoing"
        'completed' => 0, // 'completed' akan digunakan untuk "Success"
        'draft' => 0, // 'draft' juga bisa dihitung sebagai "Ongoing"
        'closed' => 0
    ]
);

// Memeriksa apakah user_id ada di URL
if (isset($_GET['user_id'])) {
    $userId = (int)$_GET['user_id'];

    // --- Query Utama untuk mengambil daftar kampanye ---
    // Query ini sudah bagus, mengambil data kampanye milik user tertentu
    $query = "
        SELECT 
            c.*, 
            u.name as creator_name 
        FROM 
            campaigns c
        LEFT JOIN 
            users u ON c.creator_user_id = u.id
        WHERE 
            c.creator_user_id = ?
        ORDER BY 
            c.created_at DESC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $campaigns = array();
    while ($row = $result->fetch_assoc()) {
        $campaigns[] = $row;
    }
    $stmt->close();

    // Set data kampanye ke dalam respons
    $response['error'] = false;
    $response['message'] = 'Data kampanye berhasil diambil.';
    $response['campaigns'] = $campaigns;

    // ========================================================
    // === LOGIKA KUNCI UNTUK MENGHITUNG JUMLAH SETIAP STATUS ===
    // ========================================================
    // Query ini akan menghitung berapa banyak campaign untuk setiap status
    $count_query = "
        SELECT 
            status, 
            COUNT(*) as count 
        FROM 
            campaigns 
        WHERE 
            creator_user_id = ? 
        GROUP BY 
            status
    ";
    
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->bind_param("i", $userId);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();

    $total_campaigns = 0;
    while ($count_row = $count_result->fetch_assoc()) {
        // Ambil nama status (misal: 'active', 'completed')
        $status = strtolower($count_row['status']); 
        // Ambil jumlahnya (misal: 2, 1)
        $count = (int)$count_row['count'];
        
        // Cek apakah status dari database ada di array 'counts' kita
        if (array_key_exists($status, $response['counts'])) {
            // Jika ada, isi jumlahnya. Contoh: $response['counts']['active'] = 2;
            $response['counts'][$status] = $count;
        }
        // Tambahkan ke total keseluruhan
        $total_campaigns += $count;
    }
    // Set jumlah total untuk chip "All"
    $response['counts']['all'] = $total_campaigns;
    $count_stmt->close();
    // ========================================================

}

// Mengirimkan respons JSON yang sudah lengkap ke aplikasi
echo json_encode($response);
$conn->close();
?>
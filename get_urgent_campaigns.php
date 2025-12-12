<?php
// Memanggil file koneksi dan header
require_once 'db_config.php';
header('Content-Type: application/json');

// Inisialisasi array respons
$response =  array(
    'error' => true,
    'message' => 'Tidak ada kampanye yang ditemukan.',
    'campaigns' => []
);

// Query untuk mengambil kampanye yang aktif, diurutkan berdasarkan tanggal akhir (yang paling mendesak)
// Kita hanya mengambil 5 kampanye  teratas

$query = "
    SELECT 
        c.*, 
        u.name as creator_name 
    FROM 
        campaigns c
    JOIN 
        users u ON c.creator_user_id = u.id
    WHERE 
        c.status = 'active'
    ORDER BY 
        c.end_date  ASC 
    LIMIT 5
";

$result = $conn->query($query);

if ($result && $result->num_rows >0) {
    $campaigns = array();
    while ($row = $result->fetch_assoc()) {
        $campaigns[] = $row;
    }
    $response['error'] = false;
    $response['message'] = 'Kampanye urgen berhasil diambil.';
    $response['campaigns'] = $campaigns;
}

// Mengirimkan respons JSON ke aplikasi
echo json_encode($response);
$conn->close();
?>
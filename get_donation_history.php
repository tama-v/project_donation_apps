<?php

// Sertakan file koneksi database Anda
include 'db_connect.php';

header('Content-Type: application/json');

// --- Validasi Input ---
// Pastikan user_id dikirim melalui GET request
if (!isset($_GET['user_id'])) {
    echo json_encode([
        'error' => true,
        'message' => 'Parameter user_id tidak ditemukan.'
    ]);
    exit;
}

$userId = $_GET['user_id'];

// --- Query Database ---
// Query ini menggabungkan tiga tabel untuk mendapatkan data yang kita butuhkan.
// Asumsi: 
// - donations.campaign_id terhubung ke campaigns.id
// - campaigns.creator_id terhubung ke users.id
$query = "SELECT 
            c.title AS campaignTitle,
            u.name AS campaignOrganizer,
            d.created_at AS donationDate,
            d.amount,
            c.image_url AS campaignImageUrl
          FROM 
            donations AS d
          JOIN 
            campaigns AS c ON d.campaign_id = c.id
          JOIN 
            users AS u ON c.creator_id = u.id
          WHERE 
            d.user_id = ?
          ORDER BY 
            d.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

$donations = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Mengubah format tanggal agar lebih mudah dibaca (opsional)
        $date = new DateTime($row['donationDate']);
        $row['formattedDate'] = $date->format('M d, Y');
        
        // Memformat jumlah donasi (opsional, bisa juga dilakukan di sisi Android)
        $row['amount'] = (float) $row['amount'];

        $donations[] = $row;
    }
}

$stmt->close();
$conn->close();

// --- Kirim Respons ---
// Mengirim data dalam format JSON yang akan diterima oleh aplikasi Android
echo json_encode([
    'error' => false,
    'donations' => $donations
]);

?>
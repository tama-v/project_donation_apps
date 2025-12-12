<?php
// Memanggil file koneksi database
require_once 'db_config.php';

// Array untuk respons JSON
$response = array();

// --- (CATATAN KEAMANAN PENTING) ---
// Di aplikasi sesungguhnya, Anda HARUS menambahkan pemeriksaan di sini
// untuk memastikan bahwa yang mengakses script ini adalah benar-benar admin.
// Misalnya, dengan memeriksa token sesi atau peran pengguna.
// Untuk saat ini, kita biarkan terbuka untuk kemudahan pengembangan.

// Memeriksa apakah semua parameter yang dibutuhkan dikirim via POST
if (isset($_POST['title']) && isset($_POST['description']) && isset($_POST['points_reward'])) {

    $title = $_POST['title'];
    $description = $_POST['description'];
    $points_reward = $_POST['points_reward'];

    // Validasi sederhana
    if (!empty($title) && !empty($description) && is_numeric($points_reward)) {
        
        // Query untuk memasukkan data quest baru
        $stmt = $conn->prepare("INSERT INTO quests (title, description, points_reward) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $title, $description, $points_reward);

        // Menjalankan query
        if ($stmt->execute()) {
            $response['error'] = false;
            $response['message'] = 'Quest berhasil ditambahkan!';
        } else {
            $response['error'] = true;
            $response['message'] = 'Database error: Gagal menambahkan quest.';
        }
        $stmt->close();

    } else {
        $response['error'] = true;
        $response['message'] = 'Data tidak valid. Pastikan semua kolom terisi dan poin adalah angka.';
    }

} else {
    // Jika ada parameter yang kurang
    $response['error'] = true;
    $response['message'] = 'Parameter yang dibutuhkan tidak lengkap (title, description, points_reward).';
}

// Menampilkan respons dalam format JSON
header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>
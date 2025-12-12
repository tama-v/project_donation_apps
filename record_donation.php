<?php
// Selalu atur header ini di awal untuk memastikan klien (Android) tahu ini adalah JSON
header('Content-Type: application/json');

// Ganti 'db_config.php' dengan nama file koneksi database Anda yang sebenarnya
require_once 'db_config.php';

// Siapkan respons default jika terjadi kegagalan
$response = ['error' => true, 'message' => 'An unknown server error occurred.'];

// 1. Cek Koneksi Database
if (!isset($conn) || $conn->connect_error) {
    http_response_code(500); // Internal Server Error
    $response["message"] = "Database connection failed.";
    echo json_encode($response);
    exit(); // Hentikan eksekusi jika koneksi gagal
}

// 2. Cek apakah semua parameter yang dibutuhkan ada
if (!isset($_POST['campaign_id'], $_POST['amount'], $_POST['user_id'])) {
    http_response_code(400); // Bad Request
    $response["message"] = "Missing required parameters (campaign_id, amount, or user_id).";
    echo json_encode($response);
    exit();
}

// 3. Ambil dan bersihkan variabel
$campaign_id = (int)$_POST['campaign_id'];
$amount_donated = (float)$_POST['amount'];
$user_id = (int)$_POST['user_id'];

// Mulai transaksi untuk menjaga integritas data (semua atau tidak sama sekali)
$conn->begin_transaction();

try {
    // Langkah A: Cek saldo pengguna
    $stmt_check = $conn->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt_check->bind_param("i", $user_id);
    $stmt_check->execute();
    $user = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$user || $user['balance'] < $amount_donated) {
        throw new Exception('Saldo tidak mencukupi untuk melakukan donasi.');
    }

    // Langkah B: Kurangi saldo pengguna
    $stmt_debit = $conn->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $stmt_debit->bind_param("di", $amount_donated, $user_id);
    $stmt_debit->execute();
    $stmt_debit->close();
    
    // Langkah C: Tambahkan jumlah donasi ke dana terkumpul kampanye
    $stmt_credit = $conn->prepare("UPDATE campaigns SET current_amount = current_amount + ? WHERE id = ?");
    $stmt_credit->bind_param("di", $amount_donated, $campaign_id);
    $stmt_credit->execute();
    $stmt_credit->close();

    // Langkah D: Ambil data kampanye yang BARU untuk dicek
    $stmt_get = $conn->prepare("SELECT current_amount, target_amount, status FROM campaigns WHERE id = ?");
    $stmt_get->bind_param("i", $campaign_id);
    $stmt_get->execute();
    $campaign = $stmt_get->get_result()->fetch_assoc();
    $stmt_get->close();

    // =================================================================
    // === LANGKAH E: INI ADALAH LOGIKA KUNCI YANG SEBELUMNYA HILANG ===
    // =================================================================
    // Cek jika dana sudah mencapai target DAN statusnya masih 'active'
    if ($campaign && $campaign['status'] === 'active' && $campaign['target_amount'] > 0 && $campaign['current_amount'] >= $campaign['target_amount']) {
        
        // Jika ya, JALANKAN QUERY UPDATE KEDUA untuk mengubah status
        $stmt_status = $conn->prepare("UPDATE campaigns SET status = 'completed' WHERE id = ?");
        $stmt_status->bind_param("i", $campaign_id);
        $stmt_status->execute();
        $stmt_status->close();
    }
    // =================================================================

    // Jika semua proses di atas berhasil, simpan permanen perubahan ke database
    $conn->commit();
    $response = ["error" => false, "message" => "Donation successful!"];
    echo json_encode($response);

} catch (Exception $e) {
    // Jika ada satu saja proses yang gagal, batalkan semua perubahan
    $conn->rollback();
    http_response_code(500); // Kode error server
    $response["message"] = "Database transaction failed: " . $e->getMessage();
    echo json_encode($response);
}

$conn->close();
?>
<?php
// MATIKAN LAPORAN ERROR AGAR TIDAK MENGGANGGU JSON
error_reporting(0);
ini_set('display_errors', 0);

require_once 'db_config.php';
header('Content-Type: application/json');

// !!! PENTING: GANTI DENGAN IP ADDRESS ANDA DARI CMD -> ipconfig !!!
$YOUR_SERVER_IP = "192.168.1.6"; // CONTOH: "192.168.1.10"

// -------------------------------------------------------------------

$response = array('error' => true, 'message' => 'Terjadi kesalahan yang tidak diketahui.');

if (isset($_POST['creator_user_id'], $_POST['title'], $_POST['description'], $_POST['target_amount'], $_POST['end_date'], $_POST['category'], $_POST['campaign_category'], $_POST['status']) && isset($_FILES['image'])) {

    $upload_path = 'uploads/';
    if (!is_dir($upload_path)) {
        mkdir($upload_path, 0777, true);
    }

    $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $file_name = 'campaign_' . time() . '_' . uniqid() . '.' . $file_extension;
    $file_path = $upload_path . $file_name;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $file_path)) {
        // Buat URL yang benar menggunakan IP yang sudah Anda set
        $image_url = "http://" . $YOUR_SERVER_IP . "/donation_api/" . $file_path;

        $creator_user_id = $_POST['creator_user_id'];
        $title = $_POST['title'];
        $description = $_POST['description'];
        $target_amount = (float) str_replace(',', '', $_POST['target_amount']);
        $end_date = $_POST['end_date'];
        $category = $_POST['category'];
        $campaign_category = $_POST['campaign_category'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("INSERT INTO campaigns (creator_user_id, title, description, target_amount, end_date, category, campaign_category, status, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issdsssss", $creator_user_id, $title, $description, $target_amount, $end_date, $category, $campaign_category, $status, $image_url);

        if ($stmt->execute()) {
            $response['error'] = false;
            $response['message'] = ($status == 'draft') ? 'Campaign berhasil disimpan.' : 'Campaign berhasil dibuat!';
        } else {
            $response['message'] = 'Database error: ' . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = 'Gagal memindahkan file gambar. Periksa izin folder (permission) di server.';
    }
} else {
    $response['message'] = 'Parameter atau file gambar tidak lengkap.';
}

echo json_encode($response);
$conn->close();
?>
<?php
require_once 'db_config.php';

$response = array();

if (isset($_GET['id'])) {
    $userId = $_GET['id'];

    // Query SELECT sekarang mengambil semua kolom yang dibutuhkan
    $stmt = $conn->prepare("SELECT id, name, email, phone_number, address, profile_image_url, role, balance, donation_balance, kind_points FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $email, $phone_number, $address, $profile_image_url, $role, $balance, $donation_balance, $kind_points);
        $stmt->fetch();

        $response['error'] = false;
        
        // Array pengguna sekarang menyertakan semua data
        $user = array(
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'phone_number' => $phone_number,
            'address' => $address,
            'profile_image_url' => $profile_image_url,
            'role' => $role,
            'balance' => $balance,
            'donation_balance' => $donation_balance,
            'kind_points' => $kind_points
        );
        $response['user'] = $user;
        
    } else {
        $response['error'] = true;
        $response['message'] = 'Pengguna tidak ditemukan.';
    }
    $stmt->close();
} else {
    $response['error'] = true;
    $response['message'] = 'Parameter ID pengguna tidak ada.';
}

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>
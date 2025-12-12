<?php
require_once 'db_config.php';

$response = array();

if (isset($_POST['email']) && isset($_POST['password'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Query SELECT sekarang mengambil semua kolom yang dibutuhkan
    $stmt = $conn->prepare("SELECT id, name, email, password, phone_number, address, profile_image_url, role, balance, donation_balance, kind_points FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $email, $hashed_password, $phone_number, $address, $profile_image_url, $role, $balance, $donation_balance, $kind_points);
        $stmt->fetch();

        if (password_verify($password, $hashed_password)) {
            $response['error'] = false;
            $response['message'] = 'Login berhasil!';
            
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
            $response['message'] = 'Email atau password salah.';
        }
    } else {
        $response['error'] = true;
        $response['message'] = 'Email atau password salah.';
    }
    $stmt->close();
} else {
    $response['error'] = true;
    $response['message'] = 'Parameter yang dibutuhkan tidak lengkap.';
}

header('Content-Type: application/json');
echo json_encode($response);
$conn->close();
?>
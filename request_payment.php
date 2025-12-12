<?php
require_once 'config.php';
require_once 'db_config.php';
require_once __DIR__ . '/vendor/autoload.php'; // Panggil autoloader dari Composer

// Inisialisasi Kunci Midtrans (Ganti dengan kunci Anda)
\Midtrans\Config::$serverKey = 'YOUR_SERVER_KEY';
\Midtrans\Config::$isProduction = false; // Set ke true jika sudah di production
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

header('Content-Type: application/json');
$response = ['error' => true, 'message' => 'Invalid request'];

if (isset($_POST['user_id'], $_POST['amount'], $_POST['campaign_id'])) {
    
    $userId = (int)$_POST['user_id'];
    $amount = (int)$_POST['amount'];
    $campaignId = (int)$_POST['campaign_id'];
    
    // Buat ID unik untuk setiap order/transaksi
    $orderId = 'DONATION-' . $campaignId . '-' . $userId . '-' . time();

    // Ambil detail user dari database (diperlukan untuk detail customer di Midtrans)
    $user_stmt = $conn->prepare("SELECT name, email, phone_number FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $userId);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result()->fetch_assoc();
    
    if ($user_result) {
        
        $transaction_details = [
            'order_id' => $orderId,
            'gross_amount' => $amount,
        ];

        $customer_details = [
            'first_name' => $user_result['name'],
            'email' => $user_result['email'],
            'phone' => $user_result['phone_number'],
        ];

        $transaction_payload = [
            'transaction_details' => $transaction_details,
            'customer_details' => $customer_details,
        ];

        try {
            $snapToken = \Midtrans\Snap::getSnapToken($transaction_payload);
            
            // Simpan detail transaksi awal ke database Anda (opsional tapi direkomendasikan)
            // INSERT INTO donations (order_id, user_id, campaign_id, amount, status, ...)
            
            $response = [
                'error' => false,
                'message' => 'Token created successfully',
                'token' => $snapToken,
                'order_id' => $orderId
            ];
            
        } catch (Exception $e) {
            $response['message'] = 'Midtrans Error: ' . $e->getMessage();
        }
        
    } else {
        $response['message'] = "User not found";
    }

} else {
    $response['message'] = "Required parameters are missing (user_id, amount, campaign_id)";
}

echo json_encode($response);
$conn->close();

?>
<?php
// --- update_profile.php ---

// Memanggil file konfigurasi database.
require_once 'db_config.php';

// Menyiapkan array kosong untuk respons.
$response = array();

// Memeriksa apakah metode request yang digunakan adalah POST.
// Ini adalah standar keamanan agar data tidak dikirim lewat URL.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Mengambil semua data yang dikirim dari aplikasi Android.
    $userId = isset($_POST['id']) ? $_POST['id'] : '';
    $name = isset($_POST['name']) ? $_POST['name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $phone = isset($_POST['phone_number']) ? $_POST['phone_number'] : '';
    $address = isset($_POST['address']) ? $_POST['address'] : '';

    // Validasi dasar: memastikan field yang wajib diisi (ID, nama, email) tidak kosong.
    if (!empty($userId) && !empty($name) && !empty($email)) {

        // --- Pengecekan Email Duplikat (Opsional tapi sangat disarankan) ---
        // Query ini memeriksa apakah email baru sudah digunakan oleh PENGGUNA LAIN (id != ?).
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt_check->bind_param("si", $email, $userId); // 's' untuk string, 'i' untuk integer
        $stmt_check->execute();
        $stmt_check->store_result();

        // Jika ditemukan baris (num_rows > 0), berarti email sudah dipakai.
        if ($stmt_check->num_rows > 0) {
            $response['error'] = true;
            $response['message'] = 'Email ini sudah terdaftar oleh pengguna lain.';
            
        } else {
            // Jika email aman, lanjutkan proses update.
            // Menyiapkan query UPDATE untuk mengubah data di database.
            $stmt_update = $conn->prepare("UPDATE users SET name = ?, email = ?, phone_number = ?, address = ? WHERE id = ?");
            
            // Mengikat semua variabel ke query. Urutannya harus sesuai dengan tanda tanya.
            // "ssssi" = string, string, string, string, integer
            $stmt_update->bind_param("ssssi", $name, $email, $phone, $address, $userId);

            // Menjalankan query update.
            if ($stmt_update->execute()) {
                
                // Jika update berhasil, kita ambil lagi data terbaru untuk dikirim kembali ke Android.
                // Ini memastikan sesi di aplikasi Android juga ikut ter-update.
                $stmt_fetch = $conn->prepare("SELECT id, name, email, phone_number, address FROM users WHERE id = ?");
                $stmt_fetch->bind_param("i", $userId);
                $stmt_fetch->execute();
                $updated_user = $stmt_fetch->get_result()->fetch_assoc();
                $stmt_fetch->close();

                // Mengatur respons sukses.
                $response['error'] = false;
                $response['message'] = 'Profil berhasil diperbarui.';
                $response['user'] = $updated_user; // Data pengguna terbaru.

            } else {
                // Mengatur respons jika query update gagal dijalankan.
                $response['error'] = true;
                $response['message'] = 'Gagal memperbarui profil.';
            }
            // Menutup statement update.
            $stmt_update->close();
        }
        // Menutup statement pengecekan email.
        $stmt_check->close();
        
    } else {
        // Mengatur respons jika field yang wajib diisi ternyata kosong.
        $response['error'] = true;
        $response['message'] = 'Field yang wajib diisi (ID, Nama, Email) tidak boleh kosong.';
    }
    
} else {
    // Mengatur respons jika metode request bukan POST.
    $response['error'] = true;
    $response['message'] = 'Metode request tidak valid.';
}

// Menutup koneksi database.
$conn->close();

// Mengirim respons dalam format JSON.
echo json_encode($response);
?>
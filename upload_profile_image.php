<?php
// --- upload_profile_image.php ---

// Memanggil file konfigurasi database.
require_once 'db_config.php';

// Menyiapkan array respons.
$response = array();

// Mendefinisikan URL dasar dari server Anda.
// PENTING: Sesuaikan 'localhost' jika nama host Anda berbeda.
// Ini akan digabungkan dengan path file untuk membuat URL gambar yang lengkap.
$base_url = "http://10.0.2.2/donation_api/"; // URL yang bisa diakses dari emulator Android

// Memeriksa apakah ada 'id' (user ID) dan file gambar ('profile_image') yang dikirim.
if (isset($_POST['id']) && isset($_FILES['profile_image'])) {

    $userId = $_POST['id'];
    $file = $_FILES['profile_image'];

    // --- Mengelola File yang Diunggah ---

    // Mengambil nama asli file, contoh: "my_photo.jpg".
    $original_name = $file['name'];
    // Mengambil lokasi sementara file di server.
    $tmp_name = $file['tmp_name'];
    // Mengambil ekstensi file (misalnya, "jpg").
    $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);

    // Membuat nama file yang unik untuk mencegah tumpang tindih.
    // Format: userID_timestamp.ekstensi -> contoh: 1_1678886400.jpg
    $unique_file_name = $userId . '_' . time() . '.' . $file_extension;

    // Menentukan folder tujuan untuk menyimpan gambar.
    $target_dir = "uploads/";
    // Path lengkap file di server, contoh: "uploads/1_1678886400.jpg".
    $target_file_path = $target_dir . $unique_file_name;

    // Memindahkan file dari lokasi sementara ke folder 'uploads'.
    if (move_uploaded_file($tmp_name, $target_file_path)) {

        // --- Jika File Berhasil Diunggah, Simpan Path ke Database ---

        // Membuat URL lengkap ke gambar yang bisa diakses dari aplikasi.
        // contoh: http://10.0.2.2/donation_api/uploads/1_1678886400.jpg
        $image_url = $base_url . $target_file_path;

        // Menyiapkan query UPDATE untuk menyimpan URL gambar ke database.
        $stmt = $conn->prepare("UPDATE users SET profile_image_url = ? WHERE id = ?");
        $stmt->bind_param("si", $image_url, $userId);

        if ($stmt->execute()) {
            // Jika update database berhasil.
            $response['error'] = false;
            $response['message'] = 'Gambar profil berhasil diperbarui.';
            $response['profile_image_url'] = $image_url;
        } else {
            // Jika update database gagal.
            $response['error'] = true;
            $response['message'] = 'Gagal menyimpan URL gambar ke database.';
        }
        $stmt->close();
    } else {
        // Jika file gagal dipindahkan ke folder 'uploads'.
        $response['error'] = true;
        $response['message'] = 'Gagal mengunggah file gambar.';
    }
} else {
    // Jika user ID atau file gambar tidak dikirim.
    $response['error'] = true;
    $response['message'] = 'ID Pengguna dan file gambar dibutuhkan.';
}

// Menutup koneksi database.
$conn->close();

// Mengirim respons dalam format JSON.
echo json_encode($response);
?>
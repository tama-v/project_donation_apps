<?php

// 1. Sertakan file koneksi database Anda
// Pastikan path ini benar sesuai dengan struktur folder Anda.
require_once 'db_config.php';
// 2. Atur header respons sebagai JSON
// Ini memberitahu aplikasi Android bahwa data yang dikirim adalah format JSON.
header('Content-Type: application/json');

// 3. Query Utama untuk Mengambil Campaign Aktif
// - Memilih semua kolom yang relevan dari tabel 'campaigns' (alias 'c').
// - Menggabungkan (JOIN) dengan tabel 'users' (alias 'u') untuk mendapatkan nama pembuat campaign.
// - Menyaring (WHERE) hasilnya agar hanya campaign dengan status 'active' yang diambil.
// - Mengurutkan (ORDER BY) hasilnya berdasarkan tanggal pembuatan terbaru (DESC).
$query = "SELECT 
            c.id,
            c.creator_user_id,
            u.name AS creator_name,
            c.title,
            c.description,
            c.image_url,
            c.target_amount,
            c.current_amount,
            c.end_date,
            c.category,
            c.campaign_category,
            c.status,
            c.created_at
          FROM 
            campaigns AS c
          JOIN 
            users AS u ON c.creator_user_id = u.id
          WHERE 
            c.status = 'active'
          ORDER BY 
            c.created_at DESC";

$result = $conn->query($query);

// 4. Siapkan array kosong untuk menampung data
$campaigns = [];

// 5. Periksa apakah query berhasil dan mengembalikan data
if ($result && $result->num_rows > 0) {
    // Loop melalui setiap baris hasil dari database
    while ($row = $result->fetch_assoc()) {
        // Masukkan setiap baris (setiap campaign) ke dalam array $campaigns
        $campaigns[] = $row;
    }
}

// 6. Tutup koneksi database untuk menghemat sumber daya
$conn->close();

// 7. Kirim Respons Final ke Aplikasi Android
// - Data dikemas dalam format JSON.
// - Strukturnya sama dengan API campaign Anda yang lain untuk konsistensi.
echo json_encode([
    'error' => false,
    'campaigns' => $campaigns
]);

?>
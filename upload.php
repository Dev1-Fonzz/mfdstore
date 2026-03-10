<?php
// upload.php - HANYA CODE PHP, TIADA HTML
header('Content-Type: application/json');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$bloggerEmail = $_POST['bloggerEmail'] ?? '';
$smtpEmail = $_POST['smtpEmail'] ?? '';
$smtpPassword = $_POST['smtpPassword'] ?? ''; // ✅ BETUL: 'smtpPassword'
$image = $_FILES['image'] ?? null;

if (!$bloggerEmail || !$smtpEmail || !$smtpPassword || !$image) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$uniqueId = 'IMG_' . time() . '_' . rand(1000, 9999);

// Step 1: Hantar email ke Blogger
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = $smtpEmail;
    $mail->Password = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;
    
    $mail->setFrom($smtpEmail);
    $mail->addAddress($bloggerEmail);
    $mail->Subject = $uniqueId;
    $mail->Body = 'Auto upload';
    $mail->addAttachment($image['tmp_name'], $image['name']);
    $mail->send();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Gagal hantar email: ' . $mail->ErrorInfo]);
    exit;
}

// Step 2: Tunggu Blogger process
sleep(20);

// Step 3: Ambil link dari Blogger API
$blogId = '9058848324866679880';
$accessToken = 'ya29.a0ATkoCc64F8p7mRnsrkLgB7LIID5fSS-qBFZEgVi9khToIGa8Go7HxzHXJtTXtBe0c_Y5OB3-pKcFaAv9sdjBaKyAg9y9QegBMXakDfQIe-2tOmjrIIRyZ1I6FtN-gOLn4X4T5kIUUNs3O8pNyotXdgrHGTV771BxTJzP-n9m7103MAyGB_hGItpjbkBsVdW_40Hz2MAaCgYKAaISARASFQHGX2MiEe9AhgGAnLlSOryExWVzSg0206';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://www.googleapis.com/blogger/v3/blogs/{$blogId}/posts?status=draft&fetchBodies=true&maxResults=10");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$imageUrl = null;

if (isset($data['items'])) {
    foreach ($data['items'] as $post) {
        if (strpos($post['title'], $uniqueId) !== false) {
            preg_match('/<img[^>]+src="([^">]+)"/i', $post['content'], $matches);
            if (isset($matches[1])) {
                $imageUrl = preg_replace('/\/s[0-9]+\//', '/s1600/', $matches[1]);
                break;
            }
        }
    }
}

if ($imageUrl) {
    echo json_encode(['success' => true, 'image_url' => $imageUrl]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal dapatkan link gambar']);
}
?>

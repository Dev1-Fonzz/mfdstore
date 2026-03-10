<?php
// upload.php - SEMUA DALAM 1 FILE, TAK PERLU VENDOR/
error_reporting(E_ALL);
ini_set('display_errors', 0);
header('Content-Type: application/json');

function sendError($msg) {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// Simple SMTP Mailer Class (Ganti PHPMailer)
class SimpleSMTP {
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $username;
    private $password;
    
    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;
    }
    
    public function send($to, $subject, $body, $attachment = null) {
        $socket = fsockopen($this->smtp_host, $this->smtp_port, $errno, $errstr, 30);
        if (!$socket) return false;
        
        $this->read($socket);
        $this->send($socket, "EHLO " . gethostname() . "\r\n");
        $this->read($socket);
        $this->send($socket, "STARTTLS\r\n");
        $this->read($socket);
        stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
        $this->send($socket, "EHLO " . gethostname() . "\r\n");
        $this->read($socket);
        $this->send($socket, "AUTH LOGIN\r\n");
        $this->read($socket);
        $this->send($socket, base64_encode($this->username) . "\r\n");
        $this->read($socket);
        $this->send($socket, base64_encode($this->password) . "\r\n");
        $this->read($socket);
        $this->send($socket, "MAIL FROM:<" . $this->username . ">\r\n");
        $this->read($socket);
        $this->send($socket, "RCPT TO:<" . $to . ">\r\n");
        $this->read($socket);
        $this->send($socket, "DATA\r\n");
        $this->read($socket);
        
        $boundary = '----=' . md5(uniqid(time()));
        $headers = "From: <" . $this->username . ">\r\n";        $headers .= "To: <" . $to . ">\r\n";
        $headers .= "Subject: " . $subject . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/mixed; boundary=\"" . $boundary . "\"\r\n\r\n";
        
        $message = "--" . $boundary . "\r\n";
        $message .= "Content-Type: text/plain; charset=utf-8\r\n\r\n" . $body . "\r\n\r\n";
        
        if ($attachment) {
            $fileContent = file_get_contents($attachment['tmp_name']);
            $encoded = chunk_split(base64_encode($fileContent));
            $message .= "--" . $boundary . "\r\n";
            $message .= "Content-Type: application/octet-stream; name=\"" . $attachment['name'] . "\"\r\n";
            $message .= "Content-Disposition: attachment; filename=\"" . $attachment['name'] . "\"\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n" . $encoded . "\r\n\r\n";
        }
        
        $message .= "--" . $boundary . "--\r\n.\r\n";
        $this->send($socket, $message);
        $this->read($socket);
        $this->send($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    }
    
    private function send($socket, $data) {
        fwrite($socket, $data);
    }
    
    private function read($socket) {
        return fgets($socket, 1024);
    }
}

// Main Process
$bloggerEmail = $_POST['bloggerEmail'] ?? '';
$smtpEmail = $_POST['smtpEmail'] ?? '';
$smtpPassword = $_POST['smtpPassword'] ?? '';
$image = $_FILES['image'] ?? null;

if (!$bloggerEmail || !$smtpEmail || !$smtpPassword || !$image) {
    sendError('Data tidak lengkap');
}

$uniqueId = 'IMG_' . time() . '_' . rand(1000, 9999);

// Step 1: Hantar email ke Blogger
$mailer = new SimpleSMTP($smtpEmail, $smtpPassword);
$sent = $mailer->send($bloggerEmail, $uniqueId, 'Auto upload', [
    'tmp_name' => $image['tmp_name'],    'name' => $image['name']
]);

if (!$sent) {
    sendError('Gagal hantar email. Check App Password & 2FA Gmail.');
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
    sendError('Gagal dapatkan link gambar. Check Access Token & Blogger setting.');
}
?>

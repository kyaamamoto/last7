<?php
session_start();
require_once 'funcs.php';

$pdo = db_conn();

$receiver_id = $_POST['receiver_id'] ?? null;
$message = $_POST['message'] ?? null;
$sender_id = $_SESSION['user_id'] ?? null;
$is_admin = $_SESSION['kanri_flg'] ?? 0; // 管理者フラグを取得

if (!$receiver_id || !$message || !$sender_id) {
    http_response_code(400);
    exit('必要なパラメータが不足しています。');
}

try {
    // is_adminを適切に設定
    $is_admin_value = ($is_admin == 1) ? 1 : 0; // 管理者であれば1、そうでなければ0

    $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message, is_admin, created_at) VALUES (:sender_id, :receiver_id, :message, :is_admin, NOW())");
    $stmt->execute([
        ':sender_id' => $sender_id,
        ':receiver_id' => $receiver_id,
        ':message' => $message,
        ':is_admin' => $is_admin_value
    ]);
    echo 'メッセージが送信されました。';
} catch (PDOException $e) {
    http_response_code(500);
    echo 'エラーが発生しました: ' . $e->getMessage();
    error_log('Database error: ' . $e->getMessage());
}
<?php

require_once 'admin_session_config.php';
require_once 'funcs.php';

// 管理者認証チェック
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    header("Location: login.php");
    exit();
}

$pdo = db_conn();

$user_ids = json_decode($_POST['user_ids'], true);
$message = $_POST['message'] ?? null;
$sender_id = $_SESSION['user_id'] ?? null; // 管理者のIDを取得

if (!$user_ids || !$message || !$sender_id) {
    http_response_code(400);
    exit('必要なパラメータが不足しています。');
}

try {
    // 管理者がuser_tableに存在するか確認
    $stmt = $pdo->prepare("SELECT * FROM user_table WHERE id = :id");
    $stmt->execute([':id' => $sender_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {
        die('指定された管理者が存在しません。');
    }

    // 1. メッセージをnotificationsテーブルに挿入
    $stmt = $pdo->prepare("INSERT INTO notifications (message, sender_id, created_at) VALUES (:message, :sender_id, NOW())");
    $stmt->execute([
        ':message' => $message,
        ':sender_id' => $sender_id
    ]);

    $notification_id = $pdo->lastInsertId(); // 挿入したメッセージのIDを取得

    // 2. 各受信者のユーザーIDをnotification_recipientsテーブルに挿入
    foreach ($user_ids as $user_id) {
        $stmt = $pdo->prepare("INSERT INTO notification_recipients (notification_id, user_id) VALUES (:notification_id, :user_id)");
        $stmt->execute([
            ':notification_id' => $notification_id,
            ':user_id' => $user_id
        ]);
    }

    echo 'メッセージが送信されました。';
} catch (PDOException $e) {
    http_response_code(500);
    // エラーメッセージの詳細を表示
    echo 'エラーが発生しました: ' . $e->getMessage();
    // デバッグ情報をログに記録
    error_log('Database error: ' . $e->getMessage());
}
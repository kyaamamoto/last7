<?php
require_once 'admin_session_config.php';
require_once 'funcs.php';

// 管理者認証チェック
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    header("Location: login.php");
    exit();
}

// 一般的なログインチェック（loginCheck関数を使用）
loginCheck();

// データベース接続
$pdo = db_conn();

// POSTデータが存在するか確認
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 必要なデータがPOSTされているか確認
    if (isset($_POST['request_id'], $_POST['status'])) {
        $request_id = $_POST['request_id'];
        $status = $_POST['status'];

        // デバッグ用: POSTデータの確認
        // var_dump($_POST); // デバッグが不要になったらコメントアウト
        // exit();

        // 予約リクエストのステータスを更新
        $stmt = $pdo->prepare("UPDATE booking_requests SET status = ?, updated_at = NOW() WHERE id = ?");
        if ($stmt->execute([$status, $request_id])) {
            echo "更新成功";
        } else {
            echo "更新失敗: " . implode(":", $stmt->errorInfo());
        }

        // ユーザーへの通知処理（必要に応じて）
        if ($status == 'confirmed') {
            // 予約が確認された場合の処理
            // 例えば、ユーザーにメールで通知するコードをここに追加します
        } elseif ($status == 'rejected') {
            // 予約が拒否された場合の処理
            // こちらも同様にユーザーに通知するコードを追加可能です
        }

        // 処理が終わったら、予約リクエスト管理ページにリダイレクト
        header("Location: booking_status_update.php");
        exit();

    } else {
        // 必要なPOSTデータが存在しない場合のエラーメッセージ
        echo "リクエストIDまたはステータスが送信されていません。";
    }
} else {
    // POSTリクエストでない場合の処理
    echo "不正なアクセスです。";
}
<?php
session_start();
require_once 'admin_session_config.php';
require_once 'funcs.php';

// 管理者のログインチェック
loginCheck();

// JSON形式でPOSTデータを受け取る
$data = json_decode(file_get_contents('php://input'), true);

// データベース接続
$pdo = db_conn();

$response = ['success' => false, 'message' => ''];

try {
    $pdo->beginTransaction();

    foreach ($data['slots'] as $slot) {
        $date = $slot['date'];
        $start_time = $slot['time'];
        $end_time = date('H:i:s', strtotime('+1 hour', strtotime($start_time))); // 例として1時間後を終了時間に設定

        // NG日程をテーブルに挿入
        $stmt = $pdo->prepare("INSERT INTO unavailable_slots (date, start_time, end_time) VALUES (?, ?, ?)");
        $stmt->execute([$date, $start_time, $end_time]);
    }

    $pdo->commit();
    $response['success'] = true;
    $response['message'] = 'NG日程が正常に保存されました。';

} catch (Exception $e) {
    $pdo->rollBack();
    $response['message'] = 'NG日程の保存中にエラーが発生しました: ' . $e->getMessage();
}

// レスポンスをJSON形式で返す
header('Content-Type: application/json');
echo json_encode($response);
?>
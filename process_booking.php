<?php
// エラー報告とログ設定
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/path/to/your/php-error.log');

session_start();
require_once 'session_config.php';
require_once 'security_headers.php';
require_once 'funcs.php';

// 受信したデータをログに記録
$input = file_get_contents('php://input');
error_log('Received data: ' . $input);

// ログイン状態チェック
if (!isset($_SESSION['user_id'])) {
    error_log('User not logged in');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ログインが必要です。']);
    exit;
}

// POSTデータを取得
$data = json_decode($input, true);
error_log('Decoded data: ' . print_r($data, true));

$frontier_id = isset($data['frontier_id']) ? filter_var($data['frontier_id'], FILTER_VALIDATE_INT) : 0;
$slots = isset($data['slots']) ? $data['slots'] : [];
$user_message = isset($data['user_message']) ? trim($data['user_message']) : '';

// データベース接続
try {
    $pdo = db_conn();
    error_log('Database connection successful');
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'データベース接続エラー。']);
    exit;
}

// 入力チェック
if ($frontier_id === false || $frontier_id === 0 || empty($slots)) {
    error_log('Invalid input: frontier_id=' . $frontier_id . ', slots=' . print_r($slots, true));
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '必要なデータが不足しています。']);
    exit;
}

if (empty($user_message)) {
    error_log('Empty user message');
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'メッセージは必須です。']);
    exit;
}

try {
    // トランザクション開始
    $pdo->beginTransaction();
    error_log('Transaction started');

    $user_id = $_SESSION['user_id'];

    // booking_requests テーブルへの挿入
    $stmt = $pdo->prepare("
        INSERT INTO booking_requests (user_id, frontier_id, user_message) 
        VALUES (:user_id, :frontier_id, :user_message)
    ");
    error_log('Executing SQL query: ' . $stmt->queryString);
    $params = [
        ':user_id' => $user_id,
        ':frontier_id' => $frontier_id,
        ':user_message' => $user_message
    ];
    error_log('Parameters: ' . print_r($params, true));
    $stmt->execute($params);

    if ($stmt->errorCode() !== '00000') {
        error_log('SQL Error: ' . print_r($stmt->errorInfo(), true));
        throw new PDOException('Error inserting into booking_requests');
    }

    $booking_request_id = $pdo->lastInsertId();
    error_log('Inserted into booking_requests. ID: ' . $booking_request_id);

    // booking_request_slots テーブルへの挿入
    $stmt = $pdo->prepare("
        INSERT INTO booking_request_slots (booking_request_id, date, start_time, end_time) 
        VALUES (:booking_request_id, :date, :start_time, :end_time)
    ");

    foreach ($slots as $slot) {
        $date = $slot['date'];
        $start_time = $slot['time'];
        $end_time = date('H:i:s', strtotime($start_time . ' +1 hour'));

        error_log("Inserting slot: date=$date, start_time=$start_time, end_time=$end_time");

        $params = [
            ':booking_request_id' => $booking_request_id,
            ':date' => $date,
            ':start_time' => $start_time,
            ':end_time' => $end_time
        ];
        error_log('Parameters: ' . print_r($params, true));
        $stmt->execute($params);

        if ($stmt->errorCode() !== '00000') {
            error_log('SQL Error: ' . print_r($stmt->errorInfo(), true));
            throw new PDOException('Error inserting into booking_request_slots');
        }
    }

    // トランザクションをコミット
    $pdo->commit();
    error_log('Transaction committed successfully');

    echo json_encode(['success' => true, 'message' => '予約リクエストが正常に送信されました。']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log('Database error: ' . $e->getMessage());
    error_log('Error code: ' . $e->getCode());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '予約処理中にデータベースエラーが発生しました。']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log('General error: ' . $e->getMessage());
    error_log('Error code: ' . $e->getCode());
    error_log('Stack trace: ' . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => '予約処理中にエラーが発生しました：' . $e->getMessage()]);
}
?>
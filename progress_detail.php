<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'admin_session_config.php';
require_once 'funcs.php';

// データベース接続
$pdo = db_conn();

// URLパラメータからユーザーIDを取得
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

if (!$user_id) {
    die('ユーザーIDが指定されていません。');
}

// ユーザー情報の取得
$stmt = $pdo->prepare("SELECT * FROM holder_table WHERE id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die('指定されたユーザーが見つかりません。');
}

// 全体の進捗状況を計算する関数
function calculateOverallProgress($user, $pdo) {
    $total_items = 8;
    $completed_items = 0;

    $fields_to_check = ['theme', 'inquiry_content', 'hypothesis', 'learning_report', 'factor_analysis', 'summary'];
    foreach ($fields_to_check as $field) {
        if (!empty($user[$field])) {
            $completed_items++;
        }
    }

    // フロンティア完了状況をチェック
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_frontier_progress WHERE user_id = :user_id AND status = 'completed'");
    $stmt->execute([':user_id' => $user['id']]);
    $completed_frontiers = $stmt->fetchColumn();
    if ($completed_frontiers > 0) {
        $completed_items++;
    }

    // 予約確定状況をチェック
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM booking_requests WHERE user_id = :user_id AND status = 'confirmed'");
    $stmt->execute([':user_id' => $user['id']]);
    $confirmed_bookings = $stmt->fetchColumn();
    if ($confirmed_bookings > 0) {
        $completed_items++;
    }

    $progress = ($completed_items / $total_items) * 100;

    // 発表資料URLが設定されている場合、追加の進捗を与える
    if (!empty($user['presentation_url'])) {
        $progress += 12.5; // 8項目中の1項目分（100/8）を追加
    }

    return min($progress, 100);
}

// フロンティア進捗の取得
function getFrontierProgress($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT f.name, f.category, ufp.status, ufp.start_time, ufp.completion_time
        FROM user_frontier_progress ufp
        JOIN gs_chiiki_frontier f ON ufp.frontier_id = f.id
        WHERE ufp.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 予約状況の取得
function getBookingStatus($pdo, $user_id) {
    $stmt = $pdo->prepare("
        SELECT br.status, f.name AS frontier_name, br.created_at
        FROM booking_requests br
        JOIN gs_chiiki_frontier f ON br.frontier_id = f.id
        WHERE br.user_id = :user_id
    ");
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// フロンティア進捗の状態を日本語に変換する関数
function translateFrontierStatus($status) {
    switch ($status) {
        case 'not_started':
            return '未開始';
        case 'in_progress':
            return '進行中';
        case 'paused':
            return '一時停止';
        case 'completed':
            return '完了';
        default:
            return '不明';
    }
}

// 予約状況の状態を日本語に変換する関数
function translateBookingStatus($status) {
    switch ($status) {
        case 'pending':
            return '承認待ち';
        case 'confirmed':
            return '確定';
        case 'cancelled':
            return 'キャンセル';
        case 'rejected':
            return '却下';
        default:
            return '不明';
    }
}

// 全体の進捗状況を計算
$overallProgress = calculateOverallProgress($user, $pdo);

// フロンティア進捗と予約状況を取得
$frontierProgress = getFrontierProgress($pdo, $user['id']);
$bookingStatus = getBookingStatus($pdo, $user['id']);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($user['name']) ?>さんの進捗詳細 - ZOUUU 管理画面</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        h1, h2 { color: #333; }
        .section { background-color: #f4f4f4; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .progress-bar {
            width: 100%;
            background-color: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
        }
        .progress {
            height: 20px;
            background-color: #4CAF50;
            text-align: center;
            line-height: 20px;
            color: white;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }

        .status-not-started { color: #888; }
        .status-in-progress { color: #007bff; }
        .status-paused { color: #ffc107; }
        .status-completed { color: #28a745; }
        .status-pending { color: #17a2b8; }
        .status-confirmed { color: #28a745; }
        .status-cancelled { color: #dc3545; }
        .status-rejected { color: #dc3545; }

        .btn-container {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><?= h($user['name']) ?>さんの進捗詳細</h1>
        
        <div class="section">
            <h2>ユーザー情報</h2>
            <p><strong>ユーザーID:</strong> <?= h($user['id']) ?></p>
            <p><strong>名前:</strong> <?= h($user['name']) ?></p>
            <p><strong>メールアドレス:</strong> <?= h($user['email']) ?></p>
            <p><strong>登録日:</strong> <?= h($user['created_at']) ?></p>
        </div>

        <div class="section">
            <h2>全体の進捗状況</h2>
            <div class="progress-bar">
                <div class="progress" style="width: <?= $overallProgress ?>%;">
                    <?= number_format($overallProgress, 1) ?>%
                </div>
            </div>
        </div>

        <div class="section">
            <h2>フロンティア進捗</h2>
            <table>
                <tr>
                    <th>フロンティア名</th>
                    <th>カテゴリ</th>
                    <th>状態</th>
                    <th>開始日時</th>
                    <th>完了日時</th>
                </tr>
                <?php foreach ($frontierProgress as $progress): ?>
                    <tr>
                        <td><?= h($progress['name']) ?></td>
                        <td><?= h($progress['category']) ?></td>
                        <td class="status-<?= strtolower($progress['status']) ?>"><?= h(translateFrontierStatus($progress['status'])) ?></td>
                        <td><?= h($progress['start_time']) ?></td>
                        <td><?= h($progress['completion_time']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section">
            <h2>予約状況</h2>
            <table>
                <tr>
                    <th>フロンティア名</th>
                    <th>状態</th>
                    <th>申込日時</th>
                </tr>
                <?php foreach ($bookingStatus as $booking): ?>
                    <tr>
                        <td><?= h($booking['frontier_name']) ?></td>
                        <td class="status-<?= strtolower($booking['status']) ?>"><?= h(translateBookingStatus($booking['status'])) ?></td>
                        <td><?= h($booking['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="section">
            <h2>学習進捗</h2>
            <p><strong>テーマ:</strong> <?= h($user['theme']) ?></p>
            <p><strong>課題（探究内容）:</strong> <?= nl2br(h($user['inquiry_content'])) ?></p>
            <p><strong>解決のための仮説:</strong> <?= nl2br(h($user['hypothesis'])) ?></p>
            <p><strong>学びレポート:</strong> <?= nl2br(h($user['learning_report'])) ?></p>
            <p><strong>要因分析:</strong> <?= nl2br(h($user['factor_analysis'])) ?></p>
            <p><strong>まとめ:</strong> <?= nl2br(h($user['summary'])) ?></p>
            <?php if (!empty($user['presentation_url'])): ?>
                <p><strong>発表資料URL:</strong> <a href="<?= h($user['presentation_url']) ?>" target="_blank"><?= h($user['presentation_url']) ?></a></p>
            <?php else: ?>
                <p><strong>発表資料URL:</strong> まだ登録されていません</p>
            <?php endif; ?>
        </div>



        <div class="btn-container">
            <a href="cms.php" class="btn">戻る</a>
        </div>
    </div> <!-- container の閉じタグ -->
</body>
</html>
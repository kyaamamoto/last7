<?php
session_start();
require_once 'funcs.php';
require_once 'session_config.php';

loginCheck();

$pdo = db_conn();
$user_id = $_SESSION['user_id'];

// 最新のデータを取得する関数
function getLatestInvolvement($pdo, $user_id) {
    $sql = "SELECT * FROM past_involvement 
            WHERE user_id = :user_id 
            ORDER BY updated_at DESC 
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':user_id' => $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// 初期データ取得（データベースから最新のデータ）
try {
    $user_involvement = getLatestInvolvement($pdo, $user_id);
    if ($user_involvement) {
        saveToSession('past_data', $user_involvement);
    }
} catch (PDOException $e) {
    error_log("Database error in past_involvement.php: " . $e->getMessage());
    $_SESSION['error_message'] = "データの取得中にエラーが発生しました: " . $e->getMessage();
    redirect('holder.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateToken($_POST['csrf_token']);

    // POSTデータを取得し、サニタイズ
    $past_data = [
        'birthplace' => filter_input(INPUT_POST, 'birthplace', FILTER_SANITIZE_STRING),
        'place_of_residence' => filter_input(INPUT_POST, 'place_of_residence', FILTER_SANITIZE_STRING),
        'travel_experience' => filter_input(INPUT_POST, 'travel_experience', FILTER_SANITIZE_STRING),
        'visit_frequency' => filter_input(INPUT_POST, 'visit_frequency', FILTER_SANITIZE_STRING),
        'stay_duration' => filter_input(INPUT_POST, 'stay_duration', FILTER_SANITIZE_STRING),
        'volunteer_experience' => filter_input(INPUT_POST, 'volunteer_experience', FILTER_SANITIZE_STRING),
        'volunteer_activity' => isset($_POST['volunteer_activity']) ? json_encode(array_map('strip_tags', $_POST['volunteer_activity'])) : null,
        'volunteer_frequency' => filter_input(INPUT_POST, 'volunteer_frequency', FILTER_SANITIZE_STRING),
        'donation_experience' => filter_input(INPUT_POST, 'donation_experience', FILTER_SANITIZE_STRING),
        'donation_count' => filter_input(INPUT_POST, 'donation_count', FILTER_SANITIZE_STRING),
        'donation_reason' => filter_input(INPUT_POST, 'donation_reason', FILTER_SANITIZE_STRING),
        'product_purchase' => filter_input(INPUT_POST, 'product_purchase', FILTER_SANITIZE_STRING),
        'purchase_frequency' => filter_input(INPUT_POST, 'purchase_frequency', FILTER_SANITIZE_STRING),
        'purchase_reason' => filter_input(INPUT_POST, 'purchase_reason', FILTER_SANITIZE_STRING),
        'work_experience' => filter_input(INPUT_POST, 'work_experience', FILTER_SANITIZE_STRING),
        'work_type' => isset($_POST['work_type']) ? json_encode(array_map('strip_tags', $_POST['work_type'])) : null,
        'work_frequency' => filter_input(INPUT_POST, 'work_frequency', FILTER_SANITIZE_STRING)
    ];

    // セッションにデータを保存
    saveToSession('past_data', $past_data);

    // データベースに保存または更新
    try {
        $columns = implode(', ', array_keys($past_data));
        $placeholders = ':' . implode(', :', array_keys($past_data));
        $updates = [];
        foreach (array_keys($past_data) as $key) {
            $updates[] = "$key = VALUES($key)";
        }
        $updateString = implode(', ', $updates);

        $sql = "INSERT INTO past_involvement (user_id, $columns, created_at, updated_at) 
                VALUES (:user_id, $placeholders, NOW(), NOW())
                ON DUPLICATE KEY UPDATE 
                $updateString, 
                updated_at = NOW()";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        foreach ($past_data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $result = $stmt->execute();
        
        if ($result) {
            $_SESSION['success_message'] = "過去の関わりの情報が更新されました。";
            redirect('future_involvement.php');
        } else {
            throw new Exception("データの保存に失敗しました。");
        }
    } catch (PDOException $e) {
        error_log("Database error in past_involvement.php: " . $e->getMessage());
        $_SESSION['error_message'] = "データベースエラー: " . $e->getMessage();
        redirect('holder.php');
    } catch (Exception $e) {
        error_log("Error in past_involvement.php: " . $e->getMessage());
        $_SESSION['error_message'] = $e->getMessage();
        redirect('holder.php');
    }
}

$csrf_token = generateToken();

// 以下、HTMLの部分
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>地域との関わり（過去）の確認 - ふるさとID</title>
    <link rel="icon" type="image/png" href="https://zouuu.sakura.ne.jp/zouuu/img/IDfavicon.ico">
    <link rel="stylesheet" href="./css/styleholder.css">
</head>
<body>
    <header>
        <div class="logo">
            <a href="holder.php"><img src="https://zouuu.sakura.ne.jp/zouuu/img/fIDLogo.png" alt="ふるさとID ロゴ"></a>
        </div>
        <nav>
            <ul>
                <li><a href="skill_check.php">ふるさとID申請</a></li>
                <li><a href="#">ふるさとID活動記録</a></li>
                <li><a href="#">ふるさと×ウェルビーイング</a></li>
                <li><a href="logoutmypage.php">ログアウト</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h2>これまでの地域との関わりを教えてください</h2>
        <?php
        if (isset($_SESSION['error_message'])) {
            echo "<p class='error'>" . h($_SESSION['error_message']) . "</p>";
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['success_message'])) {
            echo "<p class='success'>" . h($_SESSION['success_message']) . "</p>";
            unset($_SESSION['success_message']);
        }
        ?>
        <form action="past_involvement.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
            
            <h3>1. 出身地・居住地</h3>
            <label>出身地である:</label><br>
            <input type="radio" name="birthplace" value="yes" <?php echo ($user_involvement['birthplace'] ?? '') === 'yes' ? 'checked' : ''; ?> required> はい
            <input type="radio" name="birthplace" value="no" <?php echo ($user_involvement['birthplace'] ?? '') === 'no' ? 'checked' : ''; ?> required> いいえ<br><br>

            <label>居住地である:</label><br>
            <input type="radio" name="place_of_residence" value="current" <?php echo ($user_involvement['place_of_residence'] ?? '') === 'current' ? 'checked' : ''; ?> required> 居住中
            <input type="radio" name="place_of_residence" value="past" <?php echo ($user_involvement['place_of_residence'] ?? '') === 'past' ? 'checked' : ''; ?> required> 過去居住していた
            <input type="radio" name="place_of_residence" value="never" <?php echo ($user_involvement['place_of_residence'] ?? '') === 'never' ? 'checked' : ''; ?> required> 居住していない<br><br>

            <h3>2. 旅行経験</h3>
        <label>旅行したことがある:</label><br>
        <input type="radio" name="travel_experience" value="yes" <?php echo ($user_involvement['travel_experience'] ?? '') === 'yes' ? 'checked' : ''; ?> required> はい
        <input type="radio" name="travel_experience" value="no" <?php echo ($user_involvement['travel_experience'] ?? '') === 'no' ? 'checked' : ''; ?> required> いいえ<br><br>

        <label>訪問頻度:</label>
        <select name="visit_frequency" required>
            <option value="">選択してください</option>
            <option value="never" <?php echo ($user_involvement['visit_frequency'] ?? '') === 'never' ? 'selected' : ''; ?>>訪れたことがない</option>
            <option value="yearly" <?php echo ($user_involvement['visit_frequency'] ?? '') === 'yearly' ? 'selected' : ''; ?>>年1回以上</option>
            <option value="occasionally" <?php echo ($user_involvement['visit_frequency'] ?? '') === 'occasionally' ? 'selected' : ''; ?>>数年に1回程度</option>
            <option value="rarely" <?php echo ($user_involvement['visit_frequency'] ?? '') === 'rarely' ? 'selected' : ''; ?>>ほとんど訪れない</option>
        </select><br><br>

        <label>滞在期間:</label>
        <select name="stay_duration" required>
            <option value="">選択してください</option>
            <option value="no_experience" <?php echo ($user_involvement['stay_duration'] ?? '') === 'no_experience' ? 'selected' : ''; ?>>滞在経験なし</option>
            <option value="day_trip" <?php echo ($user_involvement['stay_duration'] ?? '') === 'day_trip' ? 'selected' : ''; ?>>日帰り</option>
            <option value="short_stay" <?php echo ($user_involvement['stay_duration'] ?? '') === 'short_stay' ? 'selected' : ''; ?>>1〜3日</option>
            <option value="long_stay" <?php echo ($user_involvement['stay_duration'] ?? '') === 'long_stay' ? 'selected' : ''; ?>>4日以上</option>
        </select><br><br>

        <h3>3. ボランティア経験</h3>
        <label>ボランティア活動をしたことがある:</label><br>
        <input type="radio" name="volunteer_experience" value="yes" <?php echo ($user_involvement['volunteer_experience'] ?? '') === 'yes' ? 'checked' : ''; ?> required> はい
        <input type="radio" name="volunteer_experience" value="no" <?php echo ($user_involvement['volunteer_experience'] ?? '') === 'no' ? 'checked' : ''; ?> required> いいえ<br><br>

        <label>活動内容 (複数選択可):</label><br>
        <?php
        $volunteer_activities = json_decode($user_involvement['volunteer_activity'] ?? '[]', true);
        $activity_options = [
            'no_experience' => '経験なし',
            'cleaning' => '清掃活動',
            'event' => 'イベント運営',
            'welfare' => '福祉活動',
            'education' => '教育支援',
            'other' => 'その他'
        ];
        foreach ($activity_options as $value => $label) {
            $checked = in_array($value, $volunteer_activities) ? 'checked' : '';
            echo "<input type='checkbox' name='volunteer_activity[]' value='$value' $checked> $label<br>";
        }
        ?>
        <br>

        <label>活動頻度:</label>
        <select name="volunteer_frequency">
            <option value="">選択してください</option>
            <option value="no_experience" <?php echo ($user_involvement['volunteer_frequency'] ?? '') === 'no_experience' ? 'selected' : ''; ?>>経験なし</option>
            <option value="regular" <?php echo ($user_involvement['volunteer_frequency'] ?? '') === 'regular' ? 'selected' : ''; ?>>定期的</option>
            <option value="occasional" <?php echo ($user_involvement['volunteer_frequency'] ?? '') === 'occasional' ? 'selected' : ''; ?>>不定期</option>
            <option value="one_time" <?php echo ($user_involvement['volunteer_frequency'] ?? '') === 'one_time' ? 'selected' : ''; ?>>1回のみ</option>
        </select><br><br>

        <h3>4. 地域へのふるさと納税</h3>
        <label>寄付経験:</label><br>
        <input type="radio" name="donation_experience" value="yes" <?php echo ($user_involvement['donation_experience'] ?? '') === 'yes' ? 'checked' : ''; ?> required> はい
        <input type="radio" name="donation_experience" value="no" <?php echo ($user_involvement['donation_experience'] ?? '') === 'no' ? 'checked' : ''; ?> required> いいえ<br><br>

        <label>寄付回数:</label><br>
        <select name="donation_count" required>
            <option value="">選択してください</option>
            <option value="0回" <?php echo ($user_involvement['donation_count'] ?? '') === '0回' ? 'selected' : ''; ?>>0回</option>
            <option value="毎年" <?php echo ($user_involvement['donation_count'] ?? '') === '毎年' ? 'selected' : ''; ?>>毎年</option>
            <option value="1～3回" <?php echo ($user_involvement['donation_count'] ?? '') === '1～3回' ? 'selected' : ''; ?>>1～3回</option>
            <option value="4回以上" <?php echo ($user_involvement['donation_count'] ?? '') === '4回以上' ? 'selected' : ''; ?>>4回以上</option>
        </select><br><br>

        <label>寄付先の選び方:</label><br>
        <select name="donation_reason" required>
            <option value="">選択してください</option>
            <option value="寄付経験なし" <?php echo ($user_involvement['donation_reason'] ?? '') === '寄付経験なし' ? 'selected' : ''; ?>>寄付経験なし</option>
            <option value="個人的な関心" <?php echo ($user_involvement['donation_reason'] ?? '') === '個人的な関心' ? 'selected' : ''; ?>>個人的な関心</option>
            <option value="地域の応援" <?php echo ($user_involvement['donation_reason'] ?? '') === '地域の応援' ? 'selected' : ''; ?>>地域の応援</option>
            <option value="返礼品" <?php echo ($user_involvement['donation_reason'] ?? '') === '返礼品' ? 'selected' : ''; ?>>返礼品の魅力</option>
            <option value="その他" <?php echo ($user_involvement['donation_reason'] ?? '') === 'その他' ? 'selected' : ''; ?>>その他</option>
        </select><br><br>

        <h3>5. 地域物産品の購入</h3>
        <label>物産品の購入経験:</label><br>
        <input type="radio" name="product_purchase" value="yes" <?php echo ($user_involvement['product_purchase'] ?? '') === 'yes' ? 'checked' : ''; ?> required> はい
        <input type="radio" name="product_purchase" value="no" <?php echo ($user_involvement['product_purchase'] ?? '') === 'no' ? 'checked' : ''; ?> required> いいえ<br><br>

        <label>購入頻度:</label><br>
        <select name="purchase_frequency" required>
            <option value="">選択してください</option>
            <option value="購入経験なし" <?php echo ($user_involvement['purchase_frequency'] ?? '') === '購入経験なし' ? 'selected' : ''; ?>>購入経験なし</option>
            <option value="毎月" <?php echo ($user_involvement['purchase_frequency'] ?? '') === '毎月' ? 'selected' : ''; ?>>毎月</option>
            <option value="1～3回" <?php echo ($user_involvement['purchase_frequency'] ?? '') === '1～3回' ? 'selected' : ''; ?>>1～3回</option>
            <option value="4～6回" <?php echo ($user_involvement['purchase_frequency'] ?? '') === '4～6回' ? 'selected' : ''; ?>>4～6回</option>
            <option value="7回以上" <?php echo ($user_involvement['purchase_frequency'] ?? '') === '7回以上' ? 'selected' : ''; ?>>7回以上</option>
        </select><br><br>

        <label>購入理由:</label>
        <select name="purchase_reason" required>
            <option value="">選択してください</option>
            <option value="購入経験なし" <?php echo ($user_involvement['purchase_reason'] ?? '') === '購入経験なし' ? 'selected' : ''; ?>>購入経験なし</option>
            <option value="個人的な好み" <?php echo ($user_involvement['purchase_reason'] ?? '') === '個人的な好み' ? 'selected' : ''; ?>>個人的な関心</option>
            <option value="地域支援" <?php echo ($user_involvement['purchase_reason'] ?? '') === '地域支援' ? 'selected' : ''; ?>>地域の応援</option>
            <option value="特産品の魅力" <?php echo ($user_involvement['purchase_reason'] ?? '') === '特産品の魅力' ? 'selected' : ''; ?>>特産品の魅力</option>
            <option value="その他" <?php echo ($user_involvement['purchase_reason'] ?? '') === 'その他' ? 'selected' : ''; ?>>その他</option>
        </select><br><br>

        <h3>6. 仕事での地域との関わり</h3>
        <label>仕事での関わり経験:</label><br>
        <input type="radio" name="work_experience" value="yes" <?php echo ($user_involvement['work_experience'] ?? '') === 'yes' ? 'checked' : ''; ?> required> はい
        <input type="radio" name="work_experience" value="no" <?php echo ($user_involvement['work_experience'] ?? '') === 'no' ? 'checked' : ''; ?> required> いいえ<br><br>

        <label>仕事の種類:</label><br>
        <?php
        $work_types = json_decode($user_involvement['work_type'] ?? '[]', true);
        $type_options = [
            '製造業', 'サービス業', '農業', '林業', '漁業', 'IT関連', '医療・福祉', '教育', 'その他'
        ];
        foreach ($type_options as $option) {
            $checked = in_array($option, $work_types) ? 'checked' : '';
            echo "<input type='checkbox' name='work_type[]' value='$option' $checked> $option<br>";
        }
        ?>
        <br>

        <label>仕事での関わり頻度:</label><br>
        <select name="work_frequency" required>
            <option value="">選択してください</option>
            <option value="関わりなし" <?php echo ($user_involvement['work_frequency'] ?? '') === '関わりなし' ? 'selected' : ''; ?>>関わりなし</option>
            <option value="月に1回以上" <?php echo ($user_involvement['work_frequency'] ?? '') === '月に1回以上' ? 'selected' : ''; ?>>月に1回以上</option>
            <option value="年に数回" <?php echo ($user_involvement['work_frequency'] ?? '') === '年に数回' ? 'selected' : ''; ?>>年に数回</option>
            <option value="ほとんど関わりなし" <?php echo ($user_involvement['work_frequency'] ?? '') === 'ほとんど関わりなし' ? 'selected' : ''; ?>>ほとんど関わりなし</option>
        </select><br><br>
                    <input type="submit" value="次へ">
                </form>
            </main>

    <footer>
        <p>&copy; 2024 ふるさとID. All rights reserved.</p>
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // フォームの動的な表示/非表示の制御
        function toggleVisibility(triggerName, targetNames, showValue) {
            const triggerInputs = document.querySelectorAll(`input[name="${triggerName}"]`);
            triggerInputs.forEach(input => {
                input.addEventListener('change', function() {
                    targetNames.forEach(targetName => {
                        const targetElements = document.querySelectorAll(`[name^="${targetName}"]`).forEach(el => {
                            el.closest('label').style.display = this.value === showValue ? 'block' : 'none';
                            if (this.value !== showValue) {
                                if (el.type === 'checkbox' || el.type === 'radio') {
                                    el.checked = false;
                                } else {
                                    el.value = '';
                                }
                            }
                        });
                    });
                });
            });
        }

        // 各セクションの表示/非表示制御
        toggleVisibility('donation_experience', ['donation_count', 'donation_reason'], 'yes');
        toggleVisibility('product_purchase', ['purchase_frequency', 'purchase_reason'], 'yes');
        toggleVisibility('work_experience', ['work_type', 'work_frequency'], 'yes');

        // ページ読み込み時の初期状態設定
        document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
            radio.dispatchEvent(new Event('change'));
        });
    });
    </script>
</body>
</html>
<?php
ob_start();
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session_config.php';
require_once 'security_headers.php';
require_once 'funcs.php';

function debug_log($message) {
    error_log("[DEBUG] " . $message);
}

debug_log("Script started");
debug_log("Session data: " . json_encode($_SESSION));

try {
    loginCheck();
    debug_log("Login check passed");

    $pdo = db_conn();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    debug_log("Database connection established");

    if (!isset($_SESSION['user_id'])) {
        throw new Exception("User ID not set in session");
    }
    $user_id = $_SESSION['user_id'];
    debug_log("User ID: " . $user_id);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        debug_log("POST request received");
        validateToken($_POST['csrf_token']);
        debug_log("CSRF token validated");

        $qualifications = $_POST['qualifications'] ?? [];
        $other_qualifications = $_POST['other_qualifications'] ?? '';
        debug_log("Received qualifications: " . json_encode($qualifications));
        debug_log("Received other qualifications: " . $other_qualifications);

        $sql = "INSERT INTO user_qualifications (user_id, qualifications, other_qualifications) 
                VALUES (:user_id, :qualifications, :other_qualifications)
                ON DUPLICATE KEY UPDATE 
                qualifications = VALUES(qualifications), 
                other_qualifications = VALUES(other_qualifications)";

        debug_log("Prepared SQL: " . $sql);

        $pdo->beginTransaction();
        debug_log("Transaction started");

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':qualifications', json_encode($qualifications), PDO::PARAM_STR);
            $stmt->bindValue(':other_qualifications', $other_qualifications, PDO::PARAM_STR);

            $result = $stmt->execute();
            debug_log("SQL execution result: " . ($result ? "Success" : "Failure"));

            if ($result) {
                $pdo->commit();
                debug_log("Transaction committed");
                $_SESSION['success_message'] = "資格情報が更新されました。";
                debug_log("Redirecting to past_involvement.php");
                redirect('past_involvement.php');
                exit;
            } else {
                throw new Exception("SQL execution failed");
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            debug_log("Transaction rolled back. Error: " . $e->getMessage());
            throw $e;
        }
    }

    // 既存の資格データを取得
    $stmt = $pdo->prepare("SELECT * FROM user_qualifications WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user_qualifications = $stmt->fetch(PDO::FETCH_ASSOC);
    debug_log("Fetched existing qualifications: " . json_encode($user_qualifications));

    $existing_qualifications = [];
    $other_qualifications = '';

    if ($user_qualifications) {
        $existing_qualifications = json_decode($user_qualifications['qualifications'], true) ?: [];
        $other_qualifications = $user_qualifications['other_qualifications'] ?? '';
    }

    debug_log("Parsed existing qualifications: " . json_encode($existing_qualifications));
    debug_log("Other qualifications: " . $other_qualifications);

    $csrf_token = generateToken();
    debug_log("CSRF token generated");

} catch (Exception $e) {
    debug_log("Error occurred: " . $e->getMessage());
    $_SESSION['error_message'] = "エラーが発生しました。管理者にお問い合わせください。エラー: " . $e->getMessage();
    redirect('holder.php');
    exit;
}

$qualification_categories = [
    '1. 医療福祉' => ['医師', '歯科医師', '薬剤師', '看護師', '理学療法士', '作業療法士', '臨床検査技師', '放射線技師', '管理栄養士', '社会福祉士', '介護福祉士'],
    '2. 法律公務' => ['司法書士', '行政書士', '弁護士', '公認会計士', '税理士', '社会保険労務士', '不動産鑑定士', '宅地建物取引士'],
    '3. 建築土木' => ['一級建築士', '二級建築士', '施工管理技士（1級、2級）', '土木施工管理技士', '電気工事士（第一種、第二種）'],
    '4. 情報処理IT' => ['ITパスポート', '基本情報技術者', '応用情報技術者', '情報セキュリティマネジメント'],
    '5. 教育心理' => ['教員免許（小学校、中学校、高等学校）', '臨床心理士', '保育士', '公認心理師'],
    '6. 金融保険' => ['ファイナンシャルプランナー（FP）', '証券外務員', '保険代理店登録'],
    '7. 製造技術' => ['技術士', '電気主任技術者', 'ボイラー技士', '鉄道車両整備士'],
    '8. 運輸物流' => ['海技士', '航空整備士', '自動車整備士', '物流管理士'],
    '9. 環境農林水産' => ['環境計量士', '獣医師', '林業技士', '水産技師'],
    '10. 観光サービス' => ['通訳案内士', '観光プランナー', 'ホテルマネジメント資格']
];

debug_log("Qualification categories: " . json_encode($qualification_categories));
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>資格チェック - ふるさとID</title>
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
        <h2>資格チェック</h2>
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
        <form action="skill_check2.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
            
            <?php foreach ($qualification_categories as $category => $qualifications): ?>
                <h3><?php echo h($category); ?></h3>
                <?php foreach ($qualifications as $qualification): ?>
                    <label>
                        <input type="checkbox" name="qualifications[]" value="<?php echo h($qualification); ?>"
                            <?php echo in_array($qualification, $existing_qualifications) ? 'checked' : ''; ?>>
                        <?php echo h($qualification); ?>
                    </label><br>
                <?php endforeach; ?>
                <br>
            <?php endforeach; ?>

            <h3>11. その他</h3>
            <textarea name="other_qualifications" rows="4" cols="50" placeholder="その他の資格があればご記入ください"><?php echo h($other_qualifications); ?></textarea>

            <br><br>
            <input type="submit" value="次へ">
        </form>
    </main>

    <footer>
        <p>&copy; 2024 ふるさとID. All rights reserved.</p>
    </footer>
</body>
</html>
<?php 
ob_end_flush();
debug_log("Script ended");
?>
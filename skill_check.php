<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'session_config.php';
require_once 'security_headers.php';
require_once 'funcs.php';

// ログインチェック
loginCheck();

try {
    $pdo = db_conn();
    $user_id = $_SESSION['user_id'];

    // 既存のデータを取得
    $stmt = $pdo->prepare("SELECT * FROM skill_check WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $user_id]);
    $user_skills = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        validateToken($_POST['csrf_token']);
        
        $sql = "INSERT INTO skill_check (user_id, cooking, cleaning, childcare, communication, foreign_language, logical_thinking, it_skill, data_skill) 
                VALUES (:user_id, :cooking, :cleaning, :childcare, :communication, :foreign_language, :logical_thinking, :it_skill, :data_skill)
                ON DUPLICATE KEY UPDATE 
                cooking = VALUES(cooking),
                cleaning = VALUES(cleaning),
                childcare = VALUES(childcare),
                communication = VALUES(communication),
                foreign_language = VALUES(foreign_language),
                logical_thinking = VALUES(logical_thinking),
                it_skill = VALUES(it_skill),
                data_skill = VALUES(data_skill)";
        
        $stmt = $pdo->prepare($sql);
        
        $params = [':user_id' => $user_id];
        $skills = ['cooking', 'cleaning', 'childcare', 'communication', 'foreign_language', 'logical_thinking', 'it_skill', 'data_skill'];
        
        foreach ($skills as $skill) {
            $value = filter_input(INPUT_POST, $skill, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => 5]
            ]);
            if ($value === false) {
                throw new Exception("Invalid value for $skill");
            }
            $params[":$skill"] = $value;
        }

        $stmt->execute($params);

        $_SESSION['success_message'] = "スキル情報が更新されました。";
        redirect('past_involvement.php');  
    }

    $csrf_token = generateToken();
} catch (Exception $e) {
    error_log("Error in skill_check.php: " . $e->getMessage());
    $_SESSION['error_message'] = "エラーが発生しました。管理者にお問い合わせください。";
    redirect('holder.php');
}

function getSkillLabel($value) {
    switch ($value) {
        case 1: return '苦手';
        case 2: return 'やや苦手';
        case 3: return 'どちらでもない';
        case 4: return 'やや得意';
        case 5: return '得意';
        default: return '';
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>スキルチェック - ふるさとID</title>
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
        <h2>スキルチェック</h2>
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
        <form action="skill_check.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo h($csrf_token); ?>">
            
            <h3>生活スキル</h3>
            <?php
            $life_skills = [
                'cooking' => '料理',
                'cleaning' => '掃除',
                'childcare' => '子育て',
                'communication' => 'コミュニケーション'
            ];

            foreach ($life_skills as $skill_name => $skill_label) {
                echo "<label>" . h($skill_label) . ":</label><br>";
                for ($i = 1; $i <= 5; $i++) {
                    $checked = ($user_skills[$skill_name] ?? '') == $i ? 'checked' : '';
                    $label = getSkillLabel($i);
                    echo "<label><input type='radio' name='{$skill_name}' value='{$i}' {$checked} required> {$i} - {$label}</label><br>";
                }
                echo "<br>";
            }
            ?>

            <h3>ビジネススキル</h3>
            <?php
            $business_skills = [
                'foreign_language' => '外国語',
                'logical_thinking' => '論理的思考',
                'it_skill' => 'IT（表計算やプレゼンテーション）',
                'data_skill' => 'データ収集・分析'
            ];

            foreach ($business_skills as $skill_name => $skill_label) {
                echo "<label>" . h($skill_label) . ":</label><br>";
                for ($i = 1; $i <= 5; $i++) {
                    $checked = ($user_skills[$skill_name] ?? '') == $i ? 'checked' : '';
                    $label = getSkillLabel($i);
                    echo "<label><input type='radio' name='{$skill_name}' value='{$i}' {$checked} required> {$i} - {$label}</label><br>";
                }
                echo "<br>";
            }
            ?>

            <input type="submit" value="次へ">
        </form>
    </main>

    <footer>
        <p>&copy; 2024 ふるさとID. All rights reserved.</p>
    </footer>
</body>
</html>
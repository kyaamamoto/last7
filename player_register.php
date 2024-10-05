<?php
session_start();
require_once 'funcs.php';
loginCheck();

$pdo = db_conn();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $tags = $_POST['tags'];
    $youtube_url = $_POST['youtube_url'];
    
    // YouTube URLのバリデーション
    if (!empty($youtube_url) && !preg_match('/^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+$/', $youtube_url)) {
        $error_message = '無効なYouTube URLです。';
    } else {
        // 画像のアップロード処理（前回と同じ）
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $upload_dir = 'uploads/';
            $image_name = uniqid() . '_' . $_FILES['image']['name'];
            $upload_file = $upload_dir . $image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_file)) {
                $image_url = $upload_file;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO gs_chiiki_player (name, description, image_url, youtube_url, tags) VALUES (:name, :description, :image_url, :youtube_url, :tags)");
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':description', $description, PDO::PARAM_STR);
        $stmt->bindValue(':image_url', $image_url, PDO::PARAM_STR);
        $stmt->bindValue(':youtube_url', $youtube_url, PDO::PARAM_STR);
        $stmt->bindValue(':tags', $tags, PDO::PARAM_STR);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = '地域活性化プレイヤーの情報が正常に登録されました。';
            header('Location: player_register.php');
            exit();
        } else {
            $error_message = '登録に失敗しました。もう一度お試しください。';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>地域活性化プレイヤー登録 - ZOUUU</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <!-- ヘッダーの内容 -->
    </header>

    <main class="container">
        <h1>地域活性化プレイヤー登録</h1>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="error-message"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="name">名前：</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="description">説明：</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            
            <div class="form-group">
                <label for="image">画像：</label>
                <input type="file" id="image" name="image" accept="image/*" required>
            </div>
            
            <div class="form-group">
                <label for="youtube_url">YouTube URL：</label>
                <input type="url" id="youtube_url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=...">
            </div>
            
            <div class="form-group">
                <label for="tags">タグ（カンマ区切り）：</label>
                <input type="text" id="tags" name="tags" required>
            </div>
            
            <div class="form-group">
                <button type="submit">登録</button>
            </div>
        </form>
    </main>

    <footer>
        <!-- フッターの内容 -->
    </footer>
</body>
</html>
<?php
require_once 'admin_session_config.php';
require_once 'funcs.php';

// 管理者認証チェック
if (!isset($_SESSION['admin_auth']) || $_SESSION['admin_auth'] !== true) {
    header("Location: login.php");
    exit();
}

// データベース接続
$pdo = db_conn();

// 会員情報取得SQL作成
$stmt = $pdo->prepare("SELECT * FROM holder_table");
$status = $stmt->execute();

// テーブルの開始タグとヘッダー行
$view = "<table class='table table-striped table-bordered'>";
$view .= "<thead>
            <tr class='thead-custom'>
                <th>ID</th>
                <th>名前</th>
                <th>メールアドレス</th>
                <th>登録日時</th>
                <th>編集</th>
                <th>削除</th>
            </tr></thead><tbody>";

// データ表示
if ($status == false) {
    $error = $stmt->errorInfo();
    exit("ErrorQuery: " . $error[2]);
} else {
    while ($result = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $view .= "<tr>";
        $view .= "<td>" . h($result['id']) . "</td>";
        $view .= "<td>" . h($result['name']) . "</td>";
        $view .= "<td>" . h($result['email']) . "</td>";
        $view .= "<td>" . h($result['created_at']) . "</td>";
        $view .= "<td class='text-center'><button class='btn btn-primary btn-sm' onclick='editRecord(" . $result['id'] . ")'>編集</button></td>";
        $view .= "<td class='text-center'><button class='btn btn-danger btn-sm' onclick='deleteRecord(" . $result['id'] . ")'>削除</button></td>";
        $view .= "</tr>";
    }
    $view .= "</tbody></table>";
}

// CSRFトークンの取得
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>会員情報一覧 - ZOUUU Platform</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .navbar-custom {
            background-color: #0c344e;
        }
        .navbar-custom .nav-link, .navbar-custom .navbar-brand {
            color: white;
        }
        .thead-custom {
            background-color: #0c344e;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-custom">
        <a class="navbar-brand" href="#">
            <img src="./img/ZOUUU.png" alt="ZOUUU Logo" class="d-inline-block align-top" height="30">
            ZOUUU Platform
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <span class="nav-link">ようこそ <?php echo h($_SESSION['name']); ?> さん</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="cms.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">ログアウト</a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- パンくずリスト -->
    <nav aria-label="breadcrumb" class="mt-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="cms.php">ホーム</a></li>
            <li class="breadcrumb-item active" aria-current="page">会員情報一覧</li>
        </ol>
    </nav>

    <div class="container mt-5">
        <h1 class="mb-4">会員情報一覧</h1>

        <?php
        // メッセージ表示
        if (isset($_SESSION['error_message'])) {
            echo '<div class="alert alert-danger">' . h($_SESSION['error_message']) . '</div>';
            unset($_SESSION['error_message']);
        }
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . h($_SESSION['success_message']) . '</div>';
            unset($_SESSION['success_message']);
        }
        ?>

        <div class="card">
            <div class="card-body">
                <?php echo $view; ?>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-3">
            <a href="cms.php" class="btn btn-secondary mr-2">戻る</a>
            <a href="download.php" class="btn btn-success">ダウンロード</a>
        </div>
    </div>

    <footer class="footer bg-light text-center py-3 mt-4">
        <div class="container">
            <span class="text-muted">Copyright &copy; 2024 <a href="#">ZOUUU</a>. All rights reserved.</span>
        </div>
    </footer>

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function deleteRecord(id) {
        if (confirm('このレコードを削除してもよろしいですか？')) {
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = 'delete2.php';
            
            var idInput = document.createElement('input');
            idInput.type = 'hidden';
            idInput.name = 'id';
            idInput.value = id;
            form.appendChild(idInput);
            
            var tokenInput = document.createElement('input');
            tokenInput.type = 'hidden';
            tokenInput.name = 'csrf_token';
            tokenInput.value = '<?php echo $csrf_token; ?>';
            form.appendChild(tokenInput);
            
            document.body.appendChild(form);
            form.submit();
        }
    }

    function editRecord(id) {
        window.location.href = `edit2.php?id=${id}`;
    }
    </script>
</body>
</html>
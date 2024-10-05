<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>メール配信</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        .navbar-custom {
            background-color: #0c344e;
        }
        .navbar-custom .navbar-brand,
        .navbar-custom .nav-link {
            color: white !important;
        }
        h4 {
            text-align: center;
            margin-bottom: 20px;
            color: #0c344e;
        }
        .form-container {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        .target-card {
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .target-card:hover {
            box-shadow: 0 6px 8px rgba(0,0,0,0.15);
            transform: translateY(-5px);
        }
        .target-card .card-header {
            background-color: #0c344e;
            color: white;
            font-weight: bold;
        }
        .checkbox-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        .form-check {
            margin-bottom: 0;
        }
        .btn-send {
            background-color: #007bff;
            color: white;
        }
        .btn-send:hover {
            background-color: #0056b3;
            color: white;
        }
    </style>
</head>
<body>

<!-- ヘッダー -->
<nav class="navbar navbar-expand-lg navbar-custom">
    <a class="navbar-brand" href="cms.php">
        <img src="./img/ZOUUU.png" alt="ZOUUU Logo" class="d-inline-block align-top" height="30">
        ZOUUU Platform
    </a>
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <span class="nav-link">ようこそ <?php echo htmlspecialchars($_SESSION['name']); ?> さん</span>
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
    <li class="breadcrumb-item active" aria-current="page">メール配信</li>
  </ol>
</nav>

<!-- メインコンテンツ -->
<div class="container">
    <h4>メール配信</h4>

    <div class="form-container">
        <form id="emailForm" action="send_email.php" method="post">
            <div class="form-group">
                <label for="emailTemplate">メールテンプレート:</label>
                <select class="form-control" id="emailTemplate" name="emailTemplate" required>
                    <option value="">テンプレートを選択してください</option>
                    <!-- PHPでテンプレートオプションを動的に生成 -->
                </select>
            </div>

            <div class="form-group">
                <label for="emailContent">メール内容:</label>
                <textarea class="form-control" id="emailContent" name="emailContent" rows="10"></textarea>
            </div>

            <h5>ターゲット設定</h5>
            
            <div class="card target-card">
                <div class="card-header">
                    スキル
                </div>
                <div class="card-body">
                    <div class="checkbox-grid">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cooking" name="skills[]" value="cooking">
                            <label class="form-check-label" for="cooking">料理</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="cleaning" name="skills[]" value="cleaning">
                            <label class="form-check-label" for="cleaning">掃除</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="childcare" name="skills[]" value="childcare">
                            <label class="form-check-label" for="childcare">育児</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="communication" name="skills[]" value="communication">
                            <label class="form-check-label" for="communication">コミュニケーション</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="foreign_language" name="skills[]" value="foreign_language">
                            <label class="form-check-label" for="foreign_language">外国語</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="logical_thinking" name="skills[]" value="logical_thinking">
                            <label class="form-check-label" for="logical_thinking">論理的思考</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="it_skill" name="skills[]" value="it_skill">
                            <label class="form-check-label" for="it_skill">IT スキル</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="data_skill" name="skills[]" value="data_skill">
                            <label class="form-check-label" for="data_skill">データ分析</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card target-card">
                <div class="card-header">
                    過去の関わり方
                </div>
                <div class="card-body">
                    <div class="checkbox-grid">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="birthplace" name="pastInvolvement[]" value="birthplace">
                            <label class="form-check-label" for="birthplace">出身地</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="residence" name="pastInvolvement[]" value="residence">
                            <label class="form-check-label" for="residence">居住経験</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="travel" name="pastInvolvement[]" value="travel">
                            <label class="form-check-label" for="travel">旅行経験</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="volunteer" name="pastInvolvement[]" value="volunteer">
                            <label class="form-check-label" for="volunteer">ボランティア経験</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="donation" name="pastInvolvement[]" value="donation">
                            <label class="form-check-label" for="donation">寄付経験</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="work" name="pastInvolvement[]" value="work">
                            <label class="form-check-label" for="work">仕事経験</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card target-card">
                <div class="card-header">
                    将来の関わり方
                </div>
                <div class="card-body">
                    <div class="checkbox-grid">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="furusatoTax" name="futureInvolvement[]" value="furusatoTax">
                            <label class="form-check-label" for="furusatoTax">ふるさと納税</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="localEvents" name="futureInvolvement[]" value="localEvents">
                            <label class="form-check-label" for="localEvents">地域イベント</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="futureVolunteer" name="futureInvolvement[]" value="futureVolunteer">
                            <label class="form-check-label" for="futureVolunteer">ボランティア</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="localProducts" name="futureInvolvement[]" value="localProducts">
                            <label class="form-check-label" for="localProducts">地域産品</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="relocation" name="futureInvolvement[]" value="relocation">
                            <label class="form-check-label" for="relocation">移住</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="businessSupport" name="futureInvolvement[]" value="businessSupport">
                            <label class="form-check-label" for="businessSupport">ビジネス支援</label>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-send btn-lg btn-block mt-4">メール送信</button>
        </form>
    </div>

    <!-- ナビゲーションボタン -->
    <div class="text-center mt-5 mb-5">
        <a href="cms.php" class="btn btn-secondary btn-lg">戻る</a>
    </div>
</div>

<!-- BootstrapのJSとメールテンプレート選択のスクリプトは変更なし -->

</body>
</html>
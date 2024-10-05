<?php
require_once 'funcs.php'; // funcs.php を読み込み

// フォームからのデータを取得
$growth = $_POST['growth'];
$challenge = $_POST['challenge'];
$creativity = $_POST['creativity'];
$love = $_POST['love'];
$friendship = $_POST['friendship'];
$family = $_POST['family'];
$fairness = $_POST['fairness'];
$social_contribution = $_POST['social_contribution'];
$diversity = $_POST['diversity'];
$success = $_POST['success'];
$professionalism = $_POST['professionalism'];
$innovation = $_POST['innovation'];
$faith = $_POST['faith'];
$peace = $_POST['peace'];
$gratitude = $_POST['gratitude'];
$health = $_POST['health'];
$quality_of_life = $_POST['quality_of_life'];
$sustainability = $_POST['sustainability'];

// データベース接続
$pdo = db_conn();

// データベースにデータを挿入
$sql = "INSERT INTO user_values (
            growth, challenge, creativity, love, friendship, family, fairness,
            social_contribution, diversity, success, professionalism, innovation,
            faith, peace, gratitude, health, quality_of_life, sustainability
        ) VALUES (
            :growth, :challenge, :creativity, :love, :friendship, :family, :fairness,
            :social_contribution, :diversity, :success, :professionalism, :innovation,
            :faith, :peace, :gratitude, :health, :quality_of_life, :sustainability
        )";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':growth', $growth, PDO::PARAM_INT);
    $stmt->bindValue(':challenge', $challenge, PDO::PARAM_INT);
    $stmt->bindValue(':creativity', $creativity, PDO::PARAM_INT);
    $stmt->bindValue(':love', $love, PDO::PARAM_INT);
    $stmt->bindValue(':friendship', $friendship, PDO::PARAM_INT);
    $stmt->bindValue(':family', $family, PDO::PARAM_INT);
    $stmt->bindValue(':fairness', $fairness, PDO::PARAM_INT);
    $stmt->bindValue(':social_contribution', $social_contribution, PDO::PARAM_INT);
    $stmt->bindValue(':diversity', $diversity, PDO::PARAM_INT);
    $stmt->bindValue(':success', $success, PDO::PARAM_INT);
    $stmt->bindValue(':professionalism', $professionalism, PDO::PARAM_INT);
    $stmt->bindValue(':innovation', $innovation, PDO::PARAM_INT);
    $stmt->bindValue(':faith', $faith, PDO::PARAM_INT);
    $stmt->bindValue(':peace', $peace, PDO::PARAM_INT);
    $stmt->bindValue(':gratitude', $gratitude, PDO::PARAM_INT);
    $stmt->bindValue(':health', $health, PDO::PARAM_INT);
    $stmt->bindValue(':quality_of_life', $quality_of_life, PDO::PARAM_INT);
    $stmt->bindValue(':sustainability', $sustainability, PDO::PARAM_INT);
    $stmt->execute();
    
    // データが正常に保存されたら次のページにリダイレクト
    header("Location: past_involvement.php"); // 次のページにリダイレクト
    exit();
} catch (PDOException $e) {
    echo 'データベースエラー: ' . $e->getMessage();
}
?>
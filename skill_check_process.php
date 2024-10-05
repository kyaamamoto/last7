<?php
require_once 'funcs.php'; // funcs.php を読み込み

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// フォームからのデータを取得
$reading_writing = $_POST['reading_writing'];
$reading_writing2 = $_POST['reading_writing2'];
$communication = $_POST['communication'];
$hearing = $_POST['hearing'];
$logical_thinking = $_POST['logical_thinking'];
$it_skill = $_POST['it_skill'];
$data_skill = $_POST['data_skill'];
$cooking_skills = $_POST['cooking_skills'];
$cleaning_skills = $_POST['cleaning_skills'];
$laundry_skills = $_POST['laundry_skills'];
$childcare_skills = $_POST['childcare_skills'];
$nursing_care = $_POST['nursing_care'];
$qualifications = isset($_POST['qualifications']) ? implode(', ', $_POST['qualifications']) : '';
$other_qualifications = !empty($_POST['other_qualifications']) ? $_POST['other_qualifications'] : '';

// データベース接続
$pdo = db_conn();

// データベースにデータを挿入
$sql = "INSERT INTO skill_check_results (
            reading_writing, reading_writing2, communication, hearing, logical_thinking,
            it_skill, data_skill, cooking_skills, cleaning_skills, laundry_skills,
            childcare_skills, nursing_care, qualifications, other_qualifications)
        VALUES (
            :reading_writing, :reading_writing2, :communication, :hearing, :logical_thinking,
            :it_skill, :data_skill, :cooking_skills, :cleaning_skills, :laundry_skills,
            :childcare_skills, :nursing_care, :qualifications, :other_qualifications)";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':reading_writing', $reading_writing, PDO::PARAM_INT);
    $stmt->bindValue(':reading_writing2', $reading_writing2, PDO::PARAM_INT);
    $stmt->bindValue(':communication', $communication, PDO::PARAM_INT);
    $stmt->bindValue(':hearing', $hearing, PDO::PARAM_INT);
    $stmt->bindValue(':logical_thinking', $logical_thinking, PDO::PARAM_INT);
    $stmt->bindValue(':it_skill', $it_skill, PDO::PARAM_INT);
    $stmt->bindValue(':data_skill', $data_skill, PDO::PARAM_INT);
    $stmt->bindValue(':cooking_skills', $cooking_skills, PDO::PARAM_INT);
    $stmt->bindValue(':cleaning_skills', $cleaning_skills, PDO::PARAM_INT);
    $stmt->bindValue(':laundry_skills', $laundry_skills, PDO::PARAM_INT);
    $stmt->bindValue(':childcare_skills', $childcare_skills, PDO::PARAM_INT);
    $stmt->bindValue(':nursing_care', $nursing_care, PDO::PARAM_INT);
    $stmt->bindValue(':qualifications', $qualifications, PDO::PARAM_STR);
    $stmt->bindValue(':other_qualifications', $other_qualifications, PDO::PARAM_STR);
    $stmt->execute();
    
    // データが正常に保存されたら次のページにリダイレクト
    header("Location: value_registration.php");
    exit();
} catch (PDOException $e) {
    echo 'データベースエラー: ' . $e->getMessage();
}
?>
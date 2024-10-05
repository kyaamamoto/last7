<?php
// session_config.php

if (session_status() == PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 1); // HTTPS使用時のみ有効
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// セッションの再生成を過度に行わないように変更
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30分ごとに再生成
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

if (session_status() != PHP_SESSION_ACTIVE) {
    error_log("セッションが正しく開始されていません。");
    // ログアウト、またはエラーページへリダイレクト
    header("Location: login.php");
    exit();
}

// セッションにデータを保存する関数
function saveToSession($key, $data) {
    $_SESSION[$key] = $data;
}

// セッションからデータを取得する関数
function getFromSession($key) {
    return $_SESSION[$key] ?? null;
}

// セッションから特定のキーを削除する関数
function removeFromSession($key) {
    unset($_SESSION[$key]);
}

// セッションデータをクリアする関数
function clearSessionData() {
    $exclude = ['user_id', 'last_regeneration']; // 保持したいキーを指定
    foreach ($_SESSION as $key => $value) {
        if (!in_array($key, $exclude)) {
            unset($_SESSION[$key]);
        }
    }
}

// セッションデータのバリデーション関数（必要に応じて実装）
function validateSessionData($key, $data) {
    // ここでデータの検証ロジックを実装
    // 例: $key に応じて異なるバリデーションを行う
    return true; // バリデーション成功の場合
}
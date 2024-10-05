<?php
// smtp_config.php

define('SMTP_HOST', 'smtp.sakura.ne.jp');
define('SMTP_PORT', 587);  // または 465（SSL/TLSを使用する場合）
define('SMTP_USER', 'your_email@your-domain.com');  // あなたの実際のメールアドレス
define('SMTP_PASS', 'your_email_password');  // あなたの実際のパスワード
define('SMTP_SECURE', 'tls');  // または 'ssl'（ポート465を使用する場合）
<?php
// ============================================================
// お問い合わせフォーム送信処理（エックスサーバー用）
// 設定箇所は下の2つの定数のみ。公開前に必ず設定すること。
// ============================================================

// 受信先メールアドレス（CAVAT様の受信用アドレス）
const TO_EMAIL = 'CHANGE-ME@example.com';

// 送信元アドレス（公開ドメインと同じドメインのアドレスにすること。
// 例: info@サイトのドメイン。Xserverのメール設定で作成しておく）
const FROM_EMAIL = 'CHANGE-ME@example.com';

const FROM_NAME = 'ホームヘルパーNANA お問い合わせフォーム';

mb_language('ja');
mb_internal_encoding('UTF-8');

// POST以外・スパム（ハニーポット入力あり）はトップへ返す
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST' || !empty($_POST['company'])) {
    header('Location: ./contact.html');
    exit;
}

$name    = preg_replace('/[\r\n\t]+/', ' ', trim((string)($_POST['name'] ?? '')));
$type    = trim((string)($_POST['type'] ?? ''));
$tel     = trim((string)($_POST['tel'] ?? ''));
$email   = trim((string)($_POST['email'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));

$typeLabels = [
    'care'    => 'ご利用の相談',
    'manager' => 'ケアマネージャー・関係機関の方',
    'recruit' => '採用について',
    'other'   => 'その他',
];

$errors = [];
if ($name === '' || mb_strlen($name) > 100) {
    $errors[] = 'お名前を入力してください。';
}
if (!isset($typeLabels[$type])) {
    $errors[] = 'お問い合わせ種別を選択してください。';
}
if ($message === '' || mb_strlen($message) > 5000) {
    $errors[] = 'お問い合わせ内容を入力してください。';
}
if ($tel === '' && $email === '') {
    $errors[] = '電話番号またはメールアドレスのどちらか一方をご入力ください。';
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'メールアドレスの形式をご確認ください。';
}
if ($tel !== '' && !preg_match('/\A[0-9+\-() ]{8,20}\z/', $tel)) {
    $errors[] = '電話番号の形式をご確認ください。';
}

if ($errors) {
    $list = implode('</li><li>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $errors));
    header('Content-Type: text/html; charset=UTF-8');
    echo <<<HTML
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="robots" content="noindex" />
<title>入力内容の確認 | ホームヘルパーNANA</title>
<link rel="stylesheet" href="./styles.css" />
</head>
<body>
<main class="subpage-main" style="max-width:640px;margin:0 auto;padding:120px 24px 64px;">
<h1 style="font-size:1.4rem;">入力内容をご確認ください</h1>
<ul style="margin:24px 0;padding-left:1.4em;line-height:2;"><li>{$list}</li></ul>
<p><a class="button primary" href="./contact.html" style="display:inline-block;">お問い合わせフォームに戻る</a></p>
</main>
</body>
</html>
HTML;
    exit;
}

$typeLabel = $typeLabels[$type];

$body = "ホームヘルパーNANA 公式サイトのお問い合わせフォームから送信がありました。\n\n"
    . "■ お名前\n{$name}\n\n"
    . "■ お問い合わせ種別\n{$typeLabel}\n\n"
    . "■ 電話番号\n" . ($tel !== '' ? $tel : '（未入力）') . "\n\n"
    . "■ メールアドレス\n" . ($email !== '' ? $email : '（未入力）') . "\n\n"
    . "■ お問い合わせ内容\n{$message}\n\n"
    . "----\n送信日時: " . date('Y-m-d H:i:s') . "\n";

$subject = "【お問い合わせ】{$typeLabel} - {$name}様";

$headers = 'From: ' . mb_encode_mimeheader(FROM_NAME, 'ISO-2022-JP', 'B') . ' <' . FROM_EMAIL . '>' . "\r\n";
if ($email !== '') {
    $headers .= "Reply-To: {$email}\r\n";
}

$sent = mb_send_mail(TO_EMAIL, $subject, $body, $headers);

if ($sent) {
    header('Location: ./thanks.html');
} else {
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html lang="ja"><head><meta charset="UTF-8" /><meta name="robots" content="noindex" /><title>送信エラー | ホームヘルパーNANA</title></head><body style="font-family:sans-serif;max-width:640px;margin:80px auto;padding:0 24px;line-height:2;">'
        . '<h1 style="font-size:1.3rem;">送信に失敗しました</h1>'
        . '<p>お手数ですが、時間をおいて再度お試しください。</p>'
        . '<p><a href="./contact.html">お問い合わせフォームに戻る</a></p>'
        . '</body></html>';
}
exit;

<?php
declare(strict_types=1);
namespace NvcContact;

// Zone PHP 8.2+. No credentials or visitor content are written to application logs.
ini_set('display_errors', '0');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; base-uri 'none'; frame-ancestors 'none'");
const ORIGIN = 'https://test.nvc.ee';

function respond(int $code, string $message, bool $refresh = false): never {
    http_response_code($code);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['message' => $message, 'refresh' => $refresh], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="et"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Päringu tulemus – NVC</title><p>'
            . htmlspecialchars($message, ENT_QUOTES, 'UTF-8')
            . '</p><p><a href="https://test.nvc.ee/kontakt.html">Tagasi kontaktilehele</a></p></html>';
    }
    exit;
}
if (($_SERVER['HTTPS'] ?? '') !== 'on' && ($_SERVER['HTTPS'] ?? '') !== '1') {
    respond(400, 'Kasuta turvalist aadressi https://test.nvc.ee/kontakt.html.');
}
if (strtolower($_SERVER['HTTP_HOST'] ?? '') !== 'test.nvc.ee') {
    respond(403, 'Vorm on lubatud ainult testkeskkonnas.');
}
$method = $_SERVER['REQUEST_METHOD'] ?? '';
if (!in_array($method, ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    respond(405, 'See päringumeetod ei ole lubatud.');
}
if (getenv('NVC_CONTACT_ENABLED') !== '1') {
    respond(503, 'Vorm pole praegu saadaval. Kirjuta info@nvc.ee või helista +372 50 33 697.');
}
if ($method === 'POST') {
    if (($_SERVER['HTTP_ORIGIN'] ?? '') !== ORIGIN
        || !in_array($_SERVER['HTTP_SEC_FETCH_SITE'] ?? 'same-origin', ['same-origin', 'none'], true)) {
        respond(403, 'Ava kontaktileht uuesti ja proovi uuesti.');
    }
    if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 32768) respond(413, 'Päring on liiga suur.');
    if (strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0])) !== 'application/x-www-form-urlencoded') {
        respond(415, 'Vormi andmevorming ei ole lubatud.');
    }
}
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('__Host-nvc_contact');
session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'secure' => true, 'httponly' => true, 'samesite' => 'Strict']);
if (!session_start()) respond(503, 'Vorm pole praegu saadaval. Proovi hiljem uuesti.');
if ($method === 'GET') {
    if (($_GET['action'] ?? '') !== 'token') respond(405, 'Ava päringu saatmiseks kontaktileht.');
    // Keep a small set so multiple tabs work; session lock serializes consumption.
    $tokens = array_filter($_SESSION['tokens'] ?? [], fn($time) => is_int($time) && $time > time() - 3600);
    if (count($tokens) >= 5) array_shift($tokens);
    $token = bin2hex(random_bytes(32));
    $tokens[$token] = time();
    $_SESSION['tokens'] = $tokens;
    session_write_close();
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['token' => $token]);
    exit;
}
function field(string $name, int $min, int $max, bool $multiline = false): string {
    $value = $_POST[$name] ?? null;
    if (!is_string($value) || !preg_match('//u', $value)) respond(422, 'Kontrolli väljade sisu ja pikkust.');
    $value = trim($value);
    $length = preg_match_all('/./us', $value);
    if ($length < $min || $length > $max
        || preg_match($multiline ? '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/' : '/[\x00-\x1F\x7F]/', $value)) {
        respond(422, 'Kontrolli väljade sisu ja pikkust.');
    }
    return $value;
}
$token = $_POST['token'] ?? '';
if (!is_string($token) || !preg_match('/^[a-f0-9]{64}$/D', $token)
    || !isset($_SESSION['tokens'][$token])) respond(403, 'Vorm aegus. Proovi uuesti.', true);
$issued = $_SESSION['tokens'][$token];
if ($issued < time() - 3600) respond(403, 'Vorm aegus. Proovi uuesti.', true);
if (time() - $issued < 3) respond(429, 'Palun oota mõni sekund enne saatmist.');
if (!isset($_POST['website']) || !is_string($_POST['website']) || $_POST['website'] !== '') {
    respond(422, 'Päringut ei saanud saata.');
}
$name = field('nimi', 1, 100);
$email = field('email', 3, 254);
$subject = field('teema', 1, 150);
$message = field('sonum', 10, 5000, true);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) respond(422, 'Sisesta korrektne e-posti aadress.');

// One bounded, locked state file; fixed-size hashes, no raw IPs or message content.
// Directory MUST be provisioned outside the document root; fail closed on errors.
$storage = realpath(getenv('NVC_CONTACT_STORAGE') ?: '');
$root = realpath($_SERVER['DOCUMENT_ROOT'] ?? __DIR__);
if (!$storage || !$root || $storage === $root || str_starts_with($storage . DIRECTORY_SEPARATOR, $root . DIRECTORY_SEPARATOR)
    || !is_writable($storage)) respond(503, 'Vorm pole praegu saadaval. Kirjuta info@nvc.ee.');
$lock = @fopen($storage . '/rate.json', 'c+');
if (!$lock || !flock($lock, LOCK_EX)) respond(503, 'Vorm pole praegu saadaval. Proovi hiljem uuesti.');
$raw = stream_get_contents($lock);
$state = $raw === '' ? ['key' => bin2hex(random_bytes(32)), 'last' => 0, 'ips' => []] : json_decode($raw, true);
if (!is_array($state) || !is_string($state['key'] ?? null) || !is_array($state['ips'] ?? null)) {
    respond(503, 'Vorm pole praegu saadaval. Proovi hiljem uuesti.');
}
$now = time();
$state['ips'] = array_filter($state['ips'], fn($v) => is_array($v) && ($v['start'] ?? 0) > $now - 3600);
$ip = hash_hmac('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown', $state['key']);
$bucket = $state['ips'][$ip] ?? ['start' => $now, 'count' => 0, 'last' => 0];
if ($now - ($state['last'] ?? 0) < 5 || $now - $bucket['last'] < 60 || $bucket['count'] >= 5
    || (!isset($state['ips'][$ip]) && count($state['ips']) >= 10000)) {
    header('Retry-After: 60');
    respond(429, 'Liiga palju päringuid. Palun proovi hiljem uuesti.');
}
$state['last'] = $now;
$state['ips'][$ip] = ['start' => $bucket['start'], 'count' => $bucket['count'] + 1, 'last' => $now];
$encoded = json_encode($state);
if (!ftruncate($lock, 0) || !rewind($lock) || fwrite($lock, $encoded) !== strlen($encoded) || !fflush($lock)) {
    respond(503, 'Vorm pole praegu saadaval. Proovi hiljem uuesti.');
}
unset($_SESSION['tokens'][$token]);
session_write_close();
// Fixed envelope, recipient and subject. Visitor data never enters shell arguments.
// Email validation + control rejection protects the only visitor-supplied header.
$body = "Testkeskkonna kontaktipäring\n\nNimi: $name\nE-post: $email\nTeema: $subject\n\n$message\n";
$headers = [
    'From' => 'NVC test <info@nvc.ee>',
    'Reply-To' => $email,
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding' => 'base64',
];
try {
    $sent = @mail('info@nvc.ee', '[NVC TEST] Kontaktivormi paring',
        chunk_split(base64_encode($body)), $headers, '-finfo@nvc.ee');
} catch (\Throwable $error) {
    $sent = false;
}
flock($lock, LOCK_UN);
fclose($lock);
if (!$sent) respond(503, 'Saatmine ebaõnnestus. Proovi hiljem või kirjuta info@nvc.ee.', true);
respond(200, 'Aitäh! Päring on edastatud meiliserverile. Vastame esimesel võimalusel.', true);

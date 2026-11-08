<?php
declare(strict_types=1);
$base = sys_get_temp_dir() . '/nvc-tests-' . bin2hex(random_bytes(8));
mkdir($base, 0700);
mkdir($base . '/sessions', 0700);
$counter = 0;
function request(array $overrides, int $expected, bool $sends = false): array {
    global $base, $counter;
    ++$counter;
    $storage = $base . '/store-' . $counter;
    mkdir($storage, 0700);
    $capture = $base . '/capture-' . $counter;
    $input = array_replace(['sessions' => $base . '/sessions', 'storage' => $storage, 'capture' => $capture], $overrides);
    $input['capture'] = $capture;
    $process = proc_open([PHP_BINARY, __DIR__ . '/request.php'],
        [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    fwrite($pipes[0], json_encode($input)); fclose($pipes[0]);
    $body = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $exit = proc_close($process);
    if ($exit !== 0 || $errors !== 'STATUS=' . $expected || file_exists($capture) !== $sends) {
        throw new RuntimeException("Case $counter expected $expected: $errors / $body");
    }
    $data = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
    if ($sends) {
        [$to, $subject, $encoded, $headers, $args] = json_decode(file_get_contents($capture), true);
        if ($to !== 'info@nvc.ee' || $headers['Reply-To'] !== 'visitor@example.com'
            || $args !== '-finfo@nvc.ee' || !str_contains(base64_decode($encoded), 'Õie Test')) {
            throw new RuntimeException('Unsafe mail composition');
        }
    }
    return [$input, $data];
}
try {
    request(['enabled' => '0'], 503);
    request(['server' => ['HTTPS' => 'off']], 400);
    request(['server' => ['HTTP_HOST' => 'nvc.ee']], 403);
    request(['server' => ['REQUEST_METHOD' => 'PUT']], 405);
    request(['server' => ['HTTP_ORIGIN' => 'https://evil.example']], 403);
    request(['server' => ['HTTP_SEC_FETCH_SITE' => 'cross-site']], 403);
    request(['server' => ['CONTENT_TYPE' => 'application/json']], 415);
    request(['server' => ['CONTENT_LENGTH' => 40000]], 413);
    request(['post' => ['token' => 'bad']], 403);
    request(['post' => ['token' => ['array']]], 403);
    request(['age' => 3601], 403);
    request(['age' => 0], 429);
    request(['post' => ['website' => 'spam']], 422);
    foreach (['nimi', 'email', 'teema', 'sonum'] as $field) {
        request(['post' => [$field => ['array']]], 422);
        request(['post' => [$field => '']], 422);
    }
    request(['post' => ['email' => "visitor@example.com\r\nBcc: evil@example.com"]], 422);
    request(['post' => ['email' => 'broken@']], 422);
    request(['post' => ['nimi' => str_repeat('õ', 101)]], 422);
    request(['post' => ['sonum' => str_repeat('x', 5001)]], 422);
    request(['storage' => dirname(__DIR__)], 503);
    request(['storage' => '/nonexistent/nvc-test'], 503);
    request(['mail' => 'fail'], 503, true);
    request(['mail' => 'throw'], 503, true);
    [$successful] = request(['sessionId' => 'nvc-replay-test'], 200, true);
    request(array_replace($successful, ['reuse' => true]), 403);
    request(['storage' => $successful['storage']], 429);
    request(['storage' => $successful['storage'], 'server' => ['REMOTE_ADDR' => '192.0.2.20']], 429);
    $state = json_decode(file_get_contents($successful['storage'] . '/rate.json'), true);
    $key = array_key_first($state['ips']);
    $state['last'] = time() - 10;
    $state['ips'][$key]['last'] = time() - 61;
    $state['ips'][$key]['count'] = 5;
    file_put_contents($successful['storage'] . '/rate.json', json_encode($state));
    request(['storage' => $successful['storage']], 429);
    [, $data] = request(['server' => ['REQUEST_METHOD' => 'GET'], 'get' => ['action' => 'token']], 200);
    if (!preg_match('/^[a-f0-9]{64}$/D', $data['token'] ?? '')) throw new RuntimeException('Invalid issued token');
    echo "Passed $counter isolated handler cases; no mail sent.\n";
} finally {
    $items = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($items as $item) $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    rmdir($base);
}

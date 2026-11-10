<?php
declare(strict_types=1);
namespace NvcContact {
    // Isolated test double: never contacts a mail server.
    function mail($to, $subject, $body, $headers, $parameters): bool {
        file_put_contents(getenv('NVC_TEST_CAPTURE'), json_encode(func_get_args()));
        if (getenv('NVC_TEST_MAIL') === 'throw') throw new \RuntimeException('simulated');
        return getenv('NVC_TEST_MAIL') !== 'fail';
    }
}
namespace {
    $input = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    ini_set('session.save_path', $input['sessions']);
    $_SERVER = array_merge([
        'HTTPS' => 'on', 'HTTP_HOST' => 'test.nvc.ee', 'REQUEST_METHOD' => 'POST',
        'HTTP_ORIGIN' => 'https://test.nvc.ee', 'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/x-www-form-urlencoded',
        'DOCUMENT_ROOT' => dirname(__DIR__), 'REMOTE_ADDR' => '192.0.2.10',
    ], $input['server'] ?? []);
    $_POST = array_merge([
        'token' => str_repeat('a', 64), 'website' => '', 'nimi' => 'Õie Test',
        'email' => 'visitor@example.com', 'teema' => 'ISO nõustamine',
        'sonum' => 'See on piisavalt pikk testsõnum.',
    ], $input['post'] ?? []);
    $_GET = $input['get'] ?? [];
    putenv('NVC_CONTACT_ENABLED=' . ($input['enabled'] ?? '1'));
    putenv('NVC_CONTACT_STORAGE=' . ($input['storage'] ?? ''));
    putenv('NVC_TEST_CAPTURE=' . $input['capture']);
    putenv('NVC_TEST_MAIL=' . ($input['mail'] ?? 'ok'));
    session_name('__Host-nvc_contact');
    session_id($input['sessionId'] ?? bin2hex(random_bytes(16)));
    session_start();
    if (!($input['reuse'] ?? false)) $_SESSION['tokens'] = [str_repeat('a', 64) => time() - ($input['age'] ?? 10)];
    session_write_close();
    register_shutdown_function(function () {
        fwrite(STDERR, 'STATUS=' . (http_response_code() ?: 200));
    });
    require dirname(__DIR__) . '/send.php';
}

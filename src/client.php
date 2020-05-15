<?php

declare(strict_types=1);

use Swoole\HTTP\{Server, Request, Response};

$http = new Server("0.0.0.0", (int) $_ENV['PORT']);

$mime = [
    'css'  => 'text/css',
    'js'   => 'text/javascript',
    'png'  => 'image/png',
    'gif'  => 'image/gif',
    'jpg'  => 'image/jpg',
    'jpeg' => 'image/jpg',
    'mp4'  => 'video/mp4',
    'mp3'  => 'audio/mp3'
];

$http->on('start', function (Server $server) {
    echo "Server listening at port {$server->port}...\n";
});

$http->on('request', function (Request $request, Response $response) use ($mime) {
    if ($request->server['request_uri'] === '/favicon.ico') {
        return $response->end('');
    }

    if ($request->server['request_uri'] === '/') {
        return $response->sendfile(__DIR__ . '/index.html');
    }

    $filePath = __DIR__ . $request->server['request_uri'];
    if (!file_exists($filePath)) {
        return false;
    }

    $type = pathinfo($filePath, PATHINFO_EXTENSION);
    if (!isset($mime[$type])) {
        return false;
    }

    $response->header('Content-Type', $mime[$type]);
    return $response->sendfile($filePath);
});

$http->start();

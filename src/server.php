<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Game;
use Swoole\WebSocket\{Server, Frame};
use Swoole\Http\Request;

$game = new Game(50, 50, 2500);

$server = new Server("0.0.0.0", (int) $_ENV['PORT'], SWOOLE_BASE);

function updateGameState()
{
    global $server;
    global $game;

    $command = json_encode([
        'type' => 'update',
        'state' => $game->getState(),
    ]);

    foreach ($server->connections as $fd) {
        if ($server->isEstablished($fd)) {
            $server->push($fd, $command);
        }
    }
}

function updateScore()
{
    global $server;
    global $game;

    $topPlayersCount = 10;

    $command = json_encode([
        'type' => 'update-score',
        'topPlayers' => $game->getTopPlayers($topPlayersCount),
        'totalPlayers' => count($game->getState()['players']),
    ]);

    foreach ($server->connections as $fd) {
        if ($server->isEstablished($fd)) {
            $server->push($fd, $command);
        }
    }
}

$server->on('start', function (Server $server) use ($game) {
    echo "> WebSocket server listening at port {$server->port}...\n";

    $server->tick($game->getTickInterval(), function () use ($game) {
        $game->addRandomFruit();
        updateGameState();
    });
});

$server->on('open', function (Server $server, Request $request) use ($game) {
    $playerId = $request->fd;

    echo "> Player connected {$playerId} \n";

    $size = $game->getSize();
    
    $randomName = substr(\md5(\microtime(true)), 0, 15);
    $game->addPlayer($playerId, $randomName, rand(0, $size['width'] - 1), rand(0, $size['height'] - 1));

    $command = [
        'type' => 'setup',
        'playerId' => $playerId,
        'size' => $size,
        'name' => $randomName,
    ];

    $server->push($playerId, json_encode($command));

    updateGameState();
    updateScore();
});

$server->on('message', function (Server $server, Frame $frame) use ($game) {
    $playerId = $frame->fd;
    $command = json_decode($frame->data);

    if ($command->type == 'player-move') {
        $playerHasMoved = false;

        if ($command->direction === 'up') {
            $playerHasMoved = $game->movePlayer($playerId, 0, -1);
        } else if ($command->direction === 'right') {
            $playerHasMoved = $game->movePlayer($playerId, 1, 0);
        } else if ($command->direction === 'down') {
            $playerHasMoved = $game->movePlayer($playerId, 0, 1);
        } else if ($command->direction === 'left') {
            $playerHasMoved = $game->movePlayer($playerId, -1, 0);
        }

        if ($game->checkFruitColision($playerId)) {
            $server->push($playerId, json_encode([
                "type" => "fruit-colision"
            ]));

            updateScore();
        }

        if ($playerHasMoved) {
            updateGameState();
        }
    }

    if ($command->type == 'update-player-name') {
        $newName = filter_var($command->name, FILTER_SANITIZE_STRING);
        $game->updatePlayerName($playerId, $newName);
    }
});

$server->on('close', function (Server $server, int $fd) use ($game) {
    $playerId = $fd;

    echo "> Disconnected: {$playerId} \n";

    $game->removePlayer($playerId);

    updateGameState();
});

$server->start();

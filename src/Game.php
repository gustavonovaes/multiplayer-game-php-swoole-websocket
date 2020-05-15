<?php

namespace App;

class Game
{
  private int $screenWidth;

  private int $screenHeight;

  private int $tickInterval;

  public function __construct(int $screenWidth, int $screenHeight, int $tickInterval)
  {
    $this->screenWidth = $screenWidth;
    $this->screenHeight = $screenHeight;
    $this->tickInterval = $tickInterval;
  }

  private array $state = [
    'players' => [],
    'fruits' => [],
  ];

  public function getSize(): array
  {
    return [
      'width' => $this->screenWidth,
      'height' => $this->screenHeight
    ];
  }

  public function getTickInterval()
  {
    return $this->tickInterval;
  }

  public function addPlayer(int $playerId, string $name, int $x, int $y): void
  {
    $this->state['players'][$playerId] = [
      'id' => $playerId,
      'name' => $name,
      'x' => $x,
      'y' => $y,
      'fruits' => [],
    ];
  }

  public function removePlayer(int $playerId): void
  {
    unset($this->state['players'][$playerId]);
  }

  public function updatePlayerName(int $playerId, string $newName): void
  {
    $this->state['players'][$playerId]['name'] = $newName;
  }

  public function movePlayer(int $playerId, int $x, int $y): bool
  {
    if (!isset($this->state['players'][$playerId])) {
      return false;
    }

    [
      'x' => $playerX,
      'y' => $playerY,
    ] = $this->state['players'][$playerId];

    $playerX += $x;
    $playerY += $y;

    if ($playerX < 0 || $playerX >= $this->screenWidth) {
      return false;
    }

    if ($playerY < 0 || $playerY >= $this->screenHeight) {
      return false;
    }

    $this->state['players'][$playerId]['x'] = $playerX;
    $this->state['players'][$playerId]['y'] = $playerY;

    return true;
  }

  public function addFruit(int $fruitId, int $x, int $y): void
  {
    $this->state['fruits'][$fruitId] = [
      'x' => $x,
      'y' => $y
    ];
  }

  public function removeFruit(int $fruitId): void
  {
    unset($this->state['fruits'][$fruitId]);
  }

  public function addRandomFruit(): void
  {
    $this->addFruit(
      time(),
      rand(0, $this->screenWidth - 1),
      rand(0, $this->screenHeight - 1)
    );
  }

  public function addFruitToPlayer(int $playerId, int $fruitId): void
  {
    $this->state['players'][$playerId]['fruits'][] = $fruitId;
  }

  public function checkFruitColision(int $playerId): bool
  {
    ['x' => $playerX, 'y' => $playerY] = $this->state['players'][$playerId];

    foreach ($this->state['fruits'] as $fruitId => ['x' => $fruitX, 'y' => $fruitY]) {
      if ($fruitX === $playerX && $fruitY === $playerY) {
        $this->addFruitToPlayer($playerId, $fruitId);
        $this->removeFruit($fruitId);
        return true;
      }
    }

    return false;
  }

  public function getState(): array
  {
    $players = \array_map(function ($player) {
      return [
        'id' => $player['id'],
        'name' => $player['name'],
        'x' => $player['x'],
        'y' => $player['y'],
      ];
    }, $this->state['players']);

    return [
      'players' => $players,
      'fruits' => $this->state['fruits'],
    ];
  }

  public function getTopPlayers(int $topPlayersCount = 10)
  {
    $players = \array_map(function ($player) {
      return [
        'id' => $player['id'],
        'name' => $player['name'],
        'x' => $player['x'],
        'y' => $player['y'],
        'countFruits' => count($player['fruits'])
      ];
    }, $this->state['players']);

    \uasort($players, function ($playerA, $playerB) {
      return $playerB['countFruits'] - $playerA['countFruits'];
    });

    $topPlayers = \array_slice($players, 0, $topPlayersCount);

    return $topPlayers;
  }
}

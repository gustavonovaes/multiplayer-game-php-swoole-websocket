const audio = new Audio('coin.mp3');
audio.volume = .75;

const sidebar = document.querySelector('.sidebar');
const playerName = sidebar.querySelector('input');
const screen = document.querySelector('#screen');
const score = document.querySelector('#score');
const context = screen.getContext('2d');

let gameState = {
  players: [],
  fruits: [],
  topPlayers: [],
};

let currentPlayerId = null;

const socket = new WebSocket('ws://localhost:3000');

socket.onopen = function () {
  console.info('Connected to the server');
};

socket.onerror = function (err) {
  console.error(err);
}

socket.onmessage = function (event) {
  const command = JSON.parse(event.data);

  if (command.type === 'setup') {
    onSetup(command)
  }

  if (command.type === 'update') {
    onUpdate(command)
  }

  if (command.type === 'update-score') {
    updateScore(command.topPlayers);
    updateTotalPlayers(command.totalPlayers);
  }

  if (command.type === 'fruit-colision') {
    playAudio();
  }
}

function onSetup({ playerId, size, name }) {
  screen.width = size.width;
  screen.height = size.height;

  currentPlayerId = playerId;

  playerName.value = name;
  gameLoop();
}

function onUpdate({ state }) {
  updateGameState(state);
}

function updateGameState(newState) {
  gameState = Object.assign(gameState, newState);
}

function gameLoop() {
  requestAnimationFrame(() => {
    renderGame();
    gameLoop();
  });
}

function renderGame() {
  context.fillStyle = '#FFFFFF';
  context.clearRect(0, 0, screen.width, screen.height);

  context.fillStyle = '#333333';
  for (const playerId in gameState.players) {
    const { x: playerX, y: playerY } = gameState.players[playerId];
    context.fillRect(playerX, playerY, 1, 1);
  }

  context.fillStyle = '#4BB543';
  for (const fruitId in gameState.fruits) {
    const { x: fruitX, y: fruitY } = gameState.fruits[fruitId];
    context.fillRect(fruitX, fruitY, 1, 1);
  }

  const { x: currentPlayerX, y: currentPlayerY } = gameState.players[currentPlayerId];
  context.fillStyle = '#FBB933';
  context.fillRect(currentPlayerX, currentPlayerY, 1, 1);
}

function updateScore(topPlayers) {
  const tbodyHtml = topPlayers.map(player => {
    return `<tr class="${player.id === currentPlayerId ? 'player' : ''}">
          <td>${player.name}</td>
          <td>${player.countFruits}</td>
        </tr>`;
  }).join('');

  score.querySelector('tbody').innerHTML = tbodyHtml;
}

function updateTotalPlayers(totalPlayers) {
  score.querySelector('.total-players').textContent = totalPlayers
}

function createKeyboardListener(document, keys = []) {
  let observers = [];

  function attach(callback) {
    observers.push(callback);
  }

  function notify(data) {
    observers.map(callback => callback(data));
  }

  document.addEventListener('keydown', function (event) {
    if (!keys.includes(event.key)) {
      return;
    }

    notify(event.key)
  });

  return {
    attach
  }
}

const mapKeyDirection = {
  'w': 'up',
  'd': 'right',
  's': 'down',
  'a': 'left',
  'ArrowUp': 'up',
  'ArrowRight': 'right',
  'ArrowDown': 'down',
  'ArrowLeft': 'left',
}

const keyboardListener = createKeyboardListener(document, Object.keys(mapKeyDirection));

keyboardListener.attach(key => {
  socket.send(JSON.stringify({
    type: 'player-move',
    direction: mapKeyDirection[key]
  }));
});

function playAudio() {
  audio.currentTime = 0;
  audio.play()
}

function updateName() {
  if (!playerName.value.length) {
    return;
  }

  sidebar.querySelector('tr.player td').textContent = playerName.value;

  socket.send(JSON.stringify({
    type: 'update-player-name',
    name: playerName.value
  }));
}
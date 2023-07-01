class Player {
    public $name;
    public $rank;

    public function __construct($name, $rank) {
        $this->name = $name;
        $this->rank = $rank;
    }
}

class Game {
    public $player1;
    public $player2;
    public $scoreConfirmed;

    public function __construct($player1, $player2) {
        $this->player1 = $player1;
        $this->player2 = $player2;
        $this->scoreConfirmed = false;
    }

    public function confirmScore($player1Score, $player2Score) {
        // Update player ranks based on the game result
        if ($this->scoreConfirmed) {
            return; // Score already confirmed
        }

        $this->scoreConfirmed = true;

        if ($player1Score > $player2Score) {
            // Player 1 wins
            $this->player1->rank = $this->player2->rank;
        } elseif ($player1Score < $player2Score) {
            // Player 2 wins
            $this->player2->rank = $this->player1->rank;
        }
    }
}

class Ladder {
    public $players;
    public $games;

    public function __construct() {
        $this->players = [];
        $this->games = [];
    }

    public function addPlayer($playerName) {
        $rank = count($this->players) + 1;
        $player = new Player($playerName, $rank);
        $this->players[] = $player;
    }

    public function challengePlayer($challengerName, $opponentName) {
        $challenger = $this->getPlayerByName($challengerName);
        $opponent = $this->getPlayerByName($opponentName);

        if ($challenger && $opponent) {
            $game = new Game($challenger, $opponent);
            $this->games[] = $game;
        } else {
            throw new Exception("Player not found.");
        }
    }

    public function acceptChallenge($opponentName, $challengerName) {
        $opponent = $this->getPlayerByName($opponentName);
        $challenger = $this->getPlayerByName($challengerName);

        if ($opponent && $challenger) {
            $game = $this->findPendingGame($opponent, $challenger);

            if ($game) {
                $this->startGame($game);
            } else {
                throw new Exception("Pending game not found.");
            }
        } else {
            throw new Exception("Player not found.");
        }
    }

    public function reportGameScore($player1Name, $player2Name, $player1Score, $player2Score) {
        $player1 = $this->getPlayerByName($player1Name);
        $player2 = $this->getPlayerByName($player2Name);

        if ($player1 && $player2) {
            $game = $this->findGame($player1, $player2);

            if ($game) {
                $game->confirmScore($player1Score, $player2Score);
            } else {
                throw new Exception("Game not found.");
            }
        } else {
            throw new Exception("Player not found.");
        }
    }

    private function getPlayerByName($name) {
        foreach ($this->players as $player) {
            if ($player->name === $name) {
                return $player;
            }

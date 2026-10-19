<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$title = 'Wingman';
include 'Includes/header.inc.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wingman</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        table.header {
            margin: 0 auto;
            width: 10%;
            text-align: center;
            border: 1px solid #11111;
            background: rgb(20, 20, 20);
            border-color: rgb(64, 78, 93);
            word-wrap: break-word;
            max-width: 15px;
        }

        .background-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: #00000000;
            background-image: url(../Images/wingman.png);
            background-size: contain;
            background-position: top;
            background-repeat: no-repeat;
            background-attachment: scroll;
            z-index: -1;
        }
    </style>
</head>
<body class="bg-gray-999">
    <div class="container mx-auto p-4">
        <h1 class="text-4xl font-bold text-center mb-6">Wingman Double
            <br> Elimination Tournament</h1></br>
        <div class="mb-4">
            <div table class="container mx-auto p-4">
            <h1 class="text-xl font-semibold">Enter Team Names</h1>
            <div id="teamInputs" class="grid grid-cols-2 gap-4"></div>
            <button onclick="initializeBracket()" class="mt-2 bg-orange-700 text-white px-4 py-2 rounded hover:bg-black-999">StartBracket</button></div>
        <div id="winnersBracket" class="grid grid-cols-4 gap-4">
            <!-- Winners bracket rounds will be generated here -->
        </div>
        <div id="losersBracket" class="grid grid-cols-4 gap-4">
            <!-- Losers bracket rounds will be generated here -->
        </div>
        <div id="finals" class="mt-8 text-center">
            <h2 class="text-xl font-semibold">Grand Finals</h2>
            <div id="grandFinal"></div>
            <div id="grandFinalReset" class="mt-4"></div>
        </div>
        <div id="statistics" class="mt-3 text-center">
            <h2 class="text-xl font-semibold">Team Statistics</h2>
            <table class="header">
                <thead>
                    <tr>
                        <th>Wingman</th>
                        <th>Wins</th>
                        <th>Losses</th>
                        <th>Rounds</th>
                        <th>Ratio</th>
                    </tr>
                </thead>
                <tbody id="statsBody"></tbody>
            </table>
        </div>
    </div>

    <script>
        const teams = Array(10).fill().map((_, i) => ({ name: `Team ${i + 1}`, seed: i + 1 }));
        let matches = {
            winners: [],
            losers: [],
            grandFinal: null,
            grandFinalReset: null
        };
        let stats = teams.reduce((acc, team) => ({
            ...acc,
            [team.name]: { wins: 0, losses: 0, points: 0 }
        }), {});

        function initializeTeamInputs() {
            const teamInputs = document.getElementById('teamInputs');
            teamInputs.innerHTML = '';
            teams.forEach((team, index) => {
                const inputDiv = document.createElement('div');
                inputDiv.innerHTML = `
                    <label class="block text-sm font-medium">Team ${index + 1}</label>
                    <input type="text" value="${team.name}" class="w-full p-2 border rounded" onchange="updateTeamName(${index}, this.value)">
                `;
                teamInputs.appendChild(inputDiv);
            });
        }

        function updateTeamName(index, name) {
            const oldName = teams[index].name;
            teams[index].name = name || `Team ${index + 1}`;
            if (oldName !== teams[index].name) {
                stats[teams[index].name] = stats[oldName] || { wins: 0, losses: 0, points: 0 };
                delete stats[oldName];
            }
            renderBracket();
            renderStats();
        }

        function initializeBracket() {
            matches.winners = [
                [
                    { id: 'W1-1', teams: [teams[0], teams[9]], scores: [0, 0], winner: null, loser: null },
                    { id: 'W1-2', teams: [teams[1], teams[8]], scores: [0, 0], winner: null, loser: null },
                    { id: 'W1-3', teams: [teams[2], teams[7]], scores: [0, 0], winner: null, loser: null },
                    { id: 'W1-4', teams: [teams[3], teams[6]], scores: [0, 0], winner: null, loser: null },
                    { id: 'W1-5', teams: [teams[4], null], scores: [0, 0], winner: teams[4], loser: null },
                    { id: 'W1-6', teams: [teams[5], null], scores: [0, 0], winner: teams[5], loser: null }
                ],
                [
                    { id: 'W2-1', teams: [null, null], scores: [0, 0], winner: null, loser: null },
                    { id: 'W2-2', teams: [null, null], scores: [0, 0], winner: null, loser: null },
                    { id: 'W2-3', teams: [teams[4], teams[5]], scores: [0, 0], winner: null, loser: null }
                ],
                [
                    { id: 'W3-1', teams: [null, null], scores: [0, 0], winner: null, loser: null },
                    { id: 'W3-2', teams: [null, null], scores: [0, 0], winner: null, loser: null }
                ],
                [
                    { id: 'W4-1', teams: [null, null], scores: [0, 0], winner: null, loser: null }
                ]
            ];

            matches.losers = [
                [
                    { id: 'L1-1', teams: [null, null], scores: [0, 0], winner: null, loser: null },
                    { id: 'L1-2', teams: [null, null], scores: [0, 0], winner: null, loser: null }
                ],
                [
                    { id: 'L2-1', teams: [null, null], scores: [0, 0], winner: null, loser: null },
                    { id: 'L2-2', teams: [null, null], scores: [0, 0], winner: null, loser: null }
                ],
                [
                    { id: 'L3-1', teams: [null, null], scores: [0, 0], winner: null, loser: null },
                    { id: 'L3-2', teams: [null, null], scores: [0, 0], winner: null, loser: null }
                ],
                [
                    { id: 'L4-1', teams: [null, null], scores: [0, 0], winner: null, loser: null }
                ]
            ];

            matches.grandFinal = { id: 'GF', teams: [null, null], scores: [0, 0], winner: null, loser: null };
            matches.grandFinalReset = { id: 'GFR', teams: [null, null], scores: [0, 0], winner: null, loser: null };

            renderBracket();
            renderStats();
        }

        function updateMatch(round, matchIndex, teamIndex, score, bracketType) {
            const match = bracketType === 'winners' || bracketType === 'losers' 
                ? matches[bracketType][round][matchIndex]
                : matches[bracketType];
            score = parseInt(score) || 0;
            if (score < 0) score = 0;
            match.scores[teamIndex] = score;

            const [score1, score2] = match.scores;
            if ((score1 === 9 && score2 < 9) || (score2 === 9 && score1 < 9)) {
                if (score1 === 9) {
                    match.winner = match.teams[0];
                    match.loser = match.teams[1];
                } else if (score2 === 9) {
                    match.winner = match.teams[1];
                    match.loser = match.teams[0];
                }
                if (match.winner && match.loser) {
                    stats[match.winner.name].wins += 1;
                    stats[match.winner.name].points += match.scores[match.teams[0] === match.winner ? 0 : 1];
                    stats[match.loser.name].losses += 1;
                    stats[match.loser.name].points += match.scores[match.teams[0] === match.loser ? 0 : 1];
                    saveMatchToDatabase(match);
                }
            } else {
                match.winner = null;
                match.loser = null;
            }

            advanceTeams();
            renderBracket();
            renderStats();
        }

        function saveMatchToDatabase(match) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'save_match.php', true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.send(JSON.stringify({
                match_id: match.id,
                team1: match.teams[0]?.name || 'TBD',
                team2: match.teams[1]?.name || 'TBD',
                score1: match.scores[0],
                score2: match.scores[1],
                winner: match.winner?.name || null,
                loser: match.loser?.name || null
            }));
        }

        function advanceTeams() {
            if (matches.winners[0][0].winner) matches.winners[1][0].teams[0] = matches.winners[0][0].winner;
            if (matches.winners[0][3].winner) matches.winners[1][0].teams[1] = matches.winners[0][3].winner;
            if (matches.winners[0][1].winner) matches.winners[1][1].teams[0] = matches.winners[0][1].winner;
            if (matches.winners[0][2].winner) matches.winners[1][1].teams[1] = matches.winners[0][2].winner;
            if (matches.winners[1][0].winner) matches.winners[2][0].teams[0] = matches.winners[1][0].winner;
            if (matches.winners[1][1].winner) matches.winners[2][0].teams[1] = matches.winners[1][1].winner;
            if (matches.winners[1][2].winner) matches.winners[2][1].teams[0] = matches.winners[1][2].winner;
            if (matches.winners[2][0].winner) matches.winners[3][0].teams[0] = matches.winners[2][0].winner;
            if (matches.winners[2][1].winner) matches.winners[3][0].teams[1] = matches.winners[2][1].winner;

            if (matches.winners[0][0].loser && matches.winners[0][1].loser) matches.losers[0][0].teams = [matches.winners[0][0].loser, matches.winners[0][1].loser];
            if (matches.winners[0][2].loser && matches.winners[0][3].loser) matches.losers[0][1].teams = [matches.winners[0][2].loser, matches.winners[0][3].loser];
            if (matches.losers[0][0].winner && matches.winners[1][2].loser) matches.losers[1][0].teams = [matches.losers[0][0].winner, matches.winners[1][2].loser];
            if (matches.losers[0][1].winner && matches.winners[1][0].loser) matches.losers[1][1].teams = [matches.losers[0][1].winner, matches.winners[1][0].loser];
            if (matches.losers[1][0].winner && matches.losers[1][1].winner) matches.losers[2][0].teams = [matches.losers[1][0].winner, matches.losers[1][1].winner];
            if (matches.winners[2][0].loser) matches.losers[2][1].teams[0] = matches.winners[2][0].loser;
            if (matches.losers[2][0].winner) matches.losers[3][0].teams[0] = matches.losers[2][0].winner;
            if (matches.losers[2][1].winner) matches.losers[3][0].teams[1] = matches.losers[2][1].winner;

            if (matches.winners[3][0].winner && matches.losers[3][0].winner) {
                matches.grandFinal.teams = [matches.winners[3][0].winner, matches.losers[3][0].winner];
            }
            if (matches.grandFinal.winner === matches.losers[3][0].winner) {
                matches.grandFinalReset.teams = [matches.winners[3][0].winner, matches.losers[3][0].winner];
            } else {
                matches.grandFinalReset.teams = [null, null];
                matches.grandFinalReset.winner = null;
            }
        }

        function renderBracket() {
            const winnersBracket = document.getElementById('winnersBracket');
            const losersBracket = document.getElementById('losersBracket');
            const grandFinal = document.getElementById('grandFinal');
            const grandFinalReset = document.getElementById('grandFinalReset');

            winnersBracket.innerHTML = '';
            losersBracket.innerHTML = '';
            grandFinal.innerHTML = '';
            grandFinalReset.innerHTML = '';

            matches.winners.forEach((round, roundIndex) => {
                const roundDiv = document.createElement('div');
                roundDiv.className = 'round';
                roundDiv.innerHTML = `<h3 class="text-lg font-semibold">Winners Round ${roundIndex + 1}</h3>`;
                round.forEach((match, matchIndex) => {
                    const matchDiv = document.createElement('div');
                    matchDiv.className = 'match';
                    matchDiv.innerHTML = `
                        <div class="team">
                            ${match.teams[0]?.name || 'TBD'} 
                            ${match.teams[0] && !match.winner ? `<input type="number" min="0" max="9" value="${match.scores[0]}" onchange="updateMatch(${roundIndex}, ${matchIndex}, 0, this.value, 'winners')">` : match.scores[0]}
                        </div>
                        <div class="team">
                            ${match.teams[1]?.name || 'TBD'} 
                            ${match.teams[1] && !match.winner ? `<input type="number" min="0" max="9" value="${match.scores[1]}" onchange="updateMatch(${roundIndex}, ${matchIndex}, 1, this.value, 'winners')">` : match.scores[1]}
                        </div>
                    `;
                    roundDiv.appendChild(matchDiv);
                });
                winnersBracket.appendChild(roundDiv);
            });

            matches.losers.forEach((round, roundIndex) => {
                const roundDiv = document.createElement('div');
                roundDiv.className = 'round';
                roundDiv.innerHTML = `<h3 class="text-lg font-semibold">Losers Round ${roundIndex + 1}</h3>`;
                round.forEach((match, matchIndex) => {
                    const matchDiv = document.createElement('div');
                    matchDiv.className = 'match';
                    matchDiv.innerHTML = `
                        <div class="team">
                            ${match.teams[0]?.name || 'TBD'} 
                            ${match.teams[0] && !match.winner ? `<input type="number" min="0" max="9" value="${match.scores[0]}" onchange="updateMatch(${roundIndex}, ${matchIndex}, 0, this.value, 'losers')">` : match.scores[0]}
                        </div>
                        <div class="team">
                            ${match.teams[1]?.name || 'TBD'} 
                            ${match.teams[1] && !match.winner ? `<input type="number" min="0" max="9" value="${match.scores[1]}" onchange="updateMatch(${roundIndex}, ${matchIndex}, 1, this.value, 'losers')">` : match.scores[1]}
                        </div>
                    `;
                    roundDiv.appendChild(matchDiv);
                });
                losersBracket.appendChild(roundDiv);
            });

            const gfDiv = document.createElement('div');
            gfDiv.className = 'match';
            gfDiv.innerHTML = `
                <div class="team">
                    ${matches.grandFinal.teams[0]?.name || 'TBD'} 
                    ${matches.grandFinal.teams[0] && !matches.grandFinal.winner ? `<input type="number" min="0" max="9" value="${matches.grandFinal.scores[0]}" onchange="updateMatch(0, 0, 0, this.value, 'grandFinal')">` : matches.grandFinal.scores[0]}
                </div>
                <div class="team">
                    ${matches.grandFinal.teams[1]?.name || 'TBD'} 
                    ${matches.grandFinal.teams[1] && !matches.grandFinal.winner ? `<input type="number" min="0" max="9" value="${matches.grandFinal.scores[1]}" onchange="updateMatch(0, 0, 1, this.value, 'grandFinal')">` : matches.grandFinal.scores[1]}
                </div>
            `;
            grandFinal.appendChild(gfDiv);

            if (matches.grandFinalReset.teams[0]) {
                const gfrDiv = document.createElement('div');
                gfrDiv.className = 'match';
                gfrDiv.innerHTML = `
                    <h3 class="text-lg font-semibold">Grand Final Reset</h3>
                    <div class="team">
                        ${matches.grandFinalReset.teams[0]?.name || 'TBD'} 
                        ${matches.grandFinalReset.teams[0] && !matches.grandFinalReset.winner ? `<input type="number" min="0" max="9" value="${matches.grandFinalReset.scores[0]}" onchange="updateMatch(0, 0, 0, this.value, 'grandFinalReset')">` : matches.grandFinalReset.scores[0]}
                    </div>
                    <div class="team">
                        ${matches.grandFinalReset.teams[1]?.name || 'TBD'} 
                        ${matches.grandFinalReset.teams[1] && !matches.grandFinalReset.winner ? `<input type="number" min="0" max="9" value="${matches.grandFinalReset.scores[1]}" onchange="updateMatch(0, 0, 1, this.value, 'grandFinalReset')">` : matches.grandFinalReset.scores[1]}
                    </div>
                `;
                grandFinalReset.appendChild(gfrDiv);
            }
        }

   function renderStats() {
            const statsBody = document.getElementById('statsBody');
            statsBody.innerHTML = '';
            Object.entries(stats).forEach(([teamName, teamStats]) => {
                const ratio = teamStats.losses === 0 ? '' : (teamStats.wins / teamStats.losses).toFixed(2);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${teamName}</td>
                    <td>${teamStats.wins}</td>
                    <td>${teamStats.losses}</td>
                    <td>${teamStats.points}</td>
                    <td>${ratio}</td>
                `;
                statsBody.appendChild(row);
            });
        }

        initializeTeamInputs();
    </script>
</body>
</html>

<?php
include 'Includes/footer.inc.php';
?>

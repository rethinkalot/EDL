<?php
session_start();
require 'Includes/dbh.inc.php';
require 'Includes/functions.inc.php';

$title = 'WingmanBracket';
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
        h2{
            top: -100 auto;
        }
        
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
            height: 160%;
            background-color: #00000000;
            background-image: url(../Images/wingman.png);
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            z-index: -1;
        }
    </style>
</head>
<body class="bg-gray-999">
    <div class="container mx-auto p-4">
        <h1 class="text-4xl font-bold text-center mb-6">Wingman Round-Robin Tournament</h1>
        <div class="mb-4">
            <div class="container mx-auto p-4">
                <h1 class="text-xl font-semibold">Enter Team Names</h1>
                <div id="teamInputs" class="grid grid-cols-2 gap-4"></div>
                <button onclick="initializeBracket()" class="mt-2 bg-orange-700 text-white px-4 py-2 rounded hover:bg-black-999">StartBracket</button>
            </div>
        </div>
        <div id="roundRobinBracket" class="grid grid-cols-3 gap-4">
            <!-- Round-robin matches will be generated here -->
        </div>
        <div id="finals" class="mt-8 text-center">
            <h2 class="text-xl font-semibold">Final Match</h2>
            <div id="finalMatch"></div>
            <div id="finalWinner" class="mt-4 text-2xl"></div>
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
        const teams = Array(6).fill().map((_, i) => ({ name: `Team ${i + 1}`, seed: i + 1 }));
        let matches = {
            roundRobin: [],
            finalMatch: null
        };
        let stats = teams.reduce((acc, team) => ({
            ...acc,
            [team.name]: { wins: 0, losses: 0, points: 0, roundsLost: 0 }
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
                stats[teams[index].name] = stats[oldName] || { wins: 0, losses: 0, points: 0, roundsLost: 0 };
                delete stats[oldName];
            }
            renderBracket();
            renderStats();
        }

        function initializeBracket() {
            matches.roundRobin = [
                [
                    { id: 'RR-1', teams: [teams[0], teams[1]], scores: [0, 0], winner: null, loser: null },
                    { id: 'RR-2', teams: [teams[2], teams[3]], scores: [0, 0], winner: null, loser: null },
                    { id: 'RR-3', teams: [teams[4], teams[5]], scores: [0, 0], winner: null, loser: null }
                ],
                [
                    { id: 'RR-4', teams: [teams[0], teams[2]], scores: [0, 0], winner: null, loser: null },
                    { id: 'RR-5', teams: [teams[1], teams[4]], scores: [0, 0], winner: null, loser: null },
                    { id: 'RR-6', teams: [teams[3], teams[5]], scores: [0, 0], winner: null, loser: null }
                ],
                [
                    { id: 'RR-7', teams: [teams[0], teams[3]], scores: [0, 0], winner: null, loser: null },
                    { id: 'RR-8', teams: [teams[1], teams[5]], scores: [0, 0], winner: null, loser: null },
                    { id: 'RR-9', teams: [teams[2], teams[4]], scores: [0, 0], winner: null, loser: null }
                ],
                [
                    { id: 'RR-10', teams: [teams[0], teams[4]], scores: [0, 0], winner: null, loser: null },
                    { id: 'RR-11', teams: [teams[1], teams[3]], scores: [0, 0], winner: null, loser: null },
                    { id: 'RR-12', teams: [teams[2], teams[5]], scores: [0, 0], winner: null, loser: null }
                ],
                [
                    { id: 'RR-13', teams: [teams[0], teams[5]], scores: [0, 0], winner: null, loser: null },
                    { id: 'RR-14', teams: [teams[1], teams[2]], scores: [0, 0], winner: null, loser: null },
                    { id: 'RR-15', teams: [teams[3], teams[4]], scores: [0, 0], winner: null, loser: null }
                ]
            ];

            matches.finalMatch = { id: 'FM', teams: [null, null], scores: [0, 0], winner: null, loser: null };

            renderBracket();
            renderStats();
        }

        function updateMatch(round, matchIndex, teamIndex, score, bracketType) {
            const match = bracketType === 'roundRobin' ? matches.roundRobin[round][matchIndex] : matches[bracketType];
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
                    stats[match.loser.name].roundsLost += match.scores[match.teams[0] === match.loser ? 1 : 0];
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
            const allMatchesComplete = matches.roundRobin.every(round => 
                round.every(match => match.winner || !match.teams[1])
            );

            if (allMatchesComplete) {
                const sortedTeams = Object.entries(stats)
                    .map(([name, teamStats]) => ({
                        name,
                        wins: teamStats.wins,
                        points: teamStats.points
                    }))
                    .sort((a, b) => b.wins - a.wins || b.points - a.points);

                if (sortedTeams.length >= 2) {
                    matches.finalMatch.teams = [
                        teams.find(team => team.name === sortedTeams[0].name),
                        teams.find(team => team.name === sortedTeams[1].name)
                    ];
                }
            }
        }

        function renderBracket() {
            const roundRobinBracket = document.getElementById('roundRobinBracket');
            const finalMatch = document.getElementById('finalMatch');
            const finalWinner = document.getElementById('finalWinner');

            roundRobinBracket.innerHTML = '';
            finalMatch.innerHTML = '';
            finalWinner.innerHTML = '';

            matches.roundRobin.forEach((round, roundIndex) => {
                const roundDiv = document.createElement('div');
                roundDiv.className = 'round';
                roundDiv.innerHTML = `<h3 class="text-lg font-semibold">Round ${roundIndex + 1}</h3>`;
                round.forEach((match, matchIndex) => {
                    const matchDiv = document.createElement('div');
                    matchDiv.className = 'match';
                    matchDiv.innerHTML = `
                        <div class="team">
                            ${match.teams[0]?.name || 'TBD'} 
                            ${match.teams[0] && !match.winner ? `<input type="number" min="0" max="9" value="${match.scores[0]}" onchange="updateMatch(${roundIndex}, ${matchIndex}, 0, this.value, 'roundRobin')">` : match.scores[0]}
                        </div>
                        <div class="team">
                            ${match.teams[1]?.name || 'TBD'} 
                            ${match.teams[1] && !match.winner ? `<input type="number" min="0" max="9" value="${match.scores[1]}" onchange="updateMatch(${roundIndex}, ${matchIndex}, 1, this.value, 'roundRobin')">` : match.scores[1]}
                        </div>
                    `;
                    roundDiv.appendChild(matchDiv);
                });
                roundRobinBracket.appendChild(roundDiv);
            });

            if (matches.finalMatch.teams[0] && matches.finalMatch.teams[1]) {
                const fmDiv = document.createElement('div');
                fmDiv.className = 'match';
                fmDiv.innerHTML = `
                    <div class="team">
                        ${matches.finalMatch.teams[0]?.name || 'TBD'} 
                        ${matches.finalMatch.teams[0] && !matches.finalMatch.winner ? `<input type="number" min="0" max="9" value="${matches.finalMatch.scores[0]}" onchange="updateMatch(0, 0, 0, this.value, 'finalMatch')">` : matches.finalMatch.scores[0]}
                    </div>
                    <div class="team">
                        ${matches.finalMatch.teams[1]?.name || 'TBD'} 
                        ${matches.finalMatch.teams[1] && !matches.finalMatch.winner ? `<input type="number" min="0" max="9" value="${matches.finalMatch.scores[1]}" onchange="updateMatch(0, 0, 1, this.value, 'finalMatch')">` : matches.finalMatch.scores[1]}
                    </div>
                `;
                finalMatch.appendChild(fmDiv);

                if (matches.finalMatch.winner) {
                    finalWinner.innerHTML = `<span class="font-bold">${matches.finalMatch.winner.name} WINS</span> <span class="font-bold">Score: ${matches.finalMatch.scores[0]} - ${matches.finalMatch.scores[1]}</span>`;
                }
            }
        }

        function renderStats() {
            const statsBody = document.getElementById('statsBody');
            statsBody.innerHTML = '';
            Object.entries(stats).forEach(([teamName, teamStats]) => {
                const ratio = teamStats.roundsLost === 0 ? '' : (teamStats.points / teamStats.roundsLost).toFixed(2);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${teamName}</td>
                    <td class="font-bold">${teamStats.wins}</td>
                    <td class="font-bold">${teamStats.losses}</td>
                    <td class="font-bold">${teamStats.points}</td>
                    <td class="font-bold">${ratio}</td>
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
CREATE TABLE challenges (
    id INT NOT NULL AUTO_INCREMENT,
    challenger VARCHAR(255),
    challenged VARCHAR(255),
    type VARCHAR(255),
    status VARCHAR(255),
    reporter VARCHAR(255),
    gameplayed VARCHAR(255),
    mission VARCHAR(255),
    mission1 VARCHAR(255), -- Challenger�s home for Home vs Home
    mission2 VARCHAR(255), -- Challenged�s home for Home vs Home
    winner VARCHAR(255),
    winnerKills INT,
    winnerDeaths INT,
    winner1 VARCHAR(255), -- Game 1 winner for Home vs Home
    winner1Kills INT,
    winner1Deaths INT,
    winner2 VARCHAR(255), -- Game 2 winner for Home vs Home
    winner2Kills INT,
    winner2Deaths INT,
    loser VARCHAR(255),
    loserKills INT,
    loserDeaths INT,
    loser1 VARCHAR(255), -- Game 1 loser for Home vs Home
    loser1Kills INT,
    loser1Deaths INT,
    loser2 VARCHAR(255), -- Game 2 loser for Home vs Home
    loser2Kills INT,
    loser2Deaths INT,
    combinedSuicides INT, -- Added for total suicides across both players
    winnerComment TEXT,
    loserComment TEXT,
    gamesCombined TEXT,
    score VARCHAR(255),
    PRIMARY KEY (id)
);
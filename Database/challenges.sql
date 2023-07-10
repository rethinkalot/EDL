create table challenges (
    id int not null auto_increment,
    challenger varchar(255),
    challenged varchar(255),
    type varchar(255),
    status varchar(255),
    reporter varchar(255),
    gameplayed varchar(255),
    mission varchar(255),
    winnerKills int(11),
    loserKills int (11),
    winnerDeaths int (11),
    winnerSuicides int(11),
    loserDeaths int(11),
    loserSuicides int(11),
    primary key (id)
);

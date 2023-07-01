create table games (
    id int not null auto_increment,
    time datetime default now(),
    mission varchar(255),
    winner varchar(255),
    loser varchar(255),
    winnerKills int,
    loserKills int,
    forfeit int,
    primary key (id)
);

create table challenges (
    id int not null auto_increment,
    challenger varchar(255),
    challenged varchar(255),
    type varchar(255),
    status varchar(255),
    reporter varchar(255),
    mission varchar(255),
    winnerKills int,
    loserKills int,
    forfeit int,
    primary key (id)
);

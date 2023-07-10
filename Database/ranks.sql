create table ranks (
    player varchar(255),
    rank int default 0,
    wins int default 0,
    losses int default 0,
    kills int default 0,
    deaths int default 0,
    streaks int default 0,
    suicides int default 0,
    primary key (id)
);

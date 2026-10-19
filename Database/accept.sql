create table accept (
id int not null auto_increment,
challenger varchar(255),
challenged varchar(255),
gameplayed varchar(255),
traditional int,
randomhome int,
homevshome int,
status varchar(255),
primary key (id)
);
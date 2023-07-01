create table users ( 
    id int not null auto_increment,
    userName varchar(255),
    usersEmail varchar(255),
    usersUid varchar(255),
    usersPwd varchar(255),
    pwdRepeat varchar(255),
    primary key (id),
);

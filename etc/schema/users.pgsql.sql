create table icinga_users (
  user_name varchar(255) NOT NULL,
  first_name varchar(255),
  last_name varchar(255),
  email varchar(255),
  domain varchar(255),
  last_login timestamp,
  salt varchar(255),
  password varchar(255) NOT NULL,
  active BOOL,
  PRIMARY KEY (user_name)
);
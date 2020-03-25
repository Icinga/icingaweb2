# Mysql Examples
##### Go to the mysql container
```
docker exec -it mysql mysql -ppassword
```
##### Firstly create a user names ``dashboard`` and database ``dashboard``
```mysql
CREATE USER dashboard;
CREATE DATABASE dashboard;
```
##### Give to the ``dashboard`` user all privileges
```mysql
GRANT ALL PRIVILEGES ON dashboard.* TO 'dashboard'@'%' IDENTIFIED BY 'dashboard';
```

#### Now restart mysql.service and it should work

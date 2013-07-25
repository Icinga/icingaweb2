# Authentication via internal DB

The class DbUserBackend allows 

## Configuration

The internal authentication is configured in *config/authentication.ini*. The value
of the configuration key "backend" will determine which UserBackend class to
load. To use the internal backend you will need to specifiy the value "Db"
which will cause the class "DbUserBackend" to be loaded.

There are various configuration keys in "Authentication.ini" and some are only
used by specific backends. The internal DB uses the values
*dbtype*,*table*,*host*,*password*,*user* and *db*, which define the used
connection parameters, the database and the table.

## Database support

The module currently supports these databases:

 - mysql        (dbtype=mysql)
 - PostgreSQL   (dbtype=pgsql)


## Authentication

The backend will store the salted hash of the password in the column "password" and the salt in the column "salt".
When a password is checked, the hash is calculated with the function hash_hmac("sha256",salt,password) and compared
to the stored value.
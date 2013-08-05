# Preferences

Preferences are user based configuration for Icinga 2 Web. For example max page
items, languages or date time settings can controlled by users.

# Architecture

Preferences are initially loaded from a provider (ini files or database) and
stored into session at login time. After this step preferences are only
persisted to the configured backend, but never reloaded from them.

# Configuration

Preferences can be configured in config.ini in **preferences** section, default
settings are this:

    [preferences]
    type=ini

The ini provider uses the directory **config/preferences** to create one ini
file per user and persists the data into a single file. If your want to drop your
preferences just drop the file from disk and you'll start with a new profile.

## Database provider

To be more flexible in distributed setups you can store preferences in a
database (pgsql or mysql), a typical configuration looks like the following
example:

    [preferences]
    type=db
    dbtype=pgsql
    dbhost=127.0.0.1
    dbpassword=icingaweb
    dbuser=icingaweb
    dbname=icingaweb

### Settings

* **dbtype**: Database adapter, currently supporting ***mysql*** or ***pgsql***

* **dbhost**: Host of the database server, use localhost or 127.0.0.1
for unix socket transport

* **dbpassword**: Password for the configured database user

* **dbuser**: User who can connect to database

* **dbname**: Name of the database

* **port**(optional): For network connections the specific port if not default
(3306 for mysql and 5432 for postgres)

### Preparation

To use this feature you need a running database environment. After creating a
database and a writable user you need to import the initial table file:

* etc/schema/preferences.mysql.sql (for mysql database)
* etc/schema/preferemces.pgsql.sql (for postgres databases)

#### Example for mysql

    # mysql -u root -p
    mysql> create database icingaweb;
    mysql> GRANT SELECT,INSERT,UPDATE,DELETE ON icingaweb.* TO \
        'icingaweb'@'localhost' IDENTIFIED BY 'icingaweb';
    mysql> exit
    # mysql -u root -p icingaweb < /path/to/icingaweb/etc/schema/preferences.mysql.sql

After following this steps above you can configure your preferences provider.

## Coding API

Preferenecs are controlled by the Preferences object which was injected into the
User object on application start (You do not have to think about). For example
if you have gathered the user object:

    $preferences = $user->getPreferences();
    // Get language with en_US as fallback
    $preferences->get('app.language', 'en_US');
    $preferences->set('app.language', 'de_DE');
    $preferences->remove('app.language');

    // Using transactional mode
    $preferences->startTransaction();
    $preferences->set('test.pref1', 'pref1');
    $preferences->set('test.pref2', 'pref2');
    $preferences->remove('test.pref3');
    $preferemces->commit(); // Stores 3 changes in one operation

More information can be found in the api docs.

## Namespaces and behaviour

If you are using this API please obey the following rules:

* Use dotted notation for preferences
* Namespaces starting with one context identifier
    * **app** as global identified (e.g. app.language)
    * **mymodule** for your module
    * **monitoring** for the monitoring module
* Use preferences wisely (set only when needed and write small settings)
* Use only simple data types, e.g. strings or numbers
    * If you need complex types you have to do it your self (e.g. serialization)

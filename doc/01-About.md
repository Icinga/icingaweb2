# <a id="about"></a> About Icinga Web 2

Icinga Web 2 is a powerful PHP framework for web applications that comes in a clean and reduced design.
It's fast, responsive, accessible and easily extensible with modules.

## <a id="about-monitoring"></a> The monitoring module

This is the core module for most Icinga Web 2 users.

It provides an intuitive user interface for monitoring with Icinga (1 and 2).
Especially there are lots of list and detail views (e.g. for hosts and services)
you can sort and filter depending on what you want to see.

You can also control the monitoring process itself by sending external commands to Icinga.
Most such actions (like rescheduling a check) can be done with just a single click.

## <a id="about-installation"></a> Installation

Icinga Web 2 can be installed easily from packages from the official package repositories.
Setting it up is also easy with the web based setup wizard.

See [here](02-Installation.md#installation) for more information about the installation.

## <a id="about-configuration"></a> Configuration

Icinga Web 2 can be configured via the user interface and .ini files.

See [here](03-Configuration.md#configuration) for more information about the configuration.

## <a id="about-authentication"></a> Authentication

With Icinga Web 2 you can authenticate against relational databases, LDAP and more.
These authentication methods can be easily configured (via the corresponding .ini file).

See [here](05-Authentication.md#authentication) for more information about
the different authentication methods available and how to configure them.

## <a id="about-authorization"></a> Authorization

In Icinga Web 2 there are permissions and restrictions to allow and deny (respectively)
roles to view or to do certain things.
These roles can be assigned to users and groups.

See [here](06-Security.md#security) for more information about authorization
and how to configure roles.

## <a id="about-preferences"></a> User preferences

Besides the global configuration each user has individual configuration options
like the interface's language or the current timezone.
They can be stored either in a database or in .ini files.

See [here](07-Preferences.md#preferences) for more information about a user's preferences
and how to configure their storage type.

## <a id="about-documentation"></a> Documentation

With the documentation module you can read the documentation of the framework (and any module) directly in the user interface.

The module can also export the documentation to PDF.

## <a id="about-translation"></a> Translation

With the translation module every piece of text in the user interface (of the framework itself and any module) can be translated to a language of your choice.

Currently provided languages:

* German
* Italian
* Portuguese

# Application and Module Configuration

The \Icinga\Application\Config class is a general purpose service to help you find, load and save
configuration data. It is used both by the Icinga 2 Web modules and the framework itself. With
INI files as source it enables you to store configuration in a familiar format. Icinga 2 Web
defines some configuration files for its own purposes. Please note that both modules and framework
keep their main configuration in the INI file called config.ini. Here's some example code:

    <?php
    use \Icinga\Application\Config as IcingaConfig;

    // Retrieve the default timezone using 'Europe/Berlin' in case it is not set
    IcingaConfig::app()->global->get('defaultTimezone', 'Europe/Berlin');

    // If you don't pass a configuration name to IcingaConfig::app it tries to load values from the
    // application's config.ini. For using other files you have to pass this parameter though.
    // The following example loads a section from the application's authentication.ini:
    IcingaConfig::app('authentication')->get('ldap-authentication');

    // If you don't pass a configuration name to IcingaConfig::module it tries to load values from
    // the module's config.ini. For using other files you have to pass this parameter though.
    // The following example loads values from the example module's extra.ini:
    IcingaConfig::module('example', 'extra')->logging->get('enabled', true);


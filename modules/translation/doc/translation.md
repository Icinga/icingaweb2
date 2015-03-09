# Introduction

Icinga Web 2 provides localization out of the box - for the core application and the modules, that means
that you can with a lightness use existent localizations, update or even create you own localizations.

The chapters [Translation for Developers](Translation for Developers),
[Translation for Translators](Translation for Translators) and [Testing Translations](Testing Translations) will
introduce and explain you, how to take part on localizing Icinga Web 2 for different languages and how to use the
`translation module` to make your life much easier.

# Translation for Developers

To make use of the built-in translations in your applications code or views, you should use the method
`$this->translate('String to be translated')`, let's have a look at an example:

```php
<?php

class ExampleController extends Controller
{
    public function indexAction()
    {
        $this->view->title = $this->translate('Hello World');
    }
}
```

So if there a translation available for the `Hello World` string you will get an translated output, depends on the
language which is setted in your configuration as the default language, if it is `de_DE` the output would be
`Hallo Welt`.

The same works also for views:

```
<h1><?= $this->title ?></h1>
<p>
    <?= $this->translate('Hello World') ?>
    <?= $this->translate('String to be translated') ?>
</p>
```

If you need to provide placeholders in your messages, you should wrap the `$this->translate()` with `sprintf()` for e.g.
    sprintf($this->translate('Hello User: (%s)'), $user->getName())

## Translating plural forms

To provide a plural translation, just use the `translatePlural()` function.

```php
<?php

class ExampleController extends Controller
{
    public function indexAction()
    {
        $this->view->message = $this->translatePlural('Service', 'Services', 3);
    }
}
```

## Context based translation

If you want to provide context based translations, you can easily do it with an extra parameter in both methods
`translate()` and `translatePlural()`.

```php
<?php

class ExampleController extends Controller
{
    public function indexAction()
    {
        $this->view->title = $this->translate('My Titile', 'mycontext');
        $this->view->message = $this->translatePlural('Service', 'Services', 3, 'mycontext');
    }
}
```

# Translation for Translators

Icinga Web 2 internally uses the UNIX standard gettext tool to perform internationalization, this means translation
files in the .po file format are supplied for text strings used in the code.

There are a lot of tools and techniques to work with .po localization files, you can choose what ever you prefer. We
won't let you alone on your first steps and therefore we'll introduce you a nice tool, called Poedit.

### Poedit

First of all, you have to download and install Poedit from http://poedit.net, when you are done, you have to do some
configuration under the Preferences.

#### Configuration

__Personalize__: Please provide your Name and E-Mail under Identity.

![Personalize](/img/translation/doc/poedit_001.png)

__Editor__: Under the Behavior the Automatically compile .mo files on save, should be disabled.

![Editor](/img/translation/doc/poedit_002.png)

__Translations Memory__: Under the Database please add your languages, for which are you writing translations.

![Translations Memory](/img/translation/doc/poedit_003.png)

When you are done, just save your new settings.

#### Editing .po files

To work with Icinga Web 2 .po files, you can open for e.g. the german icinga.po file which is located under
`application/locale/de_DE/LC_MESSAGES/icinga.po`, as shown below, you will get then a full list of all available
translation strings for the core application. Each module names its translation files `%module_name%.po`. For a
module called __yourmodule__ the .po translation file will be named `yourmodule.po`.


![Full list of strings](/img/translation/doc/poedit_004.png)

Now you can make changes and when there is no translation available, Poedit would mark it with a blue color, as shown
below.

![Untranslated strings](/img/translation/doc/poedit_005.png)

And when you want to test your changes, please read more about under the chapter
[Testing Translations](Testing Translations).

# Testing Translations

If you want to try out your translation changes in Icinga Web 2, you can make use of the the CLI translations commands.

** NOTE: Please make sure that the gettext package is installed **

To get an easier development with translations, you can activate the `translation module` which provides CLI commands,
after that you would be able to refresh and compile your .po files.


** NOTE: the ll_CC stands for ll=language and CC=country code for e.g de_DE, fr_FR, ru_RU, it_IT etc. **

## Application

To refresh the __icinga.po__:

    icingacli translation refresh icinga ll_CC

And to compile it:

    icingacli translation compile icinga ll_CC

** NOTE: After a compile you need to restart the web server to get new translations available in your application. **

## Modules

Let's assume, we want to provide german translations for our just new created module __yourmodule__.

If we haven't yet any translations strings in our .po file or even the .po file, we can use the CLI command, to do the
job for us:

    icingacli translation refresh module development ll_CC

This will go through all .php and .phtml files inside the module and a look after `$this->translate()` if there is
something to translate - if there is something and is not available in the __yourmodule.po__ it will updates this file
for us with new
strings.

Now you can open the __yourmodule.po__ and you will see something similar:

    # Icinga Web 2 - Head for multiple monitoring backends.
    # Copyright (C) 2014 Icinga Development Team
    # This file is distributed under the same license as Development Module.
    # FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
    #
    msgid ""
    msgstr ""
    "Project-Id-Version: Development Module (0.0.1)\n"
    "Report-Msgid-Bugs-To: dev@icinga.org\n"
    "POT-Creation-Date: 2014-09-09 10:12+0200\n"
    "PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
    "Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
    "Language: ll_CC\n"
    "Language-Team: LANGUAGE <LL@li.org>\n"
    "MIME-Version: 1.0\n"
    "Content-Type: text/plain; charset=UTF-8\n"
    "Content-Transfer-Encoding: 8bit\n"

    #: /modules/yourmodule/configuration.php:6
    msgid "yourmodule"
    msgstr ""

Great, now you can adjust the file and provide the german `msgstr` for `yourmodule`.

    #: /modules/yourmodule/configuration.php:6
    msgid "Dummy"
    msgstr "Attrappe"

The last step is to compile the __yourmodule.po__ to the __yourmodule.mo__:

    icingacli translation compile module development ll_CC

At this moment, everywhere in the module where the `Dummy` should be translated, it would returns the translated
string `Attrappe`.

[![Build Status](https://github.com/wikimedia/less.php/actions/workflows/php.yml/badge.svg)](https://github.com/wikimedia/less.php/actions)

Less.php
========

This is a PHP port of the [official LESS processor](https://lesscss.org).

* [About](#about)
* [Installation](#installation)
* [Security](#%EF%B8%8F-security)
* [Basic Use](#basic-use)
* [Caching](#caching)
* [Source Maps](#source-maps)
* [Command Line](#command-line)
* [Integration with other projects](#integration-with-other-projects)
* [Transitioning from Leafo/lessphp](#transitioning-from-leafolessphp)
* [Credits](#credits)

About
---

The code structure of Less.php mirrors that of the official processor which helps us ensure compatibility and allows for easy maintenance.

Please note, there are a few unsupported LESS features:

- Evaluation of JavaScript expressions within back-ticks (for obvious reasons).
- Definition of custom functions.

Installation
---

You can install the library with Composer or manually.

#### Composer

1. [Install Composer](https://getcomposer.org/download/)
2. Run `composer require wikimedia/less.php`

#### Manually from release

1. [Download a release](https://github.com/wikimedia/less.php/releases) and upload the PHP files to your server.

2. Include the library:

```php
require_once '[path to less.php]/lib/Less/Autoloader.php';
Less_Autoloader::register();
```

⚠️ Security
---

The LESS processor language is powerful and including features that can read or embed arbitrary files that the web server has access to, and features that may be computationally exensive if misused.

In general you should treat LESS files as being in the same trust domain as other server-side executables, such as Node.js or PHP code. In particular, it is not recommended to allow people that use your web service to provide arbitrary LESS code for server-side processing.

_See also [SECURITY](./SECURITY.md)._

Basic Use
---

#### Parsing strings

```php
$parser = new Less_Parser();
$parser->parse( '@color: #4D926F; #header { color: @color; } h2 { color: @color; }' );
$css = $parser->getCss();
```


#### Parsing LESS files
The parseFile() function takes two arguments:

1. The absolute path of the .less file to be parsed
2. The url root to prepend to any relative image or @import urls in the .less file.

```php
$parser = new Less_Parser();
$parser->parseFile( '/var/www/mysite/bootstrap.less', 'https://example.org/mysite/' );
$css = $parser->getCss();
```

#### Handling invalid LESS

An exception will be thrown if the compiler encounters invalid LESS.

```php
try{
	$parser = new Less_Parser();
	$parser->parseFile( '/var/www/mysite/bootstrap.less', 'https://example.org/mysite/' );
	$css = $parser->getCss();
}catch(Exception $e){
	$error_message = $e->getMessage();
}
```

#### Parsing multiple sources

Less.php can parse multiple sources to generate a single CSS file.

```php
$parser = new Less_Parser();
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$parser->parse( '@color: #4D926F; #header { color: @color; } h2 { color: @color; }' );
$css = $parser->getCss();
```

#### Getting info about the parsed files

Less.php can tell you which `.less` files were imported and parsed.

```php
$parser = new Less_Parser();
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
$imported_files = $parser->allParsedFiles();
```

#### Compressing output

You can tell Less.php to remove comments and whitespace to generate minimized CSS files.

```php
$options = [ 'compress' => true ];
$parser = new Less_Parser( $options );
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
```

#### Getting variables

You can use the `getVariables()` method to get an all variables defined and
their value in a php associative array. Note that LESS has to be previously
compiled.

```php
$parser = new Less_Parser;
$parser->parseFile( '/var/www/mysite/bootstrap.less');
$css = $parser->getCss();
$variables = $parser->getVariables();

```

#### Setting variables

You can use the `ModifyVars()` method to customize your CSS if you have variables stored in PHP associative arrays.

```php
$parser = new Less_Parser();
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$parser->ModifyVars( [ 'font-size-base' => '16px' ] );
$css = $parser->getCss();
```

#### Import directories

By default, Less.php will look for imported files in the directory of the file passed to `parseFile()`.
If you're using `parse()`, or if import files reside in a different directory, you can tell Less.php where to look.

```php
$directories = [ '/var/www/mysite/bootstrap/' => '/mysite/bootstrap/' ];
$parser = new Less_Parser();
$parser->SetImportDirs( $directories );
$parser->parseFile( '/var/www/mysite/theme.less', '/mysite/' );
$css = $parser->getCss();
```

Caching
---

Compiling LESS code into CSS is a time-consuming process, caching your results is highly recommended.

#### Caching CSS

Use the `Less_Cache` class to save and reuse the results of compiled LESS files.
This class will check the modified time and size of each LESS file (including imported files) and regenerate a new CSS file when changes are found.

Note: When changes are found, this method will return a different file name for the new cached content.

```php
$less_files = [ '/var/www/mysite/bootstrap.less' => '/mysite/' ];
$options = [ 'cache_dir' => '/var/www/writable_folder' ];
$css_file_name = Less_Cache::Get( $less_files, $options );
$compiled = file_get_contents( '/var/www/writable_folder/'.$css_file_name );
```

#### Caching CSS with variables

Passing options to `Less_Cache::Get()`:

```php
$less_files = [ '/var/www/mysite/bootstrap.less' => '/mysite/' ];
$options = [ 'cache_dir' => '/var/www/writable_folder' ];
$variables = [ 'width' => '100px' ];
$css_file_name = Less_Cache::Get( $less_files, $options, $variables );
$compiled = file_get_contents( '/var/www/writable_folder/'.$css_file_name );
```

#### Parser caching

Less.php will save serialized parser data for each `.less` file if a writable folder is passed to the `SetCacheDir()` method.

Note: This feature only caches intermediate parsing results to improve the performance of repeated CSS generation.

Your application should cache any CSS generated by Less.php.

```php
$options = [ 'cache_dir'=>'/var/www/writable_folder' ];
$parser = new Less_Parser( $options );
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
```

You can specify the caching technique used by changing the `cache_method` option. Supported methods are:

* `php`: Creates valid PHP files which can be included without any changes (default method).
* `var_export`: Like "php", but using PHP's `var_export()` function without any optimizations.
  It's recommended to use "php" instead.
* `serialize`: Faster, but pretty memory-intense.
* `callback`: Use custom callback functions to implement your own caching method. Give the "cache_callback_get" and
  "cache_callback_set" options with callables (see PHP's `call_user_func()` and `is_callable()` functions). Less.php
  will pass the parser object (class `Less_Parser`), the path to the parsed .less file ("/some/path/to/file.less") and
  an identifier that will change every time the .less file is modified. The `get` callback must return the ruleset
  (an array with `Less_Tree` objects) provided as fourth parameter of the `set` callback. If something goes wrong,
  return `NULL` (cache doesn't exist) or `FALSE`.


Source maps
---

Less.php supports v3 sourcemaps.

#### Inline

The sourcemap will be appended to the generated CSS file.

```php
$options = [ 'sourceMap' => true ];
$parser = new Less_Parser($options);
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
```

#### Saving to map file

```php
$options = [
	'sourceMap' => true,
	'sourceMapWriteTo' => '/var/www/mysite/writable_folder/filename.map',
	'sourceMapURL' => '/mysite/writable_folder/filename.map',
];
$parser = new Less_Parser($options);
$parser->parseFile( '/var/www/mysite/bootstrap.less', '/mysite/' );
$css = $parser->getCss();
```

Command line
---

An additional script has been included to use the compiler from the command line.
In the simplest invocation, you specify an input file and the compiled CSS is written to standard out:

```
$ lessc input.less > output.css
```

By using the `-w` flag you can watch a specified input file and have it compile as needed to the output file:

```
$ lessc -w input.less output.css
```

Errors from watch mode are written to standard out.

For more help, run `lessc --help`


Integration with other projects
---

#### Drupal 7

This library can be used as drop-in replacement of lessphp to work with [Drupal 7 less module](https://drupal.org/project/less).

How to install:

1. [Download the Less.php source code](https://github.com/wikimedia/less.php/archive/main.zip) and unzip it so that 'lessc.inc.php' is located at 'sites/all/libraries/lessphp/lessc.inc.php'.
2. Download and install [Drupal 7 less module](https://drupal.org/project/less) as usual.
3. That's it :)

#### JBST WordPress theme

JBST has a built-in LESS compiler based on lessphp. Customize your WordPress theme with LESS.

How to use / install:

1. [Download the latest release](https://github.com/bassjobsen/jamedo-bootstrap-start-theme) copy the files to your {wordpress/}wp-content/themes folder and activate it.
2. Find the compiler under Appearance > LESS Compiler in your WordPress dashboard
3. Enter your LESS code in the text area and press (re)compile

Use the built-in compiler to:
- set any [Bootstrap](https://getbootstrap.com/docs/3.4/customize/) variable or use Bootstrap's mixins:
	- `@navbar-default-color: blue;`
        - create a custom button: `.btn-custom {
  .button-variant(white; red; blue);
}`
- set any built-in LESS variable: for example `@footer_bg_color: black;` sets the background color of the footer to black
- use built-in mixins: - add a custom font: `.include-custom-font(@family: arial,@font-path, @path: @custom-font-dir, @weight: normal, @style: normal);`

The compiler can also be downloaded as [plugin](https://wordpress.org/plugins/wp-less-to-css/)

#### WordPress

This simple plugin will simply make the library available to other plugins and themes and can be used as a dependency using the [TGM Library](http://tgmpluginactivation.com/)

How to install:

1. Install the plugin from your WordPress Dashboard: https://wordpress.org/plugins/lessphp/
2. That's it :)


Transitioning from Leafo/lessphp
---

Projects looking for an easy transition from leafo/lessphp can use the lessc.inc.php adapter. To use, [Download the Less.php source code](https://github.com/wikimedia/less.php/archive/main.zip) and unzip the files into your project so that the new `lessc.inc.php` replaces the existing `lessc.inc.php`.

Note, the `setPreserveComments` will no longer have any effect on the compiled LESS.

Credits
---

Less.php was originally ported to PHP in 2011 by [Matt Agar](https://github.com/agar) and then updated by [Martin Jantošovič](https://github.com/Mordred) in 2012. From 2013 to 2017, [Josh Schmidt](https://github.com/oyejorge) lead development of the library. Since 2019, the library is maintained by Wikimedia.

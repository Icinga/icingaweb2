# Monitoring Module Hooks <a id="monitoring-module-hooks"></a>

## Detail View Extension Hook <a id="monitoring-module-hooks-detailviewextension"></a>

This hook can be used to easily extend the detail view of monitored objects (hosts and services).

### How it works <a id="monitoring-module-hooks-detailviewextension-how-it-works"></a>

#### Directory structure <a id="monitoring-module-hooks-detailviewextension-directory-structure"></a>

* `icingaweb2/modules/example`
    * `library/Example/ProvidedHook/Monitoring/DetailviewExtension/Simple.php`
    * `run.php`

#### Files <a id="monitoring-module-hooks-detailviewextension-files"></a>

##### run.php <a id="monitoring-module-hooks-detailviewextension-files-run-php"></a>

```php
<?php
/** @var \Icinga\Application\Modules\Module $this */

$this->provideHook(
    'monitoring/DetailviewExtension',
    'Icinga\Module\Example\ProvidedHook\Monitoring\DetailviewExtension\Simple'
);
```

##### Simple.php <a id="monitoring-module-hooks-detailviewextension-files-simple-php"></a>

```php
<?php
namespace Icinga\Module\Example\ProvidedHook\Monitoring\DetailviewExtension;

use Icinga\Module\Monitoring\Hook\DetailviewExtensionHook;
use Icinga\Module\Monitoring\Object\MonitoredObject;

class Simple extends DetailviewExtensionHook
{
    public function getHtmlForObject(MonitoredObject $object)
    {
        $stats = array();
        foreach (str_split($object->name) as $c) {
            if (isset($stats[$c])) {
                ++$stats[$c];
            } else {
                $stats[$c] = 1;
            }
        }

        ksort($stats);

        $view = $this->getView();

        $thead = '';
        $tbody = '';
        foreach ($stats as $c => $amount) {
            $thead .= '<th>' . $view->escape($c) . '</th>';
            $tbody .= '<td>' . $amount . '</td>';
        }

        return '<h2>'
            . $view->escape(sprintf($view->translate('A %s named "%s"'), $object->getType(), $object->name))
            . '</h2>'
            . '<h3>Character stats</h3>'
            . '<table>'
            . '<thead>' . $thead . '</thead>'
            . '<tbody>' . $tbody . '</tbody>'
            . '</table>';
    }
}
```

### How it looks <a id="monitoring-module-hooks-detailviewextension-how-it-looks"></a>

![Screenshot](img/hooks-detailviewextension-01.png)

## Plugin Output Hook <a id="monitoring-module-hooks-pluginoutput"></a>

The Plugin Output Hook allows you to rewrite the plugin output based on check commands. You have to implement the
following methods:

* `getCommands()`
* and `render()`

With `getCommands()` you specify for which commands the provided hook is responsible for. You may return a single
command as string or a list of commands as array. If you want your hook to be responsible for every command, you have to
specify the `*`.

In `render()` you rewrite the plugin output based on check commands. The parameter `$command` specifies the check
command of the host or service and `$output` specifies the plugin output. The parameter `$detail` tells you
whether the output is requested from the detail area of the host or service.

Do not use complex logic for rewriting plugin output in list views because of the performance impact!

You have to return the rewritten plugin output as string. It is also possible to return a HTML string here.
Please refer to `\Icinga\Module\Monitoring\Web\Helper\PluginOutputPurifier` for a list of allowed tags.

Please also have a look at the following examples.

**Example hook which is responsible for disk checks:**

```php
<?php

namespace Icinga\Module\Example\ProvidedHook\Monitoring;

use Icinga\Module\Monitoring\Hook\PluginOutputHook;

class PluginOutput extends PluginOutputHook
{
    public function getCommands()
    {
        return ['disk'];
    }

    public function render($command, $output, $detail)
    {
        if (! $detail) {
            // Don't rewrite plugin output in list views
            return $output;
        }
        return implode('<br>', explode(';', $output));
    }
}
```

**Example hook which is responsible for disk and procs checks:**

```php
<?php

namespace Icinga\Module\Example\ProvidedHook\Monitoring;

use Icinga\Module\Monitoring\Hook\PluginOutputHook;

class PluginOutput extends PluginOutputHook
{
    public function getCommands()
    {
        return ['disk', 'procs'];
    }

    public function render($command, $output, $detail)
    {
        switch ($command) {
            case 'disk':
                if ($detail) {
                    // Only rewrite plugin output in the detail area
                    $output = implode('<br>', explode(';', $output));
                }
                break;
            case 'procs':
                $output = preg_replace('/(\d)+/', '<b>$1</b>', $output);
                break;
        }

        return $output;
    }
}
```

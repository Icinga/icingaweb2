<?php

/* Icinga Web 2 | (c) 2026 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Util\Csp;
use ipl\Html\Table;
use ipl\I18n\Translation;

class CspConfigurationTable extends Table
{
    use Translation;

    public function __construct()
    {
        $this->getAttributes()->add('class', 'csp-config-table');
    }

    protected function assemble(): void
    {
        $this->add(self::tr([
            self::th($this->translate('Type')),
            self::th($this->translate('Info')),
            self::th($this->translate('Directive')),
            self::th($this->translate('Value')),
        ]));

        $policyDirectives = Csp::collectContentSecurityPolicyDirectives();

        foreach ($policyDirectives as $directiveGroup) {
            $reason = $directiveGroup['reason'];
            $type = $reason['type'];
            $info = match ($type) {
                'navigation' => $reason['navType']
                    . '/' . ($reason['parent'] !== null ? ($reason['parent'] . '/') : '')
                    . $reason['name'],
                'dashlet' => $reason['pane'] . '/' . $reason['dashlet'],
                'hook' => $reason['hook'],
                default => '-',
            };
            foreach ($directiveGroup['directives'] as $directive => $policies) {
                $this->add(self::tr([
                    self::td($type),
                    self::td($info),
                    self::td($directive),
                    self::td(join(', ', $policies)),
                ]));
            }
        }
    }
}

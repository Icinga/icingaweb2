<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use ErrorException;
use Icinga\Application\Config;
use Icinga\Exception\ConfigurationError;
use Icinga\Security\SecurityException;
use Icinga\Web\Controller;

/**
 * Display external or internal links within an iframe
 */
class IframeController extends Controller
{
    /**
     * Display iframe w/ the given URL
     */
    public function indexAction()
    {
        $this->view->url = $url = $this->params->getRequired('url');
        $iframe = Config::app()->getSection('iframe');
        $match = false;

        try {
            foreach (explode("\n", $iframe->regexes) as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                if (preg_match($line, $url)) {
                    $match = true;
                    break;
                }
            }
        } catch (ErrorException $e) {
            throw new ConfigurationError($this->translate('Bad PCRE %s'), var_export($line, true), $e);
        }

        if ($match !== (bool) $iframe->whitelist) {
            throw new SecurityException($this->translate(
                'You\'re not allowed to embed this URL into Icinga Web 2 via iframe'
            ));
        }
    }
}

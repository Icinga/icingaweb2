<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

# namespace Icinga\Application\Controllers;

use Icinga\Web\Controller\ActionController;
use Icinga\Application\Icinga;
use Icinga\Exception\IcingaException;

class AboutController extends ActionController
{
    public function indexAction()
    {
        $this->view->appVersion = null;
        $this->view->gitCommitID = null;
        $this->view->gitCommitDate = null;

        if (false !== ($appVersion = @file(
            Icinga::app()->getApplicationDir() . DIRECTORY_SEPARATOR . 'VERSION',
            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
        ))) {
            foreach ($appVersion as $av) {
                $matches = array();
                if (false === ($res = preg_match(
                    '/(?<!.)\s*(.+?)\s*:\s*(.+?)\s*(?!.)/ms', $av, $matches
                ))) {
                    throw new IcingaException('Failed at preg_match()');
                }
                if ($res === 0) {
                    continue;
                }

                switch ($matches[1]) {
                    case 'GitCommitID':
                        if ($this->view->gitCommitID !== null) {
                            break;
                        }

                        $matches2 = array();
                        if (false === ($res = preg_match(
                            '/(?<!.)(.+?)(?:\s*\(\s*(.+?)\s*\))?(?!.)/ms',
                            $matches[2],
                            $matches2
                        ))) {
                            throw new IcingaException('Failed at preg_match()');
                        }
                        if ($res === 0) {
                            break;
                        }

                        $this->view->gitCommitID = $matches2[1];
                        if (! isset($matches2[2])) {
                            break;
                        }

                        foreach (preg_split(
                            '/\s*,\s*/', $matches2[2], -1, PREG_SPLIT_NO_EMPTY
                        ) as $refName) {
                            $matches3 = array();
                            if (false === ($res = preg_match(
                                '/(?<!.)tag\s*:\s*v(.+?)(?!.)/ms',
                                $refName,
                                $matches3
                            ))) {
                                throw new IcingaException('Failed at preg_match()');
                            }
                            if ($res === 1) {
                                $this->view->appVersion = $matches3[1];
                                break;
                            }
                        }
                        break;
                    case 'GitCommitDate':
                        if ($this->view->gitCommitDate !== null) {
                            break;
                        }

                        $matches2 = array();
                        if (false === ($res = preg_match(
                            '/(?<!.)(\S+)/ms', $matches[2], $matches2
                        ))) {
                            throw new IcingaException('Failed at preg_match()');
                        }
                        if ($res === 1) {
                            $this->view->gitCommitDate = $matches2[1];
                        }
                }
            }
        }
    }
}

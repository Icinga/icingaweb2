<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Plugin\Perfdata;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;

class Zend_View_Helper_Perfdata extends Zend_View_Helper_Abstract
{
    /**
     * Display the given perfdata string to the user
     *
     * @param   string  $perfdataStr    The perfdata string
     * @param   bool    $compact        Whether to display the perfdata in compact mode
     * @param   int     $limit          Max labels to show; 0 for no limit
     * @param   string  $color          The color indicating the perfdata state
     *
     * @return string
     */
    public function perfdata($perfdataStr, $compact = false, $limit = 0, $color = Perfdata::PERFDATA_OK)
    {
        $pieChartData = PerfdataSet::fromString($perfdataStr)->asArray();
        $results = array();
        $table = array(
            '<td><b>' . implode(
                '</b></td><td><b>',
                array(
                    '',
                    $this->view->translate('Label'),
                    $this->view->translate('Value'),
                    $this->view->translate('Min'),
                    $this->view->translate('Max'),
                    $this->view->translate('Warning'),
                    $this->view->translate('Critical')
                )
            ) . '<b></td>'
        );
        foreach ($pieChartData as $perfdata) {

            if ($compact && $perfdata->isVisualizable()) {
                $results[] = $perfdata->asInlinePie($color)->render();
            } else {
                $row = '<tr>';

                $row .= '<td>';
                if ($perfdata->isVisualizable()) {
                    $row .= $perfdata->asInlinePie($color)->render() . '&nbsp;';
                }
                $row .= '</td>';

                if (! $compact) {
                    foreach ($perfdata->toArray() as $value) {
                        if ($value === '') {
                            $value = '-';
                        }
                        $row .= '<td>' . (string) $value . '</td>';
                    }
                }

                $row .= '</tr>';
                $table[] = $row;
            }
        }
        if ($limit > 0) {
            $count = max(count($table), count($results));
            $table = array_slice($table, 0, $limit);
            $results = array_slice($results, 0, $limit);
            if ($count > $limit) {
                $mess = sprintf($this->view->translate('%d more ...'), $count - $limit);
                $results[] = '<span title="' . $mess . '">...</span>';
            }
        }
        if ($compact) {
            return join('', $results);
        } else {
            $pieCharts = empty($table) ? '' : '<table class="perfdata">' . implode("\n", $table) . '</table>';
            return $pieCharts;
        }
    }
}

<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

use Icinga\Module\Monitoring\Plugin\Perfdata;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;
use Icinga\Util\StringHelper;

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
        uasort(
            $pieChartData,
            function ($a, $b) {
                return $a->worseThan($b) ? -1 : ($b->worseThan($a) ? 1 : 0);
            }
        );
        $results = array();
        $keys = array('', 'label', 'value', 'min', 'max', 'warn', 'crit');
        $columns = array();
        $labels = array_combine(
            $keys,
            array(
                '',
                $this->view->translate('Label'),
                $this->view->translate('Value'),
                $this->view->translate('Min'),
                $this->view->translate('Max'),
                $this->view->translate('Warning'),
                $this->view->translate('Critical')
            )
        );
        foreach ($pieChartData as $perfdata) {
            if ($perfdata->isVisualizable()) {
                $columns[''] = '';
            }
            foreach ($perfdata->toArray() as $column => $value) {
                if (empty($value) ||
                    $column === 'min' && floatval($value) === 0.0 ||
                    $column === 'max' && $perfdata->isPercentage() && floatval($value) === 100) {
                    continue;
                }
                $columns[$column] = $labels[$column];
            }
        }
        // restore original column array sorting
        $headers = array();
        foreach ($keys as $column) {
            if (isset($columns[$column])) {
                $headers[$column] = $labels[$column];
            }
        }
        $table = array('<thead><tr><th>' . implode('</th><th>', $headers) . '</th></tr></thead><tbody>');
        foreach ($pieChartData as $perfdata) {
            if ($compact && $perfdata->isVisualizable()) {
                $results[] = $perfdata->asInlinePie($color)->render();
            } else {
                $data = array();
                if ($perfdata->isVisualizable()) {
                    $data []= $perfdata->asInlinePie($color)->render();
                } elseif (isset($columns[''])) {
                    $data []= '';
                }
                if (! $compact) {
                    foreach ($perfdata->toArray() as $column => $value) {
                        if (! isset($columns[$column])) {
                            continue;
                        }
                        $text = $this->view->escape(empty($value) ? '-' : $value);
                        $data []= sprintf(
                            '<span title="%s">%s</span>',
                            $text,
                            $text
                        );
                    }
                }
                $table []= '<tr><td class="sparkline-col">' . implode('</td><td>', $data) . '</td></tr>';
            }
        }
        $table[] = '</tbody>';
        if ($limit > 0) {
            $count = $compact ? count($results) : count($table);
            if ($count > $limit) {
                if ($compact) {
                    $results = array_slice($results, 0, $limit);
                    $title = sprintf($this->view->translate('%d more ...'), $count - $limit);
                    $results[] = '<span aria-hidden="true" title="' . $title . '">...</span>';
                } else {
                    $table = array_slice($table, 0, $limit);
                }
            }
        }
        if ($compact) {
            return join('', $results);
        } else {
            if (empty($table)) {
                return '';
            }
            return sprintf(
                '<table class="performance-data-table">%s</table>',
                implode("\n", $table)
            );
        }
    }
}

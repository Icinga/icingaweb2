<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Util\Format;
use Icinga\Web\Widget\Chart\InlinePie;
use Icinga\Module\Monitoring\Plugin\Perfdata;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;

class Zend_View_Helper_Perfdata extends Zend_View_Helper_Abstract
{

    /**
     * Display the given perfdata string to the user
     *
     * @param      $perfdataStr The perfdata string
     * @param bool $compact     Whether to display the perfdata in compact mode
     * @param      $color       The color indicating the perfdata state
     *
     * @return string
     */
    public function perfdata($perfdataStr, $compact = false, $color = Perfdata::PERFDATA_DEFAULT)
    {
        $pieChartData = PerfdataSet::fromString($perfdataStr)->asArray();

        $result = '';
        $table = array(
            '<td><b>' . implode(
                '</b></td><td><b>',
                array('', t('Label'), t('Value'), t('Min'), t('Max'), t('Warning'), t('Critical'))
            ) . '<b></td>'
        );
        foreach ($pieChartData as $perfdata) {

            if ($compact && $perfdata->isVisualizable()) {
                $result .= $perfdata->asInlinePie($color)->render();
            } else {
                $row = '<tr>';

                $row .= '<td>';
                if ($perfdata->isVisualizable()) {
                    $row .= $perfdata->asInlinePie($color)->render() . '&nbsp;';
                }
                $row .= '</td>';

                if (!$compact) {
                    foreach ($perfdata->toArray() as $value) {
                        if ($value === '') {
                            $value = '-';
                        }
                        $row .= '<td>' . (string)$value  . '</td>';
                    }
                }

                $row .= '</tr>';
                $table[] = $row;
            }
        }

        if ($compact) {
            return $result;
        } else {
            $pieCharts = empty($table) ? '' : '<table class="perfdata">' . implode("\n", $table) . '</table>';
            return $pieCharts;
        }
    }
}

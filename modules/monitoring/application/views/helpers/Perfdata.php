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
    public function perfdata($perfdataStr, $compact = false, $color = Perfdata::PERFDATA_GREEN)
    {
        $pieChartData = PerfdataSet::fromString($perfdataStr)->asArray();

        $result = '';
        $table = array();
        foreach ($pieChartData as $perfdata) {
            if ($perfdata->isVisualizable()) {
                $pieChart = $perfdata->asInlinePie($color);
                if ($compact) {
                    $result .= $pieChart->render();
                } else {
                    $table[] = '<tr><th>' . $pieChart->render()
                        . htmlspecialchars($perfdata->getLabel())
                        . '</th><td> '
                        . htmlspecialchars($this->formatPerfdataValue($perfdata)) .
                        ' </td></tr>';
                }
            } else {
                $table[] = (string)$perfdata;
            }
        }

        if ($compact) {
            return $result;
        } else {
            $pieCharts = empty($table) ? '' : '<table class="perfdata">' . implode("\n", $table) . '</table>';
            return $pieCharts;
        }
    }

    protected function formatPerfdataValue(Perfdata $perfdata)
    {
        if ($perfdata->isBytes()) {
            return Format::bytes($perfdata->getValue());
        } elseif ($perfdata->isSeconds()) {
            return Format::seconds($perfdata->getValue());
        } elseif ($perfdata->isPercentage()) {
            return $perfdata->getValue() . '%';
        }

        return $perfdata->getValue();
    }

}

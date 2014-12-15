<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Util\Format;
use Icinga\Web\Widget\Chart\InlinePie;
use Icinga\Module\Monitoring\Plugin\Perfdata;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;

class Zend_View_Helper_Perfdata extends Zend_View_Helper_Abstract
{
    public function perfdata($perfdataStr, $compact = false)
    {
        $pset = PerfdataSet::fromString($perfdataStr)->asArray();
        $onlyPieChartData = array_filter($pset, function ($e) { return $e->getPercentage() > 0; });
        if ($compact) {
            $onlyPieChartData = array_slice($onlyPieChartData, 0, 5);
        } else {
            $nonPieChartData = array_filter($pset, function ($e) { return $e->getPercentage() == 0; });
        }

        $result = '';
        $table = array();
        foreach ($onlyPieChartData as $perfdata) {
            $pieChart = $this->createInlinePie($perfdata);
            if ($compact) {
                $result .= $pieChart->render();
            } else {
                if (! $perfdata->isPercentage()) {
                    // TODO: Should we trust sprintf-style placeholders in perfdata titles?
                    $pieChart->setTooltipFormat('{{label}}: {{formatted}} ({{percent}}%)');
                }
                // $pieChart->setStyle('margin: 0.2em 0.5em 0.2em 0.5em;');
                $table[] = '<tr><th>' . $pieChart->render()
                    . htmlspecialchars($perfdata->getLabel())
                    . '</th><td> '
                    . htmlspecialchars($this->formatPerfdataValue($perfdata)) .
                    ' </td></tr>';
            }
        }

        if ($compact) {
            return $result;
        } else {
            $pieCharts = empty($table) ? '' : '<table class="perfdata">' . implode("\n", $table) . '</table>';
            return $pieCharts . "\n" . implode("<br>\n", $nonPieChartData);
        }
    }

    protected function calculatePieChartData(Perfdata $perfdata)
    {
        $rawValue = $perfdata->getValue();
        $minValue = $perfdata->getMinimumValue() !== null ? $perfdata->getMinimumValue() : 0;
        $maxValue = $perfdata->getMaximumValue();
        $usedValue = ($rawValue - $minValue);
        $unusedValue = ($maxValue - $minValue) - $usedValue;

        $gray = $unusedValue;
        $green = $orange = $red = 0;
        // TODO(#6122): Add proper treshold parsing.
        if ($perfdata->getCriticalThreshold() && $perfdata->getValue() > $perfdata->getCriticalThreshold()) {
            $red = $usedValue;
        } elseif ($perfdata->getWarningThreshold() && $perfdata->getValue() > $perfdata->getWarningThreshold()) {
            $orange = $usedValue;
        } else {
            $green = $usedValue;
        }

        return array($green, $orange, $red, $gray);
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

    protected function createInlinePie(Perfdata $perfdata)
    {
        $pieChart = new InlinePie($this->calculatePieChartData($perfdata), $perfdata->getLabel());
        $pieChart->setLabel(htmlspecialchars($perfdata->getLabel()));
        $pieChart->setHideEmptyLabel();

        //$pieChart->setHeight(32)->setWidth(32);
        if ($perfdata->isBytes()) {
            $pieChart->setTooltipFormat('{{label}}: {{formatted}} ({{percent}}%)');
            $pieChart->setNumberFormat(InlinePie::NUMBER_FORMAT_BYTES);
        } else if ($perfdata->isSeconds()) {
            $pieChart->setTooltipFormat('{{label}}: {{formatted}} ({{percent}}%)');
            $pieChart->setNumberFormat(InlinePie::NUMBER_FORMAT_TIME);
        } else {
            $pieChart->setTooltipFormat('{{label}}: {{formatted}}%');
            $pieChart->setNumberFormat(InlinePie::NUMBER_FORMAT_RATIO);
            $pieChart->setHideEmptyLabel();
        }
        return $pieChart;
    }
}

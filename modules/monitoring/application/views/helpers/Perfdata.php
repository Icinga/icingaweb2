<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Util\Format;
use Icinga\Web\Widget\Chart\InlinePie;
use Icinga\Module\Monitoring\Plugin\Perfdata;
use Icinga\Module\Monitoring\Plugin\PerfdataSet;

class Zend_View_Helper_Perfdata extends Zend_View_Helper_Abstract
{
    public function perfdata($perfdataStr, $compact = false, $float = false)
    {
        $result = '';
        $table = array();
        $pset = array_slice(PerfdataSet::fromString($perfdataStr)->asArray(), 0, ($compact ? 5 : null));
        foreach ($pset as $perfdata) {
            if ($perfdata->getPercentage() == 0) {
                continue;
            }
            $pieChart = $this->createInlinePie($perfdata);
            if ($compact) {
                if (! $float) {
                    $result .= $pieChart->render();
                } else {
                    $result .= '<div style="float: right;">' . $pieChart->render() . '</div>';
                }
            } else {
                if (! $perfdata->isPercentage()) {
                    $pieChart->setTooltipFormat('{{label}}: {{formatted}} ({{percent}}%)');
                }
                $pieChart->setStyle('margin: 0.2em 0.5em 0.2em 0.5em;');
                $table[] = '<tr><th>' . $pieChart->render()
                    . htmlspecialchars($perfdata->getLabel())
                    . '</th><td> '
                    . htmlspecialchars($this->formatPerfdataValue($perfdata)) .
                    ' </td></tr>';
            }
        }

        // TODO: What if we have both? And should we trust sprintf-style placeholders in perfdata titles?
        if (empty($table)) {
            return $compact ? $result : $perfdataStr;
        } else {
            return '<table class="perfdata">' . implode("\n", $table) . '</table>';
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

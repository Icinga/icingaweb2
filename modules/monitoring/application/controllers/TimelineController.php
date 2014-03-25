<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \DateTime;
use \DateInterval;
use \Zend_Config;
use Icinga\Application\Config;
use Icinga\Web\Controller\ActionController;
use Icinga\Module\Monitoring\Timeline\TimeLine;
use Icinga\Module\Monitoring\Timeline\TimeRange;
use Icinga\Module\Monitoring\Web\Widget\TimelineIntervalBox;
use Icinga\Module\Monitoring\DataView\EventHistory as EventHistoryView;

class Monitoring_TimelineController extends ActionController
{
    public function indexAction()
    {
        $this->setupIntervalBox();
        list($displayRange, $forecastRange) = $this->buildTimeRanges();

        $timeline = new TimeLine(
            EventHistoryView::fromRequest(
                $this->getRequest(),
                array(
                    'name' => 'type',
                    'time' => 'raw_timestamp'
                )
            ),
            array(
                'notify'        => array('label' => t('Notifications'), 'color' => 'red'),
                'hard_state'    => array('label' => t('Hard state changes'), 'color' => 'green'),
                'comment'       => array('label' => t('Comments'), 'color' => 'blue'),
                'ack'           => array('label' => t('Acknowledgements'), 'color' => 'black'),
                'dt_start'      => array('label' => t('Started downtimes'), 'color' => 'grey'),
                'dt_end'        => array('label' => t('Ended downtimes'), 'color' => 'white')
            )
        );
        $timeline->setDisplayRange($displayRange);
        $timeline->setForecastRange($forecastRange);

        $this->view->timeline = $timeline;
        $this->view->intervalFormat = $this->getIntervalFormat();
        $oldBase = $timeline->getCalculationBase(false);
        $this->view->switchedContext = $oldBase !== null && $oldBase !== $timeline->getCalculationBase(true);
    }

    /**
     * Create a select box the user can choose the timeline interval from
     */
    private function setupIntervalBox()
    {
        $box = new TimelineIntervalBox(
            'intervalBox',
            array(
                '4h' => t('4 Hours'),
                '1d' => t('One day'),
                '1w' => t('One week'),
                '1m' => t('One month'),
                '1y' => t('One year')
            )
        );
        $box->applyRequest($this->getRequest());
        $this->view->intervalBox = $box;
    }

    /**
     * Return the chosen interval
     *
     * @return  DateInterval    The chosen interval
     */
    private function getTimelineInterval()
    {
        switch ($this->view->intervalBox->getInterval())
        {
            case '1d':
                return new DateInterval('P1D');
            case '1w':
                return new DateInterval('P1W');
            case '1m':
                return new DateInterval('P1M');
            case '1y':
                return new DateInterval('P1Y');
            default:
                return new DateInterval('PT4H');
        }
    }

    /**
     * Get an appropriate datetime format string for the chosen interval
     *
     * @return  string
     */
    private function getIntervalFormat()
    {
        switch ($this->view->intervalBox->getInterval())
        {
            case '1d':
                return $this->getDateFormat();
            case '1w':
                return '\W\e\ek #W \of Y';
            case '1m':
                return 'F Y';
            case '1y':
                return 'Y';
            default:
                return $this->getDateFormat() . ' ' . $this->getTimeFormat();
        }
    }

    /**
     * Return a preload interval based on the chosen timeline interval
     *
     * @return  DateInterval    The interval to pre-load
     */
    private function getPreloadInterval()
    {
        switch ($this->view->intervalBox->getInterval())
        {
            case '1d':
                return DateInterval::createFromDateString('1 week -1 second');
            case '1w':
                return DateInterval::createFromDateString('8 weeks -1 second');
            case '1m':
                return DateInterval::createFromDateString('6 months -1 second');
            case '1y':
                return DateInterval::createFromDateString('4 years -1 second');
            default:
                return DateInterval::createFromDateString('1 day -1 second');
        }
    }

    /**
     * Extrapolate the given datetime based on the chosen timeline interval
     *
     * @param   DateTime    $dateTime   The datetime to extrapolate
     */
    private function extrapolateDateTime(DateTime &$dateTime)
    {
        switch ($this->view->intervalBox->getInterval())
        {
            case '1d':
                $dateTime->setTimestamp(strtotime('tomorrow', $dateTime->getTimestamp()) - 1);
                break;
            case '1w':
                $dateTime->setTimestamp(strtotime('next monday', $dateTime->getTimestamp()) - 1);
                break;
            case '1m':
                $dateTime->setTimestamp(
                    strtotime(
                        'last day of this month',
                        strtotime(
                            'tomorrow',
                            $dateTime->getTimestamp()
                        ) - 1
                    )
                );
                break;
            case '1y':
                $dateTime->setTimestamp(strtotime('1 january next year', $dateTime->getTimestamp()) - 1);
                break;
            default:
                $hour = $dateTime->format('G');
                $end = $hour < 4 ? 4 : ($hour < 8 ? 8 : ($hour < 12 ? 12 : ($hour < 16 ? 16 : ($hour < 20 ? 20 : 24))));
                $dateTime = DateTime::createFromFormat(
                    'd/m/y G:i:s',
                    $dateTime->format('d/m/y') . ($end - 1) . ':59:59'
                );
        }
    }

    /**
     * Return a display- and forecast time range
     *
     * Assembles a time range each for display and forecast purposes based on the start- and
     * end time if given in the current request otherwise based on the current time and a
     * end time that is calculated based on the chosen timeline interval.
     *
     * @return  array   The resulting time ranges
     */
    private function buildTimeRanges()
    {
        $startTime = new DateTime();
        $startTimestamp = strtotime($this->_request->getParam('start'));
        if ($startTimestamp !== false) {
            $startTime->setTimestamp($startTimestamp);
        }
        $this->extrapolateDateTime($startTime);

        $endTime = clone $startTime;
        $endTimestamp = strtotime($this->_request->getParam('end'));
        if ($endTimestamp !== false) {
            $endTime->setTimestamp($endTimestamp);
        } else {
            $endTime->sub($this->getPreloadInterval());
        }

        $forecastStart = clone $endTime;
        $forecastStart->sub(new DateInterval('PT1S'));
        $forecastEnd = clone $forecastStart;
        $forecastEnd->sub($endTime->diff($startTime));

        $timelineInterval = $this->getTimelineInterval();
        return array(
            new TimeRange($startTime, $endTime, $timelineInterval),
            new TimeRange($forecastStart, $forecastEnd, $timelineInterval)
        );
    }

    /**
     * Get the application's global configuration or an empty one
     *
     * @return  Zend_Config
     */
    private function getGlobalConfiguration()
    {
        $globalConfig = Config::app()->global;

        if ($globalConfig === null) {
            $globalConfig = new Zend_Config(array());
        }

        return $globalConfig;
    }

    /**
     * Get the user's preferred time format or the application's default
     *
     * @return  string
     */
    private function getTimeFormat()
    {
        $globalConfig = $this->getGlobalConfiguration();
        $preferences = $this->getRequest()->getUser()->getPreferences();
        return $preferences->get('app.timeFormat', $globalConfig->get('timeFormat', 'g:i A'));
    }

    /**
     * Get the user's preferred date format or the application's default
     *
     * @return  string
     */
    private function getDateFormat()
    {
        $globalConfig = $this->getGlobalConfiguration();
        $preferences = $this->getRequest()->getUser()->getPreferences();
        return $preferences->get('app.dateFormat', $globalConfig->get('dateFormat', 'd/m/Y'));
    }
}

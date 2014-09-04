<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \DateTime;
use \DateInterval;
use \Zend_Config;
use Icinga\Web\Url;
use Icinga\Util\Format;
use Icinga\Application\Config;
use Icinga\Util\DateTimeFactory;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Timeline\TimeLine;
use Icinga\Module\Monitoring\Timeline\TimeRange;
use Icinga\Module\Monitoring\Web\Widget\TimelineIntervalBox;
use Icinga\Module\Monitoring\DataView\EventHistory as EventHistoryView;

class Monitoring_TimelineController extends Controller
{
    public function indexAction()
    {
        // TODO: filter for hard_states (precedence adjustments necessary!)
        $this->setupIntervalBox();
        list($displayRange, $forecastRange) = $this->buildTimeRanges();

        $detailUrl = Url::fromPath('monitoring/list/eventhistory');

        $timeline = new TimeLine(
            $this->backend->select()->from('eventHistory', 
                array(
                    'name' => 'type',
                    'time' => 'timestamp'
                )
            ),
            array(
                'notify'        => array(
                    'detailUrl' => $detailUrl,
                    'label'     => t('Notifications'),
                    'color'     => '#3a71ea'
                ),
                'hard_state'    => array(
                    'detailUrl' => $detailUrl,
                    'label'     => t('Hard state changes'),
                    'color'     => '#ff7000'
                ),
                'comment'       => array(
                    'detailUrl' => $detailUrl,
                    'label'     => t('Comments'),
                    'color'     => '#79bdba'
                ),
                'ack'           => array(
                    'detailUrl' => $detailUrl,
                    'label'     => t('Acknowledgements'),
                    'color'     => '#a2721d'
                ),
                'dt_start'      => array(
                    'detailUrl' => $detailUrl,
                    'label'     => t('Started downtimes'),
                    'color'     => '#8e8e8e'
                ),
                'dt_end'        => array(
                    'detailUrl' => $detailUrl,
                    'label'     => t('Ended downtimes'),
                    'color'     => '#d5d6ad'
                )
            )
        );
        $timeline->setMaximumCircleWidth('6em');
        $timeline->setMinimumCircleWidth('0.3em');
        $timeline->setDisplayRange($displayRange);
        $timeline->setForecastRange($forecastRange);
        $beingExtended = $this->getRequest()->getParam('extend') == 1;
        $timeline->setSession($this->Window()->getSessionNamespace('timeline', !$beingExtended));

        $this->view->timeline = $timeline;
        $this->view->nextRange = $forecastRange;
        $this->view->beingExtended = $beingExtended;
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
                return '\W\e\ek #W\<b\r\>\of Y';
            case '1m':
                return 'F Y';
            case '1y':
                return 'Y';
            default:
                return $this->getDateFormat() . '\<b\r\>' . $this->getTimeFormat();
        }
    }

    /**
     * Return a preload interval based on the chosen timeline interval and the given date and time
     *
     * @param   DateTime    $dateTime   The date and time to use
     *
     * @return  DateInterval            The interval to pre-load
     */
    private function getPreloadInterval(DateTime $dateTime)
    {
        switch ($this->view->intervalBox->getInterval())
        {
            case '1d':
                return DateInterval::createFromDateString('1 week -1 second');
            case '1w':
                return DateInterval::createFromDateString('8 weeks -1 second');
            case '1m':
                $dateCopy = clone $dateTime;
                for ($i = 0; $i < 6; $i++) {
                    $dateCopy->sub(new DateInterval('PT' . Format::secondsByMonth($dateCopy) . 'S'));
                }
                return $dateCopy->add(new DateInterval('PT1S'))->diff($dateTime);
            case '1y':
                $dateCopy = clone $dateTime;
                for ($i = 0; $i < 4; $i++) {
                    $dateCopy->sub(new DateInterval('PT' . Format::secondsByYear($dateCopy) . 'S'));
                }
                return $dateCopy->add(new DateInterval('PT1S'))->diff($dateTime);
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
        $startTime = DateTimeFactory::create();
        $startParam = $this->_request->getParam('start');
        $startTimestamp = is_numeric($startParam) ? intval($startParam) : strtotime($startParam);
        if ($startTimestamp !== false) {
            $startTime->setTimestamp($startTimestamp);
        } else {
            $this->extrapolateDateTime($startTime);
        }

        $endTime = clone $startTime;
        $endParam = $this->_request->getParam('end');
        $endTimestamp = is_numeric($endParam) ? intval($endParam) : strtotime($endParam);
        if ($endTimestamp !== false) {
            $endTime->setTimestamp($endTimestamp);
        } else {
            $endTime->sub($this->getPreloadInterval($startTime));
        }

        $forecastStart = clone $endTime;
        $forecastStart->sub(new DateInterval('PT1S'));
        $forecastEnd = clone $forecastStart;
        $forecastEnd->sub($this->getPreloadInterval($forecastStart));

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
        // TODO(mh): Missing localized format (#6077)
        return 'g:i A';
    }

    /**
     * Get the user's preferred date format or the application's default
     *
     * @return  string
     */
    private function getDateFormat()
    {
        // TODO(mh): Missing localized format (#6077)
        return 'd/m/Y';
    }
}

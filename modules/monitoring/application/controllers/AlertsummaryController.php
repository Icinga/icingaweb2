<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Chart\GridChart;
use Icinga\Chart\Unit\LinearUnit;
use Icinga\Chart\Unit\StaticAxis;
use Icinga\Module\Monitoring\Controller;
use Icinga\Module\Monitoring\Web\Widget\SelectBox;
use Icinga\Web\Url;

class Monitoring_AlertsummaryController extends Controller
{
    /**
     * @var string
     */
    protected $url;

    /**
     * @var array
     */
    private $notificationData;

    /**
     * @var array
     */
    private $problemData;

    /**
     * Init data set
     */
    public function init()
    {
        $tabs = $this->getTabs();
        if (in_array($this->_request->getActionName(), array('alertsummary'))) {
            $tabs->extend(new OutputFormat())->extend(new DashboardAction());
        }

        $this->url = Url::fromRequest();

        $this->notificationData = $this->createNotificationData();
        $this->problemData = $this->createProblemData();
    }

    /**
     * @param string $action
     * @param bool $title
     */
    protected function addTitleTab($action, $title = false)
    {
        $title = $title ?: ucfirst($action);
        $this->getTabs()->add(
            $action,
            array(
                'title' => $title,
                'url'   => $this->url
            )
        )->activate($action);
        $this->view->title = $title;
    }

    /**
     * Creat full report
     */
    public function indexAction()
    {
        $this->addTitleTab('alertsummary', $this->translate('Alert Summary'));
        $this->view->intervalBox = $this->createIntervalBox();
        $this->view->recentAlerts = $this->createRecentAlerts();
        $this->view->interval = $this->getInterval();
        $this->view->defectChart = $this->createDefectImage();
        $this->view->healingChart = $this->createHealingChart();
        $this->view->perf = $this->createNotificationPerfdata();
        $this->view->trend = $this->createTrendInformation();

        $this->setAutorefreshInterval(15);
        $query = $this->backend->select()->from(
            'notification',
            array(
                'host',
                'service',
                'notification_output',
                'notification_contact',
                'notification_start_time',
                'notification_state'
            )
        );

        $this->view->notifications = $query->paginate();
    }

    /**
     * Create data for charts
     *
     * @return array
     */
    private function createNotificationData()
    {
        $interval = $this->getInterval();

        $query = $this->backend->select()->from(
            'notification',
            array(
                'host',
                'service',
                'notification_output',
                'notification_contact',
                'notification_start_time',
                'notification_state'
            )
        );

        $query->setFilter(
            new Icinga\Data\Filter\FilterExpression(
                'n.start_time',
                '>=',
                $this->getBeginDate($interval)->format('Y-m-d H:i:s')
            )
        );

        $query->order('notification_start_time', 'asc');

        $records    = $query->getQuery()->fetchAll();
        $data       = array();
        $period     = $this->createPeriod($interval);

        foreach ($period as $entry) {
            $id = $this->getPeriodFormat($interval, $entry->getTimestamp());
            $data[$id] = array($id, 0);
        }

        foreach ($records as $item) {
            $id = $this->getPeriodFormat($interval, $item->notification_start_time);
            if (empty($data[$id])) {
                $data[$id] = array($id, 0);
            }

            $data[$id][1]++;
        }

        return $data;
    }

    /**
     * Trend information for notifications
     *
     * @return stdClass
     */
    private function createTrendInformation()
    {
        $date = new DateTime();

        $beginDate = $date->sub(new DateInterval('P3D'));
        $query = $this->backend->select()->from(
            'notification',
            array(
                'host',
                'service',
                'notification_output',
                'notification_contact',
                'notification_start_time',
                'notification_state'
            )
        );

        $query->setFilter(
            new Icinga\Data\Filter\FilterExpression(
                'n.start_time',
                '>=',
                $beginDate->format('Y-m-d H:i:s')
            )
        );

        $query->order('notification_start_time', 'asc');

        $records = $query->getQuery()->fetchAll();
        $slots = array();

        $period = new DatePeriod($beginDate, new DateInterval('P1D'), 2, DatePeriod::EXCLUDE_START_DATE);
        foreach ($period as $entry) {
            $slots[$entry->format('Y-m-d')] = 0;
        }

        foreach ($records as $item) {
            $id = strftime('%Y-%m-%d', $item->notification_start_time);
            if (isset($slots[$id])) {
                $slots[$id]++;
            }
        }

        $yesterday = array_shift($slots);
        $today = array_shift($slots);

        $out = new stdClass();
        if ($yesterday === $today) {
            $out->trend = 'unchanged';
        } elseif ($yesterday > $today) {
            $out->trend = 'down';
        } else {
            $out->trend = 'up';
        }

        if ($yesterday <= 0) {
            $out->percent = 100;
        } elseif ($yesterday === $today) {
            $out->percent = 0;
        } else {
            $out->percent = sprintf(
                '%.2f',
                100 - ((100/($yesterday > $today ? $yesterday : $today)) * ($yesterday > $today ? $today : $yesterday))
            );
        }

        return $out;
    }

    /**
     * Perfdata for notifications
     *
     * @return stdClass
     */
    private function createNotificationPerfdata()
    {
        $interval = $this->getInterval();

        $query = $this->backend->select()->from(
            'notification',
            array(
                'host',
                'service',
                'notification_output',
                'notification_contact',
                'notification_start_time',
                'notification_state'
            )
        );

        $query->setFilter(
            new Icinga\Data\Filter\FilterExpression(
                'n.start_time',
                '>=',
                $this->getBeginDate($interval)->format('Y-m-d H:i:s')
            )
        );

        $query->order('notification_start_time', 'desc');

        $records = $query->getQuery()->fetchAll();
        $slots = array();

        foreach ($records as $item) {
            $id = strftime('%Y-%m-%d %H:%I:00', $item->notification_start_time);

            if (empty($slots[$id])) {
                $slots[$id] = 0;
            }

            $slots[$id]++;
        }

        $out = new stdClass();
        if (! empty($slots)) {
            $out->avg = sprintf('%.2f', array_sum($slots) / count($slots));
        } else {
            $out->avg = '0.0';
        }
        $out->last = array_shift($slots);

        return $out;
    }

    /**
     * Problems for notifications
     *
     * @return array
     */
    private function createProblemData()
    {
        $interval = $this->getInterval();

        $query = $this->backend->select()->from(
            'EventHistory',
            array(
                'host_name',
                'service_description',
                'object_type',
                'timestamp',
                'state',
                'attempt',
                'max_attempts',
                'output',
                'type',
                'host',
                'service',
                'service_host_name'
            )
        );

        $query->addFilter(
            new Icinga\Data\Filter\FilterExpression(
                'timestamp',
                '>=',
                $this->getBeginDate($interval)->getTimestamp()
            )
        );

        $query->addFilter(
            new Icinga\Data\Filter\FilterExpression(
                'state',
                '>',
                0
            )
        );

        $defects = array();
        $records = $query->getQuery()->fetchAll();
        $period     = $this->createPeriod($interval);

        foreach ($period as $entry) {
            $id = $this->getPeriodFormat($interval, $entry->getTimestamp());
            $defects[$id] = array($id, 0);
        }

        foreach ($records as $item) {
            $id = $this->getPeriodFormat($interval, $item->timestamp);
            if (empty($defects[$id])) {
                $defects[$id] = array($id, 0);
            }
            $defects[$id][1]++;
        }

        return $defects;
    }

    /**
     * Healing svg image
     *
     * @return GridChart
     */
    public function createHealingChart()
    {
        $gridChart = new GridChart();

        $gridChart->alignTopLeft();
        $gridChart->setAxisLabel('', mt('monitoring', 'Notifications'))
            ->setXAxis(new StaticAxis())
            ->setAxisMin(null, 0)
            ->setYAxis(new LinearUnit(10));

        $interval = $this->getInterval();

        $query = $this->backend->select()->from(
            'notification',
            array(
                'host',
                'service',
                'notification_object_id',
                'notification_output',
                'notification_contact',
                'notification_start_time',
                'notification_state',
                'acknowledgement_entry_time'
            )
        );

        $query->setFilter(
            new Icinga\Data\Filter\FilterExpression(
                'n.start_time',
                '>=',
                $this->getBeginDate($interval)->format('Y-m-d H:i:s')
            )
        );

        $query->order('notification_start_time', 'asc');

        $records    = $query->getQuery()->fetchAll();

        $interval       = $this->getInterval();
        $period         = $this->createPeriod($interval);
        $dAvg           = array();
        $dMax           = array();
        $notifications  = array();
        $rData          = array();

        foreach ($period as $entry) {
            $id = $this->getPeriodFormat($interval, $entry->getTimestamp());
            $dMax[$id] = array($id, 0);
            $dAvg[$id] = array($id, 0, 0);
            $notifications[$id] = array($id, 0);
        }

        foreach ($records as $item) {
            $id = $this->getPeriodFormat($interval, $item->notification_start_time);

            if ($item->notification_state == '0' && isset($rData[$item->notification_object_id])) {
                $rData[$item->notification_object_id]['recover'] =
                    $item->notification_start_time - $rData[$item->notification_object_id]['entry'];
            } elseif ($item->notification_state !== '0') {
                $recover = 0;
                if ($item->acknowledgement_entry_time) {
                    $recover = $item->acknowledgement_entry_time - $item->notification_start_time;
                }
                $rData[$item->notification_object_id] = array(
                    'id'        => $id,
                    'entry'     => $item->notification_start_time,
                    'recover'   => $recover
                );
            }
        }

        foreach ($rData as $item) {
            $notifications[$item['id']][1]++;

            if ($item['recover'] > $dMax[$item['id']][1]) {
                $dMax[$item['id']][1] = (int) $item['recover'];
            }

            $dAvg[$item['id']][1] += (int) $item['recover'];
            $dAvg[$item['id']][2]++;
        }

        foreach ($dAvg as &$item) {
            if ($item[2] > 0) {
                $item[1] = ($item[1]/$item[2])/60/60;
            }
        }

        foreach ($dMax as &$item) {
            $item[1] = $item[1]/60/60;
        }


        $gridChart->drawBars(
            array(
                'label' => $this->translate('Notifications'),
                'color' => '#049baf',
                'data'  =>  $notifications,
                'showPoints' => true
            )
        );

        $gridChart->drawLines(
            array(
                'label' => $this->translate('Avg (min)'),
                'color' => '#ffaa44',
                'data'  =>  $dAvg,
                'showPoints' => true
            )
        );

        $gridChart->drawLines(
            array(
                'label' => $this->translate('Max (min)'),
                'color' => '#ff5566',
                'data'  =>  $dMax,
                'showPoints' => true
            )
        );

        return $gridChart;
    }

    /**
     * Notifications and defects
     *
     * @return GridChart
     */
    public function createDefectImage()
    {
        $gridChart = new GridChart();

        $gridChart->alignTopLeft();
        $gridChart->setAxisLabel('', mt('monitoring', 'Notifications'))
            ->setXAxis(new StaticAxis())
            ->setAxisMin(null, 0)
            ->setYAxis(new LinearUnit(10));

        $gridChart->drawBars(
            array(
                'label' => $this->translate('Notifications'),
                'color' => '#049baf',
                'data'  =>  $this->notificationData,
                'showPoints' => true
            )
        );

        $gridChart->drawLines(
            array(
                'label' => $this->translate('Defects'),
                'color' => '#ff5566',
                'data'  =>  $this->problemData,
                'showPoints' => true
            )
        );

        return $gridChart;
    }

    /**
     * Top recent alerts
     *
     * @return mixed
     */
    private function createRecentAlerts()
    {
        $query = $this->backend->select()->from(
            'notification',
            array(
                'host',
                'service',
                'notification_output',
                'notification_contact',
                'notification_start_time',
                'notification_state'
            )
        );

        $query->order('notification_start_time', 'desc');

        return $query->paginate(5);
    }

    /**
     * Interval selector box
     *
     * @return SelectBox
     */
    private function createIntervalBox()
    {
        $box = new SelectBox(
            'intervalBox',
            array(
                '1d' => mt('monitoring', 'One day'),
                '1w' => mt('monitoring', 'One week'),
                '1m' => mt('monitoring', 'One month'),
                '1y' => mt('monitoring', 'One year')
            ),
            mt('monitoring', 'Report interval'),
            'interval'
        );
        $box->applyRequest($this->getRequest());
        return $box;
    }

    /**
     * Return reasonable date time format for an interval
     *
     * @param   string $interval
     * @param   string $timestamp
     *
     * @return  string
     */
    private function getPeriodFormat($interval, $timestamp)
    {
        $format = '';
        if ($interval === '1d') {
            $format = '%H:00:00';
        } elseif ($interval === '1w') {
            $format = '%Y-%m-%d';
        } elseif ($interval === '1m') {
            $format = '%Y-%m-%d';
        } elseif ($interval === '1y') {
            $format = '%Y-%m';
        }

        return strftime($format, $timestamp);
    }

    /**
     * Create a reasonable period based in interval strings
     *
     * @param $interval
     * @return DatePeriod
     */
    private function createPeriod($interval)
    {
        if ($interval === '1d') {
            return new DatePeriod($this->getBeginDate($interval), new DateInterval('PT1H'), 24);
        } elseif ($interval === '1w') {
            return new DatePeriod($this->getBeginDate($interval), new DateInterval('P1D'), 7);
        } elseif ($interval === '1m') {
            return new DatePeriod($this->getBeginDate($interval), new DateInterval('P1D'), 30);
        } elseif ($interval === '1y') {
            return new DatePeriod($this->getBeginDate($interval), new DateInterval('P1M'), 12);
        }
    }

    /**
     * Return start timestamps based on interval strings
     *
     * @param $interval
     * @return DateTime|null
     */
    private function getBeginDate($interval)
    {
        $new = new DateTime();
        if ($interval === '1d') {
            return $new->sub(new DateInterval('P1D'));
        } elseif ($interval === '1w') {
            return $new->sub(new DateInterval('P1W'));
        } elseif ($interval === '1m') {
            return $new->sub(new DateInterval('P1M'));
        } elseif ($interval === '1y') {
            return $new->sub(new DateInterval('P1Y'));
        }

        return null;
    }

    /**
     * Getter for interval
     *
     * @return string
     *
     * @throws Zend_Controller_Action_Exception
     */
    private function getInterval()
    {
        $interval = $this->getParam('interval', '1d');
        if (false === in_array($interval, array('1d', '1w', '1m', '1y'))) {
            throw new Zend_Controller_Action_Exception($this->translate('Value for interval not valid'));
        }

        return $interval;
    }
}

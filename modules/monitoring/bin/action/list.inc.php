<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use \Icinga\Module\Monitoring\Backend;
use Icinga\Util\Format;

$backend = Backend::getInstance($params->shift('backend'));

$query = $backend->select()->from('status', array(
    'host_name',
    'host_state',
    'host_output',
    'host_acknowledged',
    'host_in_downtime',
    'service_description',
    'service_state',
    'service_acknowledged',
    'service_in_downtime',
    'service_handled',
    'service_output',
    'service_last_state_change'
))->order('service_last_state_change ASC');

$endless = $params->shift('endless');
$query->applyFilters($params->getParams());
$host_colors = array(
    0 => '2', // UP
    1 => '1', // DOWN
    2 => '3', // UNREACH (brown)
    99 => '0', // PEND
);
$host_states = array(
    0 => 'UP', // UP
    1 => 'DOWN', // DOWN
    2 => 'UNREACHABLE', // UNREACH (brown)
    99 => 'PENDING', // PEND
);
$service_colors = array(
    0 => '2', // OK
    1 => '3', // WARN
    2 => '1', // CRIT
    3 => '5', // UNKN
    99 => '0', // PEND
);
$service_states = array(
    0 => 'OK', // OK
    1 => 'WARNING', // WARN
    2 => 'CRITICAL', // CRIT
    3 => 'UNKNOWN', // UNKN
    99 => 'PENDING', // PEND
);

$finished = false;
while (! $finished) {
$out = '';
$last_host = null;

foreach ($query->fetchAll() as $key => $row) {
    $host_extra = array();
    if ($row->host_in_downtime) {
        $host_extra[] = 'DOWNTIME';
    }
    if ($row->host_acknowledged) {
        $host_extra[] = 'ACK';
    }
    if (empty($host_extra)) {
        $host_extra = '';
    } else {
        $host_extra = " \033[34;1m[" . implode(',', $host_extra) . "]\033[0m";
    }

    $service_extra = array();
    if ($row->service_in_downtime) {
        $service_extra[] = 'DOWNTIME';
    }
    if ($row->service_acknowledged) {
        $service_extra[] = 'ACK';
    }
    if (empty($service_extra)) {
        $service_extra = '';
    } else {
        $service_extra = " \033[34;52;1m[" . implode(',', $service_extra) . "]\033[0m";
    }

    if ($row->host_name !== $last_host) {
        $out .= sprintf(
            "\n\033[01;37;4%dm %-5s \033[0m \033[30;1m%s\033[0m%s: %s\n",
            $host_colors[$row->host_state],
            substr($host_states[$row->host_state], 0, 5),
            $row->host_name,
            $host_extra,
            $row->host_output
        );
    }
    $last_host = $row->host_name;
    $out .= sprintf(
        "\033[01;37;4%dm \033[01;37;4%dm %4s \033[0m %s%s since %s: %s\n",
        $host_colors[$row->host_state],
        $service_colors[$row->service_state],
        substr($service_states[$row->service_state] . ' ', 0, 4),
        $row->service_description,
        $service_extra,
        Format::timeSince($row->service_last_state_change),
        preg_replace('/\n/', sprintf(
            "\n\033[01;37;4%dm \033[01;37;4%dm      \033[0m  ",
        $host_colors[$row->host_state],
        $service_colors[$row->service_state]

        ), substr(wordwrap(str_repeat(' ', 30) . preg_replace('~\@{3,}~', '@@@', $row->service_output), 72), 30))
    );
}

$out .= "\n";

    if ($endless) {
        echo "\033[2J\033[1;1H\033[1S" . $out;
        sleep(3);
    } else {
        echo $out;
        $finished = true;
    }
}

<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Filter;

use Icinga\Application\Icinga;
use Icinga\Application\Logger;
use Icinga\Filter\Domain;
use Icinga\Filter\FilterAttribute;
use Icinga\Filter\Query\Node;
use Icinga\Filter\Query\Tree;
use Icinga\Filter\Type\BooleanFilter;
use Icinga\Filter\Type\TextFilter;
use Icinga\Filter\Type\TimeRangeSpecifier;
use Icinga\Module\Monitoring\DataView\HostStatus;
use Icinga\Module\Monitoring\DataView\ServiceStatus;
use Icinga\Module\Monitoring\Filter\Type\StatusFilter;
use Icinga\Filter\Registry as FilterRegistry;
use Icinga\Module\Monitoring\Object\Host;
use Icinga\Web\Request;
use Zend_Controller_Request_Exception;
use Icinga\Web\Url;

/**
 * Factory class to create filter for different monitoring objects
 *
 */
class Registry implements FilterRegistry
{
    /**
     * Return a TimeRangeSpecifier for the 'Next Check' query
     *
     * @return TimeRangeSpecifier
     */
    public static function getNextCheckFilterType()
    {
        $type = new TimeRangeSpecifier();
        $type->setOperator(
            array(
                'Until' => Node::OPERATOR_LESS_EQ,
                'After' => Node::OPERATOR_GREATER_EQ
            )
        )->setForceFutureValue(true);
        return $type;
    }

    /**
     * Return a TimeRangeSpecifier for the 'Last Check' query
     *
     * @return TimeRangeSpecifier
     */
    public static function getLastCheckFilterType()
    {
        $type = new TimeRangeSpecifier();
        $type->setOperator(
            array(
                'Older Than' => Node::OPERATOR_LESS_EQ,
                'Is Older Than' => Node::OPERATOR_LESS_EQ,
                'Newer Than' => Node::OPERATOR_GREATER_EQ,
                'Is Newer Than' => Node::OPERATOR_GREATER_EQ,
            )
        )->setForcePastValue(true);
        return $type;
    }

    /**
     * Registry function for the host domain
     *
     * @return Domain the domain to use in the filter registry
     */
    public static function hostFilter()
    {
        $domain = new Domain('Host');

        $domain->registerAttribute(
            FilterAttribute::create(new TextFilter())
                ->setHandledAttributes('Name', 'Host', 'Hostname')
                ->setField('host_name')
        )->registerAttribute(
            FilterAttribute::create(StatusFilter::createForHost())
                ->setField('host_state')
            )->registerAttribute(
                FilterAttribute::create(
                    new BooleanFilter(
                        array(
                            'host_is_flapping' => 'Flapping',
                            'host_problem' => 'In Problem State',
                            'host_notifications_enabled' => 'Sending Notifications',
                            'host_active_checks_enabled' => 'Active',
                            'host_passive_checks_enabled' => 'Accepting Passive Checks',
                            'host_handled' => 'Handled',
                            'host_in_downtime' => 'In Downtime',
                        )
                    )
                )
            )->registerAttribute(
                FilterAttribute::create(self::getLastCheckFilterType())
                    ->setHandledAttributes('Last Check', 'Check')
                    ->setField('host_last_check')
            )->registerAttribute(
                FilterAttribute::create(self::getNextCheckFilterType())
                    ->setHandledAttributes('Next Check')
                    ->setField('host_next_check')
            );
        return $domain;
    }

    /**
     * Registry function for the service domain
     *
     * @return Domain the domain to use in the filter registry
     */
    public static function serviceFilter()
    {
        $domain = new Domain('Service');

        $domain->registerAttribute(
            FilterAttribute::create(new TextFilter())
                ->setHandledAttributes('Name', 'Servicename')
                ->setField('service_description')
        )->registerAttribute(
            FilterAttribute::create(StatusFilter::createForService())
                ->setField('service_state')
            )->registerAttribute(
                FilterAttribute::create(StatusFilter::createForHost())
                    ->setHandledAttributes('Host')
                    ->setField('host_state')
            )->registerAttribute(
                FilterAttribute::create(
                    new BooleanFilter(
                        array(
                            'service_is_flapping' => 'Flapping',
                            'service_problem' => 'In Problem State',
                            'service_notifications_enabled' => 'Sending Notifications',
                            'service_active_checks_enabled' => 'Active',
                            'service_passive_checks_enabled' => 'Accepting Passive Checks',
                            'service_handled' => 'Handled',
                            'service_in_downtime' => 'In Downtime',
                            'host_in_downtime' => 'In Host Downtime'
                        )
                    )
                )
            )->registerAttribute(
                FilterAttribute::create(self::getLastCheckFilterType())
                    ->setHandledAttributes('Last Check', 'Check')
                    ->setField('service_last_check')
            )->registerAttribute(
                FilterAttribute::create(self::getNextCheckFilterType())
                    ->setHandledAttributes('Next Check')
                    ->setField('service_next_check')
            )->registerAttribute(
                FilterAttribute::create(new TextFilter())
                    ->setHandledAttributes('Hostname', 'Host')
                    ->setField('host_name')
            );
        return $domain;
    }

    /**
     * Resolve the given filter to an url, using the referer as the base url and base filter
     *
     * @param $domain           The domain to filter for
     * @param Tree $filter      The tree representing the fiter
     *
     * @return string           An url
     * @throws Zend_Controller_Request_Exception    Called if no referer is available
     */
    public static function getUrlForTarget($domain, Tree $filter)
    {
        if (!isset($_SERVER['HTTP_REFERER'])) {
            throw new Zend_Controller_Request_Exception('You can\'t use this method without an referer');
        }
        $request = Icinga::app()->getFrontController()->getRequest();
        switch ($domain) {
            case 'host':
                $view = HostStatus::fromRequest($request);
                break;
            case 'service':
                $view = ServiceStatus::fromRequest($request);
                break;
            default:
                Logger::error('Invalid filter domain requested : %s', $domain);
                throw new Exception('Unknown Domain ' . $domain);
        }
        $urlParser = new UrlViewFilter($view);
        $lastQuery = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_QUERY);
        $lastPath = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        $lastFilter = $urlParser->parseUrl($lastQuery);
        $lastParameters = array();

        parse_str($lastQuery, $lastParameters);
        if ($lastFilter->root) {
            $filter->insertTree($lastFilter);
        }
        $params = array();
        foreach ($lastParameters as $key => $param) {
            if (!$filter->hasNodeWithAttribute($key) && $view->isValidFilterTarget($key)) {
                $params[$key] = $param;
            }
        }

        $baseUrl = Url::fromPath($lastPath, $params);
        $urlString = $baseUrl->getRelativeUrl();
        if (stripos($urlString, '?') === false) {
            $urlString .= '?';
        } else {
            $urlString .= '&';
        }
        $urlString .= $urlParser->fromTree($filter);
        return '/' . $urlString;
    }

    public function isValid($query)
    {

    }
}

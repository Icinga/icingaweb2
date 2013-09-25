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

use Icinga\Filter\Domain;
use Icinga\Filter\FilterAttribute;
use Icinga\Filter\Query\Node;
use Icinga\Filter\Type\BooleanFilter;
use Icinga\Filter\Type\TextFilter;
use Icinga\Filter\Type\TimeRangeSpecifier;
use Icinga\Module\Monitoring\Filter\Type\StatusFilter;

/**
 * Factory class to create filter for different monitoring objects
 *
 */
class MonitoringFilter
{


    private static function getNextCheckFilterType()
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

    private static function getLastCheckFilterType()
    {
        $type = new TimeRangeSpecifier();
        $type->setOperator(
            array(
                'Older Than'    => Node::OPERATOR_LESS_EQ,
                'Is Older Than' => Node::OPERATOR_LESS_EQ,
                'Newer Than'    => Node::OPERATOR_GREATER_EQ,
                'Is Newer Than' => Node::OPERATOR_GREATER_EQ,
            )
        )->setForcePastValue(true);
        return $type;
    }

    public static function hostFilter()
    {
        $domain = new Domain('Host');

        $domain->registerAttribute(
            FilterAttribute::create(new TextFilter())
                ->setHandledAttributes('Name', 'Hostname')
                ->setField('host_name')
        )->registerAttribute(
            FilterAttribute::create(StatusFilter::createForHost())
                ->setHandledAttributes('State', 'Status', 'Current Status')
                ->setField('host_state')
        )->registerAttribute(
            FilterAttribute::create(new BooleanFilter(array(
                'host_is_flapping'              => 'Flapping',
                'host_problem'                  => 'In Problem State',
                'host_notifications_enabled'    => 'Sending Notifications',
                'host_active_checks_enabled'    => 'Active',
                'host_passive_checks_enabled'   => 'Accepting Passive Checks',
                'host_handled'                  => 'Handled',
                'host_in_downtime'              => 'In Downtime',
            )))
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

}
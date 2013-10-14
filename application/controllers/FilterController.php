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
// @codingStandardsIgnoreStart

use Icinga\Web\Form;
use Icinga\Web\Controller\ActionController;
use Icinga\Filter\Filter;
use Icinga\Filter\FilterAttribute;
use Icinga\Filter\Type\TextFilter;
use Icinga\Application\Logger;
use Icinga\Module\Monitoring\Filter\Type\StatusFilter;
use Icinga\Module\Monitoring\Filter\UrlViewFilter;
use Icinga\Module\Monitoring\DataView\HostStatus;
use Icinga\Web\Url;

/**
 * Application wide interface for filtering
 */
class FilterController extends ActionController
{
    /**
     * The current filter registry
     *
     * @var Filter
     */
    private $registry;

    /**
     * Entry point for filtering, uses the filter_domain and filter_module request parameter
     * to determine which filter registry should be used
     */
    public function indexAction()
    {
        $this->registry = new Filter();

        if ($this->getRequest()->getHeader('accept') == 'application/json') {
            $this->getResponse()->setHeader('Content-Type', 'application/json');

            $this->setupQueries(
                $this->getParam('filter_domain', ''),
                $this->getParam('filter_module', '')
            );

            $this->_helper->json($this->parse($this->getRequest()->getParam('query', '')));
        } else {
            $this->redirect('index/welcome');
        }
    }

    /**
     * Set up the query handler for the given domain and module
     *
     * @param string $domain    The domain to use
     * @param string $module    The module to use
     */
    private function setupQueries($domain, $module = 'default')
    {
        $class = '\\Icinga\\Module\\' . ucfirst($module) . '\\Filter\\Registry';
        $factory = strtolower($domain) . 'Filter';
        $this->registry->addDomain($class::$factory());
    }

    /**
     * Parse the given query text and returns the json as expected by the semantic search box
     *
     * @param  String $text     The query to parse
     * @return array            The result structure to be returned in json format
     */
    private function parse($text)
    {
        try {
            $view = HostStatus::fromRequest($this->getRequest());
            $urlParser = new UrlViewFilter($view);
            $queryTree = $this->registry->createQueryTreeForFilter($text);

            return array(
                'state'     => 'success',
                'proposals' => $this->registry->getProposalsForQuery($text),
                'urlParam'  => $urlParser->fromTree($queryTree)
            );
        } catch (\Exception $exc) {
            Logger::error($exc);
            $this->getResponse()->setHttpResponseCode(500);
            return array(
                'state'     => 'error',
                'message'   => 'Search service is currently not available'
            );
        }
    }
}
// @codingStandardsIgnoreEnd

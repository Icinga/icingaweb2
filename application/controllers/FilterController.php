<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}
// @codingStandardsIgnoreStart

use Icinga\Web\Form;
use Icinga\Web\Controller\ActionController;
use Icinga\Filter\Filter;
use Icinga\Filter\FilterAttribute;
use Icinga\Filter\Type\TextFilter;
use Icinga\Application\Logger;
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

    private $moduleRegistry;

    /**
     * Entry point for filtering, uses the filter_domain and filter_module request parameter
     * to determine which filter registry should be used
     */
    public function indexAction()
    {
        $this->registry = new Filter();
        $query = $this->getRequest()->getParam('query', '');
        $target = $this->getRequest()->getParam('filter_domain', '');

        if ($this->getRequest()->getHeader('accept') == 'application/json') {
            $this->getResponse()->setHeader('Content-Type', 'application/json');
            $this->setupQueries(
                $target,
                $this->getParam('filter_module', '')
            );
            $this->_helper->json($this->parse($query, $target));
        } else {
            $this->setupQueries(
                $target,
                $this->getParam('filter_module')
            );
            $urlTarget = $this->parse($query, $target);
            $this->redirect($urlTarget['urlParam']);
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
        $this->moduleRegistry = $class;
        $this->registry->addDomain($class::$factory());
    }

    /**
     * Parse the given query text and returns the json as expected by the semantic search box
     *
     * @param  String $text     The query to parse
     * @return array            The result structure to be returned in json format
     */
    private function parse($text, $target)
    {
        try {

            $queryTree = $this->registry->createQueryTreeForFilter($text);
            $registry = $this->moduleRegistry;
            return array(
                'state'     => 'success',
                'proposals' => $this->registry->getProposalsForQuery($text),
                'urlParam'  => $registry::getUrlForTarget($target, $queryTree),
                'valid'     => count($this->registry->getIgnoredQueryParts()) === 0
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

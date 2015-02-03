<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Web\Controller\ActionController;
use Icinga\Filter\Filter;
use Icinga\Application\Logger;

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

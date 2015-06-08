<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web;

use LogicException;
use InvalidArgumentException;
use Icinga\Web\Session\SessionNamespace;
use Icinga\Web\Form\Decorator\ElementDoubler;

/**
 * Container and controller for form based wizards
 */
class Wizard
{
    /**
     * An integer describing the wizard's forward direction
     */
    const FORWARD = 0;

    /**
     * An integer describing the wizard's backward direction
     */
    const BACKWARD = 1;

    /**
     * An integer describing that the wizard does not change its position
     */
    const NO_CHANGE = 2;

    /**
     * The name of the button to advance the wizard's position
     */
    const BTN_NEXT = 'btn_next';

    /**
     * The name of the button to rewind the wizard's position
     */
    const BTN_PREV = 'btn_prev';

    /**
     * This wizard's parent
     *
     * @var Wizard
     */
    protected $parent;

    /**
     * The name of the wizard's current page
     *
     * @var string
     */
    protected $currentPage;

    /**
     * The pages being part of this wizard
     *
     * @var array
     */
    protected $pages = array();

    /**
     * Initialize a new wizard
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Run additional initialization routines
     *
     * Should be implemented by subclasses to add pages to the wizard.
     */
    protected function init()
    {

    }

    /**
     * Return this wizard's parent or null in case it has none
     *
     * @return  Wizard|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set this wizard's parent
     *
     * @param   Wizard  $wizard     The parent wizard
     *
     * @return  $this
     */
    public function setParent(Wizard $wizard)
    {
        $this->parent = $wizard;
        return $this;
    }

    /**
     * Return the pages being part of this wizard
     *
     * In case this is a nested wizard a flattened array of all contained pages is returned.
     *
     * @return  array
     */
    public function getPages()
    {
        $pages = array();
        foreach ($this->pages as $page) {
            if ($page instanceof self) {
                $pages = array_merge($pages, $page->getPages());
            } else {
                $pages[] = $page;
            }
        }

        return $pages;
    }

    /**
     * Return the page with the given name
     *
     * Note that it's also possible to retrieve a nested wizard's page by using this method.
     *
     * @param   string      $name   The name of the page to return
     *
     * @return  null|Form           The page or null in case there is no page with the given name
     */
    public function getPage($name)
    {
        foreach ($this->getPages() as $page) {
            if ($name === $page->getName()) {
                return $page;
            }
        }
    }

    /**
     * Add a new page or wizard to this wizard
     *
     * @param   Form|Wizard     $page   The page or wizard to add to the wizard
     *
     * @return  $this
     */
    public function addPage($page)
    {
        if (! $page instanceof Form && ! $page instanceof self) {
            throw InvalidArgumentException(
                'The $page argument must be an instance of Icinga\Web\Form '
                . 'or Icinga\Web\Wizard but is of type: ' . get_class($page)
            );
        } elseif ($page instanceof self) {
            $page->setParent($this);
        }

        $this->pages[] = $page;
        return $this;
    }

    /**
     * Add multiple pages or wizards to this wizard
     *
     * @param   array   $pages      The pages or wizards to add to the wizard
     *
     * @return  $this
     */
    public function addPages(array $pages)
    {
        foreach ($pages as $page) {
            $this->addPage($page);
        }

        return $this;
    }

    /**
     * Assert that this wizard has any pages
     *
     * @throws  LogicException      In case this wizard has no pages
     */
    protected function assertHasPages()
    {
        $pages = $this->getPages();
        if (count($pages) < 2) {
            throw new LogicException("Although Chuck Norris can advance a wizard with less than two pages, you can't.");
        }
    }

    /**
     * Return the current page of this wizard
     *
     * @return  Form
     *
     * @throws  LogicException      In case the name of the current page currently being set is invalid
     */
    public function getCurrentPage()
    {
        if ($this->parent) {
            return $this->parent->getCurrentPage();
        }

        if ($this->currentPage === null) {
            $this->assertHasPages();
            $pages = $this->getPages();
            $this->currentPage = $this->getSession()->get('current_page', $pages[0]->getName());
        }

        if (($page = $this->getPage($this->currentPage)) === null) {
            throw new LogicException(sprintf('No page found with name "%s"', $this->currentPage));
        }

        return $page;
    }

    /**
     * Set the current page of this wizard
     *
     * @param   Form    $page   The page to set as current page
     *
     * @return  $this
     */
    public function setCurrentPage(Form $page)
    {
        $this->currentPage = $page->getName();
        $this->getSession()->set('current_page', $this->currentPage);
        return $this;
    }

    /**
     * Setup the given page that is either going to be displayed or validated
     *
     * Implement this method in a subclass to populate default values and/or other data required to process the form.
     *
     * @param   Form        $page       The page to setup
     * @param   Request     $request    The current request
     */
    public function setupPage(Form $page, Request $request)
    {

    }

    /**
     * Process the given request using this wizard
     *
     * Validate the request data using the current page, update the wizard's
     * position and redirect to the page's redirect url upon success.
     *
     * @param   Request     $request    The request to be processed
     *
     * @return  Request                 The request supposed to be processed
     */
    public function handleRequest(Request $request = null)
    {
        $page = $this->getCurrentPage();

        if (($wizard = $this->findWizard($page)) !== null) {
            return $wizard->handleRequest($request);
        }

        if ($request === null) {
            $request = $page->getRequest();
        }

        $this->setupPage($page, $request);
        $requestData = $this->getRequestData($page, $request);
        if ($page->wasSent($requestData)) {
            if (($requestedPage = $this->getRequestedPage($requestData)) !== null) {
                $isValid = false;
                $direction = $this->getDirection($request);
                if ($direction === static::FORWARD && $page->isValid($requestData)) {
                    $isValid = true;
                    if ($this->isLastPage($page)) {
                        $this->setIsFinished();
                    }
                } elseif ($direction === static::BACKWARD) {
                    $page->populate($requestData);
                    $isValid = true;
                }

                if ($isValid) {
                    $pageData = & $this->getPageData();
                    $pageData[$page->getName()] = $page->getValues();
                    $this->setCurrentPage($this->getNewPage($requestedPage, $page));
                    $page->getResponse()->redirectAndExit($page->getRedirectUrl());
                }
            } else {
                $page->isValidPartial($requestData);
            }
        } elseif (($pageData = $this->getPageData($page->getName())) !== null) {
            $page->populate($pageData);
        }

        return $request;
    }

    /**
     * Return the wizard for the given page or null if its not part of a wizard
     *
     * @param   Form    $page   The page to return its wizard for
     *
     * @return  Wizard|null
     */
    protected function findWizard(Form $page)
    {
        foreach ($this->getWizards() as $wizard) {
            if ($wizard->getPage($page->getName()) === $page) {
                return $wizard;
            }
        }
    }

    /**
     * Return this wizard's child wizards
     *
     * @return  array
     */
    protected function getWizards()
    {
        $wizards = array();
        foreach ($this->pages as $pageOrWizard) {
            if ($pageOrWizard instanceof self) {
                $wizards[] = $pageOrWizard;
            }
        }

        return $wizards;
    }

    /**
     * Return the request data based on given form's request method
     *
     * @param   Form        $page       The page to fetch the data for
     * @param   Request     $request    The request to fetch the data from
     *
     * @return  array
     */
    protected function getRequestData(Form $page, Request $request)
    {
        if (strtolower($request->getMethod()) === $page->getMethod()) {
            return $request->{'get' . ($request->isPost() ? 'Post' : 'Query')}();
        }

        return array();
    }

    /**
     * Return the name of the requested page
     *
     * @param   array   $requestData    The request's data
     *
     * @return  null|string             The name of the requested page or null in case no page has been requested
     */
    protected function getRequestedPage(array $requestData)
    {
        if ($this->parent) {
            return $this->parent->getRequestedPage($requestData);
        }

        if (isset($requestData[static::BTN_NEXT])) {
            return $requestData[static::BTN_NEXT];
        } elseif (isset($requestData[static::BTN_PREV])) {
            return $requestData[static::BTN_PREV];
        }
    }

    /**
     * Return the direction of this wizard using the given request
     *
     * @param   Request     $request    The request to use
     *
     * @return  int                     The direction @see Wizard::FORWARD @see Wizard::BACKWARD @see Wizard::NO_CHANGE
     */
    protected function getDirection(Request $request = null)
    {
        if ($this->parent) {
            return $this->parent->getDirection($request);
        }

        $currentPage = $this->getCurrentPage();

        if ($request === null) {
            $request = $currentPage->getRequest();
        }

        $requestData = $this->getRequestData($currentPage, $request);
        if (isset($requestData[static::BTN_NEXT])) {
            return static::FORWARD;
        } elseif (isset($requestData[static::BTN_PREV])) {
            return static::BACKWARD;
        }

        return static::NO_CHANGE;
    }

    /**
     * Return the new page to set as current page
     *
     * Permission is checked by verifying that the requested page or its previous page has page data available.
     * The requested page is automatically permitted without any checks if the origin page is its previous
     * page or one that occurs later in order.
     *
     * @param   string  $requestedPage      The name of the requested page
     * @param   Form    $originPage         The origin page
     *
     * @return  Form                        The new page
     *
     * @throws  InvalidArgumentException    In case the requested page does not exist or is not permitted yet
     */
    protected function getNewPage($requestedPage, Form $originPage)
    {
        if ($this->parent) {
            return $this->parent->getNewPage($requestedPage, $originPage);
        }

        if (($page = $this->getPage($requestedPage)) !== null) {
            $permitted = true;

            $pages = $this->getPages();
            if (! $this->hasPageData($requestedPage) && ($index = array_search($page, $pages, true)) > 0) {
                $previousPage = $pages[$index - 1];
                if ($originPage === null || ($previousPage->getName() !== $originPage->getName()
                    && array_search($originPage, $pages, true) < $index))
                {
                    $permitted = $this->hasPageData($previousPage->getName());
                }
            }

            if ($permitted) {
                return $page;
            }
        }

        throw new InvalidArgumentException(
            sprintf('"%s" is either an unknown page or one you are not permitted to view', $requestedPage)
        );
    }

    /**
     * Return the next or previous page based on the given one
     *
     * @param   Form    $page   The page to skip
     *
     * @return  Form
     */
    protected function skipPage(Form $page)
    {
        if ($this->parent) {
            return $this->parent->skipPage($page);
        }

        if ($this->hasPageData($page->getName())) {
            $pageData = & $this->getPageData();
            unset($pageData[$page->getName()]);
        }

        $pages = $this->getPages();
        if ($this->getDirection() === static::FORWARD) {
            $nextPage = $pages[array_search($page, $pages, true) + 1];
            $newPage = $this->getNewPage($nextPage->getName(), $page);
        } else { // $this->getDirection() === static::BACKWARD
            $previousPage = $pages[array_search($page, $pages, true) - 1];
            $newPage = $this->getNewPage($previousPage->getName(), $page);
        }

        return $newPage;
    }

    /**
     * Return whether the given page is this wizard's last page
     *
     * @param   Form    $page   The page to check
     *
     * @return  bool
     */
    protected function isLastPage(Form $page)
    {
        if ($this->parent) {
            return $this->parent->isLastPage($page);
        }

        $pages = $this->getPages();
        return $page->getName() === end($pages)->getName();
    }

    /**
     * Return whether all of this wizard's pages were visited by the user
     *
     * The base implementation just verifies that the very last page has page data available.
     *
     * @return  bool
     */
    public function isComplete()
    {
        $pages = $this->getPages();
        return $this->hasPageData($pages[count($pages) - 1]->getName());
    }

    /**
     * Set whether this wizard has been completed
     *
     * @param   bool    $state      Whether this wizard has been completed
     *
     * @return  $this
     */
    public function setIsFinished($state = true)
    {
        $this->getSession()->set('isFinished', $state);
        return $this;
    }

    /**
     * Return whether this wizard has been completed
     *
     * @return  bool
     */
    public function isFinished()
    {
        return $this->getSession()->get('isFinished', false);
    }

    /**
     * Return the overall page data or one for a particular page
     *
     * Note that this method returns by reference so in order to update the
     * returned array set this method's return value also by reference.
     *
     * @param   string  $pageName   The page for which to return the data
     *
     * @return  array
     */
    public function & getPageData($pageName = null)
    {
        $session = $this->getSession();

        if (false === isset($session->page_data)) {
            $session->page_data = array();
        }

        $pageData = & $session->getByRef('page_data');
        if ($pageName !== null) {
            $data = null;
            if (isset($pageData[$pageName])) {
                $data = & $pageData[$pageName];
            }

            return $data;
        }

        return $pageData;
    }

    /**
     * Return whether there is any data for the given page
     *
     * @param   string  $pageName   The name of the page to check
     *
     * @return  bool
     */
    public function hasPageData($pageName)
    {
        return $this->getPageData($pageName) !== null;
    }

    /**
     * Return a session to be used by this wizard
     *
     * @return  SessionNamespace
     */
    public function getSession()
    {
        if ($this->parent) {
            return $this->parent->getSession();
        }

        return Session::getSession()->getNamespace(get_class($this));
    }

    /**
     * Clear the session being used by this wizard
     */
    public function clearSession()
    {
        $this->getSession()->clear();
    }

    /**
     * Add buttons to the given page based on its position in the page-chain
     *
     * @param   Form    $page   The page to add the buttons to
     */
    protected function addButtons(Form $page)
    {
        $pages = $this->getPages();
        $index = array_search($page, $pages, true);
        if ($index === 0) {
            $page->addElement(
                'button',
                static::BTN_NEXT,
                array(
                    'type'          => 'submit',
                    'value'         => $pages[1]->getName(),
                    'label'         => t('Next'),
                    'decorators'    => array('ViewHelper')
                )
            );
        } elseif ($index < count($pages) - 1) {
            $page->addElement(
                'button',
                static::BTN_PREV,
                array(
                    'type'              => 'submit',
                    'value'             => $pages[$index - 1]->getName(),
                    'label'             => t('Back'),
                    'decorators'        => array('ViewHelper'),
                    'formnovalidate'    => 'formnovalidate'
                )
            );
            $page->addElement(
                'button',
                static::BTN_NEXT,
                array(
                    'type'          => 'submit',
                    'value'         => $pages[$index + 1]->getName(),
                    'label'         => t('Next'),
                    'decorators'    => array('ViewHelper')
                )
            );
        } else {
            $page->addElement(
                'button',
                static::BTN_PREV,
                array(
                    'type'              => 'submit',
                    'value'             => $pages[$index - 1]->getName(),
                    'label'             => t('Back'),
                    'decorators'        => array('ViewHelper'),
                    'formnovalidate'    => 'formnovalidate'
                )
            );
            $page->addElement(
                'button',
                static::BTN_NEXT,
                array(
                    'type'          => 'submit',
                    'value'         => $page->getName(),
                    'label'         => t('Finish'),
                    'decorators'    => array('ViewHelper')
                )
            );
        }

        $page->addDisplayGroup(
            array(static::BTN_PREV, static::BTN_NEXT),
            'buttons',
            array(
                'decorators' => array(
                    'FormElements',
                    new ElementDoubler(array(
                        'double'        => static::BTN_NEXT,
                        'condition'     => static::BTN_PREV,
                        'placement'     => ElementDoubler::PREPEND,
                        'attributes'    => array('tabindex' => -1, 'class' => 'double')
                    )),
                    array('HtmlTag', array('tag' => 'div', 'class' => 'buttons'))
                )
            )
        );
    }

    /**
     * Return the current page of this wizard with appropriate buttons being added
     *
     * @return  Form
     */
    public function getForm()
    {
        $form = $this->getCurrentPage();
        $form->create(); // Make sure that buttons are displayed at the very bottom
        $this->addButtons($form);
        return $form;
    }

    /**
     * Return the current page of this wizard rendered as HTML
     *
     * @return  string
     */
    public function __toString()
    {
        return (string) $this->getForm();
    }
}

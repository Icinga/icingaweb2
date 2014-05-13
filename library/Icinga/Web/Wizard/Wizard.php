<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Wizard;

use Zend_Config;
use Icinga\Web\Form;
use Icinga\Exception\ProgrammingError;

class Wizard extends Page
{
    /**
     * Whether this wizard has been completed
     *
     * @var bool
     */
    protected $finished = false;

    /**
     * The wizard pages
     *
     * @var array
     */
    protected $pages = array();

    /**
     * Return the wizard pages
     *
     * @return  array
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Add a new page to this wizard
     *
     * @param   Page    $page   The page to add
     */
    public function addPage(Page $page)
    {
        $ident = $this->generatePageIdentifier($page);
        $wizardConfig = $this->getConfig();
        if ($wizardConfig->get($ident) === null) {
            $wizardConfig->{$ident} = new Zend_Config(array(), true);
        }

        $page->setTokenDisabled(); // Usually default for pages, but not for wizards
        $page->setConfiguration($wizardConfig->{$ident});
        $page->setRequest($this->getRequest());
        $page->setName($ident);
        $this->pages[] = $page;
    }

    /**
     * Add multiple pages to this wizard
     *
     * The given array's keys are titles and its values are class names to add
     * as wizard pages. An array as value causes a sub-wizard being added.
     *
     * @param   array   $pages      The pages to add to the wizard
     */
    public function addPages(array $pages)
    {
        foreach ($pages as $title => $pageClassOrArray) {
            if (is_array($pageClassOrArray)) {
                $wizard = new static();
                $wizard->setTitle($title);
                $this->addPage($wizard);
                $wizard->addPages($pageClassOrArray);
            } elseif (is_string($pageClassOrArray)) {
                $page = new $pageClassOrArray();
                $page->setTitle($title);
                $this->addPage($page);
            } else {
                $pageClassOrArray->setTitle($title);
                $this->addPage($pageClassOrArray);
            }
        }
    }

    /**
     * Return this wizard's progress
     *
     * @param   int     $default    The step to return in case this wizard has no progress information yet
     *
     * @return  int                 The current step
     */
    public function getProgress($default = 0)
    {
        return $this->getConfig()->get('progress', $default);
    }

    /**
     * Set this wizard's progress
     *
     * @param   int     $step   The current step
     */
    public function setProgress($step)
    {
        $config = $this->getConfig();
        $config->progress = $step;
    }

    /**
     * Return the current page
     *
     * @return  Page
     *
     * @throws  ProgrammingError    In case there are not any pages registered
     */
    public function getCurrentPage()
    {
        $pages = $this->getPages();

        if (empty($pages)) {
            throw new ProgrammingError('This wizard has no pages');
        }

        return $pages[$this->getProgress()];
    }

    /**
     * Return whether the given page is the current page
     *
     * @param   Page    $page       The page to check
     *
     * @return  bool
     */
    public function isCurrentPage(Page $page)
    {
        return $this->getCurrentPage() === $page;
    }

    /**
     * Return whether the given page is the first page in the wizard
     *
     * @param   Page    $page       The page to check
     *
     * @return  bool
     *
     * @throws  ProgrammingError    In case there are not any pages registered
     */
    public function isFirstPage(Page $page)
    {
        $pages = $this->getPages();

        if (empty($pages)) {
            throw new ProgrammingError('This wizard has no pages');
        }

        return $pages[0] === $page;
    }

    /**
     * Return whether the given page has been completed
     *
     * @param   Page    $page       The page to check
     *
     * @return  bool
     *
     * @throws  ProgrammingError    In case there are not any pages registered
     */
    public function isCompletedPage(Page $page)
    {
        $pages = $this->getPages();

        if (empty($pages)) {
            throw new ProgrammingError('This wizard has no pages');
        }

        return $this->isFinished() || array_search($page, $pages, true) < $this->getProgress();
    }

    /**
     * Return whether the given page is the last page in the wizard
     *
     * @param   Page    $page       The page to check
     *
     * @return  bool
     *
     * @throws  ProgrammingError    In case there are not any pages registered
     */
    public function isLastPage(Page $page)
    {
        $pages = $this->getPages();

        if (empty($pages)) {
            throw new ProgrammingError('This wizard has no pages');
        }

        return $pages[count($pages) - 1] === $page;
    }

    /**
     * Return whether this wizard has been completed
     *
     * @return  bool
     */
    public function isFinished()
    {
        return $this->finished && $this->isLastPage($this->getCurrentPage());
    }

    /**
     * Return whether the given page is a wizard
     *
     * @param   Page    $page   The page to check
     *
     * @return  bool
     */
    public function isWizard(Page $page)
    {
        return $page instanceof self;
    }

    /**
     * Return whether either the back- or next-button was clicked
     *
     * @see Form::isSubmitted()
     */
    public function isSubmitted()
    {
        $checkData = $this->getRequest()->getParams();
        return isset($checkData['btn_return']) || isset($checkData['btn_advance']);
    }

    /**
     * Update the wizard's progress
     *
     * @param   bool    $lastStepIsLast     Whether the last step of this wizard is actually the very last one
     */
    public function navigate($lastStepIsLast = true)
    {
        $currentPage = $this->getCurrentPage();
        if (($pageName = $this->getRequest()->getParam('btn_advance'))) {
            if (!$this->isWizard($currentPage) || $currentPage->navigate(false) || $currentPage->isFinished()) {
                if ($this->isLastPage($currentPage) && (!$lastStepIsLast || $pageName === 'install')) {
                    $this->finished = true;
                } else {
                    $pages = $this->getPages();
                    $newStep = $this->getProgress() + 1;
                    if (isset($pages[$newStep]) && $pages[$newStep]->getName() === $pageName) {
                        $this->setProgress($newStep);
                    }
                }
            }
        } elseif (($pageName = $this->getRequest()->getParam('btn_return'))) {
            if ($this->isWizard($currentPage) && $currentPage->getProgress() > 0) {
                $currentPage->navigate(false);
            } elseif (!$this->isFirstPage($currentPage)) {
                $pages = $this->getPages();
                $newStep = $this->getProgress() - 1;
                if ($pages[$newStep]->getName() === $pageName) {
                    $this->setProgress($newStep);
                }
            }
        }

        $config = $this->getConfig();
        $config->{$currentPage->getName()} = $currentPage->getConfig();
    }

    /**
     * Return a unique identifier for the given page
     *
     * @param   Page    $page   The page for which to return the identifier
     *
     * @return  string          The page's unique identifier
     */
    protected function generatePageIdentifier(Page $page)
    {
        if (($name = $page->getName())) {
            return $name;
        }

        $pageClass = get_class($page);
        $wizardConfig = $this->getConfig();

        if ($wizardConfig->get('page_names') === null || $wizardConfig->page_names->get($pageClass) === null) {
            $wizardConfig->page_names = $wizardConfig->get('page_names', new Zend_Config(array(), true));
            $wizardConfig->page_names->{$pageClass} = sprintf('%06x', mt_rand(0, 0xffffff));
        }

        return $wizardConfig->page_names->{$pageClass};
    }

    /**
     * Setup the current wizard page
     */
    protected function create()
    {
        $currentPage = $this->getCurrentPage();
        if ($this->isWizard($currentPage)) {
            $this->createWizard($currentPage);
        } else {
            $this->createPage($currentPage);
        }
    }

    /**
     * Display the given page as this wizard's current page
     *
     * @param   Page    $page   The page
     */
    protected function createPage(Page $page)
    {
        $pages = $this->getPages();
        $currentStep = $this->getProgress();

        $this->addSubForm($page, $page->getName());

        if (!$this->isFirstPage($page)) {
            $this->addElement(
                'button',
                'btn_return',
                array(
                    'type'  => 'submit',
                    'label' => t('Previous'),
                    'value' => $pages[$currentStep - 1]->getName()
                )
            );
        }

        $this->addElement(
            'button',
            'btn_advance',
            array(
                'type'  => 'submit',
                'label' => $this->isLastPage($page) ? t('Install') : t('Next'),
                'value' => $this->isLastPage($page) ? 'install' : $pages[$currentStep + 1]->getName()
            )
        );
    }

    /**
     * Display the current page of the given wizard as this wizard's current page
     *
     * @param   Wizard  $wizard     The wizard
     */
    protected function createWizard(Wizard $wizard)
    {
        $isFirstPage = $this->isFirstPage($wizard);
        $isLastPage = $this->isLastPage($wizard);
        $currentSubPage = $wizard->getCurrentPage();
        $isFirstSubPage = $wizard->isFirstPage($currentSubPage);
        $isLastSubPage = $wizard->isLastPage($currentSubPage);

        $this->addSubForm($currentSubPage, $currentSubPage->getName());

        if (!$isFirstPage || !$isFirstSubPage) {
            $pages = $isFirstSubPage ? $this->getPages() : $wizard->getPages();
            $currentStep = $isFirstSubPage ? $this->getProgress() : $wizard->getProgress();
            $this->addElement(
                'button',
                'btn_return',
                array(
                    'type'  => 'submit',
                    'label' => t('Previous'),
                    'value' => $pages[$currentStep - 1]->getName()
                )
            );
        }

        $pages = $isLastSubPage ? $this->getPages() : $wizard->getPages();
        $currentStep = $isLastSubPage ? $this->getProgress() : $wizard->getProgress();
        $this->addElement(
            'button',
            'btn_advance',
            array(
                'type'  => 'submit',
                'label' => $isLastPage && $isLastSubPage ? t('Install') : t('Next'),
                'value' => $isLastPage && $isLastSubPage ? 'install' : $pages[$currentStep + 1]->getName()
            )
        );
    }
}

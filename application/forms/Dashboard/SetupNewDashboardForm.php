<?php

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\ItemList\DashletListMultiSelect;
use Icinga\Web\Dashboard\ItemList\EmptyDashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Notification;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class SetupNewDashboardForm extends BaseDashboardForm
{
    const DATA_TOGGLE_ELEMENT = 'dashlets-list-info';

    /**
     * Caches all module dashlets
     *
     * @var array
     */
    protected static $moduleDashlets = [];

    protected $duplicateCustomDashlet = false;

    protected function init()
    {
        parent::init();

        if (empty(self::$moduleDashlets)) {
            self::$moduleDashlets = Dashboard::getModuleDashlets();
        }

        $this->setRedirectUrl((string) Url::fromPath(Dashboard::BASE_ROUTE));
        $this->setAction($this->getRedirectUrl() . '/setup-dashboard');
    }

    /**
     * Dump all module dashlets which are not selected by the user
     * from the member variable
     *
     * @param bool $strict Whether to match populated of the dashlet against a 'y'
     *
     * @return void
     */
    protected function dumpArbitaryDashlets(bool $strict = true): void
    {
        $chosenDashlets = [];
        foreach (self::$moduleDashlets as $module => $dashlets) {
            /** @var Dashlet $dashlet */
            foreach ($dashlets as $dashlet) {
                $element = str_replace(' ', '_', $module . '|' . $dashlet->getName());
                if ($this->getPopulatedValue($element) === 'y' || (! $strict && $this->getPopulatedValue($element))) {
                    $title = $this->getPopulatedValue($element);
                    $url = $this->getPopulatedValue($element . '_url');

                    if (! $strict && $title && $url) {
                        $dashlet
                            ->setUrl($url)
                            ->setName($title)
                            ->setTitle($title);
                    }

                    $chosenDashlets[$module][$dashlet->getName()] = $dashlet;
                }
            }

            if (isset($chosenDashlets[$module]) && ! $this->duplicateCustomDashlet) {
                $this->duplicateCustomDashlet = array_key_exists(
                    $this->getPopulatedValue('dashlet'),
                    $chosenDashlets[$module]
                );
            }
        }

        self::$moduleDashlets = $chosenDashlets;
    }

    /**
     * Get whether we are updating an existing dashlet
     *
     * @return bool
     */
    protected function isUpdatingADashlet()
    {
        return Url::fromRequest()->getPath() === Dashboard::BASE_ROUTE . '/edit-dashlet';
    }

    /**
     * Assemble the next page of the modal view
     *
     * @return void
     */
    protected function assembleNextPage()
    {
        $strict = $this->isUpdatingADashlet() || $this->getPopulatedValue('btn_next') || ! $this->hasBeenSent();
        $this->dumpArbitaryDashlets($strict);
        $this->assembleNextPageDashboardPart();
        $this->assembleNexPageDashletPart();
    }

    /**
     * Assemble the browsed module dashlets on the initial view
     *
     * @return void
     */
    protected function assembleSelectDashletView()
    {
        if ($this->getPopulatedValue('btn_next')) {
            return;
        }

        $emptyList = new EmptyDashlet();
        $emptyList->setCheckBox($this->createElement('checkbox', 'custom_url', ['class' => 'sr-only']));

        $listControl = $this->createFormListControls();
        $listControl->addHtml($emptyList);

        $this->addHtml($listControl);

        foreach (self::$moduleDashlets as $module => $dashlets) {
            $listControl = $this->createFormListControls(true);
            $list = HtmlElement::create('ul', ['class' => 'dashlet-item-list']);
            $listControl->addHtml(HtmlElement::create('span', null, ucfirst($module)));

            /** @var Dashlet $dashlet */
            foreach ($dashlets as $dashlet) {
                $multi = new DashletListMultiSelect($dashlet);
                $multi->setCheckBox($this->createElement(
                    'checkbox',
                    $module . '|' . $dashlet->getName(),
                    ['class' => 'sr-only']
                ));

                $list->addHtml($multi);
            }

            $this->addHtml($listControl->addHtml($list));
        }
    }

    /**
     * Assemble the dashboard part of elements on the next page
     *
     * @return void
     */
    protected function assembleNextPageDashboardPart()
    {
        $this->addElement('text', 'pane', [
            'required'    => true,
            'label'       => t('Dashboard Title'),
            'description' => t('Enter a title for the new dashboard you want to add the dashlets to')
        ]);
    }

    /**
     * Assemble the dashlet part of elements on the next page
     *
     * @return void
     */
    protected function assembleNexPageDashletPart()
    {
        if ($this->getPopulatedValue('custom_url') === 'y') {
            $this->addHtml(HtmlElement::create('hr'));
            $this->assembleDashletElements();
        }

        if (! empty(self::$moduleDashlets)) {
            foreach (self::$moduleDashlets as $module => $dashlets) {
                /** @var Dashlet $dashlet */
                foreach ($dashlets as $dashlet) {
                    $listControl = $this->createFormListControls(true);
                    $listControl->getAttributes()->add('class', 'multi-dashlets');

                    $dashletName = $this->createElement('text', $module . '|' . $dashlet->getName(), [
                        'required'    => true,
                        'label'       => t('Dashlet Title'),
                        'value'       => $dashlet->getTitle(),
                        'description' => t('Enter a title for the dashlet'),
                    ]);

                    $dashletUrl = $this->createElement('textarea', $module . '|' . $dashlet->getName() . '_url', [
                        'required'    => true,
                        'label'       => t('Url'),
                        'value'       => $dashlet->getUrl()->getRelativeUrl(),
                        'description' => t(
                            'Enter url to be loaded in the dashlet. You can paste the full URL, including filters'
                        )
                    ]);

                    $this->registerElement($dashletName)->decorate($dashletName);
                    $this->registerElement($dashletUrl)->decorate($dashletUrl);

                    $listControl->addHtml(HtmlElement::create('span', null, t($dashlet->getTitle())));

                    $listControl->addHtml($dashletName);
                    $listControl->addHtml($dashletUrl);

                    $this->addHtml($listControl);
                }
            }
        }
    }

    protected function assembleDashletElements()
    {
        $this->addElement('text', 'dashlet', [
            'required'    => true,
            'label'       => t('Dashlet Title'),
            'placeholder' => t('Enter a dashlet title'),
            'description' => t('Enter a title for the dashlet.'),
        ]);

        $this->addElement('textarea', 'url', [
            'required'    => true,
            'label'       => t('Url'),
            'placeholder' => t('Enter dashlet url'),
            'description' => t(
                'Enter url to be loaded in the dashlet. You can paste the full URL, including filters.'
            ),
        ]);
    }

    protected function assemble()
    {
        if ($this->getPopulatedValue('btn_next')) { // Configure Dashlets
            $submitButtonLabel = t('Add Dashlets');
            $this->assembleNextPage();
        } else {
            $submitButtonLabel = t('Next');
            $this->assembleSelectDashletView();
        }

        $submitButton = $this->registerSubmitButton($submitButtonLabel);
        if (! $this->getPopulatedValue('btn_next')) {
            $submitButton
                ->setName('btn_next')
                ->getAttributes()->add('class', 'autosubmit');
        }

        $formControls = $this->createFormControls();
        $formControls->add([$submitButton, $this->createCancelButton()]);

        $this->addHtml($formControls);
    }

    protected function onSuccess()
    {
        if ($this->getPopulatedValue('submit')) {
            $conn = Dashboard::getConn();
            $pane = new Pane($this->getPopulatedValue('pane'));
            $home = $this->dashboard->getEntry(DashboardHome::DEFAULT_HOME);

            $conn->beginTransaction();

            try {
                $this->dashboard->manageEntry($home);
                $home->manageEntry($pane);

                $this->dumpArbitaryDashlets(false);

                if (($name = $this->getPopulatedValue('dashlet')) && ($url = $this->getPopulatedValue('url'))) {
                    if ($this->duplicateCustomDashlet) {
                        Notification::error(sprintf(
                            t('Failed to create new dahlets. Dashlet "%s" exists within the selected one'),
                            $name
                        ));

                        return;
                    }

                    $dashlet = new Dashlet($name, $url, $pane);
                    $pane->manageEntry($dashlet);
                }

                $pane->manageEntry(self::$moduleDashlets);

                $conn->commitTransaction();
            } catch (\Exception $err) {
                $conn->rollBackTransaction();
                throw $err;
            }

            Notification::success(t('Added new dashlet(s) successfully'));
        }
    }

    /**
     * Create form list controls (can be collapsible if you want)
     *
     * @param bool $makeCollapsible
     *
     * @return ValidHtml
     */
    protected function createFormListControls(bool $makeCollapsible = false): ValidHtml
    {
        $listControls = HtmlElement::create('div', [
            'class' => ['control-group', 'form-list-control'],
        ]);

        if ($makeCollapsible) {
            $listControls
                ->getAttributes()
                ->add('data-toggle-element', '.' . self::DATA_TOGGLE_ELEMENT)
                ->add('class', 'collapsible');

            $listControls->addHtml(HtmlElement::create('div', ['class' => self::DATA_TOGGLE_ELEMENT], [
                new Icon('angle-down', ['class' => 'expand-icon', 'title' => t('Expand')]),
                new Icon('angle-up', ['class' => 'collapse-icon', 'title' => t('Collapse')])
            ]));
        }

        return $listControls;
    }
}

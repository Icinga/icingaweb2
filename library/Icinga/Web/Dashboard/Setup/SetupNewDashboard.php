<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Dashboard\Setup;

use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Notification;
use Icinga\Web\Dashboard\ItemList\DashletListMultiSelect;
use ipl\Html\HtmlElement;
use ipl\Html\ValidHtml;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class SetupNewDashboard extends CompatForm
{
    /** @var Dashboard */
    protected $dashboard;

    /** @var array Module dashlets from the DB */
    private $dashlets = [];

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;

        $this->setRedirectUrl((string) Url::fromPath(Dashboard::BASE_ROUTE));
        $this->setAction($this->getRedirectUrl() . '/setup-dashboard');
    }

    /**
     * Initialize module dashlets
     *
     * @param array $dashlets
     *
     * @return $this
     */
    public function initDashlets(array $dashlets)
    {
        $this->dashlets = $dashlets;

        return $this;
    }

    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent()
            && ($this->getPopulatedValue('btn_cancel')
                || $this->getPopulatedValue('submit'));
    }

    protected function assemble()
    {
        $this->getAttributes()->add('class', 'modal-form');

        if ($this->getPopulatedValue('btn_next')) { // Configure Dashlets
            $this->dumpArbitaryDashlets();

            $this->addElement('text', 'pane', [
                'required'    => true,
                'label'       => t('Dashboard Title'),
                'description' => t('Enter a title for the new dashboard you want to add the dashlets to'),
            ]);

            if (empty($this->dashlets)
                || (count(array_keys($this->dashlets)) == 1 // Only one module
                    && count(reset($this->dashlets)) == 1 // Only one module dashlet
                )
            ) {
                $this->addHtml(HtmlElement::create('hr'));

                $this->addElement('text', 'dashlet', [
                    'required'    => true,
                    'label'       => t('Dashlet Title'),
                    'description' => t('Enter a title for the dashlet'),
                ]);

                $this->addElement('textarea', 'url', [
                    'required'    => true,
                    'label'       => t('Url'),
                    'description' => t(
                        'Enter url to be loaded in the dashlet. You can paste the full URL, including filters'
                    )
                ]);

                foreach ($this->dashlets as $_ => $dashlets) {
                    /** @var Dashlet $dashlet */
                    foreach ($dashlets as $dashlet) {
                        $this->getElement('dashlet')->getAttributes()->set('value', $dashlet->getTitle());
                        $this->getElement('url')->getAttributes()->set('value', $dashlet->getUrl()->getRelativeUrl());
                    }
                }
            } else {
                foreach ($this->dashlets as $module => $dashlets) {
                    /** @var Dashlet $dashlet */
                    foreach ($dashlets as $dashlet) {
                        $listControl = $this->createFormListControl();
                        $listControl->getAttributes()->add('class', 'multi-dashlets');

                        $listControl->addHtml(HtmlElement::create('div', ['class' => 'dashlets-list-info'], [
                            new Icon('angle-down', ['class' => 'expand-icon', 'title' => t('Expand')]),
                            new Icon('angle-up', ['class' => 'collapse-icon', 'title' => t('Collapse')])
                        ]));

                        $dashletName = $this->createElement('text', $module . $dashlet->getName(), [
                            'required'    => true,
                            'label'       => t('Dashlet Title'),
                            'value'       => $dashlet->getTitle(),
                            'description' => t('Enter a title for the dashlet'),
                        ]);

                        $dashletUrl = $this->createElement('textarea', $module . $dashlet->getName() . '_url', [
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

            $submitButton = $this->createElement('submit', 'submit', ['label' => t('Add Dashlets')]);
            $this->registerElement($submitButton)->decorate($submitButton);
        } else { // Select Dashlets
            $list = HtmlElement::create('ul', ['class' => 'dashlet-item-list empty-list']);
            $multi = new DashletListMultiSelect();
            $multi->setCheckBox($this->createElement('checkbox', 'custom_url', ['class' => 'sr-only']));

            $listControl = $this->createFormListControl();
            $listControl->getAttributes()->remove('class', 'collapsible');

            $this->addHtml($listControl->addHtml($list->addHtml($multi)));

            foreach ($this->dashlets as $module => $dashlets) {
                $listControl = $this->createFormListControl();
                $listControl->addHtml(HtmlElement::create('div', ['class' => 'dashlets-list-info'], [
                    new Icon('angle-down', ['class' => 'expand-icon', 'title' => t('Expand')]),
                    new Icon('angle-up', ['class' => 'collapse-icon', 'title' => t('Collapse')])
                ]));

                $list = HtmlElement::create('ul', ['class' => 'dashlet-item-list ' . $module]);
                $listControl->addHtml(HtmlElement::create('span', null, ucfirst($module)));

                /** @var Dashlet $dashlet */
                foreach ($dashlets as $dashlet) {
                    $multi = new DashletListMultiSelect($dashlet);
                    $multi->setCheckBox(
                        $this->createElement('checkbox', $module . '|' . $dashlet->getName(), ['class' => 'sr-only'])
                    );

                    $list->addHtml($multi);
                }

                $this->addHtml($listControl->addHtml($list));
            }

            $submitButton = $this->createElement('submit', 'btn_next', [
                'class' => 'autosubmit',
                'label' => t('Next'),
            ]);
            $this->registerElement($submitButton)->decorate($submitButton);
        }

        $this->addElement('submit', 'btn_cancel', ['label' => t('Cancel')]);
        $this->getElement('btn_cancel')->setWrapper($submitButton->getWrapper());
    }

    protected function onSuccess()
    {
        if ($this->getPopulatedValue('submit')) {
            $conn = Dashboard::getConn();
            $pane = new Pane($this->getPopulatedValue('pane'));

            $conn->beginTransaction();

            try {
                $this->dashboard->getHome(DashboardHome::DEFAULT_HOME)->managePanes($pane);

                // If element name "dashlet" and "url" are set we need to only store one dashlet
                if (($name = $this->getPopulatedValue('dashlet')) && ($url = $this->getPopulatedValue('url'))) {
                    $dashlet = new Dashlet($name, $url, $pane);
                    $pane->manageDashlets($dashlet);
                } else {
                    foreach ($this->dashlets as $module => $dashlets) {
                        $moduleDashlets = [];

                        /** @var Dashlet $dashlet */
                        foreach ($dashlets as $dashlet) {
                            $element = str_replace(' ', '_', $module . $dashlet->getName());
                            if (! $this->getPopulatedValue($element)) {
                                continue;
                            }

                            $title = $this->getPopulatedValue($element);
                            $url = $this->getPopulatedValue($element . '_url');

                            $dashlet
                                ->setUrl($url)
                                ->setTitle($title)
                                ->setModule($module)
                                ->setModuleDashlet(true);

                            if ($dashlet->getPane()) {
                                $paneName = $dashlet->getPane()->getName();
                                $dashlet->setUuid(Dashboard::getSHA1($module . $paneName . $dashlet->getName()));
                            } else {
                                $dashlet->setUuid(Dashboard::getSHA1($module . $dashlet->getName()));
                            }

                            $moduleDashlets[$dashlet->getName()] = $dashlet;
                        }

                        $pane->manageDashlets($moduleDashlets);
                    }
                }

                $conn->commitTransaction();
            } catch (\Exception $err) {
                $conn->rollBackTransaction();
                throw $err;
            }

            Notification::success(t('Created dashboard successfully'));
        }
    }

    /**
     * Dump all module dashlets which are not selected by the user
     * from the member variable
     *
     * @return $this
     */
    private function dumpArbitaryDashlets()
    {
        $choosenDashlets = [];
        foreach ($this->dashlets as $module => $dashlets) {
            /** @var Dashlet $dashlet */
            foreach ($dashlets as $dashlet) {
                $element = str_replace(' ', '_', $module . '|' . $dashlet->getName());
                if ($this->getPopulatedValue($element) === 'y') {
                    $choosenDashlets[$module][$dashlet->getName()] = $dashlet;
                }
            }
        }

        $this->dashlets = $choosenDashlets;

        return $this;
    }

    /**
     * Create collapsible form list control
     *
     * @return ValidHtml
     */
    private function createFormListControl()
    {
        return HtmlElement::create('div', [
            'class'               => ['control-group', 'form-list-control', 'collapsible'],
            'data-toggle-element' => '.dashlets-list-info'
        ]);
    }
}

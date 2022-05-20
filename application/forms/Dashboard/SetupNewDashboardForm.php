<?php

namespace Icinga\Forms\Dashboard;

use Icinga\Application\Modules;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\DashboardHome;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\ItemList\DashletListMultiSelect;
use Icinga\Web\Dashboard\ItemList\EmptyDashlet;
use Icinga\Web\Dashboard\Pane;
use Icinga\Util\DBUtils;
use Icinga\Web\Notification;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Html\ValidHtml;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;

class SetupNewDashboardForm extends BaseDashboardForm
{
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
            self::$moduleDashlets = Modules\DashletManager::getDashlets();
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
     * Assemble the next page of the modal view
     *
     * @return void
     */
    protected function assembleNextPage()
    {
        $strict = $this->isUpdating() || $this->getPopulatedValue('btn_next') || ! $this->hasBeenSent();
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

        $emptyDashlet = new EmptyDashlet();
        $emptyDashlet->setCheckBox($this->createElement('checkbox', 'custom_url', ['class' => 'sr-only']));
        $emptyList = HtmlElement::create('ul', ['class' => 'dashlet-item-list'], $emptyDashlet);

        $listControl = $this->createFormListControls();
        $listControl->addHtml($emptyList);

        $this->addHtml($listControl);

        foreach (self::$moduleDashlets as $module => $dashlets) {
            $listControl = $this->createFormListControls(ucfirst($module));
            $list = HtmlElement::create('ul', ['class' => 'dashlet-item-list']);

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
        if ($this->getPopulatedValue('custom_url') === 'y' && ! $this->isUpdating()) {
            $this->addHtml(HtmlElement::create('hr'));
            $this->assembleDashletElements();
        }

        if (! empty(self::$moduleDashlets)) {
            foreach (self::$moduleDashlets as $module => $dashlets) {
                /** @var Dashlet $dashlet */
                foreach ($dashlets as $dashlet) {
                    $this->addHtml(HtmlElement::create('h3', null, t($dashlet->getTitle())));

                    $this->addElement('text', $module . '|' . $dashlet->getName(), [
                        'required'    => true,
                        'label'       => t('Dashlet Title'),
                        'value'       => $dashlet->getTitle(),
                        'description' => t('Enter a title for the dashlet'),
                    ]);

                    $this->addElement('textarea', $module . '|' . $dashlet->getName() . '_url', [
                        'required'    => true,
                        'label'       => t('Url'),
                        'value'       => $dashlet->getUrl()->getRelativeUrl(),
                        'description' => t(
                            'Enter url to be loaded in the dashlet. You can paste the full URL, including filters'
                        )
                    ]);
                }
            }
        }
    }

    protected function assembleDashletElements()
    {
        $this->addElement('hidden', 'custom_url', ['required' => false, 'value' => 'y']);
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
            $conn = DBUtils::getConn();
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
     * @param ?string $title
     *
     * @return ValidHtml
     */
    protected function createFormListControls(string $title = null): ValidHtml
    {
        $controlGroup = HtmlElement::create('div', ['class' => 'control-group']);
        if ($title === null) {
            return $controlGroup;
        }

        $details = HtmlElement::create('details', [
            'class'                 => ['dashboard-list-control', 'collapsible'],
            'data-no-persistence'   => true
        ]);
        $summary = HtmlElement::create('summary', ['class' => 'collapsible-header']);
        $summary->addHtml(
            new Icon('angle-right', ['class' => 'expand-icon', 'title' => t('Expand')]),
            new Icon('angle-down', ['class' => 'collapse-icon', 'title' => t('Collapse')]),
            Text::create($title)
        );

        $details->addHtml($summary);
        $details->prependWrapper($controlGroup);

        return $details;
    }
}

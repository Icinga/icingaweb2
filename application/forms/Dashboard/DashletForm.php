<?php

/* Icinga Web 2 | (c) 2013-2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Exception;
use Icinga\Application\Logger;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Notification;
use Icinga\Web\Dashboard\Dashlet;
use Icinga\Web\Dashboard\Pane;
use ipl\Html\HtmlElement;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

/**
 * Form to add an url a dashboard pane
 */
class DashletForm extends CompatForm
{
    /**
     * @var Dashboard
     */
    private $dashboard;

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;

        $this->setAction((string) Url::fromRequest());
    }

    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent() && $this->getPopulatedValue('submit');
    }

    protected function assemble()
    {
        $requestUrl = Url::fromRequest();

        $homes = $this->dashboard->getHomeKeyTitleArr();
        $activeHome = $this->dashboard->getActiveHome();
        $currentHome = $requestUrl->getParam('home', reset($homes));
        $populatedHome = $this->getPopulatedValue('home', $currentHome);

        $panes = [];
        if ($currentHome === $populatedHome && $this->getPopulatedValue('create_new_home') !== 'y') {
            if (! $currentHome || ! $activeHome) {
                // Home param isn't passed through, so let's try to load based on the first home
                $firstHome = $this->dashboard->rewindHomes();
                if ($firstHome) {
                    $this->dashboard->loadDashboards($firstHome->getName());

                    $panes = $firstHome->getPaneKeyTitleArr();
                }
            } else {
                $panes = $activeHome->getPaneKeyTitleArr();
            }
        } elseif ($this->dashboard->hasHome($populatedHome)) {
            $this->dashboard->loadDashboards($populatedHome);

            $panes = $this->dashboard->getActiveHome()->getPaneKeyTitleArr();
        }

        $this->addElement('hidden', 'org_pane', ['required' => false]);
        $this->addElement('hidden', 'org_home', ['required' => false]);
        $this->addElement('hidden', 'org_dashlet', ['required' => false]);

        $this->addElement('checkbox', 'create_new_home', [
            'class'       => 'autosubmit',
            'required'    => false,
            'disabled'    => empty($homes) ?: null,
            'label'       => t('New Dashboard Home'),
            'description' => t('Check this box if you want to add the dashboard to a new dashboard home.'),
        ]);

        if (empty($homes) || $this->getPopulatedValue('create_new_home') === 'y') {
            // $el->attrs->set() has no effect here anymore, so we need to register a proper callback
            $this->getElement('create_new_home')
                ->getAttributes()
                ->registerAttributeCallback('checked', function () {
                    return true;
                });

            $this->addElement('text', 'home', [
                'required'    => true,
                'label'       => t('Dashboard Home'),
                'description' => t('Enter a title for the new dashboard home.')
            ]);
        } else {
            $this->addElement('select', 'home', [
                'required'     => true,
                'class'        => 'autosubmit',
                'value'        => $currentHome,
                'multiOptions' => $homes,
                'label'        => t('Dashboard Home'),
                'descriptions' => t('Select a home you want to add the dashboard pane to.')
            ]);
        }

        $disable = empty($panes) || $this->getPopulatedValue('create_new_home') === 'y';
        $this->addElement('checkbox', 'create_new_pane', [
            'required'    => false,
            'class'       => 'autosubmit',
            'disabled'    => $disable ?: null,
            'label'       => t('New Dashboard'),
            'description' => t('Check this box if you want to add the dashlet to a new dashboard.'),
        ]);

        // Pane element's values are depending on the home element's value
        if (! in_array($this->getPopulatedValue('pane'), $panes)) {
            $this->clearPopulatedValue('pane');
        }

        if ($disable || $this->getValue('create_new_pane') === 'y') {
            // $el->attrs->set() has no effect here anymore, so we need to register a proper callback
            $this->getElement('create_new_pane')
                ->getAttributes()
                ->registerAttributeCallback('checked', function () {
                    return true;
                });

            $this->addElement('text', 'pane', [
                'required'    => true,
                'label'       => t('New Dashboard Title'),
                'description' => t('Enter a title for the new dashboard.'),
            ]);
        } else {
            $this->addElement('select', 'pane', [
                'required'     => true,
                'value'        => reset($panes),
                'multiOptions' => $panes,
                'label'        => t('Dashboard'),
                'description'  => t('Select a dashboard you want to add the dashlet to.'),
            ]);
        }

        $this->addHtml(new HtmlElement('hr'));

        $this->addElement('textarea', 'url', [
            'required'    => true,
            'label'       => t('Url'),
            'description' => t(
                'Enter url to be loaded in the dashlet. You can paste the full URL, including filters.'
            ),
        ]);

        $this->addElement('text', 'dashlet', [
            'required'    => true,
            'label'       => t('Dashlet Title'),
            'description' => t('Enter a title for the dashlet.'),
        ]);

        $url = (string) Url::fromPath(Dashboard::BASE_ROUTE . '/browse');

        $element = $this->createElement('submit', 'submit', ['label' => t('Add to Dashboard')]);
        $this->registerElement($element)->decorate($element);

        // We might need this later to allow the user to browse dashlets when creating a dashlet
        $this->addElement('submit', 'btn_browse', [
            'label'      => t('Browse Dashlets'),
            'href'       => $url,
            'formaction' => $url,
        ]);

        $this->getElement('btn_browse')->setWrapper($element->getWrapper());
    }

    /**
     * Populate form data from config
     *
     * @param Dashlet $dashlet
     */
    public function load(Dashlet $dashlet)
    {
        $home = Url::fromRequest()->getParam('home');
        $this->populate(array(
            'org_home'    => $home,
            'org_pane'    => $dashlet->getPane()->getName(),
            'pane'        => $dashlet->getPane()->getTitle(),
            'org_dashlet' => $dashlet->getName(),
            'dashlet'     => $dashlet->getTitle(),
            'url'         => $dashlet->getUrl()->getRelativeUrl()
        ));
    }

    protected function onSuccess()
    {
        $conn = Dashboard::getConn();
        $dashboard = $this->dashboard;

        if (Url::fromRequest()->getPath() === Dashboard::BASE_ROUTE . '/new-dashlet') {
            $home = new DashboardHome($this->getValue('home'));
            if ($dashboard->hasHome($home->getName())) {
                $home = $dashboard->getHome($home->getName());
                if ($home->getName() !== $dashboard->getActiveHome()->getName()) {
                    $home->setActive();
                    $home->loadPanesFromDB();
                }
            }

            $pane = new Pane($this->getValue('pane'));
            if ($home->hasPane($pane->getName())) {
                $pane = $home->getPane($pane->getName());
            }

            $dashlet = new Dashlet($this->getValue('dashlet'), $this->getValue('url'), $pane);
            if ($pane->hasDashlet($dashlet->getName())) {
                Notification::error(sprintf(
                    t('Dashlet "%s" already exists within the "%s" dashboard pane'),
                    $dashlet->getTitle(),
                    $pane->getTitle()
                ));

                return;
            }

            $conn->beginTransaction();

            try {
                $dashboard->manageHome($home);
                $home->managePanes($pane);
                $pane->manageDashlets($dashlet);

                $conn->commitTransaction();
            } catch (Exception $err) { // This error handling is just for debugging purpose! Will be removed!
                Logger::error($err);
                $conn->rollBackTransaction();

                throw $err;
            }

            Notification::success(sprintf(t('Created dashlet "%s" successfully'), $dashlet->getTitle()));
        } else {
            $orgHome = $dashboard->getHome($this->getValue('org_home'));
            $orgPane = $orgHome->getPane($this->getValue('org_pane'));
            $orgDashlet = $orgPane->getDashlet($this->getValue('org_dashlet'));

            $currentHome = new DashboardHome($this->getValue('home'));
            if ($dashboard->hasHome($currentHome->getName())) {
                $currentHome = $dashboard->getHome($currentHome->getName());
                $activeHome = $dashboard->getActiveHome();
                if ($currentHome->getName() !== $activeHome->getName()) {
                    $currentHome->setActive();
                    $currentHome->loadPanesFromDB();
                }
            }

            $currentPane = new Pane($this->getValue('pane'));
            if ($currentHome->hasPane($currentPane->getName())) {
                $currentPane = $currentHome->getPane($currentPane->getName());
            }

            $currentPane->setHome($currentHome);

            $currentDashlet = clone $orgDashlet;
            $currentDashlet
                ->setPane($currentPane)
                ->setUrl($this->getValue('url'))
                ->setTitle($this->getValue('dashlet'));

            if ($orgPane->getName() !== $currentPane->getName()
                && $currentPane->hasDashlet($currentDashlet->getName())) {
                Notification::error(sprintf(
                    t('Failed to move dashlet "%s": Dashlet already exists within the "%s" dashboard pane'),
                    $currentDashlet->getTitle(),
                    $currentPane->getTitle()
                ));

                return;
            }

            $paneDiff = array_filter(array_diff_assoc($currentPane->toArray(), $orgPane->toArray()));
            $dashletDiff = array_filter(
                array_diff_assoc($currentDashlet->toArray(), $orgDashlet->toArray()),
                function ($val) {
                    return $val !== null;
                }
            );

            // Prevent meaningless updates when there weren't any changes,
            // e.g. when the user just presses the update button without changing anything
            if (empty($dashletDiff) && empty($paneDiff)) {
                return;
            }

            $conn->beginTransaction();

            try {
                $dashboard->manageHome($currentHome);
                $currentHome->managePanes($currentPane, $orgHome);
                $currentPane->manageDashlets($currentDashlet, $orgPane);

                $conn->commitTransaction();
            } catch (Exception $err) {
                Logger::error($err);
                $conn->rollBackTransaction();

                throw $err;
            }

            Notification::success(sprintf(t('Updated dashlet "%s" successfully'), $currentDashlet->getTitle()));
        }
    }
}

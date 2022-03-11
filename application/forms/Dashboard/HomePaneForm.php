<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Dashboard;

use Icinga\Application\Logger;
use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Notification;
use Icinga\Web\Dashboard\Dashboard;
use Icinga\Web\Dashboard\Pane;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

class HomePaneForm extends CompatForm
{
    /** @var Dashboard */
    protected $dashboard;

    public function __construct(Dashboard $dashboard)
    {
        $this->dashboard = $dashboard;

        // We need to set this explicitly needed for modals
        $this->setAction((string) Url::fromRequest());
    }

    /**
     * Populate form data from config
     *
     * @param DashboardHome|Pane $widget
     */
    public function load($widget)
    {
        $title = $widget instanceof Pane ? $widget->getTitle() : $widget->getLabel();
        $this->populate([
            'org_title' => $title,
            'title'     => $title,
            'org_name'  => $widget->getName()
        ]);
    }

    protected function assemble()
    {
        $this->addElement('hidden', 'org_name', ['required' => false]);
        $this->addElement('hidden', 'org_title', ['required' => false]);

        $titleDesc = t('Edit the title of this dashboard home');
        $buttonLabel = t('Update Home');
        if (Url::fromRequest()->getPath() === Dashboard::BASE_ROUTE . '/edit-pane') {
            $titleDesc = t('Edit the title of this dashboard pane');
            $buttonLabel = t('Update Pane');

            $homes = $this->dashboard->getHomeKeyTitleArr();
            $this->addElement('checkbox', 'create_new_home', [
                'required'      => false,
                'class'         => 'autosubmit',
                'disabled'      => empty($homes) ?: null,
                'label'         => t('New Dashboard Home'),
                'description'   => t('Check this box if you want to move the pane to a new dashboard home.'),
            ]);

            $activeHome = $this->dashboard->getActiveHome();
            $populatedHome = $this->getPopulatedValue('home', $activeHome->getName());

            if (empty($homes) || $this->getPopulatedValue('create_new_home') === 'y') {
                $this->getElement('create_new_home')->addAttributes(['checked' => 'checked']);

                $this->addElement('text', 'home', [
                    'required'      => true,
                    'label'         => t('Dashboard Home'),
                    'description'   => t('Enter a title for the new dashboard home.'),
                ]);
            } else {
                $this->addElement('select', 'home', [
                    'required'      => true,
                    'class'         => 'autosubmit',
                    'value'         => $populatedHome,
                    'multiOptions'  => $homes,
                    'label'         => t('Move to Home'),
                    'description'   => t('Select a dashboard home you want to move the dashboard to.'),
                ]);
            }
        }

        $this->addElement('text', 'title', [
            'required'      => true,
            'label'         => t('Title'),
            'description'   => $titleDesc
        ]);

        $this->addElement('submit', 'btn_update', ['label' => $buttonLabel]);
    }

    protected function onSuccess()
    {
        $requestUrl = Url::fromRequest();
        if ($requestUrl->getPath() === Dashboard::BASE_ROUTE . '/edit-pane') {
            $orgHome = $this->dashboard->getHome($requestUrl->getParam('home'));

            $currentHome = new DashboardHome($this->getValue('home'));
            if ($this->dashboard->hasHome($currentHome->getName())) {
                $currentHome = $this->dashboard->getHome($currentHome->getName());
                $activeHome = $this->dashboard->getActiveHome();
                if ($currentHome->getName() !== $activeHome->getName()) {
                    $currentHome->setActive();
                    $currentHome->loadDashboardsFromDB();
                }
            }

            $currentPane = $orgHome->getPane($this->getValue('org_name'));
            $currentPane
                ->setHome($currentHome)
                ->setTitle($this->getValue('title'));

            if ($orgHome->getName() !== $currentHome->getName() && $currentHome->hasPane($currentPane->getName())) {
                Notification::error(sprintf(
                    t('Failed to move dashboard "%s": Dashbaord pane already exists within the "%s" dashboard home'),
                    $currentPane->getTitle(),
                    $currentHome->getLabel()
                ));

                return;
            }

            $conn = Dashboard::getConn();
            $conn->beginTransaction();

            try {
                $this->dashboard->manageHome($currentHome);
                $currentHome->managePanes($currentPane, $orgHome);

                $conn->commitTransaction();
            } catch (\Exception $err) {
                Logger::error($err);
                $conn->rollBackTransaction();
            }

            Notification::success(sprintf(t('Updated dashboard pane "%s" successfully'), $currentPane->getTitle()));
        } else {
            $home = $this->dashboard->getActiveHome();
            $home->setLabel($this->getValue('title'));

            $this->dashboard->manageHome($home);
            Notification::success(sprintf(t('Updated dashboard home "%s" successfully'), $home->getLabel()));
        }
    }
}

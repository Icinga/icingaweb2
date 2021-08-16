<?php

namespace Icinga\Forms\Dashboard;

use Icinga\Web\Navigation\DashboardHome;
use Icinga\Web\Notification;
use Icinga\Web\Widget\Dashboard;
use Icinga\Web\Widget\Dashboard\Pane;
use ipl\Html\Html;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

/**
 * A Form used for editing, deleting and creating dashboard panes and dashboard homes.
 */
class HomePaneForm extends CompatForm
{
    /** @var Dashboard */
    private $dashboard;

    private $dashlets;

    /**
     * RenamePaneForm constructor.
     *
     * @param Dashboard $dashboard
     */
    public function __construct(Dashboard $dashboard, array $dashlets = [])
    {
        $this->dashboard = $dashboard;
        $this->dashlets = $dashlets;
    }

    /**
     * @inheritdoc
     *
     * @return bool
     */
    public function hasBeenSubmitted()
    {
        return $this->hasBeenSent()
            && ($this->getPopulatedValue('btn_remove')
                || $this->getPopulatedValue('btn_update'));
    }

    /**
     * @param Pane|DashboardHome  $paneOrHome
     */
    public function load($paneOrHome)
    {
        $requestPath = Url::fromRequest()->getPath();

        if ($requestPath === DashboardHome::BASE_PATH . '/rename-home'
            || $requestPath === DashboardHome::BASE_PATH . '/remove-home') {
            $title = $paneOrHome->getLabel();
        } else {
            $title = $paneOrHome->getTitle();
        }

        $this->populate([
            'org_name'  => $paneOrHome->getName(),
            'title'     => $title,
            'org_title' => $title
        ]);
    }

    protected function assemble()
    {
        $removeHome = DashboardHome::BASE_PATH . '/remove-home';
        $renamePane = DashboardHome::BASE_PATH . '/rename-pane';
        $removePane = DashboardHome::BASE_PATH . '/remove-pane';
        $newPanePath = DashboardHome::BASE_PATH . '/new-pane';

        $requestPath = Url::fromRequest()->getPath();

        $activeHome = $this->dashboard->getActiveHome();
        $populated = $this->getPopulatedValue('home', $activeHome->getName());
        $dashboardHomes = $this->dashboard->getHomeKeyNameArray();

        $btnUpdateLabel = t('Update Home');
        $btnRemoveLabel = t('Remove Home');
        $titleDesc = t('Edit the current home title.');
        $formaction = (string) Url::fromRequest()->setPath($removeHome);

        $this->addElement('hidden', 'org_name', ['required' => false]);

        if ($newPanePath === $requestPath) {
            $this->add(HtmlElement::create('p', ['class' => 'paragraph'], Html::sprintf(
                t('You\'re about to create a Dashboard from %s pinned Dashlets.'),
                HtmlElement::create('span', ['class' => 'count-pinned'], count($this->dashlets))
            )));
        }

        if ($requestPath === $renamePane || $newPanePath === $requestPath
            || $requestPath === DashboardHome::BASE_PATH . '/rename-home') {
            if ($renamePane === $requestPath || $newPanePath === $requestPath) {
                if ($renamePane === $requestPath) {
                    $btnUpdateLabel = t('Update Pane');
                    $btnRemoveLabel = t('Remove Pane');
                    $titleDesc = t('Edit the current pane title.');
                    $formaction = (string) Url::fromRequest()->setPath($removePane);

                    $this->addElement('hidden', 'org_title', ['required' => false]);
                }

                $this->addElement(
                    'checkbox',
                    'create_new_home',
                    [
                        'class'         => 'autosubmit',
                        'disabled'      => empty($dashboardHomes) ?: null,
                        'required'      => false,
                        'label'         => t('New Dashboard Home'),
                        'description'   => t('Check this box if you want to move the pane to a new dashboard home.'),
                    ]
                );

                if (empty($dashboardHomes) || $this->getPopulatedValue('create_new_home') === 'y') {
                    if (empty($dashboardHomes)) {
                        $this->getElement('create_new_home')->addAttributes(['checked' => 'checked']);
                    }
                    $this->addElement(
                        'text',
                        'home',
                        [
                            'required'      => true,
                            'label'         => t('Dashboard Home'),
                            'description'   => t('Enter a title for the new dashboard home.'),
                        ]
                    );
                } else {
                    $this->addElement(
                        'select',
                        'home',
                        [
                            'required'      => true,
                            'label'         => $newPanePath === $requestPath ? t('Dashboard Home') : t('Move to home'),
                            'multiOptions'  => $dashboardHomes,
                            'value'         => $populated,
                            'description'   => t('Select a dashboard home you want to move the dashboard to'),
                        ]
                    );
                }
            }

            if ($newPanePath !== $requestPath) {
                $this->addElement(
                    'text',
                    'title',
                    [
                        'required'      => true,
                        'label'         => t('Title'),
                        'description'   => $titleDesc
                    ]
                );
            } else {
                $this->addElement(
                    'text',
                    'pane',
                    [
                        'required'      => true,
                        'label'         => t('Dashboard Name'),
                        'description'   => t('Enter a title for the new dashboard.'),
                    ]
                );

                $this->addElement(
                    'textarea',
                    'shared-with',
                    [
                        'required'      => false,
                        'label'         => t('Share with'),
                        'description'   => t(
                            'Enter a username, groups or roles you want to share with.'
                        ),
                    ]
                );

                $this->addElement(
                    'textarea',
                    'write-access',
                    [
                        'required'      => false,
                        'label'         => t('Write Permissions'),
                        'description'   => t(
                            'Enter a username, groups or roles you want to grant write access to.'
                        ),
                    ]
                );
            }
        }

        if ($removePane === $requestPath || $requestPath === DashboardHome::BASE_PATH . '/remove-home') {
            $message = sprintf(t('Please confirm removal of dashboard home "%s"'), $activeHome->getName());

            if ($requestPath === $removePane) {
                $btnRemoveLabel = t('Remove Pane');
                $formaction = (string)Url::fromRequest()->setPath($removePane);
                $message = sprintf(t('Please confirm removal of dashboard "%s"'), Url::fromRequest()->getParam('pane'));
            }

            $this->add(new HtmlElement('h1', null, Text::create($message)));
        }

        if ($newPanePath !== $requestPath) {
            $this->add(
                HtmlElement::create(
                    'div',
                    ['class' => 'control-group form-controls'],
                    [
                        HtmlElement::create(
                            'input',
                            [
                                'class'             => 'btn-primary',
                                'type'              => 'submit',
                                'name'              => 'btn_remove',
                                'value'             => $btnRemoveLabel,
                                'formaction'        => $formaction
                            ]
                        ),
                        $removeHome === $requestPath || $removePane === $requestPath ? '' :
                            HtmlElement::create(
                                'input',
                                [
                                    'class' => 'btn-primary',
                                    'type'  => 'submit',
                                    'name'  => 'btn_update',
                                    'value' => $btnUpdateLabel
                                ]
                            )
                    ]
                )
            );
        } else {
            $this->addElement('submit', 'btn_new_pane', ['label' => t('Add To Dashboard')]);
        }
    }

    public function onSuccess()
    {
        $requestPath = Url::fromRequest()->getPath();
        $orgHome = $this->dashboard->getHome(Url::fromRequest()->getParam('home'));

        if ($requestPath === DashboardHome::BASE_PATH . '/rename-pane'
            || $requestPath === DashboardHome::BASE_PATH . '/remove-pane'
            || $requestPath === DashboardHome::BASE_PATH . '/new-pane') {
            if ($this->getPopulatedValue('btn_update')) { // Update a pane
                $this->renamePane();
            } elseif ($this->getPopulatedValue('btn_new_pane')) { // New Dashboard
                $this->createNewPane();
            } else { // Remove a pane & it's child refs
                $orgPane = $this->getValue('org_name');
                $pane = $orgHome->getPane($orgPane);

                $orgHome->removePane($pane->getName());

                Notification::success(t('Pane has been successfully removed: ') . $pane->getTitle());
            }
        } else { // Dashboard homes
            if ($this->getPopulatedValue('btn_update')) {
                DashboardHome::getConn()->update(DashboardHome::TABLE, [
                    'label' => $this->getValue('title')
                ], ['id = ?' => $orgHome->getId()]);

                Notification::success(sprintf(
                    t('Dashboard home "%s" has been successfully renamed to "%s".'),
                    $orgHome->getLabel(),
                    $this->getValue('title')
                ));
            } else { // Remove home
                $this->dashboard->removeHome($orgHome->getName());

                Notification::success(sprintf(
                    t('Dashboard home "%s" has been successfully removed.'),
                    $orgHome->getName()
                ));
            }
        }
    }

    protected function createNewPane()
    {

    }

    protected function renamePane()
    {
        $db = DashboardHome::getConn();
        $username = $this->dashboard->getUser()->getUsername();

        $orgHome = $this->dashboard->getHome(Url::fromRequest()->getParam('home'));

        $orgPane = $this->getValue('org_name');
        $pane = $orgHome->getPane($orgPane);

        $newHome = $this->getPopulatedValue('home', $orgHome->getName());
        $homeId = $orgHome->getId();

        $db->beginTransaction();

        $newHomeCreated = false;
        if ($this->dashboard->hasHome($newHome) && $orgHome->getName() !== $newHome) {
            $this->dashboard->loadDashboards($newHome);
            $homeId = $this->dashboard->getHome($newHome)->getId();
        } elseif (! $this->dashboard->hasHome($newHome)) {
            $db->insert(DashboardHome::TABLE, ['name' => $newHome, 'label' => $newHome, 'owner' => $username]);

            $homeId = $db->lastInsertId();
            $newHomeCreated = true;
        }

        if (! $newHomeCreated && $orgHome->getName() !== $newHome
            && $this->dashboard->getActiveHome()->hasPane($pane->getName())) {
            Notification::error(
                sprintf(t('There is already a pane "%s" within this home.'), $pane->getName())
            );
            return;
        }

        $db->update(Pane::TABLE, [
            'home_id'   => $homeId,
            'label'     => $this->getPopulatedValue('title', $pane->getTitle())
        ], ['id = ?' => $pane->getId()]);

        $db->commitTransaction();

        $message = sprintf(
            t('Pane "%s" successfully renamed to "%s".'),
            $pane->getTitle(),
            $this->getValue('title')
        );

        if ($orgHome->getName() !== $newHome) {
            $message = sprintf(
                t('Pane "%s" successfully moved from "%s" to "%s"'),
                $this->getValue('title'),
                $orgHome->getName(),
                $newHome
            );
        }

        Notification::success($message);
    }
}

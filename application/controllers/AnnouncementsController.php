<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Exception\NotFoundError;
use Icinga\Forms\Announcement\AcknowledgeAnnouncementForm;
use Icinga\Forms\Announcement\AnnouncementForm;
use Icinga\Web\Announcement\AnnouncementIniRepository;
use Icinga\Web\Controller;
use Icinga\Web\Url;

class AnnouncementsController extends Controller
{
    public function init()
    {
        $this->view->title = $this->translate('Announcements');

        parent::init();
    }

    /**
     * List all announcements
     */
    public function indexAction()
    {
        $this->getTabs()->add(
            'announcements',
            array(
                'active'    => true,
                'label'     => $this->translate('Announcements'),
                'title'     => $this->translate('List All Announcements'),
                'url'       => Url::fromPath('announcements')
            )
        );

        $announcements = (new AnnouncementIniRepository())
            ->select([
                'id',
                'author',
                'message',
                'start',
                'end'
            ]);

        $sortAndFilterColumns = [
            'author'    => $this->translate('Author'),
            'message'   => $this->translate('Message'),
            'start'     => $this->translate('Start'),
            'end'       => $this->translate('End')
        ];

        $this->setupSortControl($sortAndFilterColumns, $announcements, ['start' => 'desc']);
        $this->setupFilterControl($announcements, $sortAndFilterColumns, ['message']);

        $this->view->announcements = $announcements->fetchAll();
    }

    /**
     * Create an announcement
     */
    public function newAction()
    {
        $this->assertPermission('application/announcements');

        $form = $this->prepareForm()->add();
        $form->handleRequest();
        $this->renderForm($form, $this->translate('New Announcement'));
    }

    /**
     * Update an announcement
     */
    public function updateAction()
    {
        $this->assertPermission('application/announcements');

        $form = $this->prepareForm()->edit($this->params->getRequired('id'));
        try {
            $form->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound($this->translate('Announcement not found'));
        }
        $this->renderForm($form, $this->translate('Update Announcement'));
    }

    /**
     * Remove an announcement
     */
    public function removeAction()
    {
        $this->assertPermission('application/announcements');

        $form = $this->prepareForm()->remove($this->params->getRequired('id'));
        try {
            $form->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound($this->translate('Announcement not found'));
        }
        $this->renderForm($form, $this->translate('Remove Announcement'));
    }

    public function acknowledgeAction()
    {
        $this->assertHttpMethod('POST');
        $this->getResponse()->setHeader('X-Icinga-Container', 'ignore', true);
        $form = new AcknowledgeAnnouncementForm();
        $form->handleRequest();
    }

    /**
     * Assert permission admin and return a prepared RepositoryForm
     *
     * @return AnnouncementForm
     */
    protected function prepareForm()
    {
        $form = new AnnouncementForm();
        return $form
            ->setRepository(new AnnouncementIniRepository())
            ->setRedirectUrl(Url::fromPath('announcements'));
    }
}

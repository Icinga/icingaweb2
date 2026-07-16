<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Controllers;

use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\Announcement\AcknowledgeAnnouncementForm;
use Icinga\Forms\Announcement\AnnouncementForm;
use Icinga\Repository\RepositoryMode;
use Icinga\Web\Announcement\AnnouncementIniRepository;
use Icinga\Web\Controller;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use ipl\Web\Url;
use ipl\Html\Contract\Form;
use Throwable;

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
            [
                'active'    => true,
                'label'     => $this->translate('Announcements'),
                'title'     => $this->translate('List All Announcements'),
                'url'       => Url::fromPath('announcements')
            ]
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
    public function newAction(): void
    {
        $this->assertPermission('application/announcements');

        $this->getTabs()->add('new-announcement', [
            'active' => true,
            'label'  => $this->translate('New Announcement'),
            'title'  => $this->translate('Add a new announcement'),
            'url'    => Url::fromRequest(),
        ]);

        $form = (new AnnouncementForm(new AnnouncementIniRepository(), RepositoryMode::Insert))
            ->setCsrfCounterMeasureId(Session::getSession()->getId())
            ->setRedirectUrl(Url::fromPath('announcements'))
            ->on(Form::ON_SUBMIT, function (AnnouncementForm $form): void {
                Notification::success($this->translate('Announcement created'));
                $this->redirectNow($form->getRedirectUrl());
            })
            ->on(Form::ON_ERROR, function (Throwable $_, AnnouncementForm $_form): void {
                Notification::error($this->translate('Failed to create announcement'));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->view->form = $form;
    }

    /**
     * Update an announcement
     */
    public function updateAction(): void
    {
        $this->assertPermission('application/announcements');

        $this->getTabs()->add('update-announcement', [
            'active' => true,
            'label'  => $this->translate('Update Announcement'),
            'title'  => $this->translate('Update an announcement'),
            'url'    => Url::fromRequest(),
        ]);

        $form = (new AnnouncementForm(
            new AnnouncementIniRepository(),
            RepositoryMode::Update,
            $this->params->getRequired('id')
        ))
            ->setCsrfCounterMeasureId(Session::getSession()->getId())
            ->setRedirectUrl(Url::fromPath('announcements'))
            ->on(Form::ON_SUBMIT, function (AnnouncementForm $form): void {
                Notification::success($this->translate('Announcement updated'));
                $this->redirectNow($form->getRedirectUrl());
            })
            ->on(Form::ON_ERROR, function (Throwable $_, AnnouncementForm $_form): void {
                Notification::error($this->translate('Failed to update announcement'));
            });

        try {
            $form->handleRequest(ServerRequest::fromGlobals());
        } catch (NotFoundError) {
            $this->httpNotFound($this->translate('Announcement not found'));
        }

        $this->view->form = $form;
    }

    /**
     * Remove an announcement
     */
    public function removeAction(): void
    {
        $this->assertPermission('application/announcements');

        $this->getTabs()->add('remove-announcement', [
            'active' => true,
            'label'  => $this->translate('Remove Announcement'),
            'title'  => $this->translate('Remove an announcement'),
            'url'    => Url::fromRequest(),
        ]);

        $form = (new AnnouncementForm(
            new AnnouncementIniRepository(),
            RepositoryMode::Delete,
            $this->params->getRequired('id')
        ))
            ->setCsrfCounterMeasureId(Session::getSession()->getId())
            ->setRedirectUrl(Url::fromPath('announcements'))
            ->on(Form::ON_SUBMIT, function (AnnouncementForm $form): void {
                Notification::success($this->translate('Announcement removed'));
                $this->redirectNow($form->getRedirectUrl());
            })
            ->on(Form::ON_ERROR, function (Throwable $_, AnnouncementForm $_form): void {
                Notification::error($this->translate('Failed to remove announcement'));
            });

        try {
            $form->handleRequest(ServerRequest::fromGlobals());
        } catch (NotFoundError) {
            $this->httpNotFound($this->translate('Announcement not found'));
        }

        $this->view->form = $form;
    }

    public function acknowledgeAction()
    {
        $this->assertHttpMethod('POST');
        $this->getResponse()->setHeader('X-Icinga-Container', 'ignore', true);
        $form = new AcknowledgeAnnouncementForm();
        $form->handleRequest();
    }
}

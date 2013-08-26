<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

use \Icinga\Web\Controller\BasePreferenceController;
use \Icinga\Web\Widget\Tab;
use \Icinga\Application\Config as IcingaConfig;
use \Icinga\Web\Url;
use \Icinga\Form\Preference\GeneralForm;

/**
 * Application wide preference controller for user preferences
 */
class PreferenceController extends BasePreferenceController
{

    /**
     * This controller modifies the session
     *
     * @var bool
     *
     * @see \Icinga\Web\Controller\ActionController::$modifiesSession
     */
    protected $modifiesSession = true;

    /**
     * Create tabs for this preference controller
     *
     * @return  array
     *
     * @see     BasePreferenceController::createProvidedTabs()
     */
    public static function createProvidedTabs()
    {
        return array(
            'preference' => new Tab(
                array(
                    'name'      => 'general',
                    'title'     => 'General settings',
                    'url'       => Url::fromPath('/preference')
                )
            )
        );
    }

    /**
     * General settings for date and time
     */
    public function indexAction()
    {
        $form = new GeneralForm();
        $form->setConfiguration(IcingaConfig::app());
        $form->setRequest($this->getRequest());
        if ($form->isSubmittedAndValid()) {
            $preferences = $form->getPreferences();
            $userPreferences = $this->getRequest()->getUser()->getPreferences();

            $userPreferences->startTransaction();
            foreach ($preferences as $key => $value) {
                if (!$value) {
                    $userPreferences->remove($key);
                } else {
                    $userPreferences->set($key, $value);
                }
            }
            try {
                $userPreferences->commit();
                $this->view->success = true;

                // recreate form to show new values
                $form = new GeneralForm();
                $form->setConfiguration(IcingaConfig::app());
                $form->setRequest($this->getRequest());

            } catch (Exception $e) {
                $this->view->exceptionMessage = $e->getMessage();
            }
        }

        $this->view->form = $form;
    }
}
// @codingStandardsIgnoreEnd

<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Navigation;

class DashletForm extends NavigationItemForm
{
    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'pane',
            [
                'required'      => true,
                'label'         => $this->translate('Pane'),
                'description'   => $this->translate('The name of the dashboard pane in which to display this dashlet')
            ]
        );
        $this->addElement(
            'text',
            'url',
            [
                'required'      => true,
                'label'         => $this->translate('Url'),
                'description'   => $this->translate(
                    'The url to load in the dashlet. For external urls, make sure to prepend'
                    . ' an appropriate protocol identifier (e.g. http://example.tld)'
                )
            ]
        );
    }
}

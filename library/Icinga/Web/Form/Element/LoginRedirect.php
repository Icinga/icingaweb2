<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Form\Element;

use Icinga\Exception\Http\HttpBadRequestException;
use Icinga\Forms\Authentication\LoginForm;
use Icinga\Web\Url;
use ipl\Html\FormElement\HiddenElement;

/**
 * Hidden form element holding the post-login redirect URL
 */
class LoginRedirect extends HiddenElement
{
    /**
     * Return the redirect target for a successful login as a validated, internal Url
     *
     * Falls back to {@see LoginForm::REDIRECT_URL} when the stored value is empty or
     * points to the logout action.
     *
     * @return Url
     *
     * @throws HttpBadRequestException If the redirect URL is external
     */
    public function getUrl(): Url
    {
        $redirect = parent::getValue();

        if (empty($redirect) || str_contains($redirect, 'authentication/logout')) {
            $redirect = LoginForm::REDIRECT_URL;
        }

        $url = Url::fromPath($redirect);
        if ($url->isExternal()) {
            throw new HttpBadRequestException('Redirect to an external URL is not allowed');
        }

        return $url;
    }
}

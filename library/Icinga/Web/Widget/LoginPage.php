<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Web\Widget;

use Icinga\Authentication\LoginButtonForm;
use ipl\Html\Attributes;
use ipl\Html\Html;
use ipl\Html\HtmlDocument;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Web\Compat\CompatForm;
use ipl\Web\Url;

/**
 * Login page with logo, footer, social links, and decorative orbs
 *
 * Wraps any form content in the standard login page structure. Used for
 * both the login form and the 2FA challenge form so the visual chrome is
 * defined in one place.
 *
 * Extends `HtmlDocument` rather than `BaseHtmlElement` because the login
 * page emits `#login` and seven `.orb` sibling divs at the top level —
 * there is no single root tag.
 */
class LoginPage extends HtmlDocument
{
    use Translation;

    /**
     * Create a new LoginPage
     *
     * @param CompatForm $form Primary form to render in the center of the login box
     * @param LoginButtonForm[] $loginButtons Additional login button forms to render below the primary form
     * @param bool $requiresSetup Whether to show the setup-wizard configuration note
     */
    public function __construct(
        protected CompatForm $form,
        protected array $loginButtons = [],
        protected bool $requiresSetup = false
    ) {
        $login = HtmlElement::create(
            'div',
            Attributes::create(['id' => 'login']),
            [$this->assembleLoginForm(), $this->assembleSocialLinks()]
        );

        $this->addHtml($login);

        foreach ($this->assembleOrbs() as $orb) {
            $this->addHtml($orb);
        }
    }

    /**
     * Assemble the centered login box containing the logo, form content, and footer
     *
     * @return HtmlElement
     */
    protected function assembleLoginForm(): HtmlElement
    {
        $accessibilityNotice = HtmlElement::create(
            'div',
            Attributes::create(['role' => 'status', 'class' => 'sr-only']),
            Text::create($this->translate(
                'Welcome to Icinga Web 2. For users of the screen reader Jaws full and expectant compliant'
                . ' accessibility is possible only with use of the Firefox browser. VoiceOver on Mac OS X is'
                . ' tested on Chrome, Safari and Firefox.'
            ))
        );

        $logo = HtmlElement::create(
            'div',
            Attributes::create(['class' => 'logo-wrapper']),
            HtmlElement::create('div', Attributes::create(['id' => 'icinga-logo', 'aria-hidden' => 'true']))
        );

        $inner = HtmlElement::create(
            'div',
            Attributes::create(['class' => 'login-form', 'data-base-target' => 'layout'])
        );

        $inner->addHtml($accessibilityNotice, $logo);

        if ($this->requiresSetup) {
            $inner->addHtml($this->assembleSetupNote());
        }

        $inner->addHtml($this->form);

        if ($this->loginButtons !== []) {
            $loginButtons = HtmlElement::create('div', Attributes::create(['class' => 'login-buttons']));
            foreach ($this->loginButtons as $button) {
                $loginButtons->addHtml($button);
            }
            $inner->addHtml($loginButtons);
        }

        $inner->addHtml($this->assembleFooter());

        return $inner;
    }

    /**
     * Assemble the setup-wizard configuration note shown when no authentication method is configured
     *
     * @return HtmlElement
     */
    protected function assembleSetupNote(): HtmlElement
    {
        $setupNote = $this->translate(
            'It appears that you did not configure Icinga Web 2 yet so it\'s not possible to log in'
            . ' without any defined authentication method. Please define an authentication method by'
            . ' following the instructions in the %1$s or by using our %2$s.',
            '<documentation_link> or by using our <setup-wizard_link>'
        );

        $docLink = HtmlElement::create(
            'a',
            Attributes::create([
                'href'  => 'https://icinga.com/docs/icinga-web-2/latest/doc/05-Authentication/#authentication',
                'title' => $this->translate('Icinga Web 2 Documentation')
            ]),
            Text::create($this->translate('documentation'))
        );

        $setupLink = HtmlElement::create(
            'a',
            Attributes::create([
                'href'  => Url::fromPath('setup')->getAbsoluteUrl(),
                'title' => $this->translate('Icinga Web 2 Setup-Wizard')
            ]),
            Text::create($this->translate('web-based setup-wizard'))
        );

        return HtmlElement::create(
            'p',
            Attributes::create(['class' => 'config-note']),
            Html::sprintf($setupNote, $docLink, $setupLink)
        );
    }

    /**
     * Assemble the footer containing the copyright notice and the icinga.com link
     *
     * @return HtmlElement
     */
    protected function assembleFooter(): HtmlElement
    {
        $copyright = HtmlElement::create('p', null, Text::create('Icinga Web 2 © 2013-' . date('Y')));

        $icingaLink = HtmlElement::create(
            'a',
            Attributes::create(['href' => 'https://icinga.com']),
            Text::create('icinga.com')
        );

        return HtmlElement::create('div', Attributes::create(['id' => 'login-footer']), [$copyright, $icingaLink]);
    }

    /**
     * Assemble the social links list rendered in the bottom-right corner of the page
     *
     * @return HtmlElement
     */
    protected function assembleSocialLinks(): HtmlElement
    {
        $facebook = HtmlElement::create(
            'li',
            null,
            HtmlElement::create(
                'a',
                Attributes::create([
                    'href'       => 'https://www.facebook.com/icinga',
                    'target'     => '_blank',
                    'title'      => $this->translate('Icinga on Facebook'),
                    'aria-label' => $this->translate('Icinga on Facebook'),
                    'rel'        => 'noopener noreferrer'
                ]),
                HtmlElement::create('i', Attributes::create([
                    'class'       => 'icon-facebook-squared',
                    'aria-hidden' => 'true'
                ]))
            )
        );

        $github = HtmlElement::create(
            'li',
            null,
            HtmlElement::create(
                'a',
                Attributes::create([
                    'href'       => 'https://github.com/Icinga',
                    'target'     => '_blank',
                    'title'      => $this->translate('Icinga on GitHub'),
                    'aria-label' => $this->translate('Icinga on GitHub'),
                    'rel'        => 'noopener noreferrer'
                ]),
                HtmlElement::create('i', Attributes::create([
                    'class'       => 'icon-github-circled',
                    'aria-hidden' => 'true'
                ]))
            )
        );

        return HtmlElement::create('ul', Attributes::create(['id' => 'social']), [$facebook, $github]);
    }

    /**
     * Assemble the decorative orb elements positioned around the background
     *
     * @return HtmlElement[]
     */
    protected function assembleOrbs(): array
    {
        $orbs = [
            'orb-analytics'      => 'orb-analytics.png',
            'orb-automation'     => 'orb-automation.png',
            'orb-cloud'          => 'orb-cloud.png',
            'orb-icinga'         => 'orb-icinga.png',
            'orb-infrastructure' => 'orb-infrastructure.png',
            'orb-metrics'        => 'orb-metrics.png',
            'orb-notifications'  => 'orb-notifications.png'
        ];

        $elements = [];
        foreach ($orbs as $id => $file) {
            $elements[] = HtmlElement::create(
                'div',
                Attributes::create(['id' => $id, 'class' => 'orb']),
                HtmlElement::create('img', Attributes::create([
                    'src'         => Url::fromPath('img/' . $file)->getAbsoluteUrl(),
                    'alt'         => '',
                    'aria-hidden' => 'true'
                ]))
            );
        }

        return $elements;
    }
}

<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Session;
use ipl\Html\BaseHtmlElement;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Html\Text;
use ipl\Web\Compat\CompatController;
use ipl\Web\Url;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Tabs;

/**
 * Display external or internal links within an iframe
 */
class IframeController extends CompatController
{
    /**
     * Display iframe w/ the given URL
     */
    public function indexAction(): void
    {
        $url = Url::fromPath($this->params->getRequired('url'));
        $urlHash = $this->getRequest()->getHeader('X-Icinga-URLHash');
        $expectedHash = hash('sha256', $url->getAbsoluteUrl() . Session::getSession()->getId());
        $iframeUrl = Url::fromPath('iframe', ['url' => $url->getAbsoluteUrl()]);

        if (! in_array($url->getScheme(), ['http', 'https'], true)) {
            $this->httpBadRequest('Invalid URL scheme');
        }

        $this->injectTabs();

        $this->getTabs()->setRefreshUrl($iframeUrl);

        if ($urlHash) {
            if ($urlHash !== $expectedHash) {
                $this->httpBadRequest('Invalid URL hash');
            }
        } else {
            $this->addContent(Html::tag('div', ['class' => 'iframe-warning'], [
                Html::tag('h2', $this->translate('Attention!')),
                Html::tag('p', ['class' => 'note'], $this->translate(
                    'You are about to open untrusted content embedded in Icinga Web! Only proceed,'
                    .' by clicking the link below, if you recognize and trust the source!'
                )),
                Html::tag('a', ['data-url-hash' => $expectedHash, 'href' => Html::escape($iframeUrl)], $url),
                Html::tag('p', ['class' => 'reason'], [
                    new Icon('circle-info'),
                    Text::create($this->translate(
                        'You see this warning because you do not seem to have followed a link in Icinga Web.'
                        . ' You can bypass this in the future by configuring a navigation item instead.'
                    ))
                ])
            ]));

            return;
        }

        $this->getTabs()->setHash($expectedHash);

        $this->addContent(Html::tag(
            'div',
            ['class' => 'iframe-container'],
            Html::tag('iframe', [
                'src' => $url,
                'sandbox' => 'allow-same-origin allow-scripts allow-popups allow-forms',
            ])
        ));
    }

    private function injectTabs(): void
    {
        $this->tabs = new class extends Tabs {
            private $hash;

            public function setHash($hash)
            {
                $this->hash = $hash;

                return $this;
            }

            protected function assemble()
            {
                $tabHtml = substr($this->tabs->render(), 34, -5);
                if ($this->refreshUrl !== null) {
                    $tabHtml = preg_replace(
                        [
                            '/(?<=class="refresh-container-control spinner" href=")([^"]*)/',
                            '/(\s)(?=href)/'
                        ],
                        [
                            $this->refreshUrl->getAbsoluteUrl(),
                            ' data-url-hash="' . $this->hash . '" '
                        ],
                        $tabHtml
                    );
                }

                BaseHtmlElement::add(HtmlString::create($tabHtml));
            }
        };

        $this->controls->setTabs($this->tabs);
    }
}

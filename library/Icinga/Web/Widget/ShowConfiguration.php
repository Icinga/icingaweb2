<?php
/* Icinga Web 2 | (c) 2026 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Widget;

use Exception;
use Icinga\Application\Config;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\HtmlString;
use ipl\I18n\Translation;
use ipl\Web\Widget\CopyToClipboard;

/**
 * Widget to show the configuration that couldn't be saved.
 */
class ShowConfiguration extends BaseHtmlElement
{
    use Translation;

    protected $tag = 'div';

    /**
     * Create a new ShowConfiguration widget
     * @param Exception $exception The exception that was thrown
     * @param Config $config The configuration instance
     */
    public function __construct(
        protected Exception $exception,
        protected Config $config,
    ) {
    }

    protected function assemble(): void
    {
        $this->addHtml(HtmlElement::create(
            'h4',
            null,
            t('Saving Configuration Failed!'),
        ));

        $this->addHtml(HtmlElement::create(
            'p',
            null,
            [
                sprintf(
                    t("The file %s couldn't be stored. (Error: '%s')"),
                    $this->config->getConfigFile(),
                    $this->exception->getMessage(),
                ),
                HtmlString::create('<br>'),
                t('This could have one or more of the following reasons:'),
            ],
        ));

        $this->addHtml(HtmlElement::create(
            'ul',
            null,
            [
                HtmlElement::create('li', null, t("You don't have file-system permissions to write to the file")),
                HtmlElement::create('li', null, t('Something went wrong while writing the file')),
                HtmlElement::create(
                    'li',
                    null,
                    t("There's an application error preventing you from persisting the configuration"),
                ),
            ],
        ));

        $this->addHtml(HtmlElement::create(
            'p',
            null,
            [
                t(
                    'Details can be found in the application log. ' .
                    "(If you don't have access to this log, call your administrator in this case)",
                ),
                HtmlString::create('<br>'),
                t('In case you can access the file by yourself, you can open it and insert the config manually:'),
            ],
        ));

        $code = HtmlElement::create('code', null, (string) $this->config);
        CopyToClipboard::attachTo($code);

        $this->addHtml(HtmlElement::create(
            'p',
            null,
            HtmlElement::create(
                'pre',
                null,
                $code,
            ),
        ));
    }
}

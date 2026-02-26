<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms\Authentication;

use Icinga\Application\Hook\LoginButton\LoginButton;
use Icinga\Web\Session;
use ipl\Html\Form;
use ipl\Html\FormElement\SubmitButtonElement;
use ipl\Html\Html;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;

/**
 * Form for user authentication via external identity providers
 */
class ButtonForm extends Form
{
    use FormUid;
    use CsrfCounterMeasure;

    public function __construct(
        string $name,
        protected readonly LoginButton $button,
        protected readonly ?string $moduleName = null
    ) {
        $this->defaultAttributes['name'] = $name;
    }

    protected function assemble(): void
    {
        $button = new SubmitButtonElement('btn_submit', $this->button->attributes);

        if ($this->moduleName) {
            $button->addAttributes(['class' => "icinga-module module-$this->moduleName"]);
        }

        $this->addElement($button->addHtml($this->button->content));
        $this->addHtml($this->createUidElement());
        $this->addElement($this->createCsrfCounterMeasure(Session::getSession()->getId()));
    }
}

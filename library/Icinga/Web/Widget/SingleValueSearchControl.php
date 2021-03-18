<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Application\Icinga;
use ipl\Html\Form;
use ipl\Html\FormElement\InputElement;
use ipl\Html\HtmlElement;
use ipl\Web\Url;

class SingleValueSearchControl extends Form
{
    /** @var string */
    const DEFAULT_SEARCH_PARAMETER = 'q';

    protected $defaultAttributes = ['class' => 'icinga-controls inline'];

    /** @var string */
    protected $searchParameter = self::DEFAULT_SEARCH_PARAMETER;

    /** @var string */
    protected $inputLabel;

    /** @var string */
    protected $submitLabel;

    /** @var Url */
    protected $suggestionUrl;

    /**
     * Set the search parameter to use
     *
     * @param   string $name
     * @return  $this
     */
    public function setSearchParameter($name)
    {
        $this->searchParameter = $name;

        return $this;
    }

    /**
     * Set the input's label
     *
     * @param string $label
     *
     * @return $this
     */
    public function setInputLabel($label)
    {
        $this->inputLabel = $label;

        return $this;
    }

    /**
     * Set the submit button's label
     *
     * @param string $label
     *
     * @return $this
     */
    public function setSubmitLabel($label)
    {
        $this->submitLabel = $label;

        return $this;
    }

    /**
     * Set the suggestion url
     *
     * @param   Url $url
     *
     * @return  $this
     */
    public function setSuggestionUrl(Url $url)
    {
        $this->suggestionUrl = $url;

        return $this;
    }

    protected function assemble()
    {
        $suggestionsId = Icinga::app()->getRequest()->protectId('single-value-suggestions');

        $this->addElement(
            'text',
            $this->searchParameter,
            [
                'required'              => true,
                'minlength'             => 1,
                'autocomplete'          => 'off',
                'data-enrichment-type'  => 'completion',
                'data-term-suggestions' => '#' . $suggestionsId,
                'data-suggest-url'      => $this->suggestionUrl,
                'placeholder'           => $this->inputLabel
            ]
        );

        $this->addElement(
            'submit',
            'btn_sumit',
            [
                'label' => $this->submitLabel,
                'class' => 'btn-primary'
            ]
        );

        $this->add(new HtmlElement('div', [
            'id'    => $suggestionsId,
            'class' => 'search-suggestions'
        ]));
    }

    /**
     * Create a list of search suggestions
     *
     * @param array $values
     *
     * @return HtmlElement
     */
    public static function createSuggestions(array $values)
    {
        $ul = new HtmlElement('ul');

        foreach ($values as $value) {
            $ul->add(new HtmlElement('li', null, new InputElement(null, [
                'value'     => $value,
                'type'      => 'button',
                'tabindex'  => -1
            ])));
        }

        return $ul;
    }
}

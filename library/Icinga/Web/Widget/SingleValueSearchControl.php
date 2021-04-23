<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Application\Icinga;
use ipl\Html\Form;
use ipl\Html\FormElement\InputElement;
use ipl\Html\HtmlElement;
use ipl\Web\Control\SearchBar\Suggestions;
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

    /** @var array */
    protected $metaDataNames;

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

    /**
     * Set names for which hidden meta data elements should be created
     *
     * @param string ...$names
     *
     * @return $this
     */
    public function setMetaDataNames(...$names)
    {
        $this->metaDataNames = $names;

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
                'class'                 => 'search',
                'data-enrichment-type'  => 'completion',
                'data-term-suggestions' => '#' . $suggestionsId,
                'data-suggest-url'      => $this->suggestionUrl,
                'placeholder'           => $this->inputLabel
            ]
        );

        if (! empty($this->metaDataNames)) {
            $fieldset = new HtmlElement('fieldset');
            foreach ($this->metaDataNames as $name) {
                $hiddenElement = $this->createElement('hidden', $this->searchParameter . '-' . $name);
                $this->registerElement($hiddenElement);
                $fieldset->add($hiddenElement);
            }

            $this->getElement($this->searchParameter)->prependWrapper($fieldset);
        }

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
     * Create a list of search suggestions based on the given groups
     *
     * @param array $groups
     *
     * @return HtmlElement
     */
    public static function createSuggestions(array $groups)
    {
        $ul = new HtmlElement('ul');
        foreach ($groups as list($name, $entries)) {
            if ($name) {
                if ($entries === false) {
                    $ul->add(new HtmlElement('li', ['class' => 'failure-message'], [
                        new HtmlElement('em', null, t('Can\'t search:')),
                        $name
                    ]));
                    continue;
                } elseif (empty($entries)) {
                    $ul->add(new HtmlElement('li', ['class' => 'failure-message'], [
                        new HtmlElement('em', null, t('No results:')),
                        $name
                    ]));
                    continue;
                } else {
                    $ul->add(new HtmlElement('li', ['class' => Suggestions::SUGGESTION_TITLE_CLASS], $name));
                }
            }

            foreach ($entries as list($label, $metaData)) {
                $attributes = [
                    'value'     => $label,
                    'type'      => 'button',
                    'tabindex'  => -1
                ];
                foreach ($metaData as $key => $value) {
                    $attributes['data-' . $key] = $value;
                }

                $ul->add(new HtmlElement('li', null, new InputElement(null, $attributes)));
            }
        }

        return $ul;
    }
}

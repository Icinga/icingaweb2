<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Validator;

use Icinga\Web\Url;

/**
 * Validator that checks whether a textfield's value matches the pattern http[s]://<HOST>[:<PORT>][/<BASE_LOCATION>]
 */
class HttpUrlValidator extends UrlValidator
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->_messageTemplates['NO_SCHEME'] = t('Scheme missing');
        $this->_messageTemplates['BAD_SCHEME'] = t('Bad scheme (must be either http or https)');
        $this->_messageTemplates['NO_HOST'] = t('Host missing');
    }

    public function isValid($value)
    {
        if (! parent::isValid($value)) {
            return false;
        }

        $url = Url::fromPath($value);

        switch (true) {
            case $url->getScheme() === null:
                $this->_error('NO_SCHEME');
                return false;

            case ! in_array($url->getScheme(), array('http', 'https')):
                $this->_error('BAD_SCHEME');
                return false;

            case $url->getHost() === null:
                $this->_error('NO_HOST');
                return false;
        }

        return true;
    }
}

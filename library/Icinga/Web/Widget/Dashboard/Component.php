<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
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
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Util\Dimension;
use Icinga\Web\Form;
use Icinga\Web\Url;
use Icinga\Web\Widget\AbstractWidget;
use Icinga\Web\View;
use Zend_Config;
use Zend_Form_Element_Submit;
use Zend_Form_Element_Button;
use Exception;

/**
 * A dashboard pane component
 *
 * This is the element displaying a specific view in icinga2web
 *
 */
class Component extends AbstractWidget
{
    /**
     * The url of this Component
     *
     * @var \Icinga\Web\Url
     */
    private $url;

    /**
     * The title being displayed on top of the component
     * @var
     */
    private $title;

    /**
     * The pane containing this component, needed for the 'remove button'
     * @var Pane
     */
    private $pane;

    /**
     * The template string used for rendering this widget
     *
     * @var string
     */
    private $template =<<<'EOD'

    <div class="container" data-icinga-url="{URL}">
        <h1>{REMOVE}<a href="{FULL_URL}" data-base-target="col1">{TITLE}</a></h1>
        <noscript>
            <iframe src="{IFRAME_URL}" style="height:100%; width:99%" frameborder="no"></iframe>
        </noscript>
    </div>
EOD;

    /**
     * Create a new component displaying the given url in the provided pane
     *
     * @param string $title     The title to use for this component
     * @param Url|string $url   The url this component uses for displaying information
     * @param Pane $pane        The pane this Component will be added to
     */
    public function __construct($title, $url, Pane $pane)
    {
        $this->title = $title;
        $this->pane = $pane;
        if ($url instanceof Url) {
            $this->url = $url;
        } elseif ($url) {
            $this->url = Url::fromPath($url);
        } else {
            throw new Exception(
                sprintf(
                    'Cannot create dashboard component "%s" without valid URL',
                    $title
                )
            );
        }
    }

    /**
     * Retrieve the components title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Retrieve the components url
     *
     * @return Url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set the components URL
     *
     * @param  string|Url $url  The url to use, either as an Url object or as a path
     *
     * @return self
     */
    public function setUrl($url)
    {
        if ($url instanceof Url) {
            $this->url = $url;
        } else {
            $this->url = Url::fromPath($url);
        }
        return $this;
    }

    /**
     * Return this component's structure as array
     *
     * @return  array
     */
    public function toArray()
    {
        $array = array('url' => $this->url->getPath());
        foreach ($this->url->getParams() as $key => $value) {
            $array[$key] = $value;
        }

        return $array;
    }

    /**
     * @see Widget::render()
     */
    public function render()
    {
        $view = $this->view();
        $url = clone($this->url);
        $url->addParams(array('view' => 'compact'));
        $iframeUrl = clone($url);
        $iframeUrl->addParams(array('_render' => 'iframe'));

        $html = str_replace('{URL}', $url, $this->template);
        $html = str_replace('{IFRAME_URL}', $iframeUrl, $html);
        $html = str_replace('{FULL_URL}', $url->getUrlWithout('view'), $html);
        $html = str_replace('{REMOVE_BTN}', $this->getRemoveForm($view), $html);
        $html = str_replace('{TITLE}', $view->escape($this->getTitle()), $html);
        $html = str_replace('{REMOVE}', $this->getRemoveForm(), $html);
        return $html;
    }

    /**
     * Render the form for removing a dashboard elemetn
     *
     * @return string                       The html representation of the form
     */
    protected function getRemoveForm()
    {
        // TODO: temporarily disabled, should point to a form asking for confirmal
        return '';
        $removeUrl = Url::fromPath(
            '/dashboard/removecomponent',
            array(
                'pane' => $this->pane->getName(),
                'component' => $this->getTitle()
            )
        );
        $form = new Form();
        $form->setMethod('POST');
        $form->setAttrib('class', 'inline');
        $form->setAction($removeUrl);
        $form->addElement(
            new Zend_Form_Element_Button(
                'remove_pane_btn',
                array(
                    'class'=> 'link-like pull-right',
                    'type' => 'submit',
                    'label' => 'x'
                )
            )
        );
        return $form;
    }

    /**
     * Create a @see Component instance from the given Zend config, using the provided title
     *
     * @param $title                    The title for this component
     * @param Zend_Config $config       The configuration defining url, parameters, height, width, etc.
     * @param Pane $pane                The pane this component belongs to
     *
     * @return Component                A newly created Component for use in the Dashboard
     */
    public static function fromIni($title, Zend_Config $config, Pane $pane)
    {
        $height = null;
        $width = null;
        $url = $config->get('url');
        $parameters = $config->toArray();
        unset($parameters['url']); // otherwise there's an url = parameter in the Url

        $cmp = new Component($title, Url::fromPath($url, $parameters), $pane);
        return $cmp;
    }
}

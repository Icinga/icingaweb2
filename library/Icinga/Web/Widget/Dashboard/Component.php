<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Widget\Dashboard;

use Icinga\Util\Dimension;
use Icinga\Web\Form;
use Icinga\Web\Url;
use Icinga\Web\Widget\Widget;
use Zend_View_Abstract;
use Zend_Config;
use Zend_Form_Element_Submit;
use Zend_Form_Element_Button;

/**
 * A dashboard pane component
 *
 * This is the element displaying a specific view in icinga2web
 *
 */
class Component implements Widget
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
     * The width of the component, if set
     *
     * @var Dimension|null
     */
    private $width = null;

    /**
     * The height of the component, if set
     *
     * @var Dimension|null
     */
    private $height = null;

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

    <div data-icinga-component="app/dashboard" style="overflow:hidden" class="dashboard-component" data-icinga-url="{URL}">
        <h1 class="pull-left"><a  data-icinga-target="self" href="{FULL_URL}"> {TITLE}</a></h1>
        {REMOVE_BTN}
        <div class="container" >

        </div>
        <noscript>
            <iframe src="{URL}" style="height:100%; width:99%" frameborder="no"></iframe>
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
        } else {
            $this->url = Url::fromPath($url);
        }
    }

    /**
     * Set the with for this component or use the default width if null is provided
     *
     * @param Dimension|null $width     The width to use or null to use the default width
     */
    public function setWidth(Dimension $width = null)
    {
        $this->width = $width;
    }

    /**
     * Set the with for this component or use the default height if null is provided
     *
     * @param Dimension|null $height     The height to use or null to use the default height
     */
    public function setHeight(Dimension $height = null)
    {
        $this->height = $height;
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
     * Return this component in a suitable format and encoding for ini files
     *
     * @return string
     */
    public function toIni()
    {
        $ini =  'url = "' . $this->url->getRelativeUrl() . '"' . PHP_EOL;
        foreach ($this->url->getParams() as $key => $val) {
            $ini .= $key.' = "' . $val . '"' . PHP_EOL;
        }
        if ($this->height !== null) {
            $ini .= 'height = "' . ((string) $this->height) . '"' . PHP_EOL;
        }
        if ($this->width !== null) {
            $ini .= 'width = "' . ((string) $this->width) . '"' . PHP_EOL;
        }
        return $ini;
    }

    /**
     * @see Widget::render()
     */
    public function render(Zend_View_Abstract $view)
    {
        $url = clone($this->url);
        $url->addParams(array('view' => 'compact'));

        $html = str_replace('{URL}', $url->getAbsoluteUrl(), $this->template);
        $html = str_replace('{FULL_URL}', $url->getUrlWithout('view')->getAbsoluteUrl(), $html);
        $html = str_replace('{REMOVE_BTN}', $this->getRemoveForm($view), $html);
        $html = str_replace('{DIMENSION}', $this->getBoxSizeAsCSS(), $html);
        $html = str_replace('{TITLE}', htmlentities($this->getTitle()), $html);
        return $html;
    }

    /**
     * Render the form for removing a dashboard elemetn
     *
     * @param  Zend_View_Abstract $view     The view to use for rendering
     * @return string                       The html representation of the form
     */
    public function getRemoveForm(Zend_View_Abstract $view)
    {
        $removeUrl = Url::fromPath(
            '/dashboard/removecomponent',
            array(
                'pane' => $this->pane->getName(),
                'component' => $this->getTitle()
            )
        );
        $form = new Form();
        $form->setMethod('POST');
        $form->setAction($removeUrl);
        $form->addElement(new Zend_Form_Element_Button(
            'remove_pane_btn',
            array(
                'class'=> 'btn btn-danger pull-right',
                'type' => 'submit',
                'label' => 'Remove'
            )
        ));
        return $form->render($view);
    }

    /**
     * Return the height and width deifnition (if given) in CSS format
     *
     * @return string
     */
    private function getBoxSizeAsCSS()
    {
        $style = '';
        if ($this->height) {
            $style .= 'height:' . (string) $this->height . ';';
        }
        if ($this->width) {
            $style .= 'width:' . (string) $this->width . ';';
        }
        return $style;
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

        if (isset($parameters['height'])) {
            $height = Dimension::fromString($parameters['height']);
            unset($parameters['height']);
        }

        if (isset($parameters['width'])) {
            $width = Dimension::fromString($parameters['width']);
            unset($parameters['width']);
        }

        $cmp = new Component($title, Url::fromPath($url, $parameters), $pane);
        $cmp->setHeight($height);
        $cmp->setWidth($width);
        return $cmp;
    }
}

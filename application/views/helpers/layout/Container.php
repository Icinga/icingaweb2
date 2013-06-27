<?php

class Zend_View_Helper_Container_State {
    private $CONTROL_BOX_CLASS = "container-controls";
    private $features = array();
    private $elementId = "";
    private $iframeFallback = false;
    private $url;
    private $class = "";
    private $id = "";
    private $refreshInterval = 0;
    private $view;
    public function __construct($containerid,array $flags,$view) {
        $this->view = $view;
        $this->id = $containerid;

        foreach ($flags as $type => $value) {
            if ($type === 'elementId') {
                $this->elementId = $value;
                continue;
            }
            if ($type === 'refreshInterval') {
                $this->refreshInterval = intval($type);
                continue;
            }
            if ($type == 'detachable' && $value == true) {
                $this->features["detachable"] = true;
                continue;
            }
            if ($type == 'expandable' && $value == true) {
                $this->features["expandable"] = true;
                continue;
            }

            if ($type == "icingaUrl") {
                $this->url = $value;
                continue;
            }
            if ($type == "iframeFallback") {
                $this->iframeFallback = true;
            }
            if ($type == 'class') {
                $this->class = $value;
                continue;
            }
        }
        return $this;
    }
    public function beginContent()
    {
        ob_start();
        return $this;

    }

    public function endContent()
    {
        $content = ob_get_contents();
        ob_end_clean();
        return $this->buildDOM($content);
    }

    public function buildDOM($content = "")
    {
        $additional = "";
        if ($this->refreshInterval > 0)
            $additional .= " container-refresh-interval='{$this->refreshInterval}' ";
        if ($this->elementId)
            $additional .= " id='$this->elementId'";
        $url = "";
        if ($this->url) {
            $url = $this->view->baseUrl($this->url);
            $additional .= "icinga-url='{$url}'";
            if($this->iframeFallback) {
                $content = "
                    <noscript><iframe src='$url' style='height:100%;width:100%'></iframe></noscript>
                ";
            }
        }

        $controls = $this->getControlDOM();

        $html = "
            <div class='icinga-container {$this->class}' container-id='{$this->id}' $additional >
                $controls
                $content
            </div>
        ";

        return $html;
    }

    private function getControlDOM()
    {
        if(empty($this->features))
            return "";
        $features = "";
        foreach($this->features as $feature=>$enabled) {
            if(!$enabled)
                continue;
            if($feature == "detachable") {
                $url = $this->view->baseUrl($this->url ? $this->url : Zend_Controller_Front::getInstance()->getRequest()->getRequestUri());
                $features .= "
                <a href='$url' class='container-detach-link' target='_blank' title='detach'><i class='icon-share'></i></a>";
            }
            if($feature == "expandable") {
                $url = $this->url ? $this->url : Zend_Controller_Front::getInstance()->getRequest()->getRequestUri();
                $features .= "
                <a href='$url' class='container-expand-link' target='_self' title='expand'><i class='icon-fullscreen'></i></a>";
            }
        }
        return "<div class='{$this->CONTROL_BOX_CLASS}'>$features</div>";
    }

    public function registerTabs($tabHelper)
    {

    }

    public function __toString() {
        return $this->endContent();
    }
}

class Zend_View_Helper_Container extends Zend_View_Helper_Abstract
{

    /**
     * @param $id
     * @param array $flags
     * @return Zend_View_Helper_Container
     */
    public function container($containerid, $flags = array())
    {
        return new Zend_View_Helper_Container_State($containerid,$flags,$this->view);
        
    }


}
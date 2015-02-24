<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Widget;

use Icinga\Web\Url;

/**
 * Limiter
 */
class Limiter extends AbstractWidget
{
    /**
     * The url
     *
     * @var Url
     */
    private $url;

    private $max;

    private $pages;

    public function setUrl(Url $url)
    {
        $this->url = $url;
        return $this;
    }

    public function setCurrentPageCount($pages)
    {
        $this->pages = $pages;
        return $this;
    }

    public function setMaxLimit($max)
    {
        $this->max = $max;
        return $this;
    }

    public function render()
    {
        if ($this->url === null) {
            $this->url = Url::fromRequest();
        }

        $currentLimit = (int) $this->url->getParam('limit', 25); // Default??
        $availableLimits = array(
            10 => '10',
            25 => '25',
            50 => '50',
            100 => '100',
            500 => '500'
        );
        if ($currentLimit === 0) {
            $availableLimits[0] = t('all');
        }

        // if ($this->pages === 1 && $currentLimit === 10) return '';

        $limits = array();
        $view = $this->view();
        $gotCurrent = false;
        foreach ($availableLimits as $limit => $caption) {
            if ($gotCurrent) {
                if ($this->pages === 1) {
                //    break;
                }
            }
            if ($this->max !== null && ($limit === 0 || $limit > $this->max)) {
            //echo "$limit > $this->max"; break;
            }
            if ($limit === $currentLimit) {
                $gotCurrent = true;
                $limits[] = $caption;
            } else {
                $limits[] = $view->qlink(
                    $caption,
                    $this->url->setParam('limit', $limit),
                    null,
                    array(
                        'title' => sprintf($view->translate('Limit each page to a maximum of %u rows'), $caption)
                    )
                );
            }
        }

        if (empty($limits)) return '';
        return '<span class="widgetLimiter">' . implode(' ', $limits) . '</span>';
    }
}

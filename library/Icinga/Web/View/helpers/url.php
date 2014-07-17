<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\View;

use Icinga\Web\Url;
use Icinga\Exception\ProgrammingError;

$view = $this;

$this->addHelperFunction('href', function ($path = null, $params = null) use ($view) {
    return $view->url($path, $params);
});

$this->addHelperFunction('url', function ($path = null, $params = null) {
    if ($path === null) {
        $url = Url::fromRequest();
    } elseif ($path instanceof Url) {
        $url = $path;
    } else {
        $url = Url::fromPath($path);
    }
    if ($params !== null) {
        $url->setParams($params);
    }

    return $url;
});


$this->addHelperFunction('qlink', function ($title, $url, $params = null, $properties = array()) use ($view) {
    return sprintf(
        '<a href="%s"%s>%s</a>',
        $view->url($url, $params),
        $view->propertiesToString($properties),
        $view->escape($title)
    );
});

$this->addHelperFunction('img', function ($url, array $properties = array()) use ($view) {
    if (! array_key_exists('alt', $properties)) {
        $properties['alt'] = '';
    }

    return sprintf(
        '<img src="%s"%s />',
        $view->url($url),
        $view->propertiesToString($properties)
    );
});

$this->addHelperFunction('icon', function ($img, $title = null, array $properties = array()) use ($view) {
    // TODO: join with classes passed in $properties?
    $attributes = array(
        'class' => 'icon',
    );
    if ($title !== null) {
        $attributes['alt'] = $title;
        $attributes['title'] = $title;
    }

    return $view->img(
        'img/icons/' . $img,
        array_merge($attributes, $properties)
    );
});

$this->addHelperFunction('propertiesToString', function ($properties) use ($view) {
    if (empty($properties)) {
        return '';
    }
    $attributes = array();

    foreach ($properties as $key => $val) {
        if ($key === 'style' && is_array($val)) {
            if (empty($val)) {
                continue;
            }
            $parts = array();
            foreach ($val as $k => $v) {
                $parts[] = "$k: $v";
            }
            $val = implode('; ', $parts);
            continue;
        }

        $attributes[] = $view->attributeToString($key, $val);
    }
    return ' ' . implode(' ', $attributes);
});

$this->addHelperFunction('attributeToString', function ($key, $value)
{
    // TODO: Doublecheck this!
    if (! preg_match('~^[a-zA-Z0-9-]+$~', $key)) {
        throw new ProgrammingError(sprintf(
            'Trying to set an invalid HTML attribute name: %s',
            $key
        ));
    }

    return sprintf(
        '%s="%s"',
        $key,
        $value
    );
});


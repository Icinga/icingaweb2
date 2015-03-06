<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
        $url->overwriteParams($params);
    }

    return $url;
});

$this->addHelperFunction('qlink', function ($title, $url, $params = null, $properties = null, $escape = true) use ($view) {
    $icon = '';
    if ($properties) {
        if (array_key_exists('title', $properties) && !array_key_exists('aria-label', $properties)) {
            $properties['aria-label'] = $properties['title'];
        }

        if (array_key_exists('icon', $properties)) {
            $icon = $view->icon($properties['icon']);
            unset($properties['icon']);
        }
    }

    return sprintf(
        '<a href="%s"%s>%s</a>',
        $view->url($url, $params),
        $view->propertiesToString($properties),
        $icon . ($escape ? $view->escape($title) : $title)
    );
});

$this->addHelperFunction('img', function ($url, $params = null, array $properties = array()) use ($view) {
    if (! array_key_exists('alt', $properties)) {
        $properties['alt'] = '';
    }

    $ariaHidden = array_key_exists('aria-hidden', $properties) ? $properties['aria-hidden'] : null;
    if (array_key_exists('title', $properties)) {
        if (! array_key_exists('aria-label', $properties) && $ariaHidden !== 'true') {
            $properties['aria-label'] = $properties['title'];
        }
    } elseif ($ariaHidden === null) {
        $properties['aria-hidden'] = 'true';
    }

    return sprintf(
        '<img src="%s"%s />',
        $view->url($url, $params),
        $view->propertiesToString($properties)
    );
});

$this->addHelperFunction('icon', function ($img, $title = null, array $properties = array()) use ($view) {
    if (strpos($img, '.') !== false) {
        if (array_key_exists('class', $properties)) {
            $properties['class'] .= ' icon';
        } else {
            $properties['class'] = 'icon';
        }

        return $view->img('img/icons/' . $img, $properties);
    }

    $ariaHidden = array_key_exists('aria-hidden', $properties) ? $properties['aria-hidden'] : null;
    if ($title !== null) {
        $properties['role'] = 'img';
        $properties['title'] = $title;

        if (! array_key_exists('aria-label', $properties) && $ariaHidden !== 'true') {
            $properties['aria-label'] = $title;
        }
    } elseif ($ariaHidden === null) {
        $properties['aria-hidden'] = 'true';
    }

    if (isset($properties['class'])) {
        $properties['class'] .= ' icon-' . $img;
    } else {
        $properties['class'] = 'icon-' . $img;
    }

    return sprintf('<i %s></i>', $view->propertiesToString($properties));
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

$this->addHelperFunction('attributeToString', function ($key, $value) use ($view) {
    // TODO: Doublecheck this!
    if (! preg_match('~^[a-zA-Z0-9-]+$~', $key)) {
        throw new ProgrammingError(
            'Trying to set an invalid HTML attribute name: %s',
            $key
        );
    }

    return sprintf(
        '%s="%s"',
        $key,
        $view->escape($value)
    );
});


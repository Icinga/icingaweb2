<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\View;

use Icinga\Web\Url;
use Icinga\Exception\ProgrammingError;
use ipl\Web\Widget\Icon;

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
        if ($url === $path) {
            $url = clone $url;
        }

        $url->overwriteParams($params);
    }

    return $url;
});

$this->addHelperFunction(
    'qlink',
    function ($title, $url, $params = null, $properties = null, $escape = true) use ($view) {
        $icon = '';
        if ($properties) {
            if (array_key_exists('title', $properties) && !array_key_exists('aria-label', $properties)) {
                $properties['aria-label'] = $properties['title'];
            }

            if (array_key_exists('icon', $properties)) {
                $icon = $view->icon($properties['icon']);
                unset($properties['icon']);
            }

            if (array_key_exists('img', $properties)) {
                $icon = $view->img($properties['img']);
                unset($properties['img']);
            }
        }

        return sprintf(
            '<a href="%s"%s>%s</a>',
            $view->url($url, $params),
            $view->propertiesToString($properties),
            $icon . ($escape ? $view->escape($title) : $title)
        );
    }
);

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
        $view->escape($view->url($url, $params)->getAbsoluteUrl()),
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
        if (strpos($img, '/') === false) {
            return $view->img('img/icons/' . $img, null, $properties);
        } else {
            return $view->img($img, null, $properties);
        }
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

    if (! isset($view::LEGACY_ICONS[$img]) || substr($img, 0, 3) === 'fa-') {
        // This may not be reached, as some legacy icons have equal names as fontawesome ones.
        // Though, this is a legacy helper, so in that case one gets legacy icons...
        return new Icon($img, $properties);
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

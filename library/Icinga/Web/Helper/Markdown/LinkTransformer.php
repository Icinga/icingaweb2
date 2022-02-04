<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Helper\Markdown;

use HTMLPurifier_AttrTransform;
use HTMLPurifier_Config;
use ipl\Web\Url;

class LinkTransformer extends HTMLPurifier_AttrTransform
{
    /**
     * Link targets that are considered to have a thumbnail
     *
     * @var string[]
     */
    public static $IMAGE_FILES = [
        'jpg',
        'jpeg',
        'png',
        'bmp',
        'gif',
        'heif',
        'heic',
        'webp'
    ];

    public function transform($attr, $config, $context)
    {
        if (! isset($attr['href'])) {
            return $attr;
        }

        $url = Url::fromPath($attr['href']);
        $fileName = basename($url->getPath());

        $ext = null;
        if (($extAt = strrpos($fileName, '.')) !== false) {
            $ext = substr($fileName, $extAt + 1);
        }

        $hasThumbnail = $ext !== null && in_array($ext, static::$IMAGE_FILES, true);
        if ($hasThumbnail) {
            // I would have liked to not only base this off of the extension, but also by
            // whether there is an actual img tag inside the anchor. Seems not possible :(
            $attr['class'] = 'with-thumbnail';
        }

        if (! isset($attr['target'])) {
            if ($url->isExternal()) {
                $attr['target'] = '_blank';
            } else {
                $attr['data-base-target'] = '_next';
            }
        }

        return $attr;
    }

    public static function attachTo(HTMLPurifier_Config $config)
    {
        $module = $config->getHTMLDefinition(true)
            ->getAnonymousModule();

        if (isset($module->info['a'])) {
            $a = $module->info['a'];
        } else {
            $a = $module->addBlankElement('a');
        }

        $a->attr_transform_post[] = new self();
    }
}

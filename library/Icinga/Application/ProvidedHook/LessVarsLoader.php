<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\ProvidedHook;

use Icinga\Module\Themebuilder\Hook\LessVarsLoaderHook;

class LessVarsLoader extends LessVarsLoaderHook
{
    public function getVariables(): array
    {
        return [
            '@black'                             => '#535353',
            '@white'                             => '#fff',
            '@gray'                              => '#c4c4c4',
            '@gray-semilight'                    => '#888',
            '@gray-light'                        => '#5c5c5c',
            '@gray-lighter'                      => '#4b4b4b',
            '@gray-lightest'                     => '#3a3a3a',
            '@disabled-gray'                     => '#9a9a9a',
            // Colors
            '@color-ok'                          => '#44bb77',
            '@color-up'                          => '@color-ok',
            '@color-warning'                     => '#ffaa44',
            '@color-warning-handled'             => '#ffcc66',
            '@color-critical'                    => '#ff5566',
            '@color-critical-handled'            => '#ff99aa',
            '@color-critical-accentuated'        => 'darken(@color-critical, 10%)',
            '@color-down'                        => '@color-critical',
            '@color-down-handled'                => '@color-critical-handled',
            '@color-unknown'                     => '#aa44ff',
            '@color-unknown-handled'             => '#cc77ff',
            '@color-unreachable'                 => '@color-unknown',
            '@color-unreachable-handled'         => '@color-unknown-handled',
            '@color-pending'                     => '#77aaff',
            '@icinga-blue'                       => '#00C3ED',
            '@icinga-secondary'                  => '#EF4F98',
            '@icinga-secondary-dark'             => 'darken(@icinga-secondary, 10%)',
            '@low-sat-blue'                      => '#404d72',
            '@low-sat-blue-dark'                 => '#434374',
            '@icinga-blue-light'                 => 'fade(@icinga-blue, 50%)',
            '@icinga-blue-dark'                  => '#0081a6',
            // Notification colors
            '@color-notification-error'          => '@color-critical',
            '@color-notification-info'           => '@color-pending',
            '@color-notification-success'        => '@color-ok',
            '@color-notification-warning'        => '@color-warning',
            // Background color for <body>
            '@body-bg-color'                     => '#282E39',
            '@body-bg-color-transparent'         => 'fade(@body-bg-color, 0)',
            // Text colors
            '@text-color'                        => '@white',
            '@text-color-inverted'               => '@body-bg-color',
            '@text-color-light'                  => 'fade(@text-color, 75%)',
            '@text-color-on-icinga-blue'         => '@body-bg-color',
            '@light-text-bg-color'               => 'fade(@gray, 5%)',
            // Text color on <a>
            '@link-color'                        => '@text-color',
            '@tr-active-color'                   => 'fade(@icinga-blue, 25)',
            '@tr-hover-color'                    => 'fade(@icinga-blue, 5)',
            // Menu colors
            '@menu-bg-color'                     => '#06062B',
            '@menu-hover-bg-color'               => 'lighten(@menu-bg-color, 5%)',
            '@menu-search-hover-bg-color'        => '@menu-hover-bg-color',
            '@menu-active-bg-color'              => '#181742',
            '@menu-active-hover-bg-color'        => 'lighten(@menu-active-bg-color, 5%)',
            '@menu-color'                        => '#DBDBDB',
            '@menu-active-color'                 => '@text-color',
            '@menu-highlight-color'              => '@icinga-blue',
            '@menu-highlight-hover-bg-color'     => '@icinga-blue-dark',
            '@menu-2ndlvl-color'                 => '#c4c4c4',
            '@menu-2ndlvl-highlight-bg-color'    => '@tr-hover-color',
            '@menu-2ndlvl-active-bg-color'       => '@menu-highlight-color',
            '@menu-2ndlvl-active-color'          => '@text-color-inverted',
            '@menu-2ndlvl-active-hover-bg-color' => 'darken(@menu-2ndlvl-active-bg-color, 5%)',
            '@menu-2ndlvl-active-hover-color'    => '@menu-2ndlvl-active-color',
            '@menu-flyout-bg-color'              => '@body-bg-color',
            '@menu-flyout-color'                 => '@text-color',
            '@tab-hover-bg-color'                => 'fade(@body-bg-color, 50%)',
            // Form colors
            '@form-info-bg-color'                => 'fade(@color-ok, 20%)',
            '@form-error-bg-color'               => 'fade(@color-critical, 30%)',
            '@form-warning-bg-color'             => 'fade(@color-warning, 40%)',
            '@login-box-background'              => 'fade(#0B0B2F, 30%)',
            // Other colors
            '@color-granted'                     => '#59cd59',
            '@color-refused'                     => '#ee7373',
            '@color-restricted'                  => '#dede7d',
            // Light mode
            '@light-body-bg-color'               => '#F5F9FA'
        ];
    }
}

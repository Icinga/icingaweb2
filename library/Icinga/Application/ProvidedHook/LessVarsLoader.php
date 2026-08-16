<?php

/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Application\ProvidedHook;

use Icinga\Module\Themebuilder\Hook\LessVarsLoaderHook;

class LessVarsLoader extends LessVarsLoaderHook
{
    public function getVariables(): array
    {
        return [
            '@black'                             => [
                'value'       => '#535353',
                'description' => 'Icinga Web 2 base color. You most likely won\'t use this variable directly but other variables are often derived from this one.'
            ],
            '@white'                             => [
                'value'       => '#fff',
                'description' => 'Icinga Web 2 base color. You most likely won\'t use this variable directly but other variables are often derived from this one.'
            ],
            '@gray'                              => [
                'value'       => '#c4c4c4',
                'description' => 'Icinga Web 2 base gray color.'
            ],
            '@gray-semilight'                    => [
                'value'       => '#888',
                'description' => 'Usually used to visualize borders but sometimes also for text colors.'
            ],
            '@gray-light'                        => [
                'value'       => '#5c5c5c',
                'description' => 'Usually used to visualize borders but sometimes also for text colors.'
            ],
            '@gray-lighter'                      => [
                'value'       => '#4b4b4b',
                'description' => 'Used to visualize borders, background and text colors.'
            ],
            '@gray-lightest'                     => [
                'value'       => '#3a3a3a',
                'description' => 'Mostly used for background colors.'
            ],
            '@disabled-gray'                     => [
                'value'       => '#9a9a9a',
                'description' => 'Used to display for disabled elements, like disabled input fields or disabled buttons.'
            ],
            // Colors
            '@color-ok'                          => [
                'value'       => '#44bb77',
                'description' => 'Used to display OK Service states.'
            ],
            '@color-up'                          => [
                'value'       => '@color-ok',
                'description' => 'Used to display healthy running hosts (UP states).'
            ],
            '@color-warning'                     => [
                'value' => '#ffaa44',
                'description' => 'Used to visualize services which are in a WARING state.'
            ],
            '@color-warning-handled'             => [
                'value'       => '#ffcc66',
                'description' => 'Used to visualize services which are in a WARING state and are handled. These services are either in a downtime or are acknowledged by the user.'
            ],
            '@color-critical'                    => [
                'value'       => '#ff5566',
                'description' => 'Used to visualize services which are in a CRITICAL state.'
            ],
            '@color-critical-handled'            => [
                'value'       => '#ff99aa',
                'description' => 'Used to visualize services which are in a CRITICAL state and are handled. These services are either in a downtime or are acknowledged by the user.'
            ],
            '@color-critical-accentuated'        => [
                'value'       => 'darken(@color-critical, 10%)',
                'description' => ''
            ],
            '@color-down'                        => [
                'value'       => '@color-critical',
                'description' => 'Used to visualize hosts which are in a DOWN state.'
            ],
            '@color-down-handled'                => [
                'value'       => '@color-critical-handled',
                'description' => 'Used to visualize hosts which are in a DOWN state and are handled by the user.'
            ],
            '@color-unknown'                     => [
                'value'       => '#aa44ff',
                'description' => 'Used to visualize services which are in an UNKNOWN state.'
            ],
            '@color-unknown-handled'             => [
                'value'       => '#cc77ff',
                'description' => 'Used to visualize services which are in an UNKNOWN state and handled by the user.'
            ],
            '@color-unreachable'                 => [
                'value'       => '@color-unknown',
                'description' => 'Used to visualize hosts which are in an UNREACHABLE state.'
            ],
            '@color-unreachable-handled'         => [
                'value'       => '@color-unknown-handled',
                'description' => 'Used to visualize hosts which are in an UNREACHABLE state and handled by the user.'
            ],
            '@color-pending'                     => [
                'value'       => '#77aaff',
                'description' => 'Used to visualize Hosts/Services which are in a PENDING state.'
            ],
            '@icinga-blue'                       => [
                'value'       => '#00C3ED',
                'description' => 'Icinga Web 2 blue color and is used mostly everywhere in Icinga Web 2 and by other modules.'
            ],
            '@icinga-secondary'                  => [
                'value'       => '#EF4F98',
                'description' => 'Icinga Web 2 secondary color. Used only for background colors e.g for the login page of Icinga Web 2.'
            ],
            '@icinga-secondary-dark'             => [
                'value'       => 'darken(@icinga-secondary, 10%)',
                'description' => 'Used mostly for background colors e.g for the submit button on the login page of Icinga Web 2.'
            ],
            '@low-sat-blue'                      => [
                'value'       => '#404d72',
                'description' => 'All Icinga Web 2 and other modules input fields use this as background color.'
            ],
            '@low-sat-blue-dark'                 => [
                'value'       => '#434374',
                'description' => 'Used as a background color or for visualizing borders.'
            ],
            '@icinga-blue-light'                 => [
                'value'       => 'fade(@icinga-blue, 50%)',
                'description' => 'The light version of Icinga Web 2 blue color.'
            ],
            '@icinga-blue-dark'                  => [
                'value'       => '#0081a6',
                'description' => 'Used to highlight button hovers and sometimes for background colors.'
            ],
            // Notification colors
            '@color-notification-error'          => [
                'value'       => '@color-critical',
                'description' => ''
            ],
            '@color-notification-info'           => [
                'value'       => '@color-pending',
                'description' => ''
            ],
            '@color-notification-success'        => [
                'value'       => '@color-ok',
                'description' => ''
            ],
            '@color-notification-warning'        => [
                'value'       => '@color-warning',
                'description' => ''
            ],
            // Background color for <body>
            '@body-bg-color'                     => [
                'value'       => '#282E39',
                'description' => ''
            ],
            '@body-bg-color-transparent'         => [
                'value'       => 'fade(@body-bg-color, 0)',
                'description' => ''
            ],
            // Text colors
            '@text-color'                        => [
                'value'       => '@white',
                'description' => ''
            ],
            '@text-color-inverted'               => [
                'value'       => '@body-bg-color',
                'description' => ''
            ],
            '@text-color-light'                  => [
                'value'       => 'fade(@text-color, 75%)',
                'description' => ''
            ],
            '@text-color-on-icinga-blue'         => [
                'value'       => '@body-bg-color',
                'description' => ''
            ],
            '@light-text-bg-color'               => [
                'value'       => 'fade(@gray, 5%)',
                'description' => ''
            ],
            // Text color on <a>
            '@link-color'                        => [
                'value'       => '@text-color',
                'description' => ''
            ],
            '@tr-active-color'                   => [
                'value'       => 'fade(@icinga-blue, 25)',
                'description' => ''
            ],
            '@tr-hover-color'                    => [
                'value'       => 'fade(@icinga-blue, 5)',
                'description' => ''
            ],
            // Menu colors
            '@menu-bg-color'                     => [
                'value'       => '#06062B',
                'description' => ''
            ],
            '@menu-hover-bg-color'               => [
                'value'       => 'lighten(@menu-bg-color, 5%)',
                'description' => ''
            ],
            '@menu-search-hover-bg-color'        => [
                'value'       => '@menu-hover-bg-color',
                'description' => ''
            ],
            '@menu-active-bg-color'              => [
                'value'       => '#181742',
                'description' => ''
            ],
            '@menu-active-hover-bg-color'        => [
                'value'       => 'lighten(@menu-active-bg-color, 5%)',
                'description' => ''
            ],
            '@menu-color'                        => [
                'value'       => '#DBDBDB',
                'description' => ''
            ],
            '@menu-active-color'                 => [
                'value'       => '@text-color',
                'description' => ''
            ],
            '@menu-highlight-color'              => [
                'value'       => '@icinga-blue',
                'description' => ''
            ],
            '@menu-highlight-hover-bg-color'     => [
                'value'       => '@icinga-blue-dark',
                'description' => ''
            ],
            '@menu-2ndlvl-color'                 => [
                'value'       => '#c4c4c4',
                'description' => ''
            ],
            '@menu-2ndlvl-highlight-bg-color'    => [
                'value'       => '@tr-hover-color',
                'description' => ''
            ],
            '@menu-2ndlvl-active-bg-color'       => [
                'value'       => '@menu-highlight-color',
                'description' => ''
            ],
            '@menu-2ndlvl-active-color'          => [
                'value'       => '@text-color-inverted',
                'description' => 'Used to highlight sub items of a main navigation menus.'
            ],
            '@menu-2ndlvl-active-hover-bg-color' => [
                'value'       => 'darken(@menu-2ndlvl-active-bg-color, 5%)',
                'description' => ''
            ],
            '@menu-2ndlvl-active-hover-color'    => [
                'value'       => '@menu-2ndlvl-active-color',
                'description' => ''
            ],
            '@menu-flyout-bg-color'              => [
                'value'       => '@body-bg-color',
                'description' => ''
            ],
            '@menu-flyout-color'                 => [
                'value'       => '@text-color',
                'description' => ''
            ],
            '@tab-hover-bg-color'                => [
                'value'       => 'fade(@body-bg-color, 50%)',
                'description' => ''
            ],
            // Form colors
            '@form-info-bg-color'                => [
                'value'       => 'fade(@color-ok, 20%)',
                'description' => ''
            ],
            '@form-error-bg-color'               => [
                'value'       => 'fade(@color-critical, 30%)',
                'description' => ''
            ],
            '@form-warning-bg-color'             => [
                'value'       => 'fade(@color-warning, 40%)',
                'description' => ''
            ],
            '@login-box-background'              => [
                'value'       => 'fade(#0B0B2F, 30%)',
                'description' => ''
            ],
            // Other colors
            '@color-granted'                     => [
                'value'       => '#59cd59',
                'description' => ''
            ],
            '@color-refused'                     => [
                'value'       => '#ee7373',
                'description' => ''
            ],
            '@color-restricted'                  => [
                'value'       => '#dede7d',
                'description' => ''
            ],
            // Light mode
            '@light-body-bg-color'               => [
                'value'       => '#F5F9FA',
                'description' => ''
            ]
        ];
    }
}

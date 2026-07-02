<?php

// SPDX-FileCopyrightText: 2026 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Config\Security;

use Exception;
use Icinga\Application\Config;
use Icinga\Security\Csp\AttributedCsp;
use Icinga\Security\Csp\Loader\DashboardCspLoader;
use Icinga\Security\Csp\Loader\ModuleCspLoader;
use Icinga\Security\Csp\Loader\NavigationCspLoader;
use Icinga\Security\Csp\Reason\DashboardCspReason;
use Icinga\Security\Csp\Reason\ModuleCspReason;
use Icinga\Security\Csp\Reason\NavigationCspReason;
use Icinga\Security\Csp\Reason\StaticCspReason;
use Icinga\Util\Csp;
use Icinga\Web\Form\ConfigForm;
use Icinga\Web\Session;
use ipl\Html\Attributes;
use ipl\Html\BaseHtmlElement;
use ipl\Html\HtmlElement;
use ipl\Html\Table;
use ipl\Html\Text;
use ipl\Validator\CallbackValidator;
use ipl\Web\Common\CalloutType;
use ipl\Web\Common\Csp as CspInstance;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\DisplayFormElement;
use ipl\Web\Widget\Callout;
use ipl\Web\Widget\Icon;
use ipl\Web\Widget\Link;

/**
 * Configuration form for CSP
 *
 * This form is used to configure the CSP-Header. It is used to enable or
 * disable CSP, configure the allowed sources for automatic generation or to
 * specify a custom CSP-Header.
 */
class CspConfigForm extends ConfigForm
{
    use FormUid;
    use CsrfCounterMeasure;

    /**
     * @var string[] List of all keywords that are considered secure. {@link https://centralcsp.com/docs/csp-keywords}
     */
    protected const SECURE_KEYWORDS = [
        "'self'",
        "'none'",
        "'strict-dynamic'",
        "'report-sample'",
        "'report-sha256'",
        "'report-sha384'",
        "'report-sha512'",
    ];

    /**
     * @var string[] List of all keywords that should display a warning.
     * {@link https://centralcsp.com/docs/csp-keywords}
     */
    protected const WARNING_KEYWORDS = [
        "'unsafe-inline'",
        "'unsafe-eval'",
        "'unsafe-hashes'",
    ];

    /**
     * @var string[] List of all schemes that are considered secure.
     * {@link https://centralcsp.com/docs/csp-scheme-source}
     */
    protected const SECURE_SCHEMES = [
        'https',
        'wss',
    ];

    /**
     * @var string[] List of all schemes that should display a warning.
     * {@link https://centralcsp.com/docs/csp-scheme-source}
     */
    protected const WARNING_SCHEMES = [
        'http',
        'ws',
        'blob',
    ];

    /** @var string[] List of directives where data is considered critical */
    protected const CRITICAL_DATA_DIRECTIVES = [
        'default-src',
        'script-src',
        'object-src',
        'frame-src',
    ];

    /** @var string[] List of directives where data is considered a warning */
    protected const WARNING_DATA_DIRECTIVES = [
        'style-src',
        'worker-src',
        'child-src',
        'base-uri',
    ];

    /**
     * @var int The number of rows for the custom CSP textarea
     */
    protected const TEXTAREA_ROWS = 8;

    /**
     * @var bool Whether the form contents changed the underlying configuration
     */
    protected bool $changed = false;

    /**
     * @param Config $config The config object
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);
        $this->setAttribute('name', 'csp_config');
        $this->getAttributes()->add('class', 'csp-config-form');
        $this->applyDefaultElementDecorators();
    }

    protected function assemble(): void
    {
        Csp::createNonce();

        $this->addElement($this->createUidElement());

        $this->addCsrfCounterMeasure(Session::getSession()->getId());

        $this->addElement('checkbox', 'security__use_strict_csp', [
            'label'          => $this->translate('Send CSP header'),
            'description'    => $this->translate(
                'Use strict content security policy (CSP).'
                . ' This setting helps to protect from cross-site scripting (XSS).',
            ),
            'class'          => 'autosubmit',
            'checkedValue'   => '1',
            'uncheckedValue' => '0',
        ]);

        $useCustomCsp = $this->getPopulatedValue(
            'security__use_custom_csp',
            $this->config->get('security', 'use_custom_csp'),
        ) === '1';

        $formHintClassList = ['csp-form-hint'];
        if ($useCustomCsp) {
            $formHintClassList[] = 'csp-disabled';
        }

        $this->addHtml((new DisplayFormElement(HtmlElement::create(
            'p',
            ['class' => $formHintClassList],
            $this->translate(
                'Enabling CSP will block some requests and may prevent some functionality from working as expected.',
            ),
        )))->addAttributes(Attributes::create(['class' => 'csp-control-group'])));

        if (! $this->isCspEnabled()) {
            $this->addElement('hidden', 'security__use_custom_csp');
            $this->addElement('hidden', 'security__custom_csp');
            $this->addElement('hidden', 'security__csp_enable_modules');
            $this->addElement('hidden', 'security__csp_enable_dashboards');
            $this->addElement('hidden', 'security__csp_enable_navigation');
        } else {
            $this->addHtml((new DisplayFormElement(
                HtmlElement::create('div', null, [
                    HtmlElement::create('h3', ['class' => $formHintClassList], $this->translate('Allowed Sources')),
                    HtmlElement::create('p', ['class' => $formHintClassList], $this->translate(
                        'Sources that are used in the generation of the CSP header.',
                    )),
                    HtmlElement::create('h4', ['class' => $formHintClassList], $this->translate('System')),
                ]),
            ))->addAttributes(Attributes::create(['class' => 'csp-control-group'])));

            $this->addDirectiveContentElement(
                [Csp::getSystemCsp()],
                [$this->translate('Directive'), $this->translate('Value')],
                function (StaticCspReason $reason, string $directive, string $expression) {
                    return Table::tr([Table::td($directive), $this->buildExpression($directive, $expression)]);
                },
                ! $useCustomCsp,
                $this->translate('No system policies defined.'),
            );

            $this->addDirectiveCheckboxElement(
                $this->translate('Enable Modules'),
                $this->translate(
                    'Should module defined CSP directives be enabled?'
                    . ' Modules can define or change csp directives at any point.',
                ),
                'security__csp_enable_modules',
                ! $useCustomCsp,
            );

            $this->addDirectiveContentElement(
                (new ModuleCspLoader())->loadForAllUsers(),
                [$this->translate('Module'), $this->translate('Directive'), $this->translate('Value')],
                function (ModuleCspReason $reason, string $directive, string $expression) {
                    return Table::tr([
                        Table::td($reason->module),
                        Table::td($directive),
                        $this->buildExpression($directive, $expression),
                    ]);
                },
                ! $useCustomCsp && $this->getValue('security__csp_enable_modules') === '1',
                $this->translate('No module policies defined.'),
            );

            $this->addDirectiveCheckboxElement(
                $this->translate('Enable Dashboards'),
                $this->translate(
                    'Enable user defined dashboards. This table contains all dashboards for all users. The actual'
                    . ' header that is sent to the user will only contain the subset of directives that actually'
                    . ' matters to them.',
                ),
                'security__csp_enable_dashboards',
                ! $useCustomCsp,
            );

            $this->addDirectiveContentElement(
                (new DashboardCspLoader())->loadForAllUsers(),
                [
                    $this->translate('Dashboard'),
                    $this->translate('Dashlet'),
                    $this->translate('User'),
                    $this->translate('Directive'),
                    $this->translate('Value'),
                ],
                function (DashboardCspReason $reason, string $directive, string $expression) {
                    return Table::tr([
                        Table::td($reason->pane->getName()),
                        Table::td($reason->dashlet->getName()),
                        Table::td($reason->dashboard->getUser()->getUsername()),
                        Table::td($directive),
                        $this->buildExpression($directive, $expression),
                    ]);
                },
                ! $useCustomCsp && $this->getValue('security__csp_enable_dashboards') === '1',
                $this->translate('No dashboard policies found.'),
            );

            $this->addDirectiveCheckboxElement(
                $this->translate('Enable Navigation Items'),
                $this->translate(
                    'Enable user defined navigation items. This table contains all navigation items for'
                    . ' all users. The actual header that is sent to the user will only contain the subset of'
                    . ' directives that actually matters to them.',
                ),
                'security__csp_enable_navigation',
                ! $useCustomCsp,
            );

            $this->addDirectiveContentElement(
                (new NavigationCspLoader())->loadForAllUsers(),
                [
                    $this->translate('Navigation'),
                    $this->translate('Parent'),
                    $this->translate('Name'),
                    $this->translate('User'),
                    $this->translate('Directive'),
                    $this->translate('Value'),
                ],
                function (NavigationCspReason $reason, string $directive, string $expression) {
                    $parentCell = $reason->parent === null
                        ? Table::td($this->translate('None'))->setAttribute('class', 'empty-state')
                        : Table::td($reason->parent);

                    $sharedIcon = match ($reason->isShared) {
                        true  => new Icon('share', [
                            'class' => 'shared-item',
                            'title' => $this->translate('Shared item. Displayed user is owner.'),
                        ]),
                        false => null,
                    };

                    $userCell = $reason->username === null
                        ? Table::td([$sharedIcon, $this->translate('Unknown')])->setAttribute('class', 'empty-state')
                        : Table::td([$sharedIcon, $reason->username]);

                    return Table::tr([
                        Table::td($reason->typeConfiguration['label'] ?? $reason->type),
                        $parentCell,
                        Table::td($reason->name),
                        $userCell,
                        Table::td($directive),
                        $this->buildExpression($directive, $expression),
                    ]);
                },
                ! $useCustomCsp && $this->getValue('security__csp_enable_navigation') === '1',
                $this->translate('No navigation policies found.'),
            );

            $this->addElement(
                'checkbox',
                'security__use_custom_csp',
                [
                    'label'          => $this->translate('Enable Custom CSP'),
                    'description'    => $this->translate(
                        'Specify whether to use a custom, user-provided string as the CSP header.',
                    ),
                    'class'          => 'autosubmit csp-form-content-aligned csp-label-header-h3 csp-form-header',
                    'checkedValue'   => '1',
                    'uncheckedValue' => '0',
                ],
            );

            if ($this->isCustomCspEnabled()) {
                $this->add(new DisplayFormElement(new Callout(
                    CalloutType::Warning,
                    $this->translate(
                        'Be aware that the custom CSP header completely overrides the automatically generated one.'
                        . ' This means that you are solely responsible for keeping the custom CSP header up to date'
                        . ' and secure.',
                    ),
                    $this->translate('Warning: Use at your own risk!'),
                )));
            }

            $this->addElement('textarea', 'security__custom_csp', [
                'label'       => '',
                'description' => $this->translate(
                    'Set a custom CSP header. This completely overrides the automatically generated one.'
                    . ' Use the placeholder {style_nonce} to insert the automatically generated style nonce.',
                ),
                'rows'        => static::TEXTAREA_ROWS,
                'disabled'    => ! $this->isCustomCspEnabled(),
                'required'    => $this->isCustomCspEnabled(),
                'validators'  => [
                    new CallbackValidator(function ($value, CallbackValidator $validator) {
                        if (empty($value) || ! $this->isCustomCspEnabled()) {
                            return true;
                        }

                        try {
                            CspInstance::fromString(str_replace('{style_nonce}', "'nonce-validation'", $value));
                        } catch (Exception $e) {
                            $validator->addMessage($e->getMessage());

                            return false;
                        }

                        return true;
                    }),
                ],
            ]);
        }
    }

    protected function onSuccess(): void
    {
        $section = $this->config->getSection('security');
        $beforeSection = clone $section;

        parent::onSuccess();

        $a = iterator_to_array($section);
        $b = iterator_to_array($beforeSection);
        $this->changed = ! empty(array_diff_assoc($a, $b)) || ! empty(array_diff_assoc($b, $a));
    }

    /**
     * Has the CSP configuration changed since the last time the form was submitted?
     *
     * @return bool
     */
    public function hasConfigChanged(): bool
    {
        return $this->changed;
    }

    /**
     * Would CSP be enabled if the form contents where submitted?
     *
     * @return bool
     */
    public function isCspEnabled(): bool
    {
        return $this->getValue('security__use_strict_csp', $this->config->get('security', 'use_strict_csp')) === '1';
    }

    /**
     * Would custom CSP be enabled if the form contents where submitted?
     *
     * @return bool
     */
    public function isCustomCspEnabled(): bool
    {
        return $this->getValue('security__use_custom_csp', $this->config->get('security', 'use_custom_csp')) === '1';
    }

    /**
     * Add a checkbox that enables or disables a group of CSP directives
     *
     * @param string $label The label of the checkbox
     * @param string $description The description of the checkbox
     * @param string $field The name of the checkbox field
     * @param bool $enabled Whether the checkbox should be checked and enabled
     *
     * @return void
     */
    protected function addDirectiveCheckboxElement(
        string $label,
        string $description,
        string $field,
        bool $enabled,
    ): void {
        $classList = [
            'autosubmit',
            'csp-form-content-aligned',
            'csp-label-header-h4',
        ];

        if (! $enabled) {
            $classList[] = 'csp-disabled';
        }

        $this->addElement('checkbox', $field, [
            'label'          => $label,
            'description'    => $description,
            'class'          => $classList,
            'checkedValue'   => '1',
            'uncheckedValue' => '0',
            'disabled'       => ! $enabled,
            'value'          => $this->getPopulatedValue($field),
        ]);
    }

    /**
     * Add a table that displays the content of the given CSP directives.
     *
     * @param AttributedCsp[] $attributedCsps The list of CSPs along with their reasons
     * @param string[] $header The header of the table
     * @param callable $rowBuilder A function that builds a row for the table
     * @param bool $enabled Whether the content should be enabled
     * @param string $emptyText The text to display if there are no policies
     *
     * @return void
     */
    protected function addDirectiveContentElement(
        array $attributedCsps,
        array $header,
        callable $rowBuilder,
        bool $enabled,
        string $emptyText,
    ): void {
        $rows = [];
        foreach ($attributedCsps as $attributed) {
            foreach ($attributed->csp->getDirectives() as $directive => $expressions) {
                foreach ($expressions as $expression) {
                    $rows[] = $rowBuilder($attributed->reason, $directive, $expression);
                }
            }
        }

        if (count($rows) === 0) {
            $this->addHtml(
                (new DisplayFormElement(HtmlElement::create('p', ['class' => 'csp-form-hint'], $emptyText)))
                    ->addAttributes(Attributes::create(['class' => 'csp-control-group']))
            );

            return;
        }

        $classList = ['csp-config-table'];
        if (! $enabled) {
            $classList[] = 'csp-disabled';
        }

        $table = new Table();
        $table->addAttributes(Attributes::create(['class' => $classList]));
        $headerRow = Table::tr();
        foreach ($header as $h) {
            $headerRow->add(Table::th($h));
        }

        $table->add($headerRow);
        foreach ($rows as $row) {
            $table->add($row);
        }

        $this->add((new DisplayFormElement(
            HtmlElement::create('div', ['class' => 'collapsible', 'data-visible-height' => 100], $table),
        ))->addAttributes(Attributes::create(['class' => 'csp-control-group'])));
    }

    /**
     * Categorize the expression keywords into secure, warning, and unknown
     *
     * @param string $expression The expression to categorize
     *
     * @return string|null
     */
    protected function getKeywordType(string $expression): ?string
    {
        if (in_array(strtolower($expression), static::SECURE_KEYWORDS)) {
            return 'secure';
        }

        if (in_array(strtolower($expression), static::WARNING_KEYWORDS)) {
            return 'warning';
        }

        return null;
    }

    /**
     * Categorize the expression schemes into secure, warning, and unknown
     *
     * @param string $directive The directive that the expression belongs to
     * @param string $expression The expression to categorize
     *
     * @return string|null
     */
    protected function getSchemeType(string $directive, string $expression): ?string
    {
        if (! str_ends_with($expression, ':') || str_contains($expression, ' ')) {
            return null;
        }

        $scheme = strtolower(substr($expression, 0, -1));

        if (in_array($scheme, static::SECURE_SCHEMES)) {
            return 'secure';
        }

        if (in_array($scheme, static::WARNING_SCHEMES)) {
            return 'warning';
        }

        if ($scheme === 'data') {
            if (in_array($directive, static::CRITICAL_DATA_DIRECTIVES)) {
                return 'critical';
            }
    
            if (in_array($directive, static::WARNING_DATA_DIRECTIVES)) {
                return 'warning';
            }
        }

        return 'unknown';
    }

    /**
     * Whether the given expression is a nonce
     *
     * @param string $expression The expression to check
     *
     * @return bool
     */
    protected function isNonce(string $expression): bool
    {
        return (str_starts_with($expression, "'nonce-") && str_ends_with($expression, "'"));
    }

    /**
     * Build an HTML element that represents the given expression.
     *
     * @param string $directive The directive that the expression belongs to
     * @param string $expression The expression to build
     *
     * @return BaseHtmlElement
     */
    protected function buildExpression(string $directive, string $expression): BaseHtmlElement
    {
        if ($expression === '*') {
            $result = HtmlElement::create('span', ['class' => 'csp-wildcard'], [
                $expression,
                new Icon('warning', [
                    'class' => 'csp-expression-info',
                    'title' => $this->translate(
                        'This is a wildcard expression. It allows everything and should therefore be avoided.',
                    ),
                ]),
            ]);
        } elseif (($keyword = $this->getKeywordType($expression)) !== null) {
            $icon = match ($keyword) {
                'warning' => new Icon('warning', [
                    'class' => 'csp-expression-info',
                    'title' => $this->translate('This is a potentially unsafe keyword.'),
                ]),
                default   => null,
            };
            $result = HtmlElement::create(
                'span',
                ['class' => ['csp-keyword', 'csp-' . $keyword]],
                [$expression, $icon],
            );
        } elseif (($scheme = $this->getSchemeType($directive, $expression)) !== null) {
            $icon = match ($scheme) {
                'warning'  => new Icon('warning', [
                    'class' => 'csp-expression-info',
                    'title' => $this->translate('This is a potentially unsafe scheme.'),
                ]),
                'critical' => new Icon('warning', [
                    'class' => 'csp-expression-info',
                    'title' => $this->translate('This is a critical scheme and should not be used.'),
                ]),
                default    => null,
            };
            $result = HtmlElement::create(
                'span',
                ['class' => ['csp-scheme', 'csp-' . $scheme]],
                [$expression, $icon],
            );
        } elseif ($this->isNonce($expression)) {
            $result = HtmlElement::create('span', ['class' => 'csp-nonce'], [
                $expression,
                new Icon('info-circle', [
                    'class' => 'csp-expression-info',
                    'title' => $this->translate(
                        'This is an automatically generated nonce. Its value is unique per request.',
                    ),
                ]),
            ]);
        } elseif (filter_var($expression, FILTER_VALIDATE_URL) !== false) {
            $result = new Link($expression, $expression, ['target' => '_blank', 'rel' => 'noopener noreferrer']);
        } else {
            $result = new Text($expression);
        }

        return Table::td($result, ['class' => 'csp-expressions']);
    }
}

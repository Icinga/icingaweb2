<?php

/* Icinga Web 2 | (c) 2023 Icinga GmbH | GPLv2+ */

namespace Icinga\Forms;

use Icinga\Application\MigrationManager;
use ipl\Html\Attributes;
use ipl\Html\FormElement\CheckboxElement;
use ipl\Html\FormElement\FieldsetElement;
use ipl\Html\HtmlElement;
use ipl\Html\Text;
use ipl\I18n\Translation;
use ipl\Validator\CallbackValidator;
use ipl\Web\Common\CsrfCounterMeasure;
use ipl\Web\Common\FormUid;
use ipl\Web\Compat\CompatForm;
use ipl\Web\FormDecorator\IcingaFormDecorator;
use PDOException;

class MigrationForm extends CompatForm
{
    use CsrfCounterMeasure;
    use FormUid;
    use Translation;

    protected $defaultAttributes = [
        'class' => ['icinga-form', 'migration-form', 'icinga-controls'],
        'name'  => 'migration-form'
    ];

    /** @var bool Whether to allow changing the current database user and password */
    protected $renderDatabaseUserChange = false;

    public function hasBeenSubmitted(): bool
    {
        if (! $this->hasBeenSent()) {
            return false;
        }

        $pressedButton = $this->getPressedSubmitElement();

        return $pressedButton && strpos($pressedButton->getName(), 'migrate-') !== false;
    }

    public function setRenderDatabaseUserChange(bool $value = true): self
    {
        $this->renderDatabaseUserChange = $value;

        return $this;
    }

    public function hasDefaultElementDecorator()
    {
        // The base implementation registers a decorator we don't want here
        return false;
    }

    protected function assemble(): void
    {
        $this->addHtml($this->createUidElement());

        if ($this->renderDatabaseUserChange) {
            $mm = MigrationManager::instance();
            $newDbSetup = new FieldsetElement('database_setup', ['required' => true]);
            $newDbSetup
                ->setDefaultElementDecorator(new IcingaFormDecorator())
                ->addElement('text', 'username', [
                    'required'    => true,
                    'label'       => $this->translate('Username'),
                    'description' => $this->translate(
                        'A user which is able to create and/or alter the database schema.'
                    )
                ])
                ->addElement('password', 'password', [
                    'required'     => true,
                    'autocomplete' => 'new-password',
                    'label'        => $this->translate('Password'),
                    'description'  => $this->translate('The password for the database user defined above.'),
                    'validators'   => [
                        new CallbackValidator(function ($_, CallbackValidator $validator) use ($mm, $newDbSetup): bool {
                            /** @var array<string, string> $values */
                            $values = $this->getValue('database_setup');
                            /** @var CheckboxElement $checkBox */
                            $checkBox = $newDbSetup->getElement('grant_privileges');
                            $canIssueGrants = $checkBox->isChecked();
                            $elevationConfig = [
                                'username' => $values['username'],
                                'password' => $values['password']
                            ];

                            try {
                                if (! $mm->validateDatabasePrivileges($elevationConfig, $canIssueGrants)) {
                                    $validator->addMessage(sprintf(
                                        $this->translate(
                                            'The provided credentials cannot be used to execute "%s" SQL commands'
                                            . ' and/or grant the missing privileges to other users.'
                                        ),
                                        implode(' ,', $mm->getRequiredDatabasePrivileges())
                                    ));

                                    return false;
                                }
                            } catch (PDOException $e) {
                                $validator->addMessage($e->getMessage());

                                return false;
                            }

                            return true;
                        })
                    ]
                ])
                ->addElement('checkbox', 'grant_privileges', [
                    'required'    => false,
                    'label'       => $this->translate('Grant Missing Privileges'),
                    'description' => $this->translate(
                        'Allows to automatically grant the required privileges to the database user specified'
                        . ' in the respective resource config. If you do not want to provide additional credentials'
                        . ' each time, you can enable this and Icinga Web will grant the active database user the'
                        . ' missing privileges.'
                    )
                ]);

            $this->addHtml(
                new HtmlElement(
                    'div',
                    Attributes::create(['class' => 'change-database-user-description']),
                    new HtmlElement('span', null, Text::create(sprintf(
                        $this->translate(
                            'It seems that the currently used database user does not have the required privileges to'
                            . ' execute the %s SQL commands. Please provide an alternative user'
                            . ' that has the appropriate credentials to resolve this issue.'
                        ),
                        implode(', ', $mm->getRequiredDatabasePrivileges())
                    ))),
                    new HtmlElement('br'),
                    new HtmlElement('br'),
                    new HtmlElement('span', null, Text::create(sprintf(
                        $this->translate(
                            'The database name may contain either an underscore or a percent sign.'
                            . ' In MySQL these characters represent a wildcard. If part of a database name,'
                            . ' they might not have been escaped when manually granting privileges.'
                            . ' Privileges might not be detected in this case. Check the documentation and'
                            . ' update your grants accordingly: %s'
                        ),
                        'https://dev.mysql.com/doc/refman/8.0/en/grant.html#grant-quoting'
                    )))
                )
            );

            $this->addElement($newDbSetup);
        }
    }
}

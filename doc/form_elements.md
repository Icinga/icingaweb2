# Form Elements Shipped With Icinga Web 2

On top of the elements provided by the Zend Framework, Icinga Web 2 ships its own to offer additional functionality.
The following is a list of these classes, as well as descriptions of the functionality they offer.

## Elements

### DateTimePicker

`Icinga\Web\Form\Element\DateTimePicker` represents a control that allows the user to select date/time and to
display the date and time with a user specified format. Internally the element returns the input as Unix timestamp after
it has been proven valid. That is when the input is valid to `\DateTime::createFromFormat()` according to the user
specified format. Input is always timezone aware because the element utilizes `Icinga\Util\DateTimeFactory` which relies
on the timezone set by the user.

**Example #1 DateTimePicker expecting date**

    use Icinga\Web\Form\Element\DateTimePicker;

    $element = new DateTimePicker(
        array(
            'name'      => 'date',
            'label'     => t('Date'),
            'patterns'  => array('Y-m-d') // Allowed format
        )
    )

**Example #2 DateTimePicker expecting time**

    use Icinga\Web\Form\Element\DateTimePicker;

    $element = new DateTimePicker(
        array(
            'name'      => 'time',
            'label'     => t('Time'),
            'patterns'  => array('H:i:s') // Allowed format
        )
    )

**Example #3 DateTimePicker expecting date and time**

    use Icinga\Web\Form\Element\DateTimePicker;

    $element = new DateTimePicker(
        array(
            'name'      => 'datetime',
            'label'     => t('Date And Time'),
            'patterns'  => array('Y-m-d H:i:s') // Allowed format
        )
    )

**Example #4 DateTimePicker expecting date/time w/ default value**

    use Icinga\Web\Form\Element\DateTimePicker;
    use Icinga\Util\DateTimeFactory;

    $now = DateTimeFactory::create();

    $element =  new DateTimePicker(
        array(
            'name'      => 'datetime',
            'label'     => t('Date/Time'),
            'value'     => $now->getTimestamp() + 3600, // now plus 1 hour
            'patterns'  => array('Y-m-d H:i:s', 'Y-m-d', 'H:i:s') // Allowed format
        )
    )

## Validators

### WritablePathValidator

This *Icinga\Web\Form\Validator\WritablePathValidator* validator tests a given (string-)input for being a valid writable
path. Normally it just tests for an existing, writable path but when setRequireExistence() is called, the path must
exist on form submission and be writable.

**Example usage of writablePathValidator

    use \Icinga\Web\Form\Validator\WritablePathValidator;
    $txtLogPath = new Zend_Form_Element_Text(
        array(
            'name'          => 'logging_app_target',
            'label'         => 'Application Log Path',
            'helptext'      => 'The logfile to write the icingaweb debug logs to.'
                . 'The webserver must be able to write at this location',
            'required'      => true,
            'value'         => $logging->get('target', '/var/log/icingaweb.log')
        )
    );
    $txtLogPath->addValidator(new WritablePathValidator());


### DateTimeValidator

The *Icinga\Web\Form\Validator\DateTimeValidator* validator allows you to validate an input against a set of datetime
patterns. On successful validation, it either gives a valid pattern via getValidPattern, or null if the entered time
is a timestamp. The above DateTimePicker utilizes this validator and should be used instead of directly using the validator.


## Decorators

### ConditionalHidden Decorator

The `Icinga\Web\Form\Decorator\ConditionalHidden` allows you to hide a form element with the 'conditional' attribute for
users that don't have JavaScript enabled (the form is rendered in a \<noscript> tag when conditional is 1). Users with
javascript won't see the elements, users with javascript will see it. This is useful in a lot of cases to allow icingaweb
to be fully functional without JavaScript: Forms can show only sensible forms for most users (and, for example hide the
debug log filepath input when debugging is disabled) and automatically reload the form as soon as the forms should be
shown (e.g. when the debug checkbox is clicked), while users with text-browsers or javascript disabled see all forms,
but can only fill out the ones relative or them.

**Example use of ConditionalHidden**

    use Icinga\Web\Form\Decorator\ConditionalHidden;

    $textLoggingDebugPath = new Zend_Form_Element_Text(array(
        'name'      => 'logging_debug_target',
        'label'     => 'Debug Log Path',
        'required'  => $this->shouldDisplayDebugLog($debug),
        'condition' => $this->shouldDisplayDebugLog($debug), // 1 if displayed, otherwise 0
        'value'     => getLogPath,
        'helptext'  => 'Set the path to the debug log'
    ))
    $textLoggingDebugPath->addDecorator(new ConditionalHidden());
    $form->addElement($textLoggingDebugPath);

### HelpText Decorator ###

The `Icinga\Web\Form\Decorator\HelpText` decorator allows you to use the 'helptext' property and renders this text in
a consistent ways across the application. It is automatically added by our Form implementation, so you can just use
the 'helptext' property in your form elements.


### BootstrapForm Decorator

`Icinga\Web\Form\Decorator\BoostrapForm` is the decorator we use for our forms.
It causes the forms to be rendered in a bootstrap friendly manner instead of the \<dd> \<dt> encapsulated way Zend normally
renders the forms. You usually don't have to work with this decorator as our Form implementation automatically uses it,
but it's always good to know why forms look how they look.
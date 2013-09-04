# Form Elements Shipped With Icinga 2 Web

On top of the elements provided by the Zend Framework, Icinga 2 Web ships its own to offer additional functionality.
The following is a list of these classes, as well as descriptions of the functionality they offer.

## DateTimePicker

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



# Forms

## Abstract

This document describe how to develop forms in Icinga Web 2. This is important
if you want to write modules or extend Icinga Web 2 with your flavour of code.

## Architecture

Forms are basically Zend_Form classes with Zend_Form_Element items as controls.
To ensure common functionallity and control dependent fields Icinga Web 2
provides sub classes to build forms on that.

### Key design

#### Build of forms

Creating elements is done within protected function *create()* of your subclass.
In here you can add elements to your form, add validations and filters of your
choice. The creation method is invoked lazy just before a form is rendered or
*isValid()* is called.

In order to let icingaweb create a submit button for you (which is required for using the *isSubmittedAndValid*
method) you have to call the *setSubmitLabel($label)* method, which will add a
Zend_Form_Element_Submit element to your form.

#### Client side behaviour

A few methods in our Form implementation don't affect the rendering, but the behaviour of a form.

* **Automatic submission of fields via *\Icinga\Web\Form::enableAutoSubmit(array $forms)***
  All form element ids passed in the $forms array will cause a submission of the form when changed. This normally
  doesn't cause validation errors, as the form is not really seen as submitted by the 'isSubmittedAndValid', but allows
  you to update your form when necessary. For example, when you have a select box whose selection affects which form elements
  should be shown, you can use `$form->enableAutoSubmit(array('myForm'))`.
* **User confirmation when discarding forms.** When a user wants to leave the current page despite having unsaved changes,
  a popup will appear and asks the user if he **really** wants to leave. This is implemented by the *app/form* componenent
  and enabled by default. If you don't want this, you can call *setIgnoreChangeDiscarding($bool)* on your form.


#### Calling is *isSubmittedAndValid()*

*isSubmittedAndValid()* is used to check whether the form is ready to be processed or not.
It ensures that the current request method is POST, that the form was manually submitted
and that the data provided in the request is valid and gets repopulated in case its invalid. This only works when
the sumbit button has been added with the *setSubmitLabel($label)* function, otherwise a form is always considered to be
submitted when a POST request is received.

If the form has been updated, but not submitted (for example, because the a button has been pressed that adds or removes
some fields in the form) the form is repopulated but not validated at this time. is SubmittedAndValid() returns false
in this case, but no errors are added to the created form.

In order to be able to use isSubmittedAndValid, you have to define a submitbutton in the form.
This is done with the *setSubmitLabel(string)* function, with the first parameter being the
label set to the submit button.

#### Pre validation

To handle dependend fields you can just override *preValid()* or *postValid()*
to dynamically add or remove validations. This behaviour reduces the overhead
to write own validator classes.

* *preValidation()* Work just before pre validation

#### Autoloading of form code

Because of forms are no library code we need to put them into application code.
The application or the module has an reserved namespace for forms which loads
code from special directories:

<p></p>

<table>
    <tr>
        <th>Class name</th>
        <th>File path</th>
    </tr>
    <tr>
        <td>\Icinga\Form\Test\MyForm</td>
        <td>application/forms/Test/MyForm.php</td>
    </tr>
    <tr>
        <td>\MyModule\Form\Test</td>
        <td>modules/forms/Test.php</td>
    </tr>
</table>

If you want to create custom elements or organize library code in form context
use an other namesoace for, e.g.

```
\Icinga\Web\Form\Element\MySpecialElement
\MyModule\Web\Form\Element\FancyDatePicker
```

## Example implementation


    namespace MyModule\Form;

    use Icinga\Web\Form;

    class TestForm extends Form
    {
        /**
         * Add elements to this form (used by extending classes)
         */
        protected function create()
        {
            $this->addElement(
                'checkbox',
                'flag',
                array(
                    'label' => 'Check this box to user feature 1'
                )
            );

            $this->addElement(
                'text',
                'flagValue',
                array(
                    'label' => 'Enter text'
                )
            );
        }

        /**
         * Check dependent fields
         * @param array $data
         */
        protected function preValidation(array $data)
        {
            if (isset($data['flag']) && $data['flag'] === '1') {
                $textField = $this->getElement('flagValue');
                $textField->setRequired(true);

                $textField->addValidator(
                    'alnum',
                    true,
                    array(
                        'allowWhitespace' => true
                    )
                );
            }
        }
    }

The example above adds to elements to the form: A checkbox and a textfield.
The function *preValid()* set the textfield required if checkbox was
checked before.

### Full overriding example

The following example shows form with most usefull method utilization of
interface methods:

    namespace MyModule\Form;

    use Icinga\Web\Form;

    class TestForm extends Form
    {
        /**
         * When sub-classing replace the constructor
         */
        public function init()
        {
            // Do some initializing work here if needed
        }

        /**
         * Add elements to this form (used by extending classes)
         */
        protected function create()
        {
            // Add elements to form
        }

        /**
         * Pre validation
         * @param array $data
         */
        protected function preValidation(array $data)
        {
            // Add depending filters or validation here
        }
    }

## Testing forms

When testing forms it is a good idea to use Zend_Test_PHPUnit_ControllerTestCase
instead of others like PHPUnit_Framework_TestCase as this enables you to use a
request dummy which can be passed to your form.

### Example:

    require_once 'Zend/Test/PHPUnit/ControllerTestCase.php';

    class YourTestCase extends Zend_Test_PHPUnit_ControllerTestCase
    {
        function exampleTest()
        {
            $request = $this->getRequest();
            $request->setMethod('POST')->setPost(array(
                'key' => 'value'
                )
            );
            $form = new SomeForm();
            $form->setRequest($request);

            ...
        }
    }

## Additional resources

* [API documentation](http://build.icinga.org/jenkins/view/icinga2-web/job/icinga2web-development/javadoc/?)
* Live examples: application/forms or modules/monitoring/application/forms
* [Zend API documentation](http://framework.zend.com/apidoc/1.10/_Form.html#Zend_Form)


[form1]: res/Form.png

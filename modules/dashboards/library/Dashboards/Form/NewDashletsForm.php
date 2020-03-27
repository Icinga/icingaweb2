<?php

namespace Icinga\Module\Dashboards\Form;

use ipl\Html\Form;
use ipl\Html\FormDecorator\DivDecorator;
use ipl\Html\Html;

class NewDashletsForm extends Form
{
    public function newAction()
    {
        $this->add(Html::tag('h1', ['class' => 'hint'], 'Add Dashlet To Dashboard'));
        $this->setAction('dashboards/dashlets/new');

        $this->add(Html::tag('br'));

        $this->addElement('textarea', 'dashlet_url',[
            'label'     => 'Url',
            'placeholder'   => 'Enter Dashlet Url',
            'required'      => true,
            'rows'      => '3'
        ]);

        $this->add(Html::tag('br'));

        $this->addElement('text', 'dashlet_name', [
            'label'       => 'Dashlet Name',
            'placeholder' => 'Enter Dashlet Name',
            'required'    => true
        ]);

        $this->add(Html::tag('hr'));

        $this->addElement('select', 'select_dashboard', [
            'label'     => 'Dashboard',
            'options'    => [
                '1'     => 'Current Incidents',
                '2'     => 'Muted'
            ]
        ]);

        $this->add(Html::tag('br'));

        $this->addElement('submit', 'submit', [
            'label' => 'Add To Dashboard'
        ]);
    }

    protected function assemble()
    {
        $this->setDefaultElementDecorator(new DivDecorator());
        $this->add($this->newAction());
    }
}

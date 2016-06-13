<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Eventdb\Forms\Event;

use Exception;
use Icinga\Data\Filter\Filter;
use Icinga\Module\Eventdb\Eventdb;
use Icinga\Web\Form;

/**
 * Form for managing the connection to the EventDB backend
 */
class EventCommentForm extends Form
{
    /**
     * @var Eventdb
     */
    protected $db;

    protected $filter;

    protected static $types = array(
        'comment',
        'ack',
        'revoke'
    );

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setSubmitLabel($this->translate('Submit'));
    }

    public function setDb(Eventdb $db)
    {
        $this->db = $db;
        return $this;
    }

    public function setFilter(Filter $filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'select',
            'type',
            array(
                'label'         => $this->translate('Type'),
                'multiOptions'  => static::$types,
                'required'      => true
            )
        );
        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'         => $this->translate('Comment'),
                'required'      => true
            )
        );
    }

    public function onSuccess()
    {
        $type = $this->getValue('type');
        $comment = $this->getValue('comment');
        $username = $this->Auth()->getUser()->getUsername();

        $events = $this->db->select()->from('event', array('id'))->applyFilter($this->filter);

        $dbAdapter = $this->db->getDataSource()->getDbAdapter();

        $dbAdapter->beginTransaction();
        try {
            foreach ($events as $event) {
                $this->db->insert('comment', array(
                    'event_id'  => $event->id,
                    'type'      => $type,
                    'message'   => $comment,
                    'created'   => date(Eventdb::DATETIME_FORMAT),
                    'modified'  => date(Eventdb::DATETIME_FORMAT),
                    'user'      => $username
                ));

                if ($type !== '0') {
                    $this->db->update('event', array(
                        'ack'   => $type === '1' ? 1 : 0
                    ), Filter::where('id', $event->id));
                }
            }
            $dbAdapter->commit();
            return true;
        } catch (Exception $e) {
            $dbAdapter->rollback();
            $this->error($e->getMessage());
            return false;
        }
    }
}

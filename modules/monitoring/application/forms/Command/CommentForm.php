<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Command;

use Icinga\Protocol\Commandpipe\Comment;
use Icinga\Module\Monitoring\Command\AddCommentCommand;

/**
 * Form for adding comment commands
 */
class CommentForm extends CommandForm
{
    /**
     * Create the form's elements
     */
    protected function create()
    {
        $this->setName('form_CommentForm');

        $this->addNote(t('This command is used to add a comment to hosts or services.'));

        $this->addElement($this->createAuthorField());

        $this->addElement(
            'textarea',
            'comment',
            array(
                'label'    => t('Comment'),
                'rows'     => 4,
                'cols'     => 72,
                'required' => true,
                'helptext' => t(
                    'If you work with other administrators, you may find it useful to share information '
                    . 'about a host or service that is having problems if more than one of you may be working on '
                    . 'it. Make sure you enter a brief description of what you are doing.'
                )
            )
        );

        $this->addElement(
            'checkbox',
            'persistent',
            array(
                'label'    => t('Persistent'),
                'value'    => true,
                'helptext' => t(
                    'If you uncheck this option, the comment will automatically be deleted the next time '
                    . 'Icinga is restarted.'
                )
            )
        );

        $this->setSubmitLabel(t('Post Comment'));

        parent::create();
    }

    /**
     * Create the command object to add comments
     *
     * @return AddCommentCommand
     */
    public function createCommand()
    {
        return new AddCommentCommand(
            new Comment(
                $this->getAuthorName(),
                $this->getValue('comment'),
                $this->getValue('persistent')
            )
        );
    }
}

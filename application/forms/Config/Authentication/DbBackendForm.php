<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Authentication;

use \Exception;
use Icinga\Data\ResourceFactory;
use Icinga\Authentication\DbConnection;
use Icinga\Authentication\Backend\DbUserBackend;
use Icinga\Exception\ConfigurationError;

/**
 * Form class for adding/modifying database authentication backends
 */
class DbBackendForm extends BaseBackendForm
{
    /**
     * @var array
     */
    protected $resources;

    public function __construct()
    {
        $dbResources = array_keys(
            ResourceFactory::getResourceConfigs('db')->toArray()
        );
        if (empty($dbResources)) {
            throw new ConfigurationError(
                t('There are no database resources')
            );
        }
        $this->resources = array_combine($dbResources, $dbResources);

        parent::__construct();
    }

    public function createElements(array $formData)
    {
        return array(
            $this->createElement(
                'text',
                'name',
                array(
                    'required'      => true,
                    'allowEmpty'    => false,
                    'label'         => t('Backend Name'),
                    'helptext'      => t('The name of this authentication provider'),
                )
            ),
            $this->createElement(
                'select',
                'resource',
                array(
                    'required'      => true,
                    'allowEmpty'    => false,
                    'label'         => t('Database Connection'),
                    'helptext'      => t('The database connection to use for authenticating with this provider'),
                    'multiOptions'  => $this->resources
                )
            ),
            $this->createElement(
                'button',
                'btn_submit',
                array(
                    'type'      => 'submit',
                    'value'     => '1',
                    'escape'    => false,
                    'class'     => 'btn btn-cta btn-wide',
                    'label'     => '<i class="icinga-icon-save"></i> Save Backend'
                )
            ),
            $this->createElement(
                'hidden',
                'backend',
                array(
                    'required'  => true,
                    'value'     => 'db'
                )
            )
        );
    }

    /**
     * Validate the current configuration by creating a backend and requesting the user count
     *
     * @return  bool    Whether validation succeeded or not
     *
     * @see BaseBackendForm::isValidAuthenticationBackend
     */
    public function isValidAuthenticationBackend()
    {
        try {
            $testConnection = ResourceFactory::createResource(ResourceFactory::getResourceConfig(
                $this->getValue('resource')
            ));
            $dbUserBackend = new DbUserBackend($testConnection);
            if ($dbUserBackend->count() < 1) {
                $this->addErrorMessage(t("No users found under the specified database backend"));
                return false;
            }
        } catch (Exception $e) {
            $this->addErrorMessage(sprintf(t('Using the specified backend failed: %s'), $e->getMessage()));
            return false;
        }
        return true;
    }
}

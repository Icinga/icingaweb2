<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Eventdb;

use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Exception\ConfigurationError;
use Icinga\Repository\DbRepository;
use Icinga\Util\StringHelper;

class Eventdb extends DbRepository
{
    /**
     * {@inheritdoc}
     */
    const DATETIME_FORMAT = 'Y-m-d G:i:s';

    /**
     * {@inheritdoc}
     */
    protected $tableAliases = array(
        'comment'   => 'c',
        'event'     => 'e'
    );

    /**
     * Default query columns
     *
     * @var array
     */
    protected static $defaultQueryColumns = array(
        'event' => array(
            'id',
            'host_name',
            'host_address',
            'type',
            'facility',
            'priority',
            'program',
            'message',
            'alternative_message',
            'ack',
            'created',
            'modified',
            'active',
            'flags'
        ),
        'comment' => array(
            'id',
            'event_id',
            'type',
            'message',
            'created',
            'modified',
            'user'
        )
    );

    /**
     * {@inheritdoc}
     */
    protected function initializeQueryColumns()
    {
        $additionalColumns = Config::module('eventdb')->get('backend', 'additional_columns');
        $queryColumns = static::$defaultQueryColumns;
        if ($additionalColumns !== null) {
            $queryColumns['event'] = array_merge($queryColumns['event'], StringHelper::trimSplit($additionalColumns));
        }
        return $queryColumns;
    }

    /**
     * Create and return a new instance of the Eventdb
     *
     * @param   ConfigObject    $config     The configuration to use, otherwise the module's configuration
     *
     * @return  static
     *
     * @throws  ConfigurationError          In case no resource has been configured in the module's configuration
     */
    public static function fromConfig(ConfigObject $config = null)
    {
        if ($config === null) {
            $moduleConfig = Config::module('eventdb');
            if (($resourceName = $moduleConfig->get('backend', 'resource')) === null) {
                throw new ConfigurationError(
                    mt('eventdb', 'You need to configure a resource to access the EventDB database first')
                );
            }

            $resource = ResourceFactory::create($resourceName);
        } else {
            $resource = ResourceFactory::createResource($config);
        }

        return new static($resource);
    }
}

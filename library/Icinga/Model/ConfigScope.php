<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Model;

use ipl\Orm\Behaviors;
use ipl\Orm\Contract\PropertyBehavior;
use ipl\Orm\Contract\QueryAwareBehavior;
use ipl\Orm\Model;
use ipl\Orm\Query;
use ipl\Orm\Relations;
use ipl\Sql\Adapter\Pgsql;

use function ipl\Stdlib\get_php_type;

class ConfigScope extends Model
{
    public function getTableName()
    {
        return 'icingaweb_config_scope';
    }

    public function getKeyName()
    {
        return 'id';
    }

    public function getColumns()
    {
        return [
            'module',
            'type',
            'name',
            'hash'
        ];
    }

    public function getMetaData()
    {
        return [
            'module'    => t('Config Scope Module'),
            'type'      => t('Config Scope Type'),
            'name'      => t('Config Scope Name'),
            'hash'      => t('Config Scope Hash')
        ];
    }

    public function getSearchColumns()
    {
        return ['name'];
    }

    public function getDefaultSort()
    {
        return 'icingaweb_config_scope.name';
    }

    public function createBehaviors(Behaviors $behaviors)
    {
        $binary = new class (['hash']) extends PropertyBehavior implements QueryAwareBehavior {
            public function setQuery(Query $query)
            {
                if (! $query->getDb()->getAdapter() instanceof Pgsql) {
                    $this->properties = [];
                }
            }

            public function fromDb($value, $key, $context)
            {
                if ($value !== null) {
                    if (! is_resource($value)) {
                        throw new \UnexpectedValueException(
                            sprintf('%s should be a resource got %s instead', $key, get_php_type($value))
                        );
                    }

                    return stream_get_contents($value);
                }

                return null;
            }

            public function toDb($value, $key, $context)
            {
                if (is_resource($value)) {
                    throw new \UnexpectedValueException(sprintf('Unexpected resource for %s', $key));
                }

                return sprintf('\\x%s', bin2hex($value));
            }
        };

        $behaviors->add($binary);
    }

    public function createRelations(Relations $relations)
    {
        $relations->hasMany('option', ConfigOption::class)
            ->setForeignKey('scope_id')
            ->setJoinType('LEFT');
    }
}

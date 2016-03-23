<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Util;

use Icinga\Util\GlobFilter;
use Icinga\Test\BaseTestCase;

class GlobFilterTest extends BaseTestCase
{
    protected function assertGlobFilterRemovesMatching($filterPattern, $unfiltered, $filtered)
    {
        $filter = new GlobFilter($filterPattern);
        $this->assertTrue(
            $filter->removeMatching($unfiltered) === $filtered,
            'Filter `' . $filterPattern . '\' doesn\'t work as intended'
        );
    }

    public function testPatternWithoutAnyWildcards()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.cmdb_name',
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_name' => '',
                        'cmdb_id' => '',
                        'legacy' => array(
                            'cmdb_name' => ''
                        )
                    )
                )
            ),
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_id' => '',
                        'legacy' => array(
                            'cmdb_name' => ''
                        )
                    )
                )
            )
        );
    }

    public function testPatternWithAnAsteriskAtTheEndOfAComponent()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.cmdb_*',
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_name' => '',
                        'cmdb_id' => '',
                        'cmdb_location' => '',
                        'wiki_id' => '',
                        'legacy' => array(
                            'cmdb_name' => ''
                        )
                    )
                )
            ),
            array(
                'host' => array(
                    'vars' => array(
                        'wiki_id' => '',
                        'legacy' => array(
                            'cmdb_name' => ''
                        )
                    )
                )
            )
        );
    }

    public function testPatternWithAnAsteriskAtTheBeginningOfAComponent()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.*id',
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_name' => '',
                        'cmdb_id' => '',
                        'wiki_id' => '',
                        'legacy' => array(
                            'wiki_id' => ''
                        )
                    )
                )
            ),
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_name' => '',
                        'legacy' => array(
                            'wiki_id' => ''
                        )
                    )
                )
            )
        );
    }

    public function testPatternWithAComponentBeingTheAsteriskOnly()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.*.mysql_password',
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_name' => '',
                        'passwords' => array(
                            'mysql_password' => '',
                            'ldap_password' => ''
                        ),
                        'legacy' => array(
                            'mysql_password' => ''
                        ),
                        'backup' => array(
                            'passwords' => array(
                                'mysql_password' => ''
                            )
                        )
                    )
                )
            ),
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_name' => '',
                        'passwords' => array(
                            'ldap_password' => ''
                        ),
                        'legacy' => array(),
                        'backup' => array(
                            'passwords' => array(
                                'mysql_password' => ''
                            )
                        )
                    )
                )
            )
        );
    }

    public function testPatternWithTwoComponentsContainingAsterisks()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.*.*password',
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_name' => '',
                        'passwords' => array(
                            'mysql_password' => '',
                            'ldap_password' => '',
                            'mongodb_password' => ''
                        ),
                        'legacy' => array(
                            'cmdb_name' => '',
                            'mysql_password' => ''
                        ),
                        'backup' => array(
                            'passwords' => array(
                                'mysql_password' => '',
                                'ldap_password' => ''
                            )
                        )
                    )
                )
            ),
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_name' => '',
                        'passwords' => array(),
                        'legacy' => array(
                            'cmdb_name' => ''
                        ),
                        'backup' => array(
                            'passwords' => array(
                                'mysql_password' => '',
                                'ldap_password' => ''
                            )
                        )
                    )
                )
            )
        );
    }

    public function testTwoCommaSeparatedPatternsEachWithAnAsterisk()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.*.mysql_password,host.vars.*.ldap_password',
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_name' => '',
                        'passwords' => array(
                            'mysql_password' => '',
                            'ldap_password' => '',
                            'mongodb_password' => ''
                        ),
                        'legacy' => array(
                            'mysql_password' => ''
                        ),
                        'backup' => array(
                            'passwords' => array(
                                'mysql_password' => '',
                                'ldap_password' => ''
                            )
                        )
                    )
                )
            ),
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_name' => '',
                        'passwords' => array(
                            'mongodb_password' => ''
                        ),
                        'legacy' => array(),
                        'backup' => array(
                            'passwords' => array(
                                'mysql_password' => '',
                                'ldap_password' => ''
                            )
                        )
                    )
                )
            )
        );
    }

    public function testPatternWithAComponentBeingTheMultiLevelWildcard()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.**.*password',
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_location' => '',
                        'passwords' => array(
                            'mysql_password' => '',
                            'ldap_password' => '',
                            'mongodb_password' => ''
                        ),
                        'legacy' => array(
                            'mysql_password' => '',
                        ),
                        'backup' => array(
                            'passwords' => array(
                                'mysql_password' => '',
                                'ldap_password' => ''
                            )
                        )
                    )
                )
            ),
            array(
                'host' => array(
                    'vars' => array(
                        'cmdb_location' => '',
                        'passwords' => array(),
                        'legacy' => array(),
                        'backup' => array(
                            'passwords' => array()
                        )
                    )
                )
            )
        );
    }

    public function testPatternWithAnEscapedAsterisk()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.**.\*password',
            array(
                'host' => array(
                    'vars' => array(
                        'wiki_id' => '',
                        'passwords' => array(
                            'mongodb_password' => '',
                            '*password' => ''
                        ),
                        'legacy' => array(
                            'mysql_password' => '',
                            '*password' => ''
                        ),
                        'backup' => array(
                            'passwords' => array(
                                '*password' => '',
                                'ldap_password' => ''
                            )
                        )
                    )
                )
            ),
            array(
                'host' => array(
                    'vars' => array(
                        'wiki_id' => '',
                        'passwords' => array(
                            'mongodb_password' => ''
                        ),
                        'legacy' => array(
                            'mysql_password' => ''
                        ),
                        'backup' => array(
                            'passwords' => array(
                                'ldap_password' => ''
                            )
                        )
                    )
                )
            )
        );
    }
}

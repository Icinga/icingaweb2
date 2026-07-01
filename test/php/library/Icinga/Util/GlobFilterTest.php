<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
            [
                'host' => [
                    'vars' => [
                        'cmdb_name' => '',
                        'cmdb_id' => '',
                        'legacy' => [
                            'cmdb_name' => ''
                        ]
                    ]
                ]
            ],
            [
                'host' => [
                    'vars' => [
                        'cmdb_id' => '',
                        'legacy' => [
                            'cmdb_name' => ''
                        ]
                    ]
                ]
            ]
        );
    }

    public function testPatternWithAnAsteriskAtTheEndOfAComponent()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.cmdb_*',
            [
                'host' => [
                    'vars' => [
                        'cmdb_name' => '',
                        'cmdb_id' => '',
                        'cmdb_location' => '',
                        'wiki_id' => '',
                        'legacy' => [
                            'cmdb_name' => ''
                        ]
                    ]
                ]
            ],
            [
                'host' => [
                    'vars' => [
                        'wiki_id' => '',
                        'legacy' => [
                            'cmdb_name' => ''
                        ]
                    ]
                ]
            ]
        );
    }

    public function testPatternWithAnAsteriskAtTheBeginningOfAComponent()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.*id',
            [
                'host' => [
                    'vars' => [
                        'cmdb_name' => '',
                        'cmdb_id' => '',
                        'wiki_id' => '',
                        'legacy' => [
                            'wiki_id' => ''
                        ]
                    ]
                ]
            ],
            [
                'host' => [
                    'vars' => [
                        'cmdb_name' => '',
                        'legacy' => [
                            'wiki_id' => ''
                        ]
                    ]
                ]
            ]
        );
    }

    public function testPatternWithAComponentBeingTheAsteriskOnly()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.*.mysql_password',
            [
                'host' => [
                    'vars' => [
                        'cmdb_name' => '',
                        'passwords' => [
                            'mysql_password' => '',
                            'ldap_password' => ''
                        ],
                        'legacy' => [
                            'mysql_password' => ''
                        ],
                        'backup' => [
                            'passwords' => [
                                'mysql_password' => ''
                            ]
                        ]
                    ]
                ]
            ],
            [
                'host' => [
                    'vars' => [
                        'cmdb_name' => '',
                        'passwords' => [
                            'ldap_password' => ''
                        ],
                        'legacy' => [],
                        'backup' => [
                            'passwords' => [
                                'mysql_password' => ''
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function testPatternWithTwoComponentsContainingAsterisks()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.*.*password',
            [
                'host' => [
                    'vars' => [
                        'cmdb_name' => '',
                        'passwords' => [
                            'mysql_password' => '',
                            'ldap_password' => '',
                            'mongodb_password' => ''
                        ],
                        'legacy' => [
                            'cmdb_name' => '',
                            'mysql_password' => ''
                        ],
                        'backup' => [
                            'passwords' => [
                                'mysql_password' => '',
                                'ldap_password' => ''
                            ]
                        ]
                    ]
                ]
            ],
            [
                'host' => [
                    'vars' => [
                        'cmdb_name' => '',
                        'passwords' => [],
                        'legacy' => [
                            'cmdb_name' => ''
                        ],
                        'backup' => [
                            'passwords' => [
                                'mysql_password' => '',
                                'ldap_password' => ''
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function testTwoCommaSeparatedPatternsEachWithAnAsterisk()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.*.mysql_password,host.vars.*.ldap_password',
            [
                'host' => [
                    'vars' => [
                        'cmdb_name' => '',
                        'passwords' => [
                            'mysql_password' => '',
                            'ldap_password' => '',
                            'mongodb_password' => ''
                        ],
                        'legacy' => [
                            'mysql_password' => ''
                        ],
                        'backup' => [
                            'passwords' => [
                                'mysql_password' => '',
                                'ldap_password' => ''
                            ]
                        ]
                    ]
                ]
            ],
            [
                'host' => [
                    'vars' => [
                        'cmdb_name' => '',
                        'passwords' => [
                            'mongodb_password' => ''
                        ],
                        'legacy' => [],
                        'backup' => [
                            'passwords' => [
                                'mysql_password' => '',
                                'ldap_password' => ''
                            ]
                        ]
                    ]
                ]
            ]
        );
    }

    public function testPatternWithAComponentBeingTheMultiLevelWildcard()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.**.*password',
            [
                'host' => [
                    'vars' => [
                        'cmdb_location' => '',
                        'passwords' => [
                            'mysql_password' => '',
                            'ldap_password' => '',
                            'mongodb_password' => ''
                        ],
                        'legacy' => [
                            'mysql_password' => '',
                        ],
                        'backup' => [
                            'passwords' => [
                                'mysql_password' => '',
                                'ldap_password' => ''
                            ]
                        ]
                    ]
                ]
            ],
            [
                'host' => [
                    'vars' => [
                        'cmdb_location' => '',
                        'passwords' => [],
                        'legacy' => [],
                        'backup' => [
                            'passwords' => []
                        ]
                    ]
                ]
            ]
        );
    }

    public function testPatternWithAnEscapedAsterisk()
    {
        $this->assertGlobFilterRemovesMatching(
            'host.vars.**.\*password',
            [
                'host' => [
                    'vars' => [
                        'wiki_id' => '',
                        'passwords' => [
                            'mongodb_password' => '',
                            '*password' => ''
                        ],
                        'legacy' => [
                            'mysql_password' => '',
                            '*password' => ''
                        ],
                        'backup' => [
                            'passwords' => [
                                '*password' => '',
                                'ldap_password' => ''
                            ]
                        ]
                    ]
                ]
            ],
            [
                'host' => [
                    'vars' => [
                        'wiki_id' => '',
                        'passwords' => [
                            'mongodb_password' => ''
                        ],
                        'legacy' => [
                            'mysql_password' => ''
                        ],
                        'backup' => [
                            'passwords' => [
                                'ldap_password' => ''
                            ]
                        ]
                    ]
                ]
            ]
        );
    }
}

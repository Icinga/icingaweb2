<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Filter;

use Icinga\Filter\FilterAttribute;
use Icinga\Filter\Query\Node;
use Icinga\Filter\Type\TextFilter;
use Icinga\Test\BaseTestCase;
use Icinga\Filter\Domain;

class DomainTest extends BaseTestCase
{
    public function testDomainRecognitionInQueryString()
    {
        $domain = new Domain('host');
        $queryWithWhitespace = ' host is up';
        $camelCaseQuery = 'HOsT is down';
        $invalidQuery = 'Horst host Host';
        $this->assertTrue($domain->handlesQuery($queryWithWhitespace), 'Assert the domain to ignore starting whitespaces');
        $this->assertTrue($domain->handlesQuery($camelCaseQuery), 'Assert the domain to be case insensitive');
        $this->assertFalse($domain->handlesQuery($invalidQuery), 'Assert wrong domains to be recognized');
    }

    public function testQueryProposal()
    {
        $domain = new Domain('host');
        $attr = new TextFilter();
        $queryHandler = new FilterAttribute($attr);
        $domain->registerAttribute($queryHandler->setHandledAttributes('name', 'description'));
        $this->assertEquals(
            array('name'),
            $domain->getProposalsForQuery(''),
            'Assert the name being returned when empty query is provided to domain'
        );
        $this->assertEquals(
            array('\'value\'', '{Is} Not'),
            $domain->getProposalsForQuery('host name is'),
            'Assert mixed operator extension and value proposal being returned when provided a partial query'
        );
        $this->assertEquals(
            array('\'value\''),
            $domain->getProposalsForQuery('name is not'),
            'Assert only the value to be returned when operator is fully given'
        );
        $this->assertEquals(
            array(),
            $domain->getProposalsForQuery('sagsdgsdgdgds')
        );
    }

    public function testGetQueryTree()
    {
        $domain = new Domain('host');
        $attr = new TextFilter();
        $queryHandler = new FilterAttribute($attr);
        $domain->registerAttribute($queryHandler->setField('host_name')->setHandledAttributes('name', 'description'));
        $node = $domain->convertToTreeNode('Host name is \'my host\'');
        $this->assertEquals($node->type, Node::TYPE_OPERATOR, 'Assert a domain to produce operator query nodes');
        $this->assertEquals($node->left, 'host_name', 'Assert a domain to insert the field as the left side of a treenode');
        $this->assertEquals($node->right, 'my host', 'Assert a domain to insert the value as the right side of a treenode');
        $this->assertEquals($node->operator, Node::OPERATOR_EQUALS, 'Assert the correct operator to be set in a single query');
    }
}
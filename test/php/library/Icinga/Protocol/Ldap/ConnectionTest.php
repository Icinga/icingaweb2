<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

// We need to overwrite the library functions in the regular namespace to mock library functions. We run
// this test in a separate process to not alter different test cases.
namespace Icinga\Protocol\Ldap;

use Icinga\Test\BaseTestCase;
use Icinga\Data\ConfigObject;
use Mockery;

/**
 *  @runTestsInSeparateProcesses
 */
class ConnectionTest extends BaseTestCase
{
    private $connection;

    public $pagedResultsCalled;
    public $startTlsCalled;
    public $activatedOptions;

    private function mockLdapFunctions () {

        global $self;
        $self = $this;

        function ldap_connect()
        {
            return true;
        }

        function ldap_bind()
        {
            return true;
        }

        function ldap_search ()
        {
            return true;
        }

        function ldap_get_entries()
        {
            return 1;
        }

        function ldap_count_entries()
        {
            return 1;
        }

        function ldap_read()
        {
            return true;
        }

        function ldap_first_entry($ds, $result)
        {
            return $result;
        }

        function ldap_get_attributes()
        {
            global $self;
            return $self->getAttributesMock;
        }

        function ldap_start_tls()
        {
            global $self;
            $self->startTlsCalled = true;
        }

        function ldap_set_option($ds, $option, $value)
        {
            global $self;
            $self->activatedOptions[$option] = $value;
            return true;
        }

        function ldap_set($ds, $option)
        {
            global $self;
            $self->activatedOptions[] = $option;
        }

        function ldap_control_paged_result()
        {
            global $self;
            $self->pagedResultsCalled = true;
            return true;
        }

        function ldap_control_paged_result_response()
        {
            return true;
        }

        function ldap_get_dn()
        {
            return NULL;
        }

        function ldap_free_result()
        {
            return NULL;
        }
    }

    private function node(&$element, $name)
    {
        $element['count']++;
        $element[$name] = array('count' => 0);
        $element[] = $name;
    }

    private function addEntry(&$element, $name, $entry)
    {
        $element[$name]['count']++;
        $element[$name][] = $entry;
    }

    private function mockQuery()
    {
        return Mockery::mock('overload:Icinga\Protocol\Ldap\Query')
                ->shouldReceive(array(
                    'from' => Mockery::self(),
                    'create' => array('count' => 1),
                    'listFields' => array('count' => 1),
                    'getLimit' => 1,
                    'hasOffset' => false,
                    'hasBase' => false,
                    'getSortColumns' => array(),
                    'getUsePagedResults' => true
                ));
    }

    private function connectionFetchAll()
    {
        $this->mockQuery();
        $this->connection->connect();
        $this->connection->fetchAll(Mockery::self());
    }

    public function setUp()
    {
        $this->pagedResultsCalled = false;
        $this->startTlsCalled = false;
        $this->activatedOptions = array();

        $this->mockLdapFunctions();

        $config = new ConfigObject(
            array(
                'hostname' => 'localhost',
                'root_dn'  => 'dc=example,dc=com',
                'bind_dn'  => 'cn=user,ou=users,dc=example,dc=com',
                'bind_pw'  => '***'
            )
        );
        $this->connection = new Connection($config);

        $caps = array('count' => 0);
        $this->node($caps, 'defaultNamingContext');
        $this->node($caps, 'namingContexts');
        $this->node($caps, 'supportedCapabilities');
        $this->node($caps, 'supportedControl');
        $this->node($caps, 'supportedLDAPVersion');
        $this->node($caps, 'supportedExtension');
        $this->getAttributesMock = $caps;
    }

    public function testUsePageControlWhenAnnounced()
    {
        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped('Page control needs at least PHP_VERSION 5.4.0');
        }

        $this->addEntry($this->getAttributesMock, 'supportedControl', Capability::LDAP_PAGED_RESULT_OID_STRING);
        $this->connectionFetchAll();

        // see ticket #7993
        $this->assertEquals(true, $this->pagedResultsCalled, "Use paged result when capability is present.");
    }

    public function testDontUsePagecontrolWhenNotAnnounced()
    {
        if (version_compare(PHP_VERSION, '5.4.0') < 0) {
            $this->markTestSkipped('Page control needs at least PHP_VERSION 5.4.0');
        }
        $this->connectionFetchAll();

        // see ticket #8490
        $this->assertEquals(false, $this->pagedResultsCalled, "Don't use paged result when capability is not announced.");
    }

    public function testUseLdapV2WhenAnnounced()
    {
         // TODO: Test turned off, see other TODO in Ldap/Connection.
         $this->markTestSkipped('LdapV2 currently turned off.');

         $this->addEntry($this->getAttributesMock, 'supportedLDAPVersion', 2);
         $this->connectionFetchAll();

         $this->assertArrayHasKey(LDAP_OPT_PROTOCOL_VERSION, $this->activatedOptions, "LDAP version must be set");
         $this->assertEquals($this->activatedOptions[LDAP_OPT_PROTOCOL_VERSION], 2);
    }

    public function testUseLdapV3WhenAnnounced()
    {
        $this->addEntry($this->getAttributesMock, 'supportedLDAPVersion', 3);
        $this->connectionFetchAll();

        $this->assertArrayHasKey(LDAP_OPT_PROTOCOL_VERSION, $this->activatedOptions, "LDAP version must be set");
        $this->assertEquals($this->activatedOptions[LDAP_OPT_PROTOCOL_VERSION], 3, "LDAPv3 must be active");
    }

    public function testDefaultSettings()
    {
        $this->connectionFetchAll();

        $this->assertArrayHasKey(LDAP_OPT_PROTOCOL_VERSION, $this->activatedOptions, "LDAP version must be set");
        $this->assertEquals($this->activatedOptions[LDAP_OPT_PROTOCOL_VERSION], 3, "LDAPv3 must be active");

        $this->assertArrayHasKey(LDAP_OPT_REFERRALS, $this->activatedOptions, "Following referrals must be turned off");
        $this->assertEquals($this->activatedOptions[LDAP_OPT_REFERRALS], 0, "Following referrals must be turned off");
    }


    public function testActiveDirectoryDiscovery()
    {
        $this->addEntry($this->getAttributesMock, 'supportedCapabilities', Capability::LDAP_CAP_ACTIVE_DIRECTORY_OID);
        $this->connectionFetchAll();

        $this->assertEquals(true, $this->connection->getCapabilities()->hasAdOid(),
            "Server with LDAP_CAP_ACTIVE_DIRECTORY_OID must be recognized as Active Directory.");
    }

    public function testDefaultNamingContext()
    {
        $this->addEntry($this->getAttributesMock, 'defaultNamingContext', 'dn=default,dn=contex');
        $this->connectionFetchAll();

        $this->assertEquals('dn=default,dn=contex', $this->connection->getCapabilities()->getDefaultNamingContext(),
            'Default naming context must be correctly recognized.');
    }

    public function testDefaultNamingContextFallback()
    {
        $this->addEntry($this->getAttributesMock, 'namingContexts', 'dn=some,dn=other,dn=context');
        $this->addEntry($this->getAttributesMock, 'namingContexts', 'dn=default,dn=context');
        $this->connectionFetchAll();

        $this->assertEquals('dn=some,dn=other,dn=context', $this->connection->getCapabilities()->getDefaultNamingContext(),
            'If defaultNamingContext is missing, the connection must fallback to first namingContext.');
    }
}

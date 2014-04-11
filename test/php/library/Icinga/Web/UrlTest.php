<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web;

use \Mockery;
use Icinga\Web\Url;
use Icinga\Test\BaseTestCase;

/**
 * Tests for the Icinga\Web\Url class that provides convenient access to Url manipulation method
 */
class UrlTest extends BaseTestCase
{
    /**
     * Tests whether a simple Url without query parameters and baseUrl is correctly parsed and returns correct Urls
     */
    function testFromStringWithoutQuery()
    {
        $url = Url::fromPath('http://myHost/my/test/url.html');
        $this->assertEquals(
            '/my/test/url.html',
            $url->getPath(),
            'Assert the parsed url path to be equal to the input path'
        );
        $this->assertEquals(
            $url->getPath(),
            '/' . $url->getRelativeUrl(),
            'Assert the path of an url without query to be equal the relative path'
        );
    }

    /**
     * Tests whether a simple Url without query parameters and with baseUrl is correctly parsed and returns correct Urls
     */
    function testFromUrlWithBasePath()
    {
        $url = Url::fromPath('my/test/url.html');
        $url->setBaseUrl('the/path/to');
        $this->assertEquals(
            '/the/path/to/my/test/url.html',
            $url->getAbsoluteUrl(),
            'Assert the url path to be the base path with the relative path'
        );
    }

    /**
     *  Tests whether query parameters in Urls are correctly recognized and decoded
     */
    function testFromUrlWithKeyValueQuery()
    {
        $url = Url::fromPath('/my/test/url.html?param1=%25arg1&param2=arg2');
        $this->assertEquals(
            '/my/test/url.html',
            $url->getPath(),
            'Assert the parsed url path to be equal to the input path'
        );
        $this->assertEquals(
            array(
                'param1' => '%arg1',
                'param2' => 'arg2'
            ),
            $url->getParams(),
            'Assert single key=value Url parameters to be correctly parsed and recognized'
        );
    }

    /**
     *  Tests whether unnamed query parameters in Urls are correctly recognized and decoded
     */
    function testFromUrlWithArrayInQuery()
    {
        $url = Url::fromPath('/my/test/url.html?param[]=%25val1&param[]=%40val2');
        $this->assertEquals(
            array(
                'param' => array('%val1', '@val2')
            ),
            $url->getParams(),
            'Assert arrays in param[] = value syntax to be correctly recognized and parsed as arrays'
        );
    }

    /**
     *  Tests whether named query parameters in Urls are correctly recognized and decoded
     */
    function testFromUrlWithAssociativeArrayInQuery()
    {
        $url = Url::fromPath('/my/test/url.html?param[value]=%25val1&param[value2]=%40val2');
        $this->assertEquals(
            array(
                'param' => array(
                    'value'     =>  '%val1',
                    'value2'    =>  '@val2'
                )
            ),
            $url->getParams(),
            'Assert arrays in param[] = value syntax to be correctly recognized and parsed as arrays'
        );
    }

    /**
     *  Tests whether simple query parameters can be correctly added on an existing query and ends up in correct Urls
     */
    function testAddQueryParameterToUrlWithoutQuery()
    {
        $url = Url::fromPath(
            '/my/test/url.html',
            array(
                'param1' => 'val1',
                'param2' => 'val2'
            )
        );
        $url->setBaseUrl('path/to');
        $this->assertEquals(
            '/path/to/my/test/url.html?param1=val1&amp;param2=val2',
            $url->getAbsoluteUrl(),
            'Assert additional parameters to be correctly added to the Url'
        );
    }

    /**
     * Test whether parameters are correctly added to existing query parameters
     * and existing ones are correctly overwritten if they have the same key
     */
    function testOverwritePartialQuery()
    {
        $url = Url::fromPath(
            '/my/test/url.html?param1=oldval1',
            array(
                'param1' => 'val1',
                'param2' => 'val2'
            )
        );
        $url->setBaseUrl('path/to');
        $this->assertEquals(
            '/path/to/my/test/url.html?param1=val1&amp;param2=val2',
            $url->getAbsoluteUrl(),
            'Assert additional parameters to be correctly added to the Url and overwriting existing parameters'
        );
    }

    /**
     * Test whether array parameters are correctly added to an existing Url and end up in correct Urls
     */
    function testSetQueryWithArrayParameter()
    {
        $url = Url::fromPath(
            '/my/test/url.html',
            array(
                'flatarray' => array('val1', 'val2'),
                'param' => array('value1'=>'val1', 'value2' => 'val2')
            )
        );
        $url->setBaseUrl('path/to');
        $this->assertEquals(
            '/path/to/my/test/url.html?flatarray'.urlencode('[0]').'=val1&amp;'.
                'flatarray'.urlencode('[1]').'=val2&amp;'.
                'param'.urlencode('[value1]').'=val1&amp;'.
                'param'.urlencode('[value2]').'=val2',
            $url->getAbsoluteUrl(),
            'Assert array parameters to be correctly encoded and added to the Url'
        );
    }

    /**
     * Test whether Urls from the request are correctly parsed when no query is given
     */
    function testUrlFromRequestWithoutQuery()
    {
        $request = Mockery::mock('RequestWithoutQuery');
        $request->shouldReceive('getPathInfo')->andReturn('my/test/url.html')
            ->shouldReceive('getBaseUrl')->andReturn('path/to')
            ->shouldReceive('getQuery')->andReturn(array());

        $url = Url::fromRequest(array(), $request);
        $this->assertEquals(
            '/path/to/my/test/url.html',
            $url->getAbsoluteUrl(),
            'Asserting absolute path resembling the requests path appended by the baseUrl'
        );
    }

    /**
     * Test whether Urls from the request are correctly parsed when a query is given
     */
    function testUrlFromRequestWithQuery()
    {
        $request = Mockery::mock('RequestWithoutQuery');
        $request->shouldReceive('getPathInfo')->andReturn('my/test/url.html')
            ->shouldReceive('getBaseUrl')->andReturn('path/to')
            ->shouldReceive('getQuery')->andReturn(array(
                'param1' => 'value1',
                'param2' => array('key1' => 'value1', 'key2' => 'value2')
            )
        );

        $url = Url::fromRequest(array(), $request);
        $this->assertEquals(
            '/path/to/my/test/url.html?param1=value1&amp;'.
                'param2'.urlencode('[key1]').'=value1&amp;'.
                'param2'.urlencode('[key2]').'=value2',
            $url->getAbsoluteUrl(),
            'Asserting absolute path resembling the requests path appended by the baseUrl'
        );
    }

    /**
     * Test the @see Url::getParam($name, $default) function
     */
    function testGetParameterByName()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2');
        $this->assertEquals(
            "val",
            $url->getParam("param", "wrongval"),
            "Asserting a parameter can be fetched via getParam()"
        );
        $this->assertEquals(
            "val2",
            $url->getParam("param2", "wrongval2"),
            "Asserting a parameter can be fetched via getParam()"
        );
        $this->assertEquals(
            "nonexisting",
            $url->getParam("param3", "nonexisting"),
            "Asserting a non existing parameter returning the default value when fetched via getParam()"
        );
    }

    /**
     * Test the Url::remove function with a single key passed
     */
    function testRemoveSingleParameter()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2');
        $url->remove("param");
        $this->assertEquals(
            "val2",
            $url->getParam("param2", "wrongval2"),
            "Asserting other parameters (param2) not being affected by remove"
        );
        $this->assertEquals(
            "rightval",
            $url->getParam("param", "rightval"),
            "Asserting a parameter (param) can be removed via remove"
        );
    }

    /**
     * Test the Url::remove function with an array of keys passed
     */
    function testRemoveMultipleParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url->remove(array("param", "param2"));
        $this->assertEquals(
            "val3",
            $url->getParam("param3", "wrongval"),
            "Asserting other parameters (param3) not being affected by remove"
        );
        $this->assertEquals(
            "rightval",
            $url->getParam("param", "rightval"),
            "Asserting a parameter (param) can be removed via remove in a batch"
        );
        $this->assertEquals(
            "rightval",
            $url->getParam("param2", "rightval"),
            "Asserting a parameter (param2) can be removed via remove in a batch"
        );
    }

    /**
     * Test the Url::without call and whether it returns a copy instead of working on the called object
     */
    function testWithoutCall()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url2 = $url->getUrlWithout(array("param"));
        $this->assertNotEquals(
            $url,
            $url2,
            "Asserting without creating a copy of the url"
        );
        $this->assertTrue(
            $url->hasParam("param"),
            "Asserting the original Url not being affected when calling 'without'"
        );
        $this->assertFalse(
            $url2->hasParam("param"),
            "Asserting the returned Url being without the passed parameter when calling 'without'"
        );
    }

    function testAddParamAfterCreation()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url->addParams(array(
            "param4" => "val4",
            "param3" => "newval3"
        ));
        $this->assertEquals(
            "val4",
            $url->getParam("param4", "wrongval"),
            "Asserting that a parameter can be added with addParam"
        );
        $this->assertEquals(
            "val3",
            $url->getParam("param3", "wrongval"),
            "Asserting that addParam doesn't overwrite existing parameters"
        );
    }

    /**
     * Test whether toString is the same as getAbsoluteUrl
     */
    function testToString()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $this->assertEquals(
            $url->getAbsoluteUrl(),
            (string) $url,
            "Asserting whether toString returns the absolute Url"
        );
    }
}

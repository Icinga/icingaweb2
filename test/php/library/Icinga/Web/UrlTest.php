<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web;

use Mockery;
use Icinga\Web\Url;
use Icinga\Test\BaseTestCase;

class UrlTest extends BaseTestCase
{
    public function testWhetherFromPathCutsOfTheFirstCharOfThePathIfUrlIsInternalAndHasAUsernameInIt()
    {
        $this->getRequestMock()->shouldReceive('getServer')->with("SERVER_NAME")->andReturn('localhost')
            ->shouldReceive('getServer')->with("SERVER_PORT")->andReturn('8080');

        $url = Url::fromPath('http://testusername:testpassword@localhost:8080/path/to/my/url.html');
        $this->assertEquals(
            'path/to/my/url.html',
            $url->getPath(),
            'Url::fromPath does not cut of the first char of path if the url is internal and has a username in it'
        );
    }

    public function testWhetherGetAbsoluteUrlReturnsTheBasePathIfUrlIsInternalAndHasAUsernameInIt()
    {
        $this->getRequestMock()->shouldReceive('getServer')->with("SERVER_NAME")->andReturn('localhost')
            ->shouldReceive('getServer')->with("SERVER_PORT")->andReturn('8080');

        $url = Url::fromPath('http://testusername:testpassword@localhost:8080/path/to/my/url.html');
        $this->assertEquals(
            'http://testusername:testpassword@localhost:8080/path/to/my/url.html',
            $url->getAbsoluteUrl(),
            'Url::getAbsoluteUrl does not reassemble the correct basePath'
        );
    }

    public function testWhetherGetAbsoluteUrlReturnsTheBasePathIfUrlIsInternalAndHasNoUsernameInIt()
    {
        $url = Url::fromPath('/path/to/my/url.html');
        $this->assertEquals(
            '/path/to/my/url.html',
            $url->getAbsoluteUrl(),
            'Url::getAbsoluteUrl does not reassemble the correct basePath'
        );
    }

    public function testWhetherGetAbsoluteUrlReturnsTheBasePathIfUrlIsExternalAndHasAUsernameInIt()
    {
        $this->getRequestMock()->shouldReceive('getServer')->with("SERVER_NAME")->andReturn('localhost')
            ->shouldReceive('getServer')->with("SERVER_PORT")->andReturn('8080');

        $url = Url::fromPath('http://testusername:testpassword@testhost/path/to/my/url.html');
        $this->assertEquals(
            'http://testusername:testpassword@testhost/path/to/my/url.html',
            $url->getAbsoluteUrl(),
            'Url::getAbsoluteUrl does not reassemble the correct basePath'
        );
    }

    public function testWhetherGetAbsoluteUrlReturnsTheBasePathIfUrlIsExternalAndHasNoUsernameInIt()
    {
        $this->getRequestMock()->shouldReceive('getServer')->with("SERVER_NAME")->andReturn('localhost')
            ->shouldReceive('getServer')->with("SERVER_PORT")->andReturn('8080');

        $url = Url::fromPath('http://testhost/path/to/my/url.html');
        $this->assertEquals(
            'http://testhost/path/to/my/url.html',
            $url->getAbsoluteUrl(),
            'Url::getAbsoluteUrl does not reassemble the correct basePath'
        );
    }

    public function testWhetherGetAbsoluteUrlReturnsTheGivenUsernameAndPassword()
    {
        $url = Url::fromPath('http://testusername:testpassword@testsite.com/path/to/my/url.html');
        $this->assertEquals(
            'http://testusername:testpassword@testsite.com/path/to/my/url.html',
            $url->getAbsoluteUrl(),
            'Url::fromPath does not reassemble the correct url'
        );
    }

    public function testWhetherFromRequestWorksWithoutARequest()
    {
        $this->getRequestMock()->shouldReceive('getBaseUrl')->andReturn('/path/to')
            ->shouldReceive('getPathInfo')->andReturn('my/test/url.html')
            ->shouldReceive('getQuery')->andReturn(array('param1' => 'value1', 'param2' => 'value2'));

        $url = Url::fromRequest();
        $this->assertEquals(
            '/path/to/my/test/url.html?param1=value1&amp;param2=value2',
            $url->getAbsoluteUrl('&amp;'),
            'Url::fromRequest does not reassemble the correct url from the global request'
        );
    }

    public function testWhetherFromRequestWorksWithARequest()
    {
        $request = Mockery::mock('Icinga\Web\Request');
        $request->shouldReceive('getPathInfo')->andReturn('my/test/url.html')
            ->shouldReceive('getBaseUrl')->andReturn('/path/to')
            ->shouldReceive('getQuery')->andReturn(array());

        $url = Url::fromRequest(array(), $request);
        $this->assertEquals(
            '/path/to/my/test/url.html',
            $url->getAbsoluteUrl(),
            'Url::fromRequest does not reassemble the correct url from a given request'
        );
    }

    public function testWhetherFromRequestAcceptsAdditionalParameters()
    {
        $request = Mockery::mock('Icinga\Web\Request');
        $request->shouldReceive('getPathInfo')->andReturn('')
            ->shouldReceive('getBaseUrl')->andReturn('/')
            ->shouldReceive('getQuery')->andReturn(array('key1' => 'val1'));

        $url = Url::fromRequest(array('key1' => 'newval1', 'key2' => 'val2'), $request);
        $this->assertEquals(
            'val2',
            $url->getParam('key2', 'wrongval'),
            'Url::fromRequest does not accept additional parameters'
        );
        $this->assertEquals(
            'newval1',
            $url->getParam('key1', 'wrongval1'),
            'Url::fromRequest does not overwrite existing parameters with additional ones'
        );
    }

    /**
     * @expectedException Icinga\Exception\ProgrammingError
     */
    public function testWhetherFromPathProperlyHandlesInvalidUrls()
    {
        Url::fromPath(null);
    }

    public function testWhetherFromPathAcceptsAdditionalParameters()
    {
        $url = Url::fromPath('/my/test/url.html', array('key' => 'value'));

        $this->assertEquals(
            'value',
            $url->getParam('key', 'wrongvalue'),
            'Url::fromPath does not accept additional parameters'
        );
    }

    public function testWhetherFromPathProperlyParsesUrlsWithoutQuery()
    {
        $url = Url::fromPath('/my/test/url.html');

        $this->assertEquals(
            '',
            $url->getBasePath(),
            'Url::fromPath does not recognize the correct base path'
        );
        $this->assertEquals(
            '/my/test/url.html',
            $url->getAbsoluteUrl(),
            'Url::fromPath does not recognize the correct url path'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyParsesUrlsWithoutQuery
     */
    public function testWhetherFromPathProperlyRecognizesTheBaseUrl()
    {
        $url = Url::fromPath(
            '/path/to/my/test/url.html',
            array(),
            Mockery::mock(array('getBaseUrl' => '/path/to'))
        );

        $this->assertEquals(
            '/path/to/my/test/url.html',
            $url->getAbsoluteUrl(),
            'Url::fromPath does not properly differentiate between the base url and its path'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesTheBaseUrl
     */
    public function testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param1=%25arg1&param2=arg%202'
            . '&param3[]=1&param3[]=2&param3[]=3&param4[key1]=val1&param4[key2]=val2');

        $this->assertEquals(
            '%arg1',
            $url->getParam('param1', 'wrongval'),
            'Url::fromPath does not properly decode escaped characters in query parameter values'
        );
        $this->assertEquals(
            'arg 2',
            $url->getParam('param2', 'wrongval'),
            'Url::fromPath does not properly decode aliases characters in query parameter values'
        );
        /*
        // Temporarily disabled, no [] support right now
        $this->assertEquals(
            array('1', '2', '3'),
            $url->getParam('param3'),
            'Url::fromPath does not properly reassemble query parameter values as sequenced values'
        );
        $this->assertEquals(
            array('key1' => 'val1', 'key2' => 'val2'),
            $url->getParam('param4'),
            'Url::fromPath does not properly reassemble query parameters as associative arrays'
        );
        */
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherGetAbsoluteUrlReturnsTheAbsoluteUrl()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2');

        $this->assertEquals(
            '/my/test/url.html?param=val&param2=val2',
            $url->getAbsoluteUrl(),
            'Url::getAbsoluteUrl does not return the absolute url'
        );
    }

    public function testWhetherGetRelativeUrlReturnsTheEmptyStringForAbsoluteUrls()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2');

        $this->assertEquals(
            '',
            $url->getRelativeUrl(),
            'Url::getRelativeUrl does not return the empty string for absolute urls'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherGetParamReturnsTheCorrectParameter()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2');

        $this->assertEquals(
            'val',
            $url->getParam('param', 'wrongval'),
            'Url::getParam does not return the correct value for an existing parameter'
        );
        $this->assertEquals(
            'val2',
            $url->getParam('param2', 'wrongval2'),
            'Url::getParam does not return the correct value for an existing parameter'
        );
        $this->assertEquals(
            'nonexisting',
            $url->getParam('param3', 'nonexisting'),
            'Url::getParam does not return the default value for a non existing parameter'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherRemoveRemovesAGivenSingleParameter()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2');
        $url->remove('param');

        $this->assertEquals(
            'val2',
            $url->getParam('param2', 'wrongval2'),
            'Url::remove removes not only the given parameter'
        );
        $this->assertEquals(
            'rightval',
            $url->getParam('param', 'rightval'),
            'Url::remove does not remove the given parameter'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherRemoveRemovesAGivenSetOfParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url->remove(array('param', 'param2'));

        $this->assertEquals(
            'val3',
            $url->getParam('param3', 'wrongval'),
            'Url::remove removes not only the given parameters'
        );
        $this->assertEquals(
            'rightval',
            $url->getParam('param', 'rightval'),
            'Url::remove does not remove all given parameters'
        );
        $this->assertEquals(
            'rightval',
            $url->getParam('param2', 'rightval'),
            'Url::remove does not remove all given parameters'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherGetUrlWithoutReturnsACopyOfTheUrlWithoutAGivenSetOfParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url2 = $url->getUrlWithout(array('param', 'param2'));

        $this->assertNotSame($url, $url2, 'Url::getUrlWithout does not return a new copy of the url');
        $this->assertEquals(
            array(array('param3', 'val3')),
            $url2->getParams()->toArray(),
            'Url::getUrlWithout does not remove a given set of parameters from the url'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherAddParamsDoesNotOverwriteExistingParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url->addParams(array('param4' => 'val4', 'param3' => 'newval3'));

        $this->assertEquals(
            'val4',
            $url->getParam('param4', 'wrongval'),
            'Url::addParams does not add new parameters'
        );
        $this->assertEquals(
            'newval3',
            $url->getParam('param3', 'wrongval'),
            'Url::addParams does not overwrite existing existing parameters'
        );
        $this->assertEquals(
            array('val3', 'newval3'),
            $url->getParams()->getValues('param3'),
            'Url::addParams does not overwrite existing existing parameters'
        );
    }

    /**
     * @depends testWhetherFromPathProperlyRecognizesAndDecodesQueryParameters
     */
    public function testWhetherOverwriteParamsOverwritesExistingParameters()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');
        $url->overwriteParams(array('param4' => 'val4', 'param3' => 'newval3'));

        $this->assertEquals(
            'val4',
            $url->getParam('param4', 'wrongval'),
            'Url::addParams does not add new parameters'
        );
        $this->assertEquals(
            'newval3',
            $url->getParam('param3', 'wrongval'),
            'Url::addParams does not overwrite existing parameters'
        );
    }

    public function testWhetherEqualUrlMaches()
    {
        $url1 = '/whatever/is/here?a=b&c=d';
        $url2 = Url::fromPath('whatever/is/here', array('a' => 'b', 'c' => 'd'));
        $this->assertEquals(
            true,
            $url2->matches($url1)
        );
    }

    public function testWhetherDifferentUrlDoesNotMatch()
    {
        $url1 = '/whatever/is/here?a=b&d=d';
        $url2 = Url::fromPath('whatever/is/here', array('a' => 'b', 'c' => 'd'));
        $this->assertEquals(
            false,
            $url2->matches($url1)
        );
    }

    /**
     * @depends testWhetherGetAbsoluteUrlReturnsTheAbsoluteUrl
     */
    public function testWhetherToStringConversionReturnsTheAbsoluteUrlForHtmlAttributes()
    {
        $url = Url::fromPath('/my/test/url.html?param=val&param2=val2&param3=val3');

        $this->assertEquals(
            '/my/test/url.html?param=val&amp;param2=val2&amp;param3=val3',
            (string) $url,
            'Converting a url to string does not return the absolute url'
        );
    }
}

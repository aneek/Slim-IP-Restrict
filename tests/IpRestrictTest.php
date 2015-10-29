<?php
/**
 * @file
 * Contains Aneek\IpRestrict\Tests\IpRestrictTest.
 */

namespace Aneek\IpRestrict\Tests;

use PHPUnit_Framework_TestCase;
use Aneek\IpRestrict\IpRestrictMiddleware;
use Slim\App;
use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;

/**
 * Tests \Aneek\IpRestrict\IpRestrictMiddleware.
 */
class IpRestrictTest extends PHPUnit_Framework_TestCase
{
    /**
     * The IP set for allowing or blocking the requests.
     *
     * @var array
     */
    private $ipSet = [];
    
    /**
     * The client machine IP.
     *
     * @var string
     */
    private $clientIp;
    
    /**
     * Flag sets if the IP set is a allow list or disallow list.
     *
     * @var bool
     */
    private $negate;
    
    /**
     * The extra options to be provided in the middleware.
     *
     * @var array
     */
    private $options = [];
    
    /**
     * The IpRestrictMiddleware class instance.
     *
     * @var \Aneek\IpRestrict\IpRestrictMiddleware
     */
    private $ipRestrictMiddleware;
    
    /**
     * Setup the test case variables.
     */
    protected function setUp()
    {
        $this->ipSet = [
          '127.0.0.2',
          '127.0.0.3',
        ];
        $this->clientIp = '127.0.0.1';
        $this->negate = false;
        
        $this->ipRestrictMiddleware = new IpRestrictMiddleware($this->ipSet, $this->negate, $this->options);
    }
    
    protected function tearDown()
    {
        
    }
    
    /**
     * Tests IpRestrictMiddleware::restrict() method.
     */
    public function testRestrict()
    {
        // If allow is true then the IP set will act as an allow list.
        $ipSetAllowed = $this->ipRestrictMiddleware->restrict($this->clientIp, $this->ipSet, !$this->negate);
        $this->assertTrue($ipSetAllowed, sprintf('Client IP %s should not be allowed', $this->clientIp));
        
        // If we make the allow list a disallow list then the the client's IP address will be rejected.
        $ipSetDisallowed = $this->ipRestrictMiddleware->restrict('127.0.0.3', $this->ipSet, $this->negate);
        $this->assertTrue($ipSetDisallowed, sprintf('Client IP %s should not be allowed', '127.0.0.3'));
        
        $isIpRejected = $this->ipRestrictMiddleware->restrict('127.0.0.2', $this->ipSet, !$this->negate);
        $this->assertFalse($isIpRejected, sprintf('Client IP %s should be allowed', '127.0.0.2'));
    }
    
    /**
     * Integration test IpRestrictMiddleware::_invoke() with JSON accept header.
     */
    public function testIpRestrictWithJson()
    {
        // Prepare the Request and the application.
        $app = new App();
        // Setup a demo environment
        $env = Environment::mock([
          'SCRIPT_NAME' => '/index.php',
          'REQUEST_URI' => '/foo',
          'REQUEST_METHOD' => 'GET',
          'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $headers->set('Accept', 'application/json');
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();
        $app->getContainer()['request'] = $req;
        $app->getContainer()['response'] = $res;

        // Set the options value.
        $this->options = [
          'error_code' => 403,
          'exception_message' => 'NOT ALLOWED',
        ];
        
        $app->add(new IpRestrictMiddleware($this->ipSet, false, $this->options));
        $app->get('/foo', function ($req, $res) {
            return $res;
        });
        $resOut = $app->run();

        // Check the response status code.
        $statusCode = $resOut->getStatusCode();
        $this->assertEquals(403, $statusCode);
        
        // Check the response header content type.
        $contentType = $resOut->getHeader('Content-type')[0];
        $this->assertEquals('application/json', $contentType, 'Content type is application/json');
        
        // Check the response body.
        $body = (string) $resOut->getBody();
        $decoded = json_decode($body, true);
        $this->assertEquals($this->options['exception_message'], $decoded['message'], 'The response is matched with the given input.');
    }
    
    /**
     * Integration test IpRestrictMiddleware::_invoke() with XML accept header.
     */
    public function testIpRestrictWithXml()
    {
        // Prepare the Request and the application.
        $app = new App();
        // Setup a demo environment
        $env = Environment::mock([
          'SCRIPT_NAME' => '/index.php',
          'REQUEST_URI' => '/foo',
          'REQUEST_METHOD' => 'GET',
          'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $headers->set('Accept', 'application/xml');
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();
        $app->getContainer()['request'] = $req;
        $app->getContainer()['response'] = $res;

        // Set the options value.
        $this->options = [
          'error_code' => 403,
          'exception_message' => 'NOT ALLOWED',
        ];
        
        $app->add(new IpRestrictMiddleware($this->ipSet, false, $this->options));
        $app->get('/foo', function ($req, $res) {
            return $res;
        });
        $resOut = $app->run();
        
        // Check the response status code.
        $statusCode = $resOut->getStatusCode();
        $this->assertEquals(403, $statusCode);
        
        // Check the response header content type.
        $contentType = $resOut->getHeader('Content-type')[0];
        $this->assertEquals('application/xml', $contentType, 'Content type is application/xml');
        
        // Check the response body.
        $body = (string) $resOut->getBody();
        $decoded = simplexml_load_string($body);
        $this->assertEquals($this->options['exception_message'], $decoded->message, 'The response is matched with the given input.');
    }
    
    /**
     * Integration test IpRestrictMiddleware::_invoke() with HTML accept header.
     */
    public function testIpRestrictWithHtml()
    {
        // Prepare the Request and the application.
        $app = new App();
        // Setup a demo environment
        $env = Environment::mock([
          'SCRIPT_NAME' => '/index.php',
          'REQUEST_URI' => '/foo',
          'REQUEST_METHOD' => 'GET',
          'REMOTE_ADDR' => '127.0.0.1',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $headers->set('Accept', 'text/html');
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();
        $app->getContainer()['request'] = $req;
        $app->getContainer()['response'] = $res;

        // Set the options value.
        $this->options = [
          'error_code' => 403,
          'exception_message' => 'NOT ALLOWED',
        ];
        
        $app->add(new IpRestrictMiddleware($this->ipSet, false, $this->options));
        $app->get('/foo', function ($req, $res) {
            return $res;
        });
        $resOut = $app->run();
        
        // Check the response status code.
        $statusCode = $resOut->getStatusCode();
        $this->assertEquals(403, $statusCode);
        
        // Check the response header content type.
        $contentType = $resOut->getHeader('Content-type')[0];
        $this->assertEquals('text/html', $contentType, 'Content type is text/html');
        
        // Check the response body.
        $body = (string) $resOut->getBody();
        $expected = '<div>NOT ALLOWED</div>';
        $this->assertEquals($expected, $body, 'The response is matched with the given input.');
    }
    
    /**
     * Integration test IpRestrictMiddleware::_invoke() when the given IP is allowed.
     */
    public function testIpAllow()
    {
        // Prepare the Request and the application.
        $app = new App();
        // Setup a demo environment
        $env = Environment::mock([
          'SCRIPT_NAME' => '/index.php',
          'REQUEST_URI' => '/foo',
          'REQUEST_METHOD' => 'GET',
          'REMOTE_ADDR' => '127.0.0.2',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $headers->set('Accept', 'text/html');
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();
        $app->getContainer()['request'] = $req;
        $app->getContainer()['response'] = $res;

        // Set the options value.
        $this->options = [
          'error_code' => 403,
          'exception_message' => 'NOT ALLOWED',
        ];
        
        $app->add(new IpRestrictMiddleware($this->ipSet, false, $this->options));
        $appMessage = 'I am In';
        $app->get('/foo', function ($req, $res) use ($appMessage) {
            $res->write($appMessage);
            return $res;
        });
        $resOut = $app->run();
        
        $body = (string) $resOut->getBody();
        $this->assertEquals($appMessage, $body, 'The client is allowed to access the application.');
    }
}

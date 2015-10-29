<?php
/**
 * @file
 * Contains Aneek\IpRestrict\Tests\IpRestrictTest.
 */

namespace Aneek\IpRestrict\Tests;

use PHPUnit_Framework_TestCase;
use Aneek\IpRestrict\IpRestrictMiddleware;

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
    
    /*public function testIpRestrictMiddleware()
    {
        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'POST',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('POST', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();
    }*/
}

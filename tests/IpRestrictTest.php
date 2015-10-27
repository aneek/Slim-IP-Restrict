<?php
/**
 * @file
 * Contains Aneek\IpRestrict\Tests\IpRestrictTest.
 */

namespace Aneek\IpRestrict\Tests;

use PHPUnit_Framework_TestCase;

/**
 * Tests \Aneek\IpRestrict\IpRestrictMiddleware.
 */
class IpRestrictTest extends PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        
    }
    
    protected function tearDown()
    {
        
    }
    
    public function testIpRestrictMiddleware()
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
    }
}

<?php
/**
 * @file
 * Contains \Aneek\IpRestrict\IpRestrictMiddleware.
 */

namespace Aneek\IpRestrict;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 
 */
class IpRestrictMiddleware
{   
    /**
     * The allowed IP list.
     * 
     * @var array
     */
    private $allowList = [];
    
    /**
     * This flag turns the allowed IP list to disallow list.
     *
     * By default this is set as false so the middleware will allow only the given IPs.
     * 
     * @var bool
     */
    private $negate = false;
    
    /**
     * Extra parameter to change the message to show or the Content-type in HTTP header.
     *
     * Available keys in this parameter:
     *   - exception_message: The exception message shown to end user.
     *   - error_code: The Http error code. Defaults to 403 Forbidden.
     * 
     * @var array
     */
    private $options = [];
    
    /**
     * Class Construct method.
     * 
     * @param array $allowList
     * @param bool $negate
     * @param array $options
     */
    public function __construct(array $allowList, $negate = false, array $options = [])
    {
        $this->allowList = $allowList;
        $this->negate = $negate;
        $this->options = $options;
    }
    
    /**
     * Example middleware invokable class
     *
     * @param  \Psr\Http\Message\ServerRequestInterface $request  PSR7 request
     * @param  \Psr\Http\Message\ResponseInterface      $response PSR7 response
     * @param  callable                                 $next     Next middleware
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $next)
    {
        // Get client's IP.
        $ip = $request->getIp();
        // Get the Content-Type set in the Request's Accept header.
        $contentType = $this->determineContentType($request->getHeaderLine('Accept'));
        // Desired status code to send.
        $statusCode = $this->setStatusCode($this->options);
        // Exception Message to send.
        $message = $this->setExceptionMessage($contentType, $this->options);
        
        if (!$this->negate && !array_key_exists($ip, $this->allowList)) {
            // Allow only the given IPs if negate is false.
            return $response->withStatus($statusCode)->withHeader('Content-type', $contentType)->write($message);
        }
        elseif ($this->negate && array_key_exists($ip, $this->allowList)) {
            // Disallow only the given IPs if negate is true.
            return $response->withStatus($statusCode)->withHeader('Content-type', $contentType)->write($message);
        }
        else {
            // Proceed with the application access.
            $response = $next($request, $response);
            return $response;
        }
    }
    
    /**
     * Read the accept header and determine which content type we know about
     * is wanted.
     *
     * @param  string $acceptHeader Accept header from request
     * @return string
     */
    private function determineContentType($acceptHeader)
    {
        $list = explode(',', $acceptHeader);
        $known = ['application/json', 'application/xml', 'text/html'];
        
        foreach ($list as $type) {
            if (in_array($type, $known)) {
                return $type;
            }
        }

        return 'text/html';
    }
    
    /**
     * Method sets the HTTP status code when a IP is rejected.
     * 
     * @param type $options
     *   The array of extra options provided in the Middleware call.
     * 
     * @return int $code
     *   The HTTP status code.
     */
    protected function setStatusCode(array $options = [])
    {
        $code = 403;
        if (array_key_exists('error_code', $options)) {
            $code = $options['error_code'];
        }
        return (int) $code;
    }
    
    /**
     * Method sets a proper Exception message when a IP is rejected. 
     *
     * @param array $options
     *   The array of extra options provided in the Middleware call.
     *
     * @return string $message
     *   The Exception message.
     */
    protected function setExceptionMessage($contentType = 'application/json', array $options = [])
    {
        $message = 'Forbidden';
        switch ($contentType) {
            case 'application/json':
                $message = array_key_exists('exception_message', $options) ? json_encode(['message' => $options['exception_message']], 0) : 'Forbidden';
                break;
            
            case 'text/html':
                $message = sprintf('<div>%s</div>', (array_key_exists('exception_message', $options) ? $options['exception_message'] : 'Forbidden'));
                break;
            
            case 'application/xml':
                $xml = new \SimpleXMLElement('<root/>');
                $item = $xml->addChild('message', (array_key_exists('exception_message', $options) ? $options['exception_message'] : 'Forbidden'));
                $message = $xml->asXML();
                break;
            
            default:
                break;
        }
        return $message;
    }
    
}

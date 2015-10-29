<?php
/**
 * @file
 * Contains \Aneek\IpRestrict\IpRestrictMiddleware.
 */

namespace Aneek\IpRestrict;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Ip Restriction class.
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
     * Class Constructor method.
     * 
     * @param array $allowList
     *   The allowed IP addresses.
     * @param bool $negate
     *   Set to true if the allow list should be turned to disallow list.
     * @param array $options
     *   Extra inputs like status code or exception messages.
     */
    public function __construct(array $allowList, $negate = false, array $options = [])
    {
        $this->allowList = $allowList;
        $this->negate = $negate;
        $this->options = $options;
    }
    
    /**
     * IP Restriction Middleware call.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request 
     *   PSR7 request
     * @param \Psr\Http\Message\ResponseInterface $response
     *   PSR7 response
     * @param callable $next 
     *   Next middleware
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
        // Restrict The IP access if needed.
        $restrict = $this->restrict($ip, $this->allowList, !$this->negate);
        if ($restrict === true) {
            return $response->withStatus($statusCode)->withHeader('Content-type', $contentType)->write($message);
        }
        else {
            // Proceed with the application access.
            return $response = $next($request, $response);
        }
        
    }
    
    /**
     * Method returns whether to allow or disallow the client's IP.
     *
     * @param string $clientIp
     *   The client's ip address.
     * @param array $ipSet
     *   The given IP addresses set.
     * @param bool $allow
     *   The flag denotes whether to think the ip set array as a allowable or disallowable list.
     *
     * @return bool
     *   Returns true if IP is not allowed else false.
     */
    public function restrict($clientIp, array $ipSet = [], $allow = true)
    {
        if ($allow && !in_array($clientIp, $ipSet)) {
            return true;
        }
        elseif (!$allow && in_array($clientIp, $ipSet)) {
            return true;
        }
        else {
            return false;
        }
    }
    
    /**
     * Read the accept header and determine which content type we know about
     * is wanted.
     *
     * @param string $acceptHeader 
     *   Accept header from request
     *
     * @return string
     *   The HTTP header content type
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
                $m = array_key_exists('exception_message', $options) ? $options['exception_message'] : 'Forbidden';
                $message = json_encode(['message' => $m]);
                break;
            
            case 'text/html':
                $message = sprintf('<div>%s</div>', (array_key_exists('exception_message', $options) ? $options['exception_message'] : 'Forbidden'));
                break;
            
            case 'application/xml':
                $xml = new \SimpleXMLElement('<root/>');
                $xml->addChild('message', (array_key_exists('exception_message', $options) ? $options['exception_message'] : 'Forbidden'));
                $message = $xml->asXML();
                break;
            
            default:
                $message = sprintf('%s', (array_key_exists('exception_message', $options) ? $options['exception_message'] : 'Forbidden'));
                break;
        }
        return $message;
    }
    
}


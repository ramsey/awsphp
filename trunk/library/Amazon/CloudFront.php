<?php
/**
 * A library for interfacing with Amazon Web Services
 *
 * LICENSE
 *
 * This source file is subject to the Simplified BSD License that is bundled
 * with this package in the file LICENSE.
 * It is also available through the world-wide-web at this URL:
 * http://awsphp.googlecode.com/svn/trunk/LICENSE
 *
 * @package Amazon
 * @subpackage CloudFront
 * @copyright Copyright (c) 2009, Ben Ramsey (benramsey.com)
 * @license http://awsphp.googlecode.com/svn/trunk/LICENSE Simplified BSD License
 * @version $Id$
 */

/**
 * @package Amazon
 * @subpackage CloudFront
 * @copyright Copyright (c) 2009, Ben Ramsey (benramsey.com)
 * @license http://awsphp.googlecode.com/svn/trunk/LICENSE Simplified BSD License
 * @author Ben Ramsey <ramsey@php.net>
 */
class Amazon_CloudFront
{   
    /**
     * The API_VERSION constant represents the version of the Amazon CloudFront
     * service that this library uses.
     */
    const API_VERSION = '2008-06-30';
    
    /**
     * The DISTRIBUTION_URI constant represents the URI of the Amazon CloudFront
     * distribution resource.
     */
    const DISTRIBUTION_URI = '/2008-06-30/distribution';

    /**
     * The HOST constant represents the host domain for Amazon CloudFront 
     * requests.
     */
    const HOST = 'cloudfront.amazonaws.com';
    
    /**
     * The NAMESPACE constant represents the namespace for Amazon CloudFront 
     * XML documents.
     */
    const NAMESPACE = 'http://cloudfront.amazonaws.com/doc/2008-06-30/';
    
    /**
     * AWS access key ID
     * @var string
     */
    protected $_accessKeyId;

    /**
     * The HTTP Date header value to use in requests
     * @var string
     */
    protected $_httpDate;

    /**
     * AWS secret access key
     * @var string
     */
    protected $_secretAccessKey;

    /**
     * Creates an instance of an Amazon CloudFront client
     *
     * @param string $accessKeyId AWS access key ID
     * @param string $secretAccessKey AWS secret access key
     * @return void
     */
    public function __construct($accessKeyId, $secretAccessKey)
    {
        $this->_accessKeyId = $accessKeyId;
        $this->_secretAccessKey = $secretAccessKey;
        $this->_httpDate = utf8_encode(gmdate('D, j M Y H:i:s \G\M\T'));
    }
    
    /**
     * Generates the HTTP Authorization header value for Amazon CloudFront 
     * requests
     *
     * According to the Amazon CloudFront documentation, the Authorization
     * header value should follow this format:
     * 
     * <pre><code>
     * "AWS" + " " + AWSAccessKeyID + ":" + Base64(HMAC-SHA1(UTF-8(Date), UTF-8(AWSSecretAccessKey)))
     * </code></pre>
     *
     * @return string
     */
    protected function _getAuthorizationHeader()
    {
        $value  = 'AWS ';
        $value .= $this->_accessKeyId . ':';
        $value .= base64_encode(hash_hmac('sha1', $this->_httpDate, utf8_encode($this->_secretAccessKey), true));
        
        return $value;
    }
    
    /**
     * Sends a request through a socket connection
     *
     * Returns an appropriate Amazon CloudFront object if there is a response 
     * body. If the response is a 200 series status code and there is no 
     * response body, then it returns boolean true or the HTTP ETag header, if
     * present.
     *
     * This method borrows some code from the Zend_Http_Client_Adapter_Socket 
     * class in the {@link http://framework.zend.com/ Zend Framework}.
     *
     * @param string $method The HTTP method to use
     * @param string $requestUri The URI to request
     * @param string $body Optionally, the body of the request
     * @param array $headers Optionally, additional headers to add to the request
     * @return mixed
     * @throws Amazon_CloudFront_Exception
     * @todo Use Zend_Http_Client here for a much cleaner implementation
     */
    protected function _sendRequest($method, $requestUri, $body = null, array $headers = array())
    {
        // Build the request
        $request  = "{$method} {$requestUri} HTTP/1.1\r\n";
        $request .= "Host: " . self::HOST . "\r\n";
        $request .= "Date: {$this->_httpDate}\r\n";
        $request .= "x-amz-date: {$this->_httpDate}\r\n";
        $request .= "Authorization: {$this->_getAuthorizationHeader()}\r\n";

        // If the mbstring extension is enabled and function overloading is
        // set to overload string functions, then use mb_strlen() to determine
        // the byte length of the string.
        if (!is_null($body)) {
            if (ini_get('mbstring.func_overload') & 2) {
                $length = mb_strlen($body, '8bit');
            } else {
                $length = strlen($body);
            }
            $request .= "Content-Length: {$length}\r\n";
            $request .= "Content-Type: application/xml\r\n";
        }
        
        // Add additional headers to the request
        foreach ($headers as $header => $value) {
            $request .= trim($header) . ': ' . trim($value) . "\r\n";
        }

        $request .= "Connection: close\r\n\r\n";
        
        if (!is_null($body)) {
            $request .= $body;
        }

        // Create the socket connection and write to it
        $socket = @stream_socket_client('ssl://' . self::HOST . ':443',
                                        $errno,
                                        $errstr,
                                        30,
                                        STREAM_CLIENT_CONNECT);
        
        if (! @fwrite($socket, $request)) {
            require_once 'Amazon/CloudFront/Exception.php';
            throw new Amazon_CloudFront_Exception('Error writing request to server');
        }

        $status = '';
        $responseHeaders = array();
        $responseBody = '';
        
        // Get the response status and headers
        while (($line = @fgets($socket)) !== false) {
            if (strpos($line, 'HTTP') !== false) {
                $status = $line;
            } else {
                if (rtrim($line) === '') break;
                $header = explode(':', $line, 2);
                $responseHeaders[trim($header[0])] = trim($header[1]);
            }
        }
        $responseHeaders = array_change_key_case($responseHeaders, CASE_LOWER);
        
        // Read to the end of the response
        if (isset($responseHeaders['transfer-encoding']) 
            && strtolower($responseHeaders['transfer-encoding']) == 'chunked') {
            
            do {
                $line  = @fgets($socket);
                $chunk = $line;

                // Figure out the next chunk size
                $chunksize = trim($line);
                if (! ctype_xdigit($chunksize)) {
                    @fclose($socket);
                    require_once 'Amazon/CloudFront/Exception.php';
                    throw new Amazon_CloudFront_Exception('Invalid chunk size "' .
                        $chunksize . '" unable to read chunked body');
                }

                // Convert the hexadecimal value to plain integer
                $chunksize = hexdec($chunksize);

                // Read chunk
                $left_to_read = $chunksize;
                while ($left_to_read > 0) {
                    $line = @fread($socket, $left_to_read);
                    if ($line === false || strlen($line) === 0)
                    {
                        break;
                    } else {
                        $chunk .= $line;
                        $left_to_read -= strlen($line);
                    }

                    // Break if the connection ended prematurely
                    if (feof($socket)) break;
                }

                $chunk .= @fgets($socket);
                $responseBody .= $chunk;
            } while ($chunksize > 0);

        } else {
            while (($line = @fgets($socket)) !== false) {
                $responseBody .= $line;
            }
        }
        
        if (isset($responseHeaders['connection']) && strtolower($responseHeaders['connection']) == 'close') {
            @fclose($socket);
        }
        
        // Get the status code
        preg_match('(\d{3})', $status, $matches);
        $statusCode = (isset($matches[0]) ? $matches[0] : null);
        
        if (is_null($statusCode)) {
            require_once 'Amazon/CloudFront/Exception.php';
            throw new Amazon_CloudFront_Exception('Error receiving response from server');
        }
        
        // If the status code is in the 200 series and there is no response
        // body, then simply return true.
        if ($statusCode >= 200 && $statusCode < 300 && empty($responseBody)) {
            if (isset($responseHeaders['etag'])) {
                return $responseHeaders['etag'];
            }
            return true;
        }

        // Create DOMDocument from the response body
        preg_match('(<[^\?].*[^\?]>)', $responseBody, $matches);

        if (isset($matches[0])) {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($matches[0]);
            $sxe = simplexml_import_dom($dom);
        } else {
            require_once 'Amazon/CloudFront/Exception.php';
            throw new Amazon_CloudFront_Exception('XML document not found in response body');
        }
        
        // Capture the ETag header, if present
        $etag = null;
        if (isset($responseHeaders['etag'])) {
            $etag = $responseHeaders['etag'];
        }
        
        switch ($dom->documentElement->nodeName) {
            case 'Distribution':
                require_once 'Amazon/CloudFront/Distribution.php';
                return new Amazon_CloudFront_Distribution($sxe, $etag);
                break;
            case 'DistributionList':
                require_once 'Amazon/CloudFront/Distribution/List.php';
                return new Amazon_CloudFront_Distribution_List($sxe);
                break;
            case 'DistributionConfig':
                require_once 'Amazon/CloudFront/Distribution/Config.php';
                return new Amazon_CloudFront_Distribution_Config($sxe);
                break;
            case 'ErrorResponse':
                require_once 'Amazon/CloudFront/Exception.php';
                $message = "{$sxe->Error->Code}: {$sxe->Error->Message}";
                throw new Amazon_CloudFront_Exception($message, $statusCode);
                break;
            default:
                require_once 'Amazon/CloudFront/Exception.php';
                throw new Amazon_CloudFront_Exception('Invalid response document from server');
        }
    }
    
    /**
     * Creates a distribution on Amazon CloudFront
     *
     * @param Amazon_CloudFront_Distribution_Config $config Configuration object to create a new distribution
     * @return Amazon_CloudFront_Distribution
     */
    public function createDistribution(Amazon_CloudFront_Distribution_Config $config)
    {
        $dom = $config->generateDom();
        return $this->_sendRequest('POST', self::DISTRIBUTION_URI, $dom->saveXML());
    }

    /**
     * Deletes a distribution
     *
     * @param Amazon_CloudFront_Distribution $distribution Distribution object to update
     * @return bool
     * @throws Amazon_CloudFront_Exception If the distribution is currently enabled
     *                                     or when the distribution status is still
     *                                     labeled as being "InProgress"
     */
    public function deleteDistribution(Amazon_CloudFront_Distribution $distribution)
    {
        // If the distribution is enabled, throw an exception
        if ($distribution->getConfig()->isEnabled()) {
            require_once 'Amazon/CloudFront/Exception.php';
            throw new Amazon_CloudFront_Exception(
                'The distribution must first be disabled before it can be deleted');
        }
        
        // Check the status of the distribution. If the distribution status 
        // is "InProgress," throw an exception; cannot delete until distribution
        // is no longer in progress.
        $checkDist = $this->getDistribution($distribution->getId());
        if ($checkDist->getStatus() == "InProgress") {
            require_once 'Amazon/CloudFront/Exception.php';
            throw new Amazon_CloudFront_Exception(
                'The distribution cannot be deleted because it is still listed as being "in progress"');
        } else if ($distribution->getEtag() != $checkDist->getEtag()) {
            $distribution->setEtag($checkDist->getEtag());
        }
        
        $headers = array('If-Match' => $distribution->getEtag());
        
        $uri = self::DISTRIBUTION_URI . '/' . urlencode($distribution->getId());
        return $this->_sendRequest('DELETE', $uri, null, $headers);
    }
    
    /**
     * Retrieves a distribution from Amazon CloudFront
     *
     * @param string $id The distribution ID
     * @return Amazon_CloudFront_Distribution
     */
    public function getDistribution($id)
    {
        $uri = self::DISTRIBUTION_URI . '/' . urlencode($id);
        return $this->_sendRequest('GET', $uri);
    }
    
    /**
     * Retrieves a list of distributions from Amazon CloudFront
     *
     * @param string $marker Use this when paginating results to indicate where
     *                       in your list of distributions to begin. The results
     *                       include distributions in the list that occur after
     *                       the marker. To get the next page of results, set 
     *                       the Marker to the value of the NextMarker from the
     *                       current page's response (which is also the ID of 
     *                       the last distribution on that page).
     * @param int $maxItems The maximum number of distributions you want in the 
     *                      response body.
     * @return Amazon_CloudFront_Distribution_List
     */
    public function getDistributionList($marker = null, $maxItems = null)
    {
        $query = array();
        
        if (!is_null($marker)) {
            $query['Marker'] = $marker;
        }
        
        if (!is_null($maxItems)) {
            $query['MaxItems'] = $maxItems;
        }
        
        $queryString = http_build_query($query);
        
        $uri = self::DISTRIBUTION_URI . ($queryString ? "?{$queryString}" : '');

        return $this->_sendRequest('GET', $uri);
    }
    
    /**
     * Updates a distribution's configuration and returns true on success
     *
     * @param Amazon_CloudFront_Distribution $distribution Distribution object to update
     * @return Amazon_CloudFront_Distribution
     */
    public function updateDistribution(Amazon_CloudFront_Distribution $distribution)
    {        
        $headers = array('If-Match' => $distribution->getEtag());
        
        $uri = self::DISTRIBUTION_URI . '/' . urlencode($distribution->getId()) . '/config';
        $dom = $distribution->getConfig()->generateDom();
        $response = $this->_sendRequest('PUT', $uri, $dom->saveXML(), $headers);

        if ($response instanceof Amazon_CloudFront_Distribution) {
            $distribution->setEtag($response->getEtag());
        } else if (is_string($response)) {
            $distribution->setEtag($response);
        }
        
        return $distribution;
    }
}
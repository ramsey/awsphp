<?php
/**
 * A library for interfacing with Amazon Web Services
 *
 * LICENSE
 *
 * This source file is subject to the Simplified BSD License that is bundled
 * with this package in the file LICENSE.
 * It is also available through the World Wide Web at this URL:
 * http://awsphp.googlecode.com/svn/trunk/LICENSE
 *
 * @category awsphp
 * @package Amazon
 * @subpackage CloudFront
 * @copyright Copyright (c) 2009, Ben Ramsey (benramsey.com)
 * @license http://awsphp.googlecode.com/svn/trunk/LICENSE Simplified BSD License
 * @version $Id$
 */

/**
 * @category awsphp
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
     * The request object to use when making requests
     * @var Amazon_Request_Interface
     */
    protected $_request;

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
     * @param Amazon_Request_Interface $request The request object to use
     * @return void
     */
    public function __construct($accessKeyId,
                                $secretAccessKey,
                                Amazon_Request_Interface $request = null)
    {
        $this->_accessKeyId = $accessKeyId;
        $this->_secretAccessKey = $secretAccessKey;
        $this->_httpDate = utf8_encode(gmdate('D, j M Y H:i:s \G\M\T'));

        if (!is_null($request)) {
            $this->_request = $request;
        }
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
     */
    protected function _sendRequest($method, $requestUri, $body = null, array $headers = array())
    {
        if (is_null($this->_request)) {
            require_once 'Amazon/CloudFront/Exception.php';
            throw new Amazon_CloudFront_Exception(
                'Request object must first be set with setRequest()');
        }

        // Build the request
        $this->_request->setMethod($method);
        $this->_request->setUri('https://' . self::HOST . $requestUri);

        $this->_request->setHeader('Date', $this->_httpDate);
        $this->_request->setHeader('x-amz-date', $this->_httpDate);
        $this->_request->setHeader('Authorization', $this->_getAuthorizationHeader());

        // Add additional headers to the request
        foreach ($headers as $header => $value) {
            $this->_request->setHeader($header, $value);
        }

        if (!is_null($body)) {
            $this->_request->setBody($body, 'text/xml');
        }

        // Send the request
        $response = $this->_request->send();

        if (!($response instanceof Amazon_Response_Interface)) {
            require_once 'Amazon/CloudFront/Exception.php';
            throw new Amazon_CloudFront_Exception(
                'Response does not conform to Amazon_Response_Interface');
        }

        // If the status code is in the 200 series and there is no response
        // body, then simply return true.
        if ($response->getStatus() >= 200
            && $response->getStatus() < 300
            && strlen(trim($response->getBody())) == 0) {

            if (!is_null($response->getHeader('ETag'))) {
                return $response->getHeader('ETag');
            }
            return true;
        }

        // Create DOMDocument from the response body
        preg_match('(<[^\?].*[^\?]>)', $response->getBody(), $matches);

        if (isset($matches[0])) {
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($matches[0]);
            $sxe = simplexml_import_dom($dom);
        } else {
            require_once 'Amazon/CloudFront/Exception.php';
            throw new Amazon_CloudFront_Exception('XML document not found in response body');
        }

        switch ($dom->documentElement->nodeName) {
            case 'Distribution':
                require_once 'Amazon/CloudFront/Distribution.php';
                return new Amazon_CloudFront_Distribution($sxe, $response->getHeader('ETag'));
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
                throw new Amazon_CloudFront_Exception($message, $response->getStatus());
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
     * Sets the request object to use for Amazon CloudFront requests
     *
     * @param Amazon_Request_Interface $request
     * @return Amazon_CloudFront
     */
     public function setRequest(Amazon_Request_Interface $request)
     {
         $this->_request = $request;
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

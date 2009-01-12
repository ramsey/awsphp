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
class Amazon_CloudFront_Distribution
{
    /**
     * The distribution's config object
     * @var Amazon_CloudFront_Distribution_Config
     */
    protected $_config;

    /**
     * The domain name for this distribution
     * @var string
     */
    protected $_domainName;

    /**
     * The HTTP ETag header
     * @var string
     */
    protected $_etag;

    /**
     * The distribution's ID
     * @var string
     */
    protected $_id;

    /**
     * The date/time this distribution was last modified
     * @var string
     */
    protected $_lastModifiedTime;

    /**
     * The status of the distribution, either "Deployed" or "InProgress"
     * @var string
     */
    protected $_status;

    /**
     * Creates an instance of an Amazon CloudFront Distribution
     *
     * @param SimpleXMLElement $sxe The Distribution XML document or DistributionSummary node
     * @param string $etag An HTTP header representing the current version of
     *                     the distribution.
     * @return void
     */
    public function __construct(SimpleXMLElement $sxe, $etag = null)
    {
        if (!is_null($etag)) {
            $this->setEtag($etag);
        }

        // If the DistributionConfig element is present, then use it to create
        // the config object. Otherwise, this must be a DistributionSummary
        // element, so pass the entire node to create the config object.
        if ($sxe->DistributionConfig) {
            $this->_config = new Amazon_CloudFront_Distribution_Config($sxe->DistributionConfig);
        } else {
            $this->_config = new Amazon_CloudFront_Distribution_Config($sxe);
        }

        $this->_domainName = (string) $sxe->DomainName;
        $this->_id = (string) $sxe->Id;
        $this->_lastModifiedTime = (string) $sxe->LastModifiedTime;
        $this->_status = (string) $sxe->Status;
    }

    /**
     * Returns the Amazon_Distribution_Config object for this distribution
     *
     * @return Amazon_Distribution_Config
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Returns the domain name for this distribution
     *
     * @return string
     */
    public function getDomainName()
    {
        return $this->_domainName;
    }

    /**
     * Returns the HTTP ETag header representing the current version of this
     * distribution
     *
     * @return string
     */
    public function getEtag()
    {
        return $this->_etag;
    }

    /**
     * Returns the ID for this distribution
     *
     * @return string
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * Returns the last modified time for this distribution
     *
     * @return string
     */
    public function getLastModifiedTime()
    {
        return $this->_lastModifiedTime;
    }

    /**
     * Returns the status for this distribution
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * Sets the HTTP ETag header representing the current version of this
     * distribution
     *
     * @param string $etag The ETag representing the current version of this distribution
     * @return Amazon_CloudFront_Distribution
     */
    public function setEtag($etag)
    {
        $this->_etag = $etag;
        return $this;
    }
}

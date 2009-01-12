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
class Amazon_CloudFront_Distribution_Config
{
    /**
     * A unique identifier that makes sure the request cannot be replayed
     * @var string
     */
    protected $_callerReference;

    /**
     * Array of CNAME domains for the distribution
     * @var array
     */
    protected $_cname = array();

    /**
     * Comments about the distribution
     * @var string
     */
    protected $_comment;

    /**
     * Flag indicating whether the distribution is enabled to accept end user requests
     * @var bool
     */
    protected $_enabled;

    /**
     * The origin Amazon S3 bucket associated with this distribution
     * @var string
     */
    protected $_origin;

    /**
     * Creates an instance of an Amazon CloudFront DistributionConfig
     *
     * @param SimpleXMLElement $sxe The DistributionConfig XML document (optional)
     * @param string $etag An HTTP header representing the current version of
     *                     the distribution.
     * @return void
     */
    public function __construct(SimpleXMLElement $sxe = null)
    {
        if (!is_null($sxe)) {
            $this->setComment((string) $sxe->Comment);
            $this->setEnabled((string) $sxe->Enabled == 'true' ? true : false);
            $this->setOrigin((string) $sxe->Origin);

            if ($sxe->CallerReference) {
                $this->setCallerReference((string) $sxe->CallerReference);
            }

            if ($sxe->CNAME) {
                foreach ($sxe->CNAME as $cname) {
                    $this->addCname((string) $cname);
                }
            }
        }
    }

    /**
     * Adds a new CNAME to the distribution's config
     *
     * @param string $cname A new CNAME to associate with the distribution
     * @return Amazon_CloudFront_Distribution_Config
     */
    public function addCname($name)
    {
        $this->_cname[] = $name;
        return $this;
    }

    /**
     * Generates and returns a DOMDocument representing the DistributionConfig
     * element for use in POST and PUT requests.
     *
     * @return DOMDocument
     */
    public function generateDom()
    {
        require_once 'Amazon/CloudFront.php';

        $dom = new DOMDocument();

        $root = $dom->createElementNS(
            Amazon_CloudFront::NAMESPACE,
            'DistributionConfig');
        $dom->appendChild($root);

        if (empty($this->_origin)) {
            require_once 'Amazon/CloudFront/Exception.php';
            throw new Amazon_CloudFront_Exception(
                "DistributionConfig requires 'Origin'");
        }
        $origin = $dom->createElementNS(
            Amazon_CloudFront::NAMESPACE,
            'Origin',
            $this->getOrigin());
        $root->appendChild($origin);

        $ref = $dom->createElementNS(
            Amazon_CloudFront::NAMESPACE,
            'CallerReference',
            $this->getCallerReference());
        $root->appendChild($ref);

        foreach ($this->getCname() as $cname) {
            $cnameElement = $dom->createElementNS(
                Amazon_CloudFront::NAMESPACE,
                'CNAME',
                $cname);
            $root->appendChild($cnameElement);
        }

        $comment = $dom->createElementNS(
            Amazon_CloudFront::NAMESPACE,
            'Comment',
            $this->getComment());
        $root->appendChild($comment);

        $enabled = $dom->createElementNS(
            Amazon_CloudFront::NAMESPACE,
            'Enabled',
            $this->isEnabled(true));
        $root->appendChild($enabled);

        return $dom;
    }

    /**
     * Returns the caller reference identifier
     *
     * @return string
     */
    public function getCallerReference()
    {
        if (empty($this->_callerReference)) {
            $this->setCallerReference(date('YmdHis'));
        }

        return $this->_callerReference;
    }

    /**
     * Returns the array of CNAME values or, optionally, the CNAME at $index
     *
     * @return array|string
     */
    public function getCname($index = null)
    {
        if (is_null($index)) {
            return $this->_cname;
        }

        return $this->_cname[$index];
    }

    /**
     * Returns the comments about the distribution
     *
     * @return string
     */
    public function getComment()
    {
        return $this->_comment;
    }

    /**
     * Returns the Amazon S3 bucket associated with this distribution
     *
     * @return string
     */
    public function getOrigin()
    {
        return $this->_origin;
    }

    /**
     * Returns true if the distribution is currently set to receive requests
     * from end users
     *
     * @param bool $asString Returns the value as the string "true" or "false"
     * @return bool
     */
    public function isEnabled($asString = false)
    {
        if ($asString) {
            if ((bool) $this->_enabled) {
                return 'true';
            } else {
                return 'false';
            }
        } else {
            return (bool) $this->_enabled;
        }
    }

    /**
     * Removes the CNAME identified by the $index or value
     *
     * @param int|string $indexOrValue The index of value of the CNAME to remove
     * @return Amazon_CloudFront_Distribution_Config
     */
    public function removeCname($indexOrValue)
    {
        if (array_key_exists($indexOrValue, $this->_cname)) {
            unset($this->_cname[$indexOrValue]);
        } else if (($key = array_search($indexOrValue, $this->_cname)) !== false) {
            unset($this->_cname[$key]);
        }

        return $this;
    }

    /**
     * Sets the caller reference identifier
     *
     * @param string $callerReference A unique identifier that makes sure the
     *                                request cannot be replayed
     * @return Amazon_CloudFront_Distribution_Config
     */
    public function setCallerReference($callerReference)
    {
        $this->_callerReference = $callerReference;
        return $this;
    }

    /**
     * Sets the comment
     *
     * @param string $comment Comments about the distribution
     * @return Amazon_CloudFront_Distribution_Config
     */
    public function setComment($comment)
    {
        $this->_comment = $comment;
        return $this;
    }

    /**
     * Sets whether to enable this distribution to accept requests from end users
     *
     * @param bool $enabled
     * @return Amazon_CloudFront_Distribution_Config
     */
    public function setEnabled($enabled = true)
    {
        $this->_enabled = $enabled;
        return $this;
    }

    /**
     * Sets the Amazon S3 bucket to associate with this distribution
     *
     * @param string $origin The associated Amazon S3 bucket URI
     * @return Amazon_CloudFront_Distribution_Config
     */
    public function setOrigin($origin)
    {
        $this->_origin = $origin;
        return $this;
    }
}

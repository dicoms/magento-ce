<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Mage
 * @package    Mage_Page
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Html page block
 *
 * @category   Mage
 * @package    Mage_Page
 */
class Mage_Page_Block_Html_Footer extends Mage_Core_Block_Template
{

    protected $_copyright;

    protected function _construct()
    {
        $this->addData(array(
            'cache_lifetime'=> false,
            'cache_tags'    => array(Mage_Core_Model_Store::CACHE_TAG, Mage_Cms_Model_Block::CACHE_TAG)
        ));
    }

    public function getCacheKey()
    {
        return 'PAGE_FOOTER_'.Mage::app()->getStore()->getId();
    }

    public function setCopyright($copyright)
    {
        $this->_copyright = $copyright;
        return $this;
    }

    public function getCopyright()
    {
        if (!$this->_copyright) {
            $this->_copyright = Mage::getStoreConfig('design/footer/copyright');
        }

        return $this->_copyright;
    }

    /**
     * Retrieve child block HTML, sorted by default
     *
     * @param   string $name
     * @param   boolean $useCache
     * @return  string
     */
    public function getChildHtml($name='', $useCache=true, $sorted=true)
    {
        return parent::getChildHtml($name, $useCache, $sorted);
    }

}

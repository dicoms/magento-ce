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
 * @package    Mage_Cms
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * CMS page collection
 *
 * @category   Mage
 * @package    Mage_Cms
 */

class Mage_Cms_Model_Mysql4_Page_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
	protected $_previewFlag;

    protected function _construct()
    {
        $this->_init('cms/page');
    }

    public function toOptionArray()
    {
        return $this->_toOptionArray('identifier', 'title');
    }

    public function setFirstStoreFlag($flag = false)
    {
    	$this->_previewFlag = $flag;$this->_afterLoad();
    	return $this;
    }

    protected function _afterLoad()
    {
    	if ($this->_previewFlag) {
	    	$items = $this->getColumnValues('page_id');
	    	if (count($items)) {
	    		$select = $this->getConnection()->select()
		    			->from($this->getTable('cms/page_store'))
		    			->where($this->getTable('cms/page_store').'.page_id IN (?)', $items);
				if ($result = $this->getConnection()->fetchPairs($select)) {
					foreach ($this as $item) {
					    if (!isset($result[$item->getData('page_id')])) {
					        continue;
					    }
			    		if ($result[$item->getData('page_id')] == 0) {
			    			$storeCode = key(Mage::app()->getStores(false, true));
			    		} else {
			    			$storeCode = Mage::app()->getStore($result[$item->getData('page_id')])->getCode();
			    		}
			    		$item->setData('store_code', $storeCode);
			    	}
				}
	    	}
    	}

        parent::_afterLoad();
    }

}

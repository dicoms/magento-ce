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
 * @package    Mage_Rss
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Default rss helper
 *
 */
class Mage_Rss_Helper_Order extends Mage_Core_Helper_Abstract
{
    public function isStatusNotificationAllow()
    {
        if (Mage::getStoreConfig('rss/order/status_notified')) {
			return true;
		}
		return false;
    }

    public function getStatusHistoryRssUrl($order)
    {
        $key = $order->getId().":".$order->getIncrementId().":".$order->getCustomerId();
        return $this->_getUrl('rss/order/status', array('_secure' => false, '_query'=>array('data'=>Mage::helper('core')->encrypt($key))));
    }

}
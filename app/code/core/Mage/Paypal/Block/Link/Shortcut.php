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
 * @package    Mage_Paypal
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Paypal shortcut link
 *
 * @category   Mage
 * @package    Mage_Paypal
 */
class Mage_Paypal_Block_Link_Shortcut extends Mage_Core_Block_Template
{
    public function getCheckoutUrl()
    {
        return $this->getUrl('paypal/express/shortcut', array('_secure'=>true));
    }

    public function getImageUrl()
    {
        $locale = Mage::app()->getLocale()->getLocaleCode();
        if (strpos('en_GB', $locale)===false) {
            $locale = 'en_US';
        }

        return 'https://www.paypal.com/'.$locale.'/i/btn/btn_xpressCheckout.gif';
    }

    public function _toHtml()
    {
        if((bool)Mage::getStoreConfig('payment/paypal_express/active')) {
            return parent::_toHtml();
        }

        return '';
    }
}
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
 * @package    Mage_Checkout
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Shopping cart helper
 *
 */
class Mage_Checkout_Helper_Cart extends Mage_Core_Helper_Url
{
    /**
     * Retrieve cart instance
     *
     * @return Mage_Checkout_Model_Cart
     */
    public function getCart()
    {
        return Mage::getSingleton('checkout/cart');
    }

    /**
     * Retrieve url for add product to cart
     *
     * @param   Mage_Catalog_Model_Product $product
     * @return  string
     */
    public function getAddUrl($product, $additional = array())
    {
        /**
         * Identify continue shopping url
         */
        if ($currentProduct = Mage::registry('current_product')) {
            /**
             * go to product view page
             */
            $continueShoppingUrl = $currentProduct->getProductUrl();
        } elseif ($currentCategory = Mage::registry('current_category')) {
            /**
             * go to category view page
             */
            $continueShoppingUrl = $currentCategory->getUrl();
        } else {
            $continueShoppingUrl = $this->_getUrl('*/*/*', array('_current'=>true));
        }

        $params = array(
            Mage_Core_Controller_Front_Action::PARAM_NAME_URL_ENCODED => Mage::helper('core')->urlEncode($continueShoppingUrl),
            'product' => $product->getId()
        );

        if ($this->_getRequest()->getModuleName() == 'checkout'
            && $this->_getRequest()->getControllerName() == 'cart') {
            $params['in_cart'] = 1;
        }

        if (count($additional)){
            $params = array_merge($params, $additional);
        }

        return $this->_getUrl('checkout/cart/add', $params);
    }

    /**
     * Retrieve url for remove product from cart
     *
     * @param   Mage_Sales_Quote_Item $item
     * @return  string
     */
    public function getRemoveUrl($item)
    {
        $params = array(
            'id'=>$item->getId(),
            Mage_Core_Controller_Front_Action::PARAM_NAME_BASE64_URL => $this->getCurrentBase64Url()
        );
        return $this->_getUrl('checkout/cart/delete', $params);
    }

    /**
     * Retrieve shopping cart url
     *
     * @return unknown
     */
    public function getCartUrl()
    {
        return $this->_getUrl('checkout/cart');
    }

    /**
     * Retrieve current quote instance
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return Mage::getSingleton('checkout/session')->getQuote();
    }

    public function getItemsCount()
    {
        return $this->getCart()->getItemsCount();
    }

    public function getItemsQty()
    {
        return $this->getCart()->getItemsQty();
    }

    public function getSummaryCount()
    {
        return Mage::getSingleton('checkout/cart')->getSummaryQty();
    }

}

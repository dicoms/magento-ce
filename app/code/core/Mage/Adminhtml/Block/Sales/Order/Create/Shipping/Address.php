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
 * @package    Mage_Adminhtml
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Adminhtml sales order create shipping address block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */

class Mage_Adminhtml_Block_Sales_Order_Create_Shipping_Address extends Mage_Adminhtml_Block_Sales_Order_Create_Form_Address
{
    protected function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    public function getHeaderText()
    {
        return Mage::helper('sales')->__('Shipping Address');
    }

    public function getHeaderCssClass()
    {
        return 'head-shipping-address';
    }

    protected function _prepareForm()
    {
        if (!$this->_form) {
            parent::_prepareForm();
            $this->_form->addFieldNameSuffix('order[shipping_address]');
            $this->_form->setHtmlIdPrefix('order:shipping_address_');
        }
        return $this;
    }

    public function getIsShipping()
    {
        return true;
    }

    public function getIsAsBilling()
    {
        return $this->getCreateOrderModel()->getShippingAddress()->getSameAsBilling();
    }

    public function getFormValues()
    {
        return $this->getCreateOrderModel()->getShippingAddress()->getData();
    }


    public function getAddressId()
    {
        return $this->getCreateOrderModel()->getShippingAddress()->getCustomerAddressId();
    }

    public function getAddress()
    {
        return $this->getCreateOrderModel()->getShippingAddress();
    }
}
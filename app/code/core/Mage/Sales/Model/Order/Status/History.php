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
 * @package    Mage_Sales
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


class Mage_Sales_Model_Order_Status_History extends Mage_Core_Model_Abstract
{
    /**
     * Order instance
     *
     * @var Mage_Sales_Model_Order
     */
    protected $_order;

    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('sales/order_status_history');
    }

    /**
     * declare order instance
     *
     * @param   Mage_Sales_Model_Order $order
     * @return  Mage_Sales_Model_Order_Status_History
     */
    public function setOrder(Mage_Sales_Model_Order $order)
    {
        $this->_order = $order;
        return $this;
    }

    /**
     * Retrieve order instance
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        return $this->_order;
    }

    /**
     * Retrieve status label
     *
     * @return string
     */
    public function getStatusLabel()
    {
        if($this->getOrder()) {
            return $this->getOrder()->getConfig()->getStatusLabel($this->getStatus());
        }
        
    }

}

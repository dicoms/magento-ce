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
 * @package    Mage_GoogleCheckout
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_GoogleCheckout_Model_Payment extends Mage_Payment_Model_Method_Abstract
{
    const ACTION_AUTHORIZE = 0;
    const ACTION_AUTHORIZE_CAPTURE = 1;

    protected $_code  = 'googlecheckout';

    /**
     * Availability options
     */
    protected $_isGateway               = false;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canVoid                 = true;
    protected $_canUseInternal          = false;
    protected $_canUseCheckout          = false;
    protected $_canUseForMultishipping  = false;

    /**
     * Authorize
     *
     * @param   Varien_Object $orderPayment
     * @return  Mage_GoogleCheckout_Model_Payment
     */
    public function authorize(Varien_Object $payment, $amount)
    {
        $api = Mage::getModel('googlecheckout/api');
        $api->authorize($payment->getOrder()->getExtOrderId());

        return $this;
    }

    /**
     * Capture payment
     *
     * @param   Varien_Object $orderPayment
     * @return  Mage_GoogleCheckout_Model_Payment
     */
    public function capture(Varien_Object $payment, $amount)
    {
        try {
            $this->authorize($payment, $amount);
        } catch (Exception $e) {
            // authorization is not expired yet
        }

        $api = Mage::getModel('googlecheckout/api');
        $api->charge($payment->getOrder()->getExtOrderId(), $amount);

        return $this;
    }

    /**
     * Refund money
     *
     * @param   Varien_Object $invoicePayment
     * @return  Mage_GoogleCheckout_Model_Payment
     */
    //public function refund(Varien_Object $payment, $amount)
    public function refund(Varien_Object $payment, $amount)
    {
        $hlp = Mage::helper('googlecheckout');

//        foreach ($payment->getCreditMemo()->getCommentsCollection() as $comment) {
//            $this->setReason($hlp->__('See Comments'));
//            $this->setComment($comment->getComment());
//        }

        $reason = $this->getReason() ? $this->getReason() : $hlp->__('No Reason');
        $comment = $this->getComment() ? $this->getComment() : $hlp->__('No Comment');

        $api = Mage::getModel('googlecheckout/api');
        $api->refund($payment->getOrder()->getExtOrderId(), $amount, $reason, $comment);

        return $this;
    }

    public function void(Varien_Object $payment)
    {
        $this->cancel($payment);

        return $this;
    }

    /**
     * Void payment
     *
     * @param   Varien_Object $invoicePayment
     * @return  Mage_GoogleCheckout_Model_Payment
     */
    public function cancel(Varien_Object $payment)
    {
        $hlp = Mage::helper('googlecheckout');
        $reason = $this->getReason() ? $this->getReason() : $hlp->__('Unknown Reason');
        $comment = $this->getComment() ? $this->getComment() : $hlp->__('No Comment');

        $api = Mage::getModel('googlecheckout/api');
        #$api->cancel($payment->getOrder()->getExtOrderId(), $reason, $comment);

        return $this;
    }
}
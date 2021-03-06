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


class Mage_GoogleCheckout_Model_Source_Checkout_Image
{
    public function toOptionArray()
    {
        $hlp = Mage::helper('googlecheckout');

        $sizes = array(
            '180/46' => $hlp->__('Large - %s', '180x46'),
            '168/44' => $hlp->__('Medium - %s', '168x44'),
            '160/43' => $hlp->__('Small - %s', '160x43'),
        );

        $styles = array(
            'trans' => $hlp->__('Transparent'),
            'white'   => $hlp->__('White Background'),
        );

        $options = array();
        foreach ($sizes as $size=>$sizeLabel) {
            foreach ($styles as $style=>$styleLabel) {
                $options[] = array(
                    'value' => $size.'/'.$style,
                    'label' => $sizeLabel.' ('.$styleLabel.')'
                );
            }
        }

        return $options;
    }
}
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
 * Admin tax class content block
 *
 * @category   Mage
 * @package    Mage_Adminhtml
 */

class Mage_Adminhtml_Block_Tax_Class extends Mage_Adminhtml_Block_Widget_Grid_Container
{
    public function __construct()
    {
        $this->_controller      = 'tax_class';
        parent::__construct();
    }

    public function setClassType($classType)
    {
        if ($classType == 'PRODUCT') {
            $this->_headerText      = Mage::helper('tax')->__('Product Tax Classes');
            $this->_addButtonLabel  = Mage::helper('tax')->__('Add New Class');
        }
        elseif ($classType == 'CUSTOMER') {
            $this->_headerText      = Mage::helper('tax')->__('Customer Tax Classes');
            $this->_addButtonLabel  = Mage::helper('tax')->__('Add New Class');
        }

        $this->getChild('grid')->setClassType($classType);
        $this->setData('class_type', $classType);

        return $this;
    }
}
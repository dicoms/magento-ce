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
 * @package    Mage_Dataflow
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */


/**
 * Convert dry run validator
 *
 * Insert where you want to step profile execution if dry run flag is set
 *
 * @category   Mage
 * @package    Mage_Dataflow
 */
class Mage_Dataflow_Model_Convert_Validator_Dryrun extends Mage_Dataflow_Model_Convert_Validator_Abstract
{

    public function validate()
    {
        if ($this->getVar('dry_run') || $this->getProfile()->getDryRun()) {
            $this->addException(__("Dry run set, stopping execution"), Mage_Dataflow_Model_Convert_Exceptin::FATAL);
        }
        return $this;
    }

}

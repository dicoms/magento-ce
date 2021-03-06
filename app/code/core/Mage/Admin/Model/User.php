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
 * @package    Mage_Permissions
 * @copyright  Copyright (c) 2004-2007 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mage_Admin_Model_User extends Mage_Core_Model_Abstract
{
    const XML_PATH_FORGOT_EMAIL_TEMPLATE    = 'system/emails/forgot_email_template';
    const XML_PATH_FORGOT_EMAIL_IDENTITY    = 'system/emails/forgot_email_identity';

    protected function _construct()
    {
        $this->_init('admin/user');
    }

    public function save() {

        $data = array(
                'firstname' => $this->getFirstname(),
                'lastname'  => $this->getLastname(),
                'email'     => $this->getEmail(),
                'modified'  => now(),
                'extra'     => serialize($this->getExtra())
            );

        if($this->getId() > 0) {
            $data['user_id']   = $this->getId();
        }

        if( $this->getUsername() ) {
            $data['username']   = $this->getUsername();
        }

        if ($this->getPassword()) {
            $data['password']   = $this->_getEncodedPassword($this->getPassword());
        }

        if ($this->getNewPassword()) {
            $data['password']   = $this->_getEncodedPassword($this->getNewPassword());
        }

        if ( !is_null($this->getIsActive()) ) {
            $data['is_active']  = intval($this->getIsActive());
        }

        $this->setData($data);
        $this->_getResource()->save($this);
        return $this;
    }

    public function delete()
    {
        $this->_getResource()->delete($this);
        return $this;
    }

    public function saveRelations()
    {
        $this->_getResource()->_saveRelations($this);
        return $this;
    }

    public function getRoles()
    {
        return $this->_getResource()->_getRoles($this);
    }

    public function deleteFromRole()
    {
        $this->_getResource()->deleteFromRole($this);
        return $this;
    }

    public function roleUserExists()
    {
        $result = $this->_getResource()->roleUserExists($this);
        return ( is_array($result) && count($result) > 0 ) ? true : false;
    }

    public function add()
    {
        $this->_getResource()->add($this);
        return $this;
    }

    public function userExists()
    {
        $result = $this->_getResource()->userExists($this);
        return ( is_array($result) && count($result) > 0 ) ? true : false;
    }

    public function getCollection() {
        return Mage::getResourceModel('admin/user_collection');
    }

    /**
     * Send email with new user password
     *
     * @return Mage_Admin_Model_User
     */
    public function sendNewPasswordEmail()
    {
        Mage::getModel('core/email_template')
            ->setDesignConfig(array('area'=>'adminhtml', 'store'=>$this->getStoreId()))
            ->sendTransactional(
                Mage::getStoreConfig(self::XML_PATH_FORGOT_EMAIL_TEMPLATE),
                Mage::getStoreConfig(self::XML_PATH_FORGOT_EMAIL_IDENTITY),
                $this->getEmail(),
                $this->getName(),
                array('user'=>$this, 'password'=>$this->getPlainPassword()));
        return $this;
    }

    public function getName($separator=' ')
    {
        return $this->getFirstname().$separator.$this->getLastname();
    }

    public function getId()
    {
        return $this->getUserId();
    }

    /**
     * Get user ACL role
     *
     * @return string
     */
    public function getAclRole()
    {
        return 'U'.$this->getUserId();
    }

    /**
     * Authenticate user name and password and save loaded record
     *
     * @param string $username
     * @param string $password
     * @return boolean
     */
    public function authenticate($username, $password)
    {
        $this->loadByUsername($username);
        if (!$this->getId()) {
            return false;
        }
        $auth = Mage::helper('core')->validateHash($password, $this->getPassword());
        if ($auth) {
            return true;
        } else {
            $this->unsetData();
            return false;
        }
    }

    /**
     * Login user
     *
     * @param   string $login
     * @param   string $password
     * @return  Mage_Admin_Model_User
     */
    public function login($username, $password)
    {
        if ($this->authenticate($username, $password)) {
            $this->getResource()->recordLogin($this);
        }

        return $this;
    }

    public function reload()
    {
        $this->load($this->getId());
        return $this;
    }

    public function loadByUsername($username)
    {
        $this->setData($this->getResource()->loadByUsername($username));
        return $this;
    }

    public function hasAssigned2Role($user)
    {
        return $this->getResource()->hasAssigned2Role($user);
    }

    protected function _getEncodedPassword($pwd)
    {
        return Mage::helper('core')->getHash($pwd, 2);
    }

    public function findFirstAvailableMenu($parent=null, $path='', $level=0)
    {
        if ($parent == null) {
            $parent = Mage::getConfig()->getNode('adminhtml/menu');
        }
        foreach ($parent->children() as $childName=>$child) {
            $aclResource = 'admin/'.$path.$childName;
            if (Mage::getSingleton('admin/session')->isAllowed($aclResource)) {
                if (!$child->children) {
                    return (string)$child->action;
                } else if ($child->children) {
                    $action = $this->findFirstAvailableMenu($child->children, $path.$childName.'/', $level+1);
                    return $action?$action:(string)$child->action;
                }
            }
        }
    }
}
<?php
/**
 * @category     Yireo
 * @package      EmailOverride
 * @author       Wilfried Wolf <wilfried.wolf@sandstein.de> 
 * @copyright    Copyright (c) 2017 Sandstein Neue Medien GmbH
 */  
class Yireo_EmailOverride_Core_Locale extends Mage_Core_Model_Locale {

    protected $_emulatedTranslateConfigs = array();

    protected $_emulatedDesingPackages = array();
    /**
     * Push current locale to stack and replace with locale from specified store
     * Event is not dispatched.
     *
     * @param int $storeId
     */
    public function emulate($storeId)
    {
        if ($storeId) {

            $this->_emulatedLocales[] = clone $this->getLocale();
            $this->_emulatedTranslateConfigs[] = array(
                Mage_Core_Model_Translate::CONFIG_KEY_AREA => 'adminhtml',
                Mage_Core_Model_Translate::CONFIG_KEY_STORE => Mage::getSingleton('core/translate')->getConfig(Mage_Core_Model_Translate::CONFIG_KEY_STORE),
                Mage_Core_Model_Translate::CONFIG_KEY_DESIGN_PACKAGE => Mage::getSingleton('core/translate')->getConfig(Mage_Core_Model_Translate::CONFIG_KEY_DESIGN_PACKAGE),
                Mage_Core_Model_Translate::CONFIG_KEY_DESIGN_THEME => Mage::getSingleton('core/translate')->getConfig(Mage_Core_Model_Translate::CONFIG_KEY_DESIGN_THEME),
            );

            $designPackage = Mage::getSingleton('core/design_package');

            $this->_emulatedDesingPackages[] = clone $designPackage;

            $this->_locale = new Zend_Locale(Mage::getStoreConfig(self::XML_PATH_DEFAULT_LOCALE, $storeId));
            $this->_localeCode = $this->_locale->toString();

            Mage::app()->setCurrentStore($storeId);

            $designPackage
                ->setArea('frontend')
                ->setPackageName($this->_getPackageFromStore($storeId))
                ->setTheme($this->_getLocaleThemeFromStore($storeId));

            $newConfig = array(
                Mage_Core_Model_Translate::CONFIG_KEY_AREA => 'frontend',
                Mage_Core_Model_Translate::CONFIG_KEY_STORE => $storeId,
                Mage_Core_Model_Translate::CONFIG_KEY_DESIGN_PACKAGE => $this->_getPackageFromStore($storeId),
                Mage_Core_Model_Translate::CONFIG_KEY_DESIGN_THEME => $this->_getLocaleThemeFromStore($storeId)

            );
            Mage::getSingleton('core/translate')
                ->setLocale($this->_locale)
                ->init($newConfig, true);

        }
        else {
            $this->_emulatedLocales[] = false;
        }
    }

    /**
     * Get last locale, used before last emulation
     *
     */
    public function revert()
    {
        if ($locale = array_pop($this->_emulatedLocales)) {
            $oldConfig = array_pop($this->_emulatedTranslateConfigs);
            $oldDesingPackage = array_pop($this->_emulatedDesingPackages);

            $this->_locale = $locale;
            $this->_localeCode = $this->_locale->toString();

            Mage::getSingleton('core/translate')
                ->setLocale($this->_locale)
                ->init($oldConfig, true);

            $designPackage = Mage::getSingleton('core/design_package');
            $designPackage = $oldDesingPackage;
        }
    }

    protected function _getPackageFromStore($storeId)
    {
        return Mage::getStoreConfig('design/package/name', Mage::app()->getStore($storeId));
    }

    protected function _getLocaleThemeFromStore($storeId)
    {
        return Mage::getStoreConfig('design/theme/locale', Mage::app()->getStore($storeId));
    }
}
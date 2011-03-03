<?php
/**
 * Magento CobreDireto Payment Gateway
 *
 * @category   Mage
 * @package    Cobredireto
 * @copyright  DGmike (mike@visie.com.br)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 *
 * Cobredireto Payment Action Dropdown source
 *
 */
class Cobredireto_Model_Source_Paginas
{
    public function toOptionArray()
    {
        $collection = Mage::getModel('cms/page')->getCollection();
        $pages = array();
        foreach ($collection as $page) {
            $pages[$page->getIdentifier()] = $page->getTitle();
        }
        return $pages;
    }
}
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
class Cobredireto_Model_Source_Ambiente
{
    public function toOptionArray()
    {
        return array(
            array(
                'value' => Cobredireto_Model_Standard::AMBIENTE_TESTE,
                'label' => Mage::helper('Cobredireto')->__('Teste')
            ),
            array(
                'value' => Cobredireto_Model_Standard::AMBIENTE_PRODUCAO,
                'label' => Mage::helper('Cobredireto')->__('Produção')
            ),
        );
    }
}
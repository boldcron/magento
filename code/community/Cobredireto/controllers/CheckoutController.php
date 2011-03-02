<?php
set_include_path(get_include_path().PS.Mage::getBaseDir('code').'/community/Cobredireto');
require_once('Pg.php');

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
 * @package    CobreDireto
 * @copyright  Copyright (c) 2008 Irubin Consulting Inc. DBA Varien (http://www.varien.com)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * CobreDireto Standard Checkout Controller
 *
 * @author      Michael Granados <mike@visie.com.br>
 */
class Cobredireto_CheckoutController
    extends Mage_Core_Controller_Front_Action
{

	function formatNumber ($number)
	{
		return sprintf('%.2f', (double) $number) * 1;
	}

    public function getStandard()
    {
        return Mage::getSingleton('cobredireto/standard');
    }

    public function indexAction()
    {
        $order = Mage::getModel('sales/order');
        $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());

        $standard = $this->getStandard();

        // dados de configuração
        $codPedido        = $standard->getCheckout()->getLastRealOrderId();
        $ambienteCobreDireto = $standard->getConfigData('ambiente');
        $codLoja             = $standard->getConfigData('codloja');
        $codUsuario          = $standard->getConfigData('usuario');
        $senha               = $standard->getConfigData('senha');

        /**
         * Caso tenha escolhido que o usuário será encaminhado para a página da compra, atualiza a URL que será redirecionado
         */
        if($standard->getConfigData('urlretornoorder')==true){
            $orderId = ($standard->getConfigData('urlretornoorder')==true) ? $standard->getCheckout()->getLastOrderId() : '';
            define('CD_URL_RECIBO'  , Mage::getUrl('sales/order/view/',array('order_id'=>$orderId) ));
        }else {
            define('CD_URL_RECIBO'  , Mage::getUrl($standard->getConfigData('urlretorno')).'?');
        }

        define('CD_URL_ERRO'    , Mage::getUrl($standard->getConfigData('urlerro')));
        define('CD_URL_RETORNO' , Mage::getUrl('cobredireto/bell/'));
        define('CD_FRETE'       , ((double) $order->getShipping_amount())*100);

        $pg=new Pg($codPedido, $codLoja, $codUsuario, $senha, $ambienteCobreDireto); // Codigo do pedido

        // Adicionando dois produto
        //$address = $standard->getQuote()->getShippingAddress();
	    $address = $order->getShippingAddress();
	    $email = $order->getCustomerEmail();	

        $telephone = preg_replace('@[^\d]@', '', $address->getTelephone());
        $telephone = str_pad($telephone, 10, "0", STR_PAD_LEFT);
        $pg->endereco(array (
                'primeiro_nome' => $address->getFirstname(),
                'meio_nome'     => $address->getMiddlename(),
                'ultimo_nome'   => $address->getLastname(),
                'email'         => $email,//$address->getEmail(),
                'documento'     => '',
                'tel_casa'      => array (
                    'area'      => substr($telephone, 0, -8),
                    'numero'    => substr($telephone, -8),
                ),
                'cep'           => preg_replace('@\D@', '', $address->getPostcode()),
            ));



         // pega todos os ítens
        $itens = $order->getAllItems();

        // Array de ítens do carrinho
        $cart = array();

        // valor SubTotal
        $subTotal = 0;

        // Descrição
        $descricaoProdutos = "";

        // Efetua a soma dos produtos e adiciona todos os nomes na descrição
        foreach ($itens as $item) {
            $item_price = 0;
            $item_qty = $item->getQtyToShip();

            if ($children = $item->getChildrenItems()) {
                foreach ($children as $child) {
                    $item_price += $child->getBasePrice() * $child->getQtyOrdered() / $item_qty;
                }
                $item_price = $this->formatNumber($item_price);
            }
            if (!$item_price) {
				$item_price = $this->formatNumber($item->getBasePrice());
            }
        
            //$descricaoProdutos.= $item->getName() . ", " ;
            $subTotal=$item->getRowTotal();

            // valor total do carrinho = ((soma de todos os produtos) - desconto))
            $subtotalComDesconto = ($subTotal + $order->getDiscountAmount());

            $cart[] = array(
                'descricao'  => $item->getName(),
                'quantidade' => $item_qty,
                'id'	       => '',
                'valor'      => $item_price,
            );
        }        

        $pg->adicionar($cart);

        // Cria a compra junto ao CobreDireto e redireciona o usuário
        $pg->pagar();

        //Limpando o carrinho
        foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ){
            Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
        }
        die(' erro ');
    }

}

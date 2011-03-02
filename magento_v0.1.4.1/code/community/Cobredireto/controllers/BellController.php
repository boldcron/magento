<?php
set_include_path(get_include_path().PS.Mage::getBaseDir('code').'/community/Cobredireto');
require_once('Retorno.php');


// Abaixo a função que captura a resposta
function capturar($codpedido, $status)
{
    $salesOrder = Mage::getSingleton('sales/order');
    $order = $salesOrder->loadByIncrementId($codpedido);

    if ($order->getId()) { // Claro! Conseguiu pegar o produto
        // Verificando o Status passado pelo CobreDireto
        if ($status == 0) {
            if (!$order->canInvoice()) {
                //when order cannot create invoice, need to have some logic to take care
                $order->addStatusToHistory(
                    $order->getStatus(), // keep order status/state
                    'Erro na cobranca',
                    $notified = false
                );
            } else {
                $order->getPayment()->setTransactionId($codpedido);
                $invoice = $order->prepareInvoice();
                $invoice->register()->pay();
                Mage::getModel('core/resource_transaction')
                   ->addObject($invoice)
                   ->addObject($invoice->getOrder())
                   ->save();
                $order->setState(
                   Mage_Sales_Model_Order::STATE_PROCESSING, true,
                    sprintf('Pagamento da compra #%s processado junto ao CobreDireto.', $invoice->getIncrementId()),
                   $notified = true
                );
            }
        } else {
            // Não está completa, vamos processar...
            if ( $status == 1 ) {
                $comment = 'Não Pago - Transação cancelada';
                $changeTo = Mage_Sales_Model_Order::STATE_CANCELED;
            } else {
                $comment = 'Pendente – transação em análise ou não capturada';
                $changeTo = Mage_Sales_Model_Order::STATE_HOLDED;
            }

            $order->addStatusToHistory(
                $changeTo,
                $comment,
                $notified = false
            );
        }
        $order->save();
        // Enviar o e-mail assim que receber a confirmação
        if ($status == 0) {
            $order->sendNewOrderEmail();
        }
    }

}





class Cobredireto_BellController
    extends Mage_Core_Controller_Front_Action
{

    public function getStandard()
    {
        return Mage::getSingleton('cobredireto/standard');
    }

    function indexAction()
    {
         $standard = $this->getStandard();

        // dados de configuração
        $ambienteCobreDireto = $standard->getConfigData('ambiente');
        $codLoja             = $standard->getConfigData('codloja');
        $codUsuario          = $standard->getConfigData('usuario');
        $senha               = $standard->getConfigData('senha');

        $pag = new Retorno($codLoja, $codUsuario, $senha, $ambienteCobreDireto);
        $pag->campainha();
        $pag->probe();
    }
}

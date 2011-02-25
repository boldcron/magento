<?php
/**
    * Classe para o retorno do CobreDireto
    *
    * Classe com a finalidade de executar os procedimentos adequados ao retorno do CobreDireto
    * (Bell e Probe)
    *
    * @package CobreDireto
    * @subpackage Retorno
    * @version 0.1
    * @author RZamana <zamana@visie.com.br>
    * @date 05/14/2009
    **/
  class Retorno extends CobreDireto {


    const MSG_CAMPAINHA = 'Mensagem campainha';
    /**
      * Código do pedido na loja
      * @var int
      * @access private
      **/
    private $merch_ref;

    /**
      * Código do pedido no CobreDireto
      * @var int
      * @access private
      **/
    private $id;

    /**
      * Método Construtor com as configurações e já recebendo os POST's
      *
      * Recebe o POST do CobreDireto
      *
      **/
    function __construct($codLoja, $codUsuario, $senha, $ambiente){
      parent::configuraCobreDireto($codLoja, $codUsuario, $senha, $ambiente);

      try {
	      $this->merch_ref  = $_POST['merch_ref'];
	      $this->id         = $_POST['id'];
      }catch(Exception $e){
          echo "erro na captura dos parametros POST: "; // . $e->getMessage();
          exit();
      }

    }

    /**
      * Método para montar o Bell do CobreDireto
      *
      * Monta a estrutura XML para o BELL
      *
      **/
    public function campainha(){
      @header('Content-type: text/xml');
      $bell = new DomDocument('1.0','utf8');
      $payOrder = $bell->createElement('payOrder');
      // envia status igual 0 pois é o status de sucesso
      $status   = $bell->createElement('status',(function_exists('checagem'))? checagem($this->merch_ref): '0');
      $msg      = $bell->createElement('msg',self::MSG_CAMPAINHA);
      $payOrder->appendChild($status);
      $payOrder->appendChild($msg);
      $bell->appendChild($payOrder);
      echo $bell->saveXML();
    }

    /**
      * Método para montar o Probe para o CobreDireto
      *
      * Monta a estrtura XML para o Probe e solicita o WebService
      * caso tenha a função capturar, já solicita a mesma
      *
      **/
    public function probe(){
      $soapProbe =  $this->request->createElement('probe');

      $merch  = $this->request->createElement('merch_ref',$this->merch_ref);
      $id     = $this->request->createElement('id', $this->id);

      $soapProbe->appendChild($merch);
      $soapProbe->appendChild($id);

      $this->request->appendChild($soapProbe);
      parent::initCobreDireto('probe');
      if (function_exists('capturar')){
         capturar($this->merch_ref,(string) $this->xml->order_data->order->bpag_data->status, array(
          'url' => (string) $this->xml->order_data->order->bpag_data->url,
          'msg' => self::MSG_CAMPAINHA,
          'cobredireto_id' => (string) $this->xml->order_data->order->bpag_data->id,
         ));
      } else {
          Mage::log('não existe função capturar');
      }
    }
}
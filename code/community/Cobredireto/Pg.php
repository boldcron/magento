<?php


/**
 * Classe para pagamento com o CobreDireto
 *
 * Classe com a finalidade de executar os procedimentos e adequação
 * para pagamento no CobreDireto
 *
 * @package CobreDireto
 * @subpackage pagamento
 * @version 0.1
 * @author RZamana <zamana@visie.com.br>
 * @date 05/14/2009
 **/
Class Pg extends CobreDireto {

    /**
     * Código do Pedido dentro da Loja
     * @var int
     * @access private
     **/
    private $codpedido;

    /**
     * Frete do pedido
     * @var float
     * @access private
     **/
    private $frete;

    /**
     * root node for DomDocument
     * @var object
     * @access private
     **/
    private $payOrder;

    /**
     * URL onde encontra-se o recibo

     * @var string
     * @access private
     **/
    private $url_recibo;

    /**
     * URL em caso de erro
     * @var string
     * @access private
     **/
    private $url_erro;

    /**
     * URL para uso do Bell
     * @var string
     * @access private
     **/
    private $url_retorno;

    /**
     * Objeto com as configurações do consumidor
     * @var object
     * @access private
     **/
    private $customer_info;

    /**
     * Objeto com as configurações de cobrança
     * @var object
     * @access private
     **/
    private $billing_info;

    /**
     * Objeto com as configurações de entrega
     * @var object
     * @access private
     **/
    private $shipment_info;

    /**
     * Objeto com as configurações de pagamento
     * @var object
     * @access private
     **/
    private $payment_data;


    /**
     * Recebe a configuração inicial do CobreDireto
     *
     * Configura toda a instancia para poder se comunicar com o CobreDireto
     * @param string $codPedido Código do Pedido na Lojoa
     * @param string $codLoja Código da Loja
     * @param string $codUsuario Código do Usuário no Cobre Direto
     * @param string $senha Senha
     * @param string $ambiente Ambiente de execução
     * @param string $orderId Id da ordem, para ser encaminhado para a order
     **/
    public function __construct($codPedido, $codLoja, $codUsuario, $senha, $ambiente) {
        parent::configuraCobreDireto($codLoja, $codUsuario, $senha, $ambiente);

        $this->payOrder =  $this->request->createElement('payOrder');

        $this->codpedido = $codPedido;

        preg_match('@^([[:alnum:]]+)/@i',$_SERVER['SERVER_PROTOCOL'],$matche);
        $urlHost = strtolower($matche[1]).'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $partes = explode('/',$urlHost);
        $tot = count($partes) - 1;
        $urlHost = str_replace($partes[$tot],'',$urlHost);
        $this->url_recibo   = ((defined('CD_URL_RECIBO')) ? CD_URL_RECIBO  : $urlHost.'recibo.php').'&id='.$this->codpedido;
        $this->url_erro     = (defined('CD_URL_ERRO'))   ? CD_URL_ERRO    : $urlHost.'erro.php';
        $this->url_retorno  = (defined('CD_URL_RETORNO'))? CD_URL_RETORNO : $urlHost.'retorno.php';
        $this->frete        = (defined('CD_FRETE'))      ? CD_FRETE       : 0;
    }

    function frete($valor) {
        $this->frete = $valor;
    }
    function url_recibo($valor) {
        $this->url_recibo = $valor;
    }
    function url_erro($valor) {
        $this->url_erro = $valor;
    }
    function url_retorno($valor) {
        $this->url_retorno = $valor;
    }

    /**
     * Adiciona as configurações do consumidor
     *
     * Adiciona, caso tenha, as configurações do consumidor ao XML
     * @access private
     **/
    private function configuraConsumidor() {
        if (!is_object($this->customer_info)) {
            if (is_object($this->billing_info))
                $this->customer_info = $this->billing_info;
            else if (is_object($this->shipment_info))
                $this->customer_info = $this->shipment_info;
        }
        if (is_object($this->customer_info)) {
            $enderecos = $this->request->createElement('customer_data');
            $enderecos->appendChild($this->customer_info);
            if (is_object($this->billing_info))
                $enderecos->appendChild($this->billing_info);
            if (is_object($this->shipment_info))
                $enderecos->appendChild($this->shipment_info);
            $this->payOrder->appendChild($enderecos);
        }
    }

    /**
     * Recebe os produtos a serem enviados para o CobreDireto
     *
     * Recebe um array de produtos a serem adicionados no carrinho do CobreDireto, para cobrança
     * $produtos = array(
     *   array(
     *     "descricao"=>"Descrição do Produto",
     *     "valor"=>12.90,
     *     "quantidade"=>1,
     *     "id"=>33
     *   ),
     * );
     * @param array $dados Array com os produtos
     **/
    public function adicionar($produtos, $desconto) {
        $order_data = $this->request->createElement('order_data');

        $merch_ref  =  $this->request->createElement('merch_ref',$this->codpedido);
        $order_data->appendChild($merch_ref);

        $tax_freight  =  $this->request->createElement('tax_freight',$this->frete);
        $order_data->appendChild($tax_freight);

        $discount_plus  =  $this->request->createElement('discount_plus',number_format($desconto,2,'',''));
        $order_data->appendChild($discount_plus);

        $total = 0;
        foreach($produtos as $k=>$v)
            $total += (floatval($v['valor']) * $v['quantidade']);

        $order_subtotal  =  $this->request->createElement('order_subtotal',number_format($total,2,'',''));
        $order_data->appendChild($order_subtotal);

        $order_total  =  $this->request->createElement('order_total',number_format(($total + $desconto + $this->frete/100),2,'',''));
        $order_data->appendChild($order_total);


        $prods =  $this->request->createElement('order_items');
        foreach($produtos as $k=>$v) {
            $item =  $this->request->createElement('order_item');
            $codigo     = $this->request->createElement('code',$v['id']);
            $descricao  = $this->request->createElement('description',$v['descricao']);
            $quantidade = $this->request->createElement('units',$v['quantidade']);
            $valor      = $this->request->createElement('unit_value',number_format($v['valor'],2,'',''));

            $item->appendChild($codigo);
            $item->appendChild($descricao);
            $item->appendChild($quantidade);
            $item->appendChild($valor);

            $prods->appendChild($item);
        }
        $order_data->appendChild($prods);
        $this->payOrder->appendChild($order_data);
    }

    /**
     * Insere em $request as url pré-configuradas para o CobreDireto
     *
     **/
    function configuraBehavior() {
        $behavior_data = $this->request->createElement('behavior_data');

        $url_post_bell = $this->request->createElement('url_post_bell',$this->url_retorno);
        $behavior_data->appendChild($url_post_bell);

        $url_redirect_success = $this->request->createElement('url_redirect_success');
        $cdata = $this->request->createCDATASection($this->url_recibo);
        $url_redirect_success->appendChild($cdata);
        $behavior_data->appendChild($url_redirect_success);

        $url_redirect_error = $this->request->createElement('url_redirect_error', $this->url_erro);
        $behavior_data->appendChild($url_redirect_error);

        $this->payOrder->appendChild($behavior_data);
    }

    /**
     * Configura o pagamento pela Loja
     *
     * Configura o pagamento estabelecido pela Loja, deixando ao CobreDireto apenas o pagamento em si
     *
     * @param string $tipo Qual a forma de pagamento (ver Apendice A do manual)
     * @param int $parcelas Quantidade de parcelas
     *
     **/
    function pagamento($tipo,$parcelas = '') {
        $payment_data = $this->request->createElement('payment');
        $method = $this->request->createElement('payment_method',$tipo);
        $payment_data->appendChild($method);
        if ($parcelas != '') {
            $installments = $this->request->createElement('installments',$parcelas);
            $payment_data->appendChild($installments);
        }
        if (!is_object($this->payment_data))
            $this->payment_data = $this->request->createElement('payment_data');
        $this->payment_data->appendChild($payment_data);
    }


    /**
     * Insere as informação do consumidor
     *
     * Insere no XML as informações do consumidor para ser enviada ao CobreDireto
     *
     * $data = array (
     *     'primeiro_nome' => '',
     *     'meio_nome'     => '',
     *     'ultimo_nome'   => '',
     *     'email'         => '',
     *     'documento'     => '',
     *     'tel_casa'      => array (
     *       'area'    => '',
     *       'numero'  => '',
     *     ),
     *     'cep'           => '',
     * )
     * @param array $data array contendo todas as informações do consumidor
     * @param string $tipo Qual o endereço a ser inserido, ex.: TODOS, CONSUMIDOR, COBRANCA, ENTREGA
     **/
    public function endereco($dados, $tipo = 'TODOS') {
        $CD_tipos = array('TODOS','CONSUMIDOR','COBRANCA','ENTREGA');
        if (!in_array($tipo,$CD_tipos))
            return false;
        switch($tipo) {
            case 'TODOS':
                $insere = array('customer_info','billing_info','shipment_info');
                break;
            case 'CONSUMIDOR':
                $insere = array('customer_info');
                break;
            case 'COBRANCA':
                $insere = array('billing_info');
                break;
            case 'ENTREGA':
                $insere = array('shipment_info');
                break;
        }
        foreach($insere as $v) {
            $this->$v = $this->request->createElement($v);
            $first_name   = $this->request->createElement('first_name',   $dados['primeiro_nome']);
            $middle_name  = $this->request->createElement('middle_name',  $dados['meio_nome']);
            $last_name    = $this->request->createElement('last_name',    $dados['ultimo_nome']);
            $email        = $this->request->createElement('email',        $dados['email']);
            $document     = $this->request->createElement('document',     $dados['documento']);
            $phone_home   = $this->request->createElement('phone_home');
            $area_cod     = $this->request->createElement('area_code',    $dados['tel_casa']['area']);
            $phone_number = $this->request->createElement('phone_number', $dados['tel_casa']['numero']);
            $phone_home->appendChild($area_cod);
            $phone_home->appendChild($phone_number);
            $address_zip  = $this->request->createElement('address_zip',  $dados['cep']);

            $this->$v->appendChild($first_name );
            $this->$v->appendChild($middle_name);
            $this->$v->appendChild($last_name  );
            $this->$v->appendChild($email      );
            $this->$v->appendChild($document   );
            $this->$v->appendChild($phone_home );
            $this->$v->appendChild($address_zip);
        }
    }

    /**
     * Método para enviar para o CobreDireto
     *
     * Valida todas as informações e envia para o CobreDireto, já redirecionando para a URL do CobreDireto
     *
     **/
    public function pagar() {
        self::configuraBehavior();
        if (is_object($this->payment_data))
            $this->payOrder->appendChild($this->payment_data);
        self::configuraConsumidor();
        $this->request->appendChild($this->payOrder);
        parent::initCobreDireto('payOrder');
        if ($this->xml->status != 0)
            die('<strong>Erro:</strong> '.$this->xml->msg);
        else
            header('Location: '.$this->xml->bpag_data->url);

    }

}



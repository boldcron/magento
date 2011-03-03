<?php

	/**
    * Classe para integração com o CobreDireto
    *
    * Classe com a finalidade de facilitar a integração com CobreDireto
    * utilizando de métodos simples e objetivos
    * e já realizando as consultas e consumos dos webservices do CobreDireto
    *
    * @package CobreDireto
    * @author RZamana <zamana@visie.com.br>
    * @date 05/14/2009
    * @version 0.1.1
    * @abstract
    **/
    abstract class CobreDireto {

    /**
      * Código da Loja dentro do CobreDireto
      * @var int
      * @access private
      **/
    private $codloja;

    /**
      * Usuario para se conectar ao webservice do CobreDireto
      * @var string
      * @access private
      **/
    private $usuario;

    /**
      * Senha para se conectar ao webservice do CobreDireto
      * @var string
      * @access private
      **/
    private $senha;

    /**
      * Ambiente do CobreDireto (producao,teste)
      * @var string
      * @access private
      **/
    private $ambiente;

    /**
      * DomDocument com o request para o CobreDireto
      * @var object
      * @access protected
      **/
    protected $request;

    /**
      * Url do WebService a ser utilizada
      * @var string
      * @access protected
      **/
    protected $__url;

    /**
      * XML de retorno do cobreDireto
      * @var object
      * @access protected
      **/
    protected $xml;

    /**
      * Configuração inicial do CobreDireto
      *
      * Método para configurar todas as informações necessárias
      * para o CobreDireto
      *
      * @param string $codLoja código da loja no CobreDireto
      * @param string $codUsuario código do Usuário no CobreDireto
      * @param string $senha senha do Usuário no CobreDireto
      * @param string $ambiente Ambiente - teste/produção
      *
      * @access private
      **/
    protected function configuraCobreDireto($codLoja, $codUsuario, $senha, $ambiente){

      $this->codloja = $codLoja;
      $this->usuario = $codUsuario;
      $this->senha = $senha;
      $this->ambiente = isset($ambiente) ? $ambiente : 'producao';
      $this->__url = ($this->ambiente == 'producao') ?
              'https://psp.cobredireto.com.br/bpag2/services/BPagWS?wsdl'
            : 'https://psp.cobredireto.com.br/bpag2Sandbox/services/BPagWS?wsdl';

      $this->request = new DomDocument('1.0','utf8');
    }

    /**
      * Função para auxiliar as alterações durante o processo
      *
      * Utilizada para poder 'setar' durante o processo algumas variaveis vinda do BD
      * @param string $method O método a ser 'criado'
      * @param array $argument os Argumentos a serem enviados para o novo método
      * @access public
      **/
    public function __call($method, $argument){
      $liberados = array('codpedido','url_recibo','url_retorno','url_erro','usuario','senha','codloja','ambiente','frete');
      if (preg_match('@^set_@i',$method)){
        $var = substr($method,4);
        if (in_array($var,$liberados)){
          $this->$var = $argument[0];
        }else
          return false;
      }else
        return false;
    }

    /**
      * Inicializa a conexão com o webservice do CobreDireto
      *
      * Faz as chamadas iniciais do webservice.
      * @access private
      **/
    protected function initCobreDireto($action){

      $__CD = new SoapClient($this->__url);
      $retorno = $__CD->doService(
        array (
          'version'   => '1.1.0',
          'action'    => $action,
          'merchant'  => $this->codloja,
          'user'      => $this->usuario,
          'password'  => $this->senha,
          'data'      => $this->request->saveXML(),
        )
      );
      $this->xml = simplexml_load_string($retorno->doServiceReturn);
    }
  }
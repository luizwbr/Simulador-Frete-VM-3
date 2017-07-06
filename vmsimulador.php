<?php
/**
 * $Id: vmsimulador.php 1.0.0 2012-06-28 11:21:25 Luiz Weber $
 * @package	    Virtuemart Simulador de Frete antes da Compra 
 * @subpackage	Virtuemart Simulador Frete
 * @version     1.0.0
 * @description Plugin que mostra no site a simulação do frete no carrinho e na tela de produtos.
 * @copyright	  Copyright © 2012 -  All rights reserved.
 * @license		  GNU General Public License v2.0
 * @author		  Luiz Weber
 *
 *
 */

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );

jimport( 'joomla.plugin.plugin' );

/**
 *
 * @package	    Virtuemart Simulador de Frete antes da Compra 
 * @subpackage	Virtuemart Simulador Frete
 * @class       plgSystemVmsimulador
 * @since       1.5
 */
 
class plgSystemVmsimulador extends JPlugin {	
    function plgSystemVmsimulador( &$subject, $config ) {
       parent::__construct( $subject, $config );
       // Do some extra initialisation in this constructor if required
    }

    /**
     * Do something onAfterInitialise 
     *
	   * @access	
	   * @param	
     */
    function onAfterInitialise()
    {
      // Your custom code here
    }

   /**
     * Do something onAfterRoute 
     *
	   * @access	
	   * @param	
     */
    function onAfterRoute()
    {
      // Your custom code here        
    }

   /**
     * Do something onAfterDispatch 
     *
	   * @access	
	   * @param	
     */
    function onAfterDispatch()
    {
      // Your custom code here    
    }

   /**
     * Do something onAfterRender 
     *
	   * @access	
	   * @param	
     */
    function onAfterRender()
    {
		$app = JFactory::getApplication();
		if($app->getName() != 'site') {
			return true;
		}
		$tag_simulador = $this->params->get('tag_simulador');
		$this->injetarSimulador($tag_simulador);
		
      	$view = JRequest::getVar('view','');
		$option = JRequest::getVar( 'option', '' );
		$calcularSimulador = JRequest::getVar( 'calcularSimulador', '' );
		if ($option=='com_virtuemart' && $calcularSimulador == '1') {
			$this->simulaFrete();
		}

    }
	
	function injetarSimulador($tag_simulador) {
		$body = JResponse::getBody();		
		$html_tag_simulador = '<'.$tag_simulador.'/>';
		$pos = strpos($body, $html_tag_simulador);

		if ($pos == true) {
			$conteudo_simulador = $this->conteudoSimulador();
			$new_body = str_replace($html_tag_simulador,$conteudo_simulador,$body);
			JResponse::setBody($new_body);
		}
	}
	
	function conteudoSimulador() {
		$app		= JFactory::getApplication();
		//$template = $app->getTemplate();
		$imagem_plugin = JURI::base() . 'plugins' .DS. 'system' .DS. 'vmsimulador';
		$view = JRequest::getVar('view');
		$virtuemart_product_id = JRequest::getVar('virtuemart_product_id');
		$virtuemart_category_id = JRequest::getVar('virtuemart_category_id');
		$somar_valor_carrinho = $this->params->get('somar_valor_carrinho');

		$js_hide = 'jQuery("#resultAjax input").hide();';
		if ($view == 'cart') {
			$url_simulador = JURI::base().DS. ('index.php?option=com_virtuemart&tmpl=component&calcularSimulador=1&view=cart');
			if ($somar_valor_carrinho) {
				$js_hide = "				
					jQuery('#resultAjax input').click(function(){
						if (jQuery(this).next().find('span.preco').length > 0) {
							valor_frete = jQuery(this).next().find('span.preco').html();
						} else {
							valor_frete = '0.00';
						}
						calculaFreteCarrinho(valor_frete);
					});
				";
			}
		} else {
			$url_simulador = JURI::base().DS. ('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $virtuemart_product_id . '&virtuemart_category_id=' . $virtuemart_category_id . '&tmpl=component&calcularSimulador=1');			
		}

		$usar_quantidade = $this->params->get('quantidade_produto');
		$js_quantidade = '';
		$js_quantidade2 = '';		


		if ($view == 'productdetails') {
			$js_quantidade = ($usar_quantidade?'jQuery(".quantity-input").val();':"1;");
			$js_quantidade2 = " jQuery('.quantity-input').change(function(){ doAjaxCEP() }); ";
		} else {
			$js_quantidade = '1;';
		}

		$url_correios = "http://www.buscacep.correios.com.br/";
		$html = '
		<script src="'.JURI::root(true).'/plugins/system/vmsimulador/jquery.maskinput.js" language="javascript"></script>
		<script language="javascript">				
			function doAjaxCEP() {
					var quantidade = '.$js_quantidade.'

					jQuery("#resultAjax").html("Carregando valores do frete...");
					var zip_code = jQuery("#cep_simulador").val();
					jQuery.ajax({
						type: "POST",
						url: "'.$url_simulador.'",   
						data: "zip_code="+zip_code+"&quantity="+quantidade,
						success: function(html) {
							jQuery("#resultAjax").html(html);

							'.$js_hide.'
						}
					});
				}
		'.
		"jQuery(document).ready(function($) {
				$('a.correios_cep').click( function(){
					$.facebox({
						iframe: '" . $url_correios . "',
						rev: 'iframe|350|750'
					});
					return false ;
				});
				jQuery('#cep_simulador').mask('99999-999');
			});	

		function calculaFreteCarrinho(valor_frete) {
			var valor_total_antes = parseFloat(moeda2float(jQuery('span.PricesalesPrice:last').html()));
			var valor_total = valor_total_antes + parseFloat(moeda2float(valor_frete));
			jQuery('span.PricebillTotal').html(float2moeda(valor_total));
		}

		function float2moeda(num) {
		   x = 0;
		   if(num<0) {
		      num = Math.abs(num);
		      x = 1;
		   }
		   if(isNaN(num)) num = '0';
		      cents = Math.floor((num*100+0.5)%100);
		   num = Math.floor((num*100+0.5)/100).toString();
		   if(cents < 10) cents = '0' + cents;
		      for (var i = 0; i < Math.floor((num.length-(1+i))/3); i++)
		         num = num.substring(0,num.length-(4*i+3))+'.'
		               +num.substring(num.length-(4*i+3));
		   ret = num + ',' + cents;
		   if (x == 1) ret = ' - ' + ret;
		   return 'R$ '+ret;
		}

		function moeda2float(moeda){
		   	moeda = moeda.replace('R$ ','');
		   	moeda = moeda.replace('.','');
   			moeda = moeda.replace(',','.');
   			return parseFloat(moeda);
		}
		</script>
		".
		'<style type="text/css">
		.boxSimuleFrete {
			padding:5px;	
			background:#dddddd;
			padding-top:5px;
			color: #434343;		
			-moz-border-radius-bottomleft:5px;
			-moz-border-radius-topleft:5px;
			-moz-border-radius-bottomright:5px;
			-moz-border-radius-topright:5px;
			
			border-bottom-left-radius:5px;
			border-bottom-right-radius:5px;
			border-top-left-radius:5px;
			border-top-right-radius:5px;	
			width: 100%;	
			min-height: 70px
		}
		.boxSimuleFrete .icon {
			float:left;
			margin-right:5px;
		}
		.boxSimuleFrete #resultAjax {	
			-moz-border-radius-bottomleft:5px;
			-moz-border-radius-topleft:5px;
			-moz-border-radius-bottomright:5px;
			-moz-border-radius-topright:5px;
			
			border-bottom-left-radius:5px;
			border-bottom-right-radius:5px;
			border-top-left-radius:5px;
			border-top-right-radius:5px;
			background:#FFF;
		}
		</style>
		<div class="boxSimuleFrete">
				<input type="hidden" name="product_id_simulador" id="product_id_simulador" value="'.@$this->product->virtuemart_product_id.'">
				<div class="icon">
					<img src="'.$imagem_plugin.'/images/boxes.png">
				</div>
				<div class="inf">
					<strong>Calcule o frete e o prazo de entrega estimados para sua região. </strong>
						<div class="formCEP">
							<div>Informe seu CEP: </div>
							<input type="text" class="inputbox" name="cep" id="cep_simulador" style="width:120px; display:inline">
							<input type="button" class="button btn" onclick="doAjaxCEP()" style="display:inline; float:none; vertical-align: middle" value="OK" /> 
							<br/>
							<a href="'.$url_correios.'" target="_blank" class="correios_cep">Não sei meu CEP</a>
						</div>
				</div>
				<br class="clear">
				<div id="resultAjax">
				
				</div>
		</div>
		<script language="javascript">
		'.$js_quantidade2.'
		</script>
		';
		return $html;

	}
	
	
	function simulaFrete(){
		header('Content-Type: text/html; charset=utf-8');
		$zipcode = JRequest::getVar('zip_code','');
		if ($zipcode != '') {
			$view = JRequest::getVar('view');
			$cart = VirtueMartCart::getCart(false);
			// caso não haja produtos, não retorna
			if (empty($cart->products) and $view=='cart') {
				die('Carrinho vazio: Adicione produtos para simular o frete.');
			}

			//if (!isset($cart->vendorId))
			$cart->vendorId = 1;

			//if (!isset($cart->ST))
			$cart->ST = array("zip"=>str_replace('-','',$zipcode));
			if (!isset($cart->STsameAsBT))
				$cart->STsameAsBT = 1;			
			if (!isset($cart->ST["virtuemart_country_id"]))
				$cart->ST["virtuemart_country_id"] = 30; // seta o brasil principal
			
			$product_id = @JRequest::getVar('virtuemart_product_id', $this->product->virtuemart_product_id);		 // id do produto
			// verifica pela view do carrinho
			if ($view != 'cart') {

				$product_model = VmModel::getModel('product');
				$virtuemart_product_idArray = $product_id;
				if (is_array($virtuemart_product_idArray)) {
					$virtuemart_product_id = $virtuemart_product_idArray[0];
				} else {
					$virtuemart_product_id = $virtuemart_product_idArray;
				}
				$product = $product_model->getProduct($virtuemart_product_id);
				$preco_produto = $product->prices['salesPrice'];
				$cart->products = array();
				$cart->products[$virtuemart_product_id] = $product;

				$quantity = JRequest::getVar('quantity','');
				$cart->products[$virtuemart_product_id]->quantity = $quantity;
				$cart->pricesUnformatted = array();
				$cart->pricesUnformatted["salesPrice"] = $preco_produto;
				$cart->pricesUnformatted['billTotal'] = $preco_produto;				
				$cart->cartPrices = $cart->pricesUnformatted;

			} else {
				$preco_produto = 0;
				foreach($cart->products as $k => $produto) {
					$preco_produto += $cart->cartPrices[$k]['salesPrice'];
				}
			}

			$shipments_shipment_rates = array();
			if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
			JPluginHelper::importPlugin('vmshipment');
			$dispatcher = JDispatcher::getInstance();
			$selectedShipment = false;
			$returnValues = $dispatcher->trigger('plgVmDisplayListFEShipment', array( $cart, $selectedShipment, &$shipments_shipment_rates));

			if (empty($shipments_shipment_rates)) {
				echo 'Método de envio configurado errado.';
			} else {
				foreach($shipments_shipment_rates as $entregas) {
					foreach ($entregas as $metodo_envio) {
						echo '<div>';
						echo $metodo_envio;						
						echo '</div>';
					}
				}
			}

			/*
			$_POST['quantity']=$quantidade_anterior;		
			$cart->updateProductCart($product_id);
			*/
		} else {
			echo 'Erro: Cep inválido';
		}
		die();
	}

} // END PLUGIN  Vmsimulador

?>
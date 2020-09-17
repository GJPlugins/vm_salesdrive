<?php
	/**
	 * @package     Salesdrive
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Salesdrive;
	
	
	use Joomla\CMS\Factory;
	use stdClass;
	use Throwable;
	use Joomla\CMS\Language\Text;
	
	
	class Helper
	{
		
		
		public static function sendDataCrm( $method , $order){
			
			$_salesdrive_values = \Salesdrive\Shoper::getShoperData($method , $order) ;
			# Способ доставки
			$_salesdrive_values['products'] = self::getProductArr($method , $order) ;
			# Способ доставки
			$_salesdrive_values['shipping_method'] = \Salesdrive\Shipping::getShippingMethod($method , $order) ;
			$_salesdrive_values['payment_method'] = \Salesdrive\Payment::getPaymentMethod($method , $order) ;
			
			# Получение информации о способе доставки
			$ShippingData = \Salesdrive\Shipping::getShippingData($method , $order) ;
			
			$result = array_merge ($_salesdrive_values , $ShippingData );
			# секретныый ключ
			$result['form'] = $method->secret_key ;
			
			$doc = Factory::getDocument();
			
			
			
			
			
			$doc->addScriptDeclaration( "
				window.dataLayer = window.dataLayer || []
				dataLayer.push({
				    'transactionId': 'TYTYTYTY222222222',
					'transactionId2': '55555',
				    'transactionAffiliation': '88888',
				    'transactionTotal': '11.99',
				    'transactionTax': '1.29',
				    'transactionShipping': '5',
				    'transactionProducts': [{
				        'sku': 'DD44',
				        'name': 'T-Shirt',
				        'category': 'Apparel',
				        'price': '11.99',
				        'quantity': '1'
				    }]
				});
			" );
			
			
			
			echo'<pre>';print_r(  $result );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );

			
			
			$sendResult = Api::send( $method  , $result);
			
			$dbData =  new stdClass();
			$dbData->virtuemart_order_id = $order['details']['BT']->virtuemart_order_id ;
			$dbData->order_number = $order['details']['BT']->order_number ;
			$dbData->crm_status = 1 ;
			$dbData->data = json_encode( $result  );
			if( !$sendResult )
			{
				$dbData->crm_status = 0 ;
			}#END IF
			
			try
			{
				# Сохранение в таблице плагина Sales Drive
				self::saveResult( $dbData );
			}
			catch( Throwable $e )
			{
				return false ;
			}
			
			return true ;
		}
		
		/**
		 * Сохранение в таблице плагина Sales Drive
		 * @param $dbData
		 *
		 *
		 * @throws Throwable
		 * @since version
		 */
		private static function saveResult( $dbData )
		{
			try
			{
				$result = Factory::getDbo()->insertObject( '#__virtuemart_payment_plg_vm_salesdrive' , $dbData );
			}
			catch( Throwable $e )
			{
				$app = Factory::getApplication() ;
				$app->enqueueMessage( Text::_('VM_SALESDRIVE_ERR_SAVE_RESULT') , 'error');
			}
		}
		
		/**
		 * Получить id - способа оплаты для плагина vm_salesdrive
		 * @param   string  $payment_element
		 *
		 * @return mixed
		 *
		 * @since 3.9
		 */
		public static function getMethodId( $payment_element = 'vm_salesdrive' ){
			
			$db = Factory::getDbo();
			
			$query = $db->getQuery(true);
			$query->select($db->quoteName( 'virtuemart_paymentmethod_id' ))
				->from($db->quoteName('#__virtuemart_paymentmethods'));
			$where = [
				$db->quoteName('payment_element') . '=' .$db->quote('vm_salesdrive')
			] ;
			$query->where( $where );
			
			$db->setQuery($query) ;
			
			return $db->loadResult();
		}
		
		/**
		 * Получить массив товаров из заказа
		 *
		 * @param $method object TablePaymentmethods Object
		 * @param $order  array Order Items
		 *
		 * @return array
		 *
		 * @since version
		 */
		private static function getProductArr ( $method , $order )
		{
			$productArr = [];
			foreach( $order[ 'items' ] as $i => $item )
			{
				# id товара
				$productArr[ $i ][ 'id' ] = $item->virtuemart_product_id;
				# название товара
				$productArr[ $i ][ 'name' ] = $item->order_item_name;
				
				# цена - Без учета скидки или С учетам скидки
				$productArr[ $i ][ 'costPerItem' ] = $item->{$method->costPerItem};
				
				# количество
				$productArr[ $i ][ 'amount' ] = $item->product_quantity;
				# скидка, задается в % или в абсолютной величине
				$productArr[ $i ][ 'discount' ] = abs( $item->product_subtotal_discount  ) ;
				
			}#END FOREACH
			
			return $productArr;
		}
		
		
	}
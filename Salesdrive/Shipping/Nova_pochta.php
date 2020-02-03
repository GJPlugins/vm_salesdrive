<?php
	/**
	 * @package     Salesdrive\Shipping
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Salesdrive\Shipping;
	
	
	class Nova_pochta
	{
		private $method ;
		
		/**
		 * Nova_pochta constructor.
		 *
		 * @param $method
		 * @since 3.9
		 */
		public function __construct ( $method )
		{
			$this->method = $method;
		}
		
		public function getData( $order )
		{
			\JPluginHelper::importPlugin( 'vmshipment' );
			$dispatcher = \JEventDispatcher::getInstance();
			
			$virtuemart_shipmentmethod_id = $order[ 'details' ][ 'BT' ]->virtuemart_order_id;
			
			$db = \JFactory::getDBO();
			$q  = 'SELECT * , DATE_FORMAT(DateTime,"%d.%m.%Y") AS DateTime ' . ' FROM `#__virtuemart_shipment_plg_nova_pochta` ' . 'WHERE `virtuemart_order_id` = ' . $virtuemart_shipmentmethod_id;
			$db->setQuery( $q );
			if( !( $shipinfo = $db->loadObject() ) )
			{
				vmWarn( 500 , $q . " " . $db->getErrorMsg() );
				
				return '';
			}
			
			
			$retArr                  = [];
			$retArr[ 'city' ]        = $shipinfo->CityRecipient;
			$retArr[ 'ServiceType' ] = $shipinfo->ServiceType;
			if( $shipinfo->ServiceType == 'WarehouseDoors' )
			{
				$Address = json_decode( $shipinfo->Address ) ;
				# название и тип улицы, или Ref улицы в системе Новой почты
				$retArr["Street"] = $Address->street ;
				# Номер дома
				$retArr["BuildingNumber"] = $Address->house ;
				# Номер квартиры
				$retArr["Flat"] = $Address->flat ;
			}
			else
			{
				# Todo - Поправить плагин новой почты - что бы данные брать из заказа !!!!
				$app                         = \JFactory::getApplication();
				$nova_pochta                 = $app->input->get( 'nova_pochta' , [] , 'ARRAY' );
				$retArr[ 'WarehouseNumber' ] = $nova_pochta[ 'RecipientAddress' ];
			}#END IF
			
			# TODO - определить по статусу заказа как отправлять обратную доставку !!!!
			$retArr['backwardDeliveryCargoType'] = 'Money' ;
			
			return [ 'novaposhta'=>$retArr ] ;
		}
		
		
	}
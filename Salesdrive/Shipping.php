<?php
	/**
	 * @package     Salesdrive
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Salesdrive;
	
	
	class Shipping
	{
		/**
		 * Получить название способа доставки
		 * @param $method
		 * @param $order
		 *
		 * @return mixed
		 *
		 * @since version
		 */
		public static function getShippingMethod ( $method , $order )
		{
			$Shipment = self::getMethod( $order );
			return $Shipment->shipment_name;
		}
		
		/**
		 * Получить параметры доставки
		 * @param $method
		 * @param $order
		 *
		 * @return array - массив с параметрами доставкки
		 *
		 * @since 3.9
		 */
		public static function getShippingData( $method , $order ){
			
			$Shipment = self::getMethod( $order );
			$namespace = '\\Salesdrive\\Shipping\\'.ucfirst( $Shipment->shipment_element) ;
			$shipment_element = new $namespace( $method ) ;
			return  $shipment_element->getData( $order ) ;
			
		}
		
		/**
		 * @param $order
		 *
		 * @return mixed
		 *
		 * @since  3.9
		 */
		private static function getMethod ( $order )
		{
			$virtuemart_shipmentmethod_id = $order[ 'details' ][ 'BT' ]->virtuemart_shipmentmethod_id;
			
			$modelShipment = \VmModel::getModel( 'shipmentmethod' );
			
			$Shipment = $modelShipment->getShipment( $virtuemart_shipmentmethod_id );
			
			return $Shipment;
		}
		
		
	}
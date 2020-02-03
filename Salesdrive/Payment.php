<?php
	/**
	 * @package     Salesdrive
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Salesdrive;
	
	
	class Payment
	{
		/**
		 * Получить название способа оплаты
		 * @param $method
		 * @param $order
		 *
		 * @return mixed
		 *
		 * @since version
		 */
		public static function getPaymentMethod ( $method , $order )
		{
			$Payment = self::getMethod( $order );
			return $Payment->payment_name;
		}
		
		/**
		 * Получить метод оплаты
		 * @param $order
		 *
		 * @return mixed
		 *
		 * @since  3.9
		 */
		private static function getMethod ( $order )
		{
			$virtuemart_paymentmethod_id = $order[ 'details' ][ 'BT' ]->virtuemart_paymentmethod_id;
			$modelPayment = \VmModel::getModel( 'paymentmethod' );
			return $modelPayment->getPayment( $virtuemart_paymentmethod_id );
		}
		
		
	}
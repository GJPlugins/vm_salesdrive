<?php
	/**
	 * @package     Salesdrive
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Salesdrive;
	
	/**
	 * Класс работы с электронной комерцией
	 * @since       3.9
	 * @package     Salesdrive
	 *
	 */
	class Analitiks
	{
		/**
		 * составление текста Js скрипта для отправи в аналитику
		 *
		 * @param $order - массив с данными о заказе
		 *
		 * @return string - javascript строка
		 *
		 * @since 3.9
		 */
		public static function getJsdataLayerOrder( $order ){
			
			$transaction = new \stdClass() ;
			$transaction->transactionId = $order['details']['BT']->order_number ;
//			$transaction->transactionId2 = '55555' ;
			$transaction->transactionAffiliation = 'Мой СВЕТ' ;
			$transaction->transactionTotal = $order['details']['BT']->order_total ;
			$transaction->transactionTax = 0  ;
			$transaction->transactionShipping = 0  ;
			
			# Загрузка информации о товарах
			$transaction->transactionProducts =  array()  ;
			foreach( $order['items'] as $items ) {
				$Products = new \stdClass() ;
				# артикул
				$Products->sku = $items->order_item_sku ;
				# наименование
				$Products->name = $items->order_item_name ;
				# категория
				$categoryText = null ;
				foreach( $items->categoryItem as $i => $category ){
					if( $i > 0  ) $categoryText .= '/' ;   #END IF
					$categoryText .= $category['category_name'] ;
				}
				$Products->category = $categoryText ;
				$Products->price = $items->product_final_price ;
				$Products->quantity = $items->product_quantity ;
				$transaction->transactionProducts[] = $Products ; 
			}
			$Js = null ;
//			$Js.= ';document.addEventListener("DOMContentLoaded", function () {' ;
			$Js.= 'window.dataLayer=window.dataLayer||[];' ;
			$Js.= 'window.dataLayer.push('.json_encode( $transaction ).');' ;
			$Js.= 'console.log(window.dataLayer)';
//			$Js.= '});';
			
			return $Js ;
			
			
			
			
			
		
			
			/*
			 
	dataLayer.push({
     'transactionId' = 'TYTYTYTY'
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
			*/
			
		}
		
	}
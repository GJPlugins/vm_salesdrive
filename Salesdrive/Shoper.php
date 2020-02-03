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
	 * Обработка данных о пакупателе
	 * @since       version
	 * @package     Salesdrive
	 *
	 */
	class Shoper
	{
		/**
		 * Установить данные о пакупателе
		 * @param $method
		 * @param $order
		 *
		 * @return array
		 *
		 * @since version
		 */
		public static function getShoperData($method , $order){
			
			$BT     = $order[ 'details' ][ 'BT' ];
			$retArr = [ "comment"     => '<MS> '. $BT->customer_note , // Комментарий
			            "fName"       => $BT->first_name , // Имя
			            "lName"       => $BT->last_name , // Фамилия
			            "mName"       => $BT->middle_name , // Отчество
			            "phone"       => $BT->phone_2 , // Телефон
			            "email"       => $BT->email , // Email
						# TODO - не понятное поле "con_comment"
			            "con_comment" => "" ,
				];
			return $retArr ;
		}
		
		
	}
<?php
	/**
	 * @package     Salesdrive
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Salesdrive;
	
	
	class Api
	{
		/**
		 * Передача запроса к API CRM
		 *
		 * @param $method
		 * @param $result
		 *
		 *
		 * @return bool
		 * @since 3.9
		 */
		public static function send ( $method  , $_salesdrive_values ){
			
			$_salesdrive_url = $method->salesdrive_url ;
			$_salesdrive_ch = curl_init();
			curl_setopt($_salesdrive_ch, CURLOPT_URL, $_salesdrive_url);
			curl_setopt($_salesdrive_ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($_salesdrive_ch, CURLOPT_HTTPHEADER,
				array('Content-Type:application/json'));
			curl_setopt($_salesdrive_ch, CURLOPT_SAFE_UPLOAD, true);
			curl_setopt($_salesdrive_ch, CURLOPT_CONNECTTIMEOUT, 30);
			curl_setopt($_salesdrive_ch, CURLOPT_POST, 1);
			curl_setopt($_salesdrive_ch, CURLOPT_POSTFIELDS,
				json_encode($_salesdrive_values));
			curl_setopt($_salesdrive_ch, CURLOPT_TIMEOUT, 10);
			
			$_salesdrive_res = curl_exec($_salesdrive_ch);
			$_salesdriveerrno = curl_errno($_salesdrive_ch);
			$_salesdrive_error = 0;
			
			if ($_salesdriveerrno or $_salesdrive_res != "") {
				$_salesdrive_error = 1;
			}
			if ($_salesdrive_error) {
//				echo "<p>Ошибка при отправке заявки! Заявка не отправлена.</p>";
				return false ;
			}
			else{
//				echo "<p>Ваша заявка успешно отправлена.</p>";
				return true ;
			}
		}
	}
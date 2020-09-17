<?php


error_reporting(E_ALL);


/**
 * @package           TINKOFF Payment Module for VirtueMart 3
 * @author            Ricardo Jacobs <ricardo.jacobs@tinkoff.com>
 * @copyright     (c) 2015 TINKOFF. All rights reserved.
 * @version           1.0.1, July 2015
 * @license           BSD-2-Clause, see LICENSE.md
 */

defined('_JEXEC') or die('Direct access to ' . basename(__FILE__) . ' is not allowed.');

if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

/*require_once( dirname( __FILE__ ) . '/tinkoff/api/tinkoff_api_basic.php' );

require_once( dirname( __FILE__ ) . '/tinkoff/TinkoffMerchantAPI.php' );
require_once( dirname( __FILE__ ) . '/tinkoff/Debug.php' );*/

class plgVmPaymentVm_salesdrive extends vmPSPlugin
{


    /**
     * Affects constructor behavior. If true, language files will be loaded automatically.
     *
     * @var    boolean
     * @since  3.1
     */
    protected $autoloadLanguage = true;


    function __construct(&$subject, $config)
    {
        parent::__construct($subject, $config);

        $this->_loggable = true;
        $this->_tablepkey = 'id';
        $this->_tableId = 'id';

        $varsToPush = $this->getVarsToPush();
        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);


    }


    public function getVmPluginCreateTableSQL()
    {
        return $this->createTableSQL('Payment CRM Sales Drive Table');
    }


    /**
     * Создает столбцы для таблицы при создании способа доставки
     * @return array
     *
     * @since 3.9
     */
    function getTableSQLFields()
    {
        $SQLfields = [
            'id' => 'tinyint(1) unsigned NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id' => 'int(11) UNSIGNED DEFAULT NULL',
            'order_number' => 'char(32) DEFAULT NULL',
            'crm_status' => 'int(11) UNSIGNED DEFAULT NULL',
            'data' => 'text'
        ];
        return $SQLfields;
    }

    /**
     * Срабатывает после того как заказ подтвержден.
     * @param $cart  Object VirtueMartCart
     * @param $order array Order Itams
     *
     * @return bool|void|null
     *
     * @throws Exception
     * @since version
     */
    function plgVmConfirmedOrder($cart, $order)
    {

        JLoader::registerNamespace('Salesdrive', JPATH_PLUGINS . '/vmpayment/vm_salesdrive/Salesdrive', $reset = false, $prepend = false, $type = 'psr4');

        # Плучить ID способа облаты Sales drive
        $virtuemart_paymentmethod_id = \Salesdrive\Helper::getMethodId();
        # Получить объект способа оплаты
        $method = $this->getVmPluginMethod($virtuemart_paymentmethod_id);

        \Salesdrive\Helper::sendDataCrm($method, $order);

        /*$html = $this->renderByLayout('post_payment', array(
            'order_number' =>$order['details']['BT']->order_number,
            'order_pass' =>$order['details']['BT']->order_pass,
//				'payment_name' => $dbValues['payment_name'],
//				'displayTotalInPaymentCurrency' => $totalInPaymentCurrency['display'],

            'virtuemart_paymentmethod_id' => $order['details']['BT']->virtuemart_paymentmethod_id

        ));*/

        # составление текста Js скрипта для отправи в аналитику
        $Js = \Salesdrive\Analitiks::getJsdataLayerOrder($order);
        # Сохраняем в переменную сессии
        $session = JFactory::getSession();
        $session->set('orderSessionVar', json_encode($Js));


        return null;


//


        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id)))
        {
            return null;
        }

        if (!$this->selectedThisElement($method->payment_element))
        {
            return false;
        }

        $app = \JFactory::getApplication();


        return $this->processConfirmedOrderPaymentResponse(2, $cart, $order, $html, $dbValues['payment_name'], $method->status_pending);
    }

    function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId)
    {
        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id)))
        {
            return null;
        }

        if (!$this->selectedThisElement($method->payment_element))
        {
            return false;
        }

        $this->getPaymentCurrency($method);
        $paymentCurrencyId = $method->payment_currency;
    }

    function plgVmOnPaymentResponseReceived(&$html)
    {
        if (!class_exists('VirtueMartModelOrders')) require(JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php');

        $jinput = JFactory::getApplication()->input;
        $virtuemart_paymentmethod_id = $jinput->get('pm');

        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id)))
        {
            return null;
        }

        $tinkoff = $_SERVER['REQUEST_METHOD'] == 'POST' ? $this->_tinkoff->postback() : $this->_tinkoff->result();
        $tinkoff->setMerchantID($method->merchantid)->setSecretCode($method->secretcode);

        if ($_SERVER['REQUEST_METHOD'] != 'POST')
        {
            if ($tinkoff->validate())
            {
                $modelOrder = VmModel::getModel('orders');

                switch ($tinkoff->getStatus())
                {
                    case Tinkoff_StatusCode::OPEN:
                        $order['order_status'] = $method->status_pending;
                        break;
                    case Tinkoff_StatusCode::SUCCESS:
                        $order['order_status'] = $method->status_success;
                        $this->emptyCart();
                        break;
                    case Tinkoff_StatusCode::ERROR:
                        $order['order_status'] = $method->status_canceled;

                        return false;
                        break;
                }

                $modelOrder->updateStatusForOneOrder($tinkoff->getOrderID(), $order, true);
            }
        } else
        {
            if ($tinkoff->validate())
            {
                $modelOrder = VmModel::getModel('orders');

                switch ($tinkoff->getStatus())
                {
                    case Tinkoff_StatusCode::OPEN:
                        $order['order_status'] = $method->status_pending;
                        $order['comments'] = $tinkoff->getTransactionString();
                        break;
                    case Tinkoff_StatusCode::SUCCESS:
                        $order['order_status'] = $method->status_success;
                        $order['customer_notified'] = 1;
                        $order['comments'] = $tinkoff->getTransactionString();
                        break;
                    case Tinkoff_StatusCode::ERROR:
                        $order['order_status'] = $method->status_canceled;
                        $order['comments'] = $tinkoff->getTransactionString();
                        break;
                    case Tinkoff_StatusCode::REFUND:
                        $order['order_status'] = $method->status_chargeback;
                        $order['customer_notified'] = 1;
                        $order['comments'] = $tinkoff->getTransactionString();
                        break;
                    case Tinkoff_StatusCode::CHARGEBACK:
                        $order['order_status'] = $method->status_chargeback;
                        $order['customer_notified'] = 1;
                        $order['comments'] = $tinkoff->getTransactionString();
                        break;
                }
            }

            $modelOrder->updateStatusForOneOrder($tinkoff->getOrderID(), $order, true);
            exit();
        }
    }

    function plgVmOnUserPaymentCancel()
    {
        return true;
    }

    /**
     * Required functions by Joomla or VirtueMart. Removed code comments due to 'file length'.
     * All copyrights are (c) respective year of author or copyright holder, and/or the author.
     */
    function getCosts(VirtueMartCart $cart, $method, $cart_prices)
    {

        if (preg_match('/%$/', $method->cost_percent_total))
        {

            $cost_percent_total = substr($method->cost_percent_total, 0, -1);

        } else
        {

            $cost_percent_total = $method->cost_percent_total;
        }

        $res = $method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01);


        return $res;
    }


    protected function getPluginHtml($plugin, $selectedPlugin, $pluginSalesPrice)
    {
        $doc = JFactory::getDocument();
        $doc->addStyleDeclaration('
        form#bypv_cart div.method_block #bypv_cart_payment_15 span.vmpayment_name {
                width: 100%;
        }
        form#bypv_cart div.method_block #bypv_cart_payment_15 .vmpayment_cost.discount{
            font-weight: 600;
            color: #558b25;
        }
        ');


        $pluginmethod_id = $this->_idName;
        $pluginName = $this->_psType . '_name';
        if ($selectedPlugin == $plugin->$pluginmethod_id)
        {
            $checked = 'checked="checked"';
        } else
        {
            $checked = '';
        }

        if (!class_exists('CurrencyDisplay'))
        {
            require(VMPATH_ADMIN . DS . 'helpers' . DS . 'currencydisplay.php');
        }
        $currency = CurrencyDisplay::getInstance();
        $costDisplay = "";
        if ($pluginSalesPrice)
        {
            $costDisplay = $currency->priceDisplay($pluginSalesPrice);

            $t = vmText::_('COM_VIRTUEMART_PLUGIN_COST_DISPLAY');


            if (strpos($t, '/') !== false)
            {
                list($discount, $fee) = explode('/', vmText::_('COM_VIRTUEMART_PLUGIN_COST_DISPLAY'));


                if ($pluginSalesPrice >= 0)
                {
                    $costDisplay = '<span class="' . $this->_type . '_cost fee"> (' . $fee . ' +' . $costDisplay . ")</span>";
                } else if ($pluginSalesPrice < 0)
                {
                    $costDisplay = '<span class="' . $this->_type . '_cost discount"> ' . $discount . ': ' . $costDisplay . "</span>";
                }
            } else
            {
                $costDisplay = '<span class="' . $this->_type . '_cost fee"> (' . $t . ' +' . $costDisplay . ")</span>";
            }

        }
        $dynUpdate = '';
        if (VmConfig::get('oncheckout_ajax', false))
        {
            //$url = JRoute::_('index.php?option=com_virtuemart&view=cart&task=updatecart&'. $this->_idName. '='.$plugin->$pluginmethod_id );
            $dynUpdate = ' data-dynamic-update="1" ';
        }
        $html = '<input type="radio"' . $dynUpdate . ' name="' . $pluginmethod_id . '" id="' . $this->_psType . '_id_' . $plugin->$pluginmethod_id . '"   value="' . $plugin->$pluginmethod_id . '" ' . $checked . ">\n";

        $html .= '<label for="' . $this->_psType . '_id_' . $plugin->$pluginmethod_id . '">';

        $html .= '<span class="' . $this->_type . '">';

        $html .= '<span class="vmCartPaymentLogo"><img align="middle" src="https://protect-sc.ru/images/virtuemart/payment/card.png" alt="card"></span>';

        $html .= '<span class="plg_name_val">';
        $html .= $plugin->$pluginName . $costDisplay;
        $html .= '</span>';

        $html .= "</span>";

        $html .= "</label>\n";


        return $html;
    }


    protected function checkConditions($cart, $method, $cart_prices)
    {

    }

    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id)
    {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart)
    {
        return $this->OnSelectCheck($cart);
    }

    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn)
    {
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name)
    {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = [], &$paymentCounter)
    {
        return $this->onCheckAutomaticSelected($cart, $cart_prices, $paymentCounter);
    }

    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name)
    {
        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    function plgVmonShowOrderPrintPayment($order_number, $method_id)
    {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPaymentVM3(&$data)
    {

        return $this->declarePluginParams('payment', $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table)
    {
        return $this->setOnTablePluginParams($name, $id, $table);
    }#END FN


    /**
     *  Прием ответа от банка...
     *
     *
     */
    function plgVmOnPaymentNotification()
    {

    }#END FN

    function updateOrderStatus($order_status)
    {

    }#END FN

    ####################################################################################################

    private function _getLangISO()
    {
        $language = JFactory::getLanguage();
        $tag = strtolower(substr($language->get('tag'), 0, 2));

        return $tag;
    }

}


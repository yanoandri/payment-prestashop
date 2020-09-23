<?php
/*
* 2007-2015 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2015 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

/**
 * @since 1.5.0
 */
class PaymentExampleValidationModuleFrontController extends ModuleFrontController
{
    /**
     * @see FrontController::postProcess()
     */
    public function postProcess()
    {
        $cart = $this->context->cart;
        $customer = $this->context->customer;
        if ($cart->id_customer == 0 || $cart->id_address_delivery == 0 || $cart->id_address_invoice == 0 || !$this->module->active) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // Check that this payment option is still available in case the customer changed his address just before the end of the checkout process
        $authorized = false;
        foreach (Module::getPaymentModules() as $module) {
            if ($module['name'] == 'paymentexample') {
                $authorized = true;
                break;
            }
        }

        if (!$authorized) {
            die($this->module->l('This payment method is not available.', 'validation'));
        }

        $this->context->smarty->assign([
            'params' => $_REQUEST,
        ]);

        $this->setTemplate('module:paymentexample/views/templates/front/payment_return.tpl');

        $total = (float) $cart->getOrderTotal(true, Cart::BOTH);

        $email = $customer->email;
        $duration = intval(Configuration::get('XENDIT_INVOICE_EXPIRE'));

        $timestamp = date("YmdHis");
        $params = [
            'external_id' => 'demo_'.$timestamp,
            'payer_email' => $email,
            'description' => 'Checkout Odading Mang Oleh',
            'amount' => $total,
            'invoice_duration' => $duration,
            'should_send_email' => 'true'
        ];
        $createInvoice = $this->getXenditInvoice($params);
        
        Tools::redirect($createInvoice->invoice_url);
    }

    protected function getXenditInvoice($data){
        $url = 'https://api.xendit.co/v2/invoices';
        $ch 	= curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_SSLVERSION, 6);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

        $headers = array();
        $headers[] =  "Content-Type: application/x-www-form-urlencoded";
        $headers[] =  "Authorization: Basic ".$this->getXenditAuth();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $result = curl_exec($ch);
        if($result){
            return json_decode($result);
        }else{
            return null;
        }
    }

    protected function getXenditAuth(){
        $secret = Configuration::get('XENDIT_SECRET');
        return base64_encode($secret.':');
    }
}

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

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

if (!defined('_PS_VERSION_')) {
    exit;
}

class PaymentExample extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = [];

    public $details;
    public $owner;
    public $address;
    public $extra_mail_vars;

    public function __construct()
    {
        $this->name = 'paymentexample';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];
        $this->author = 'XenditYano';
        $this->controllers = ['validation'];
        $this->is_eu_compatible = 1;

        $this->currencies = true;
        $this->currencies_mode = 'checkbox';

        $this->bootstrap = true;
        parent::__construct();

        $this->displayName = $this->l('Xendit Payment Example');
        $this->description = $this->l('Xendit Payment Example in Prestashop');

        if (!count(Currency::checkPaymentCurrencies($this->id))) {
            $this->warning = $this->l('No currency has been set for this module.');
        }
    }

    public function install()
    {
        if (!parent::install() || !$this->registerHook('paymentOptions') || !$this->registerHook('paymentReturn')) {
            return false;
        }

        return true;
    }

    /**
     * Uninstall this module and remove it from all hooks.
     *
     * @return bool
     */
    public function uninstall()
    {
        return parent::uninstall();
    }

    /**
     * Returns a string containing the HTML necessary to
     * generate a configuration screen on the admin.
     *
     * @return string
     */
    public function getContent()
    {
        $output = null;
 
        if (Tools::isSubmit('submit'.$this->name))
        {
            $xenditSecret = strval(Tools::getValue('XENDIT_SECRET'));
            $xenditExpiry = strval(Tools::getValue('XENDIT_INVOICE_EXPIRE'));
            if ((!$xenditSecret || !$xenditExpiry)
            || (empty($xenditSecret) || empty($xenditExpiry))
            || !Validate::isGenericName($xenditSecret)
            || !is_numeric($xenditExpiry))
                $output .= $this->displayError($this->l('Invalid Configuration value'));
            else
            {
                Configuration::updateValue('XENDIT_SECRET', $xenditSecret);
                Configuration::updateValue('XENDIT_INVOICE_EXPIRE', $xenditExpiry);
                $output .= $this->displayConfirmation($this->l('Settings updated'));
            }
        }
        return $output.$this->displayForm();
    }

    public function displayForm()
    {
        // Get default language
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        
        // Init Fields form array
        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Xendit API Key'),
                    'name' => 'XENDIT_SECRET',
                    'size' => 5000,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Xendit Invoice Expiry Time'),
                    'name' => 'XENDIT_INVOICE_EXPIRE',
                    'size' => 100,
                    'required' => true
                )
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            )
        );
        
        $helper = new HelperForm();
        
        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;
        
        // Language
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        
        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );
        
        // Load current value
        $helper->fields_value['XENDIT_SECRET'] = Configuration::get('XENDIT_SECRET');
        $helper->fields_value['XENDIT_INVOICE_EXPIRE'] = Configuration::get('XENDIT_INVOICE_EXPIRE');
        
        return $helper->generateForm($fields_form);
    }

    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        if (!$this->checkCurrency($params['cart'])) {
            return;
        }

        $payment_options = [
            $this->getNewPaymentOption(),
        ];

        return $payment_options;
    }

    public function hookPaymentReturn($params)
    {
        /*
         * Verify if this module is enabled
         */
        if (!$this->active) {
            return;
        }

        return $this->fetch('module:paymentexample/views/templates/front/payment_return.tpl');
    }

    public function checkCurrency($cart)
    {
        $currency_order = new Currency($cart->id_currency);
        $currencies_module = $this->getCurrency($cart->id_currency);

        if (is_array($currencies_module)) {
            foreach ($currencies_module as $currency_module) {
                if ($currency_order->id == $currency_module['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getNewPaymentOption()
    {
        $formAction = $this->context->link->getModuleLink($this->name, 'validation', [], true);
        /*
         * Assign the url form action to the template var $action
         */
        $this->smarty->assign(['action' => $formAction]);

        $paymentForm = $this->fetch('module:paymentexample/views/templates/front/payment_options.tpl');
        $newOption = new PaymentOption();
        $newOption->setModuleName($this->l('Test new option'))
            ->setCallToActionText($this->l('Xendit Payment'))
            ->setAction($formAction)
            ->setForm($paymentForm);

        return $newOption;
    }
}

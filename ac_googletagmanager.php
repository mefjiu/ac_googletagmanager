<?php 
if (!defined('_PS_VERSION_')) {
	exit;
}

class ac_googletagmanager extends Module
{
	public function __construct() 
	{
		$this->name = 'ac_googletagmanager';
		$this->tab = 'analytics_stats';
		$this->version = '1.0.0';
		$this->author = 'Mateusz Borowik';
		$this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);

		$this->need_instance = 0;
		$this->bootstrap = true;

        parent::__construct();
        $this->displayName = $this->getTranslator()->trans('Google Tag Manager & dataLayer');
        $this->description = $this->getTranslator()->trans('Install the Google Tag Manager and  dataLayer for E-commerce');

        $this->defaults = array(
            'GTM_ENABLED' , 0,
            'GTM_CONTAINER_ID' , '',
            'GTM_DATALAYER_ENABLED' , 0
        );
	}

    public function install()
    {
        return (
            parent::install()
            // Add Header code GTM
            && $this->registerHook('displayHeader')

            // Add Body code GTM
            && $this->registerHook('displayAfterBodyOpeningTag')

            // Add data Layer in order Confirmation
            && $this->registerHook('displayOrderConfirmation')

            // Set Default Configuration
            && $this->setDefaults()
        );
    }

    public function uninstall()
    {
        Configuration::deleteByName('GTM_ENABLED');
        Configuration::deleteByName('GTM_CONTAINER_ID');
        Configuration::deleteByName('GTM_DATALAYER_ENABLED');

        return parent::uninstall();
    }

    public function setDefaults()
	{
		foreach ($this->defaults as $default => $value) {
			Configuration::updateValue('GTM_' . $default , $value);
        }
        
		return true;
	}

    public function getContent()
    {
        $output = '';
        
        if (Tools::isSubmit('submit' . $this->name)) {  
            
            if ( $this->_postValidation() ) {
                if ( $this->postProcess() ) {
                    $output .= $this->displayConfirmation($this->l('Settings saved'));
                }
            } else {
                $output .= $this->displayWarning($this->l('Something went wrong! Check form values.'));    
            }        
        }

        $output .= $this->displayForm();
        return $output;
    }

    public function displayForm()
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;

        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->submit_action = 'submit' . $this->name;

        $fields_forms = array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('General settings'),
                    'icon' => 'icon-cogs'
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Enable Google Tag Manager?'),
                        'name' => 'GTM_ENABLED',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->l('Set "Yes" to add Google Tag Manager to your e-commerce.'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('On')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Off')
                            )
                        ),
                    ),
                    array(
                        'type' => 'text',
                        'label' => $this->l('Tag Manager ID'),
                        'name' => 'GTM_CONTAINER_ID',
                        'size' => 20,
                        'required' => true,
                        'hint' => $this->l('Enter here your GTM ID (GTM-XXXXXX).')
                    ),
                    array(
                        'type' => 'switch',
                        'label' => $this->getTranslator()->trans('Enable DataLayer?'),
                        'name' => 'GTM_DATALAYER_ENABLED',
                        'required' => true,
                        'is_bool' => true,
                        'desc' => $this->getTranslator()->trans('Set "Yes" to add Order Confirmation dataLayer'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->getTranslator()->trans('On')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->getTranslator()->trans('Off')
                            )
                        ),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save')
                )
            )
        );

        // Load current value
        $helper->fields_value['GTM_ENABLED'] = Configuration::get('GTM_ENABLED');
        $helper->fields_value['GTM_CONTAINER_ID'] = Configuration::get('GTM_CONTAINER_ID');
        $helper->fields_value['GTM_DATALAYER_ENABLED'] = Configuration::get('GTM_DATALAYER_ENABLED');

        return $helper->generateForm(array($fields_forms));
    }

    protected function postProcess()
    {  
        if (
            Configuration::updateValue('GTM_ENABLED', (bool)Tools::getValue('GTM_ENABLED'))
            && Configuration::updateValue('GTM_CONTAINER_ID', Tools::getValue('GTM_CONTAINER_ID'))
            && Configuration::updateValue('GTM_DATALAYER_ENABLED', (bool)Tools::getValue('GTM_DATALAYER_ENABLED'))
        ) {
            return true;
        } else {
            return false;
        }
    }

    public function hookDisplayHeader()
    {
        if ((bool)Configuration::get('GTM_ENABLED')) {
            $conf = [
                'gtm_container_id' => Configuration::get('GTM_CONTAINER_ID')
            ];
    
            $this->context->smarty->assign($conf);
            
            return $this->display(__FILE__, '/views/templates/hook/head.tpl');
        }
    }
    
    public function hookDisplayAfterBodyOpeningTag()
    {
        if ((bool)Configuration::get('GTM_ENABLED')) {
            $conf = [
                'gtm_container_id' => Configuration::get('GTM_CONTAINER_ID')
            ];

            $this->context->smarty->assign($conf);

            return $this->display(__FILE__, '/views/templates/hook/body.tpl');
        }
    }

    public function hookDisplayOrderConfirmation($order)
    {
        //cancelled, payment error, refunded
        $ids_payment_error = array(6, 8, 7);

        if (
            (bool)Configuration::get('GTM_ENABLED') 
            && (bool)Configuration::get('GTM_ENABLED')
            && isset($order['order'])
        ) {
            $_order = $order['order'];

            if (in_array($_order->current_state, $ids_payment_error)) {
                // Validate all orders except payment error status
                return false;
            }
            $order_cart = new Cart($_order->id_cart);
            $products_cart = $order_cart->getProducts(true);

            $conf = [
                'order'  => $_order,
                'products_cart'  => $products_cart,
            ];

            $this->context->smarty->assign($conf);

            return $this->display(__FILE__, '/views/templates/hook/datalayer.tpl');
        }
    }

    private function _postValidation()
    {
        if (!preg_match('/^GTM-[0-9A-Z]{6,8}$/i', Tools::getValue('GTM_CONTAINER_ID'))) {
                return false;
        } else {
            return true;
        }
    }
}

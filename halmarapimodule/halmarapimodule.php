<?php

if(!defined('_PS_VERSION_')) {
    exit;
}

require_once 'api/HalmarAPI.php';

class HalmarApiModule extends Module {

    public function __construct() {
        $this->name = "halmarapimodule";
        $this->tab = "front_office_features";
        $this->version = "1.0.0";
        $this->author = "Mateusz Czosnyka";
        $this->need_instance = 0;
        $this->ps_version_compliancy = [
            "min" => "1.7",
            "max" => _PS_VERSION_
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l("Test halmar api.");
        $this->description = $this->l("Testing halmar api.");
        $this->confirmUninstall = $this->l("Are you sure?");
        $this->adminTemplateFile = 'views/templates/admin/configuration.tpl';
    }

    public function install(): Bool {
        return parent::install();
    }

    public function uninstall(): Bool {
        return parent::uninstall();
    }

    public function getContent() {
        $message = null;
	
        if (Tools::isSubmit('testLogin') && 
            Tools::getValue('login') && 
            Tools::getValue('password'))
        {
            $message = $this->testLogin(Tools::getValue('login'), Tools::getValue('password'));
        }

        if (Tools::isSubmit('testPostOrder') && 
            Tools::getValue('login') && 
            Tools::getValue('password') &&
            Tools::getValue('data'))
        {
            $message = $this->testPostOrder(Tools::getValue('login'), Tools::getValue('password'), Tools::getValue('data'));
        }
      
      	if (Tools::isSubmit('testGetOrders') && 
            Tools::getValue('login') && 
            Tools::getValue('password'))
        {
            $message = $this->testGetOrders(Tools::getValue('login'), Tools::getValue('password'));
        }

        $this->context->smarty->assign([
            'message' => $message,
            'login' => Tools::getValue('login'),
            'password' => Tools::getValue('password')
        ]);
        return $this->display(__FILE__, $this->adminTemplateFile);
    }

    private function testLogin($login, $password) {
        try {
            $h = new HalmarAPI();
            $h->login($login, $password);
          	return 'Success.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function testPostOrder($login, $password, $data) {
        try {
            $h = new HalmarAPI();
            $h->login($login, $password);
            $h->postOrder($data);
          	return 'Success.';
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
  
  	private function testGetOrders($login, $password) {
    	try {
            $h = new HalmarAPI();
            $h->login($login, $password);
            return $h->getOrders();
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
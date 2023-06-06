<?php

if(!defined('_PS_VERSION_')) {
    exit;
}

class MyBasicModule extends Module {

    public function __construct() {
        $this->name = "mybasicmodule";
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

        $this->displayName = $this->l("My first module");
        $this->description = $this->l("This is a teasting module");
        $this->confirmUninstall = $this->l("Are you sure?");
    }

    public function install(): Bool {
        return parent::install() && $this->registerHook("registerGDPRConsent");
    }

    public function uninstall(): Bool {
        return parent::uninstall();
    }

    public function hookdisplayFooter($params) {
        $this->context->smarty->assign([
            "botPressId" => Configuration::get('BOT_PRESS_ID')
        ]);
        return $this->display(__FILE__, 'views/templates/hook/footer.tpl');
    }
  
    public function getContent() {
        $message = null;
      
        if(Tools::getValue("botPressId")) {
            Configuration::updateValue('BOT_PRESS_ID', Tools::getValue("botPressId"));
            $message = "Form saved correctly.";
        }
      
        $botPressId = Configuration::get('BOT_PRESS_ID');
        $this->context->smarty->assign([
          'botPressId' => $botPressId,
          'message' => $message
        ]);
        return $this->display(__FILE__, 'views/templates/admin/configuration.tpl');
    }
}
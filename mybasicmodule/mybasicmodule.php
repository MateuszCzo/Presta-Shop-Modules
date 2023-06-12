<?php

if(!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

class MyBasicModule extends Module implements WidgetInterface {

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

        $this->displayName = $this->l("My first module.");
        $this->description = $this->l("Adds bot to your website.");
        $this->confirmUninstall = $this->l("Are you sure?");
        $this->adminTemplateFile = 'views/templates/admin/configuration.tpl';
        $this->frontTemplateFile = 'module:mybasicmodule/views/templates/hook/footer.tpl';

    }

    public function install(): Bool {
        return parent::install() && $this->registerHook('displayFooter');
    }

    public function uninstall(): Bool {
        return parent::uninstall();
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
        return $this->display(__FILE__, $this->adminTemplateFile);
    }

    public function renderWidget($hookName, array $configuration) {
        $this->context->smarty->assign($this->getWidgetVariables($hookName, $configuration));
        return $this->fetch($this->frontTemplateFile);
    }

    public function getWidgetVariables($hookName, array $configuration) {
        return [
            'botPressId' => Configuration::get('BOT_PRESS_ID')
        ];
    }
}
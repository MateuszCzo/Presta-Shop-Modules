<?php

if(!defined('_TB_VERSION_')) {
    exit;
}


class StatusAndMail extends Module {

    public function __construct() {
        $this->name = "statusandmail";
        $this->tab = "front_office_features";
        $this->version = "1.0.0";
        $this->author = "Mateusz Czosnyka";
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l("Status and Mail.");
        $this->description = $this->l("Check status and send mail.");
        $this->adminTemplateFile = 'views/templates/admin/configuration.tpl';
        $this->tableName = 'order_status2';
    }

    public function install(): Bool {
        return parent::install() && 
            $this->createTable();
    }

    public function createTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "$this->tableName` (
                `order_id` INT(10) NOT NULL,
                `status` VARCHAR(255) NOT NULL
            );
        ";
        return Db::getInstance()->execute($sql);
    }

    public function uninstall(): Bool {
        return parent::uninstall() &&
            $this->dropTable();
    }

    public function dropTable() {
        $sql = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "$this->tableName`";
        return Db::getInstance()->execute($sql);
    }
  
    public function getContent() {
        $message = null;
      
        if (Tools::isSubmit("addOrder") && 
            Tools::getValue("orderId") && 
            Tools::getValue("orderStatus"))
        {
            $message = $this->addOrder();
        }

        if (Tools::isSubmit("getStatus") && 
            Tools::getValue("orderId"))
        {
            $message = $this->getStatus();
        }

        if (Tools::isSubmit("sendMail") && 
            Tools::getValue("orderId"))
        {
            $message = $this->sendMail();
        }
      
        $this->context->smarty->assign([
            'message' => $message,
            'orderId' => Tools::getValue("orderId"),
            'orderStatus' => Tools::getValue("orderStatus")
        ]);
        return $this->display(__FILE__, $this->adminTemplateFile);
    }

    public function addOrder() {
        $data = array(
            'order_id' => (int)Tools::getValue("orderId"),
            'status' => Tools::getValue("orderStatus")
        );
      	$result = Db::getInstance()->insert(_DB_PREFIX_ . $this->tableName, $data);
      	if ($result === false) {
         	return "No data added to table.";
        }
        return $result;
    }

    public function getStatus() {
        $sql = "SELECT status FROM `" . _DB_PREFIX_ . "$this->tableName` WHERE order_id = " . (int)Tools::getValue("orderId");
      	$result = Db::getInstance()->getValue($sql);
      	if ($result === false) {
          	return "No status found for the order.";
        }
        return $result;
    }

    public function sendMail() {
        //todo send mail
    }
}
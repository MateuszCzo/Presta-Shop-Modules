<?php

if(!defined('_TB_VERSION_')) {
    exit;
}

include_once(dirname(__FILE__) . '/StatusModel.php');
include_once(dirname(__FILE__) . '/SendMailModel.php');
include_once(dirname(__FILE__) . '/StatusNamesModel.php');

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
    }

    public function install(): Bool {
        return parent::install() && 
        StatusModel::createTable();
    }

    public function uninstall(): Bool {
        return parent::uninstall() &&
        StatusModel::dropTable();
    }
  
    public function getContent() {
        $message = null;
      
        try{
            if (Tools::isSubmit("addOrder") && 
                Tools::getValue("orderId") && 
                Tools::getValue("orderStatus"))
            {
                $message = StatusModel::addOrder(Tools::getValue("orderId"), Tools::getValue("orderStatus"));
            }

            if (Tools::isSubmit("getStatus") && 
                Tools::getValue("orderId"))
            {
                $message = StatusModel::getStatus(Tools::getValue("orderId"));
            }

            if (Tools::isSubmit("changeStatus") && 
                Tools::getValue("orderId") && 
                Tools::getValue("orderStatus"))
            {
                $message = $this->changeStatus(Tools::getValue("orderId"), Tools::getValue("orderStatus"));
            }

            if (Tools::isSubmit("howManyOrders"))
            {
                $message = StatusModel::getCount();
            }

            if (Tools::isSubmit("deleteOrder") && 
                Tools::getValue("orderId"))
            {
                $message = StatusModel::deleteOrder(Tools::getValue("orderId"));
            }
        } catch (Exception $e) {
            $message = $e->getMessage();
        }
      
        $this->context->smarty->assign([
            'message' => $message,
            'orderId' => Tools::getValue("orderId"),
            'orderStatus' => Tools::getValue("orderStatus")
        ]);
        return $this->display(__FILE__, $this->adminTemplateFile);
    }

    public function changeStatus($orderId, $status) {
        if(!in_array($status, StatusNamesModel::STATUS_NAMES)) {
            throw new Exception("Wrong status name.");
        }
        if (!StatusModel::checkIfExists($orderId)) {
            throw new Exception('No order with given id');
        }
        if(StatusModel::getStatus($orderId) !== $status) {
            StatusModel::changeStatus($orderId, $status);
            return SendMailModel::sendMail($orderId, $status);
        }
        return 'status the same';
    }
}
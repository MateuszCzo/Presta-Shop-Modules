<?php

if(!defined('_TB_VERSION_')) {
    exit;
}

class StatusAndMail extends Module {

    const STATUS_NAMES = array(
        'Pending',
        'Plukket',
        'Sendt',
    );
    const TABLE_NAME = _DB_PREFIX_ . 'order_status2';

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
            $this->createTable();
    }

    public function uninstall(): Bool {
        return parent::uninstall() &&
            $this->dropTable();
    }
  
    public function getContent() {
        $message = null;
      
        try{
            if (Tools::isSubmit("addOrder") && 
                Tools::getValue("orderId") && 
                Tools::getValue("orderStatus"))
            {
                $message = $this->addOrder(Tools::getValue("orderId"), Tools::getValue("orderStatus"));
            }

            if (Tools::isSubmit("getStatus") && 
                Tools::getValue("orderId"))
            {
                $message = $this->getStatus(Tools::getValue("orderId"));
            }

            if (Tools::isSubmit("changeStatus") && 
                Tools::getValue("orderId") && 
                Tools::getValue("orderStatus"))
            {
                $message = $this->changeOrderStatus(Tools::getValue("orderId"), Tools::getValue("orderStatus"));
            }

            if (Tools::isSubmit("howManyOrders"))
            {
                $message = $this->getCount();
            }

            if (Tools::isSubmit("deleteOrder") && 
                Tools::getValue("orderId"))
            {
                $message = $this->deleteOrder(Tools::getValue("orderId"));
            }

            if (Tools::isSubmit("downloadOrderStatus"))
            {
                $message = $this->downloadOrderStatus();
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

    public function downloadOrderStatus() {
        //todo pobieranie danych z zewnetrznego api
        //przykładowa tablica z danymi
        $statuses = array(
            'Pending' => array(1, 3), //status oraz id zamowienia
            'Plukket' => array(4, 5),
        );
        
        try {
            foreach(array_keys($statuses) as $status) {
                foreach($statuses[$status] as $orderId) {
                    $this->changeOrderStatus($orderId, $status);
                }
            }
        } catch (Exception $e) {
            PrestaShopLogger::addLog('Error! Mail not sent. ' . $e->getMessage());
        }
    }

    public function changeOrderStatus($orderId, $status) {
        if(!in_array($status, StatusAndMail::STATUS_NAMES)) {
            throw new Exception("Wrong status name.");
        }
        //dodawanie zamowienia jezeli nie istnieje i wysylanie maila
        if (!$this->checkIfExists($orderId)) {
            $this->addOrder($orderId, $status);
            $this->sendMails($orderId, $status);
            return 'Mails sent';
        }
        //zmiana statusu zamowienia i wysylanie maila
        $oldStatus = $this->getStatus($orderId);
        if($oldStatus !== $status) {
            $this->sendMails($orderId, $status, $oldStatus);
            $this->changeStatus($orderId, $status);
            return 'Mails sent';
        }
        return 'status the same';
    }

    //wysylanie kilku maili jezeli wczesniej zostaly pominiete
    public function sendMails($orderId, $newStatus, $oldStatus = null) {
        $statusNames = StatusAndMail::STATUS_NAMES;
        if ($oldStatus === null) {
            $oldStatus = $statusNames[0];
        } else {
            $oldStatus = $statusNames[array_search($oldStatus, $statusNames) + 1];
        }
        for ($i = array_search($oldStatus, $statusNames); $i <= array_search($newStatus, $statusNames); $i++) {
            $this->sendMail($orderId, $statusNames[$i]);
        }
    }

    public function createTable() {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . StatusAndMail::TABLE_NAME . ' (
            order_id INT UNSIGNED NOT NULL UNIQUE,
            status ENUM("'.implode('", "', StatusAndMail::STATUS_NAMES).'") NOT NULL
        )';
        return Db::getInstance()->execute($sql);
    }

    public function dropTable() {
        $sql = "DROP TABLE IF EXISTS `" . StatusAndMail::TABLE_NAME ."`";
        return Db::getInstance()->execute($sql);
    }

    public  function addOrder($orderId, $status) {
        if(!in_array($status, StatusAndMail::STATUS_NAMES)) {
            throw new Exception("Wrong status name.");
        }
        if($this->checkIfExists($orderId)) {
            return 'Order already exists';
        }
        $data = array(
            'order_id' => $orderId,
            'status' => $status
        );
      	$result = Db::getInstance()->insert(StatusAndMail::TABLE_NAME, $data);
      	if ($result === false) {
            throw new Exception("No data added to table.");
        }
        return "Data added to table.";
    }

    public function checkIfExists($orderId) {
        $where = 'order_id = ' . (int)$orderId;
        $row = Db::getInstance()->getRow('SELECT * FROM ' . StatusAndMail::TABLE_NAME . ' WHERE ' . $where);
        if($row) {
            return true;
        } else {
            return false;
        }
    }

    public function getStatus($orderId) {
        $sql = "SELECT status FROM `" . StatusAndMail::TABLE_NAME . "` WHERE order_id = " . $orderId;
      	$result = Db::getInstance()->getValue($sql);
      	if ($result === false) {
            throw new Exception("No status found for this order.");
        }
        return $result;
    }

    public function changeStatus($orderId, $status) {
        if(!in_array($status, StatusAndMail::STATUS_NAMES)) {
            throw new Exception("Wrong status name.");
        }
        $data = array(
            'status' => $status
        );
        $where = 'order_id = ' . (int)$orderId;
      	$result = Db::getInstance()->update(StatusAndMail::TABLE_NAME, $data, $where);
      	if ($result === false) {
            throw new Exception("No data updated in table. Check status name.");
        }
        return "Status changed succesfuly.";
    }

    public function deleteOrder($orderId) {
        $where = 'order_id = ' . (int)$orderId;
      	$result = Db::getInstance()->delete(StatusAndMail::TABLE_NAME, $where);
      	if ($result === false) {
            throw new Exception("No data deleted in table.");
        }
        return "Order deleted succesfuly.";
    }

    public function getCount() {
        $sql = "SELECT COUNT(*) FROM `" . StatusAndMail::TABLE_NAME . "`";
        $result = Db::getInstance()->getValue($sql);
      	if ($result === false) {
            throw new Exception("Something went wrong.");
        }
        return $result;
    }

    public function sendMail($orderId, $status)  {
        $order = new Order((int)$orderId);
        $customer = new Customer($order->id_customer);

        $language = $customer->id_lang;
        $template = strtolower($status);
        $topic = $this->l('Test from') . ' ' . $this->context->shop->name;
        $data = array(  '{email}' => $customer->email, 
                        '{firstName}' => $customer->firstname, 
                        '{lastName}' => $customer->lastname,
                        '{shopName}' => $this->context->shop->name,
                        '{orderStatus}' => $status);
        $recipientEmail = $customer->email;
        $recipientName = $customer->firstname.' '.$customer->lastname;
        $senderEmail = null;
        $shopName = $this->context->shop->name;
        $cc = null;
        $bcc = null;
        $templatesPath = dirname(__FILE__).'/mails/';

        $response = '';
        if (!$response = Mail::Send($language, $template, $topic, $data, $recipientEmail, 
            $recipientName, $senderEmail, $shopName, $cc, $bcc, $templatesPath)) {
            throw new Exception('Error sending email.');
        }
        return 'Email send';
    }
}
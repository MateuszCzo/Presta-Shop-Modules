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

        $this->shortDescription = true;
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

    /**
     * Process status updates.
     * Retrieves data from the external API and compares them with the data in the store.
     * Changes the order status and sends an email notification if necessary.
     *
     * @return string Returns a message with the status updates.
     */
    public function processStatusUpdates()
    {
        $message = '';
        // Retrieve statuses from the external API
        $statuses = $this->getStatusesFromApi();
        // Get all orders from the store
        $orders = Db::getInstance()->executeS('SELECT id_order, reference FROM ' . _DB_PREFIX_ . 'orders');

        if (!$orders) {
            return 'Can not connect to database.';
        }

        foreach ($orders as $order) {
            $orderId = $order['id_order'];
            $orderReference = $order['reference'];

            if (!array_key_exists($orderReference, $statuses)) {
                // If the order is missing in the external API, add a message indicating it
                $message .= 'OrderId: ' . $orderId . ', order is missing in the external API.';
                continue;
            }

            try {
                // Change the order status and send an email notification
                $message .= $this->changeStatusAndSendMail($orderId, $statuses[$orderReference]) . PHP_EOL;
            } catch (Exception $e) {
                // If an exception occurs, add the error message to the result
                $message .= $e->getMessage() . PHP_EOL;
            }
        }

        return $message;
    }

    /**
     * Get statuses from API.
     * 
     * @return array Returns the array of statuses retrieved from the API.
     * Example array structure:
     * array(
     *    'ASDFGH' => 'Sendt',
     *    {orderIndex} => {orderStatus},
     *    ...
     * )
     */
    public function getStatusesFromApi() {
        //TODO get data from external API
        return $data = array(
            'ASDFGH' => 'Sendt'
        );
    }

    /**
     * Change status and send mail for an order.
     * Add the order if it doesn't exist in the database.
     *
     * @param int $orderId The ID of the order.
     * @param string $status The new status.
     * @param string $trackingNumber The tracking number.
     * @return string Returns the message after changing the status and sending the mail.
     * @throws Exception if the status name is wrong.
     */
    public function changeStatusAndSendMail($orderId, $status) {
        if(!in_array($status, StatusAndMail::STATUS_NAMES)) {
            throw new Exception('Order ID: ' . $orderId . ', status: ' . $status . ', wrong status name.');
        }
        $message = '';
        if (!$this->checkIfExists($orderId)) {
            $message .= $this->addOrder($orderId, $status);
            $message .= $this->sendMail($orderId, $status) . PHP_EOL;
            if ($this->shortDescription) {
                return 'Order ID: ' . $orderId . ', status: ' . $status . ', sending email.';
            }
            return $message;
        }
        if($this->getStatus($orderId) !== $status) {
            $message .= $this->changeStatus($orderId, $status);
            $message .= $this->sendMail($orderId, $status) . PHP_EOL;
            if ($this->shortDescription) {
                return 'Order ID: ' . $orderId . ', status: ' . $status . ', sending email.';
            }
            return $message;
        }
        
        if ($this->shortDescription) {
            return 'Order ID: ' . $orderId . ', status: ' . $status . ', no change.';
        }
        return 'Order ID: ' . $orderId . ', status: ' . $status . ', status name the same.';
    }

    /**
     * Create table in the database.
     *
     * @return bool Returns true if the table creation was successful, false otherwise.
     */
    public function createTable() {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . StatusAndMail::TABLE_NAME . ' (
            order_id INT UNSIGNED NOT NULL UNIQUE,
            status ENUM("'.implode('", "', StatusAndMail::STATUS_NAMES).'") NOT NULL
        )';
        return Db::getInstance()->execute($sql);
    }

    /**
     * Drop table from the database.
     *
     * @return bool Returns true if the table deletion was successful, false otherwise.
     */
    public function dropTable() {
        $sql = "DROP TABLE IF EXISTS `" . StatusAndMail::TABLE_NAME ."`";
        return Db::getInstance()->execute($sql);
    }

    /**
     * Add order to the database.
     *
     * @param int $orderId The ID of the order.
     * @param string $status The status of the order.
     * @return string Returns the message after adding the order.
     * @throws Exception if the status name is wrong or the order already exists.
     */
    public  function addOrder($orderId, $status) {
        if(!in_array($status, StatusAndMail::STATUS_NAMES)) {
            throw new Exception('Order ID: ' . $orderId . ', status: ' . $status . ', wrong status name.');
        }
        if($this->checkIfExists($orderId)) {
            throw new Exception('Order ID: ' . $orderId . ', status: ' . $status . ', order already exists.');
        }
        $data = array(
            'order_id' => $orderId,
            'status' => $status
        );
      	$result = Db::getInstance()->insert(StatusAndMail::TABLE_NAME, $data);
      	if ($result === false) {
            throw new Exception('Order ID: ' . $orderId . ', status: ' . $status . ', something went wrong when adding order.');
        }
        return 'Order ID: ' . $orderId . ', status: ' . $status . ', order added successfuly';
    }


    /**
     * Check if order exists in the database.
     *
     * @param int $orderId The ID of the order.
     * @return bool Returns true if the order exists, false otherwise.
     */
    public function checkIfExists($orderId) {
        $where = 'order_id = ' . (int)$orderId;
        $row = Db::getInstance()->getRow('SELECT * FROM ' . StatusAndMail::TABLE_NAME . ' WHERE ' . $where);
        if($row) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the status of an order.
     *
     * @param int $orderId The ID of the order.
     * @return string Returns the status of the order.
     * @throws Exception if something went wrong when getting the status.
     */
    public function getStatus($orderId) {
        $sql = "SELECT status FROM `" . StatusAndMail::TABLE_NAME . "` WHERE order_id = " . $orderId;
      	$result = Db::getInstance()->getValue($sql);
      	if ($result === false) {
            throw new Exception('Order ID: ' . $orderId . ', something went wrong when getting status.');
        }
        return $result;
    }

    /**
     * Change the status of an order.
     *
     * @param int $orderId The ID of the order.
     * @param string $status The new status.
     * @return string Returns the message after changing the status.
     * @throws Exception if the status name is wrong or something went wrong when changing the status.
     */
    public function changeStatus($orderId, $status) {
        if(!in_array($status, StatusAndMail::STATUS_NAMES)) {
            throw new Exception('Order ID: ' . $orderId . ', status: ' . $status . ', wrong status name.');
        }
        $data = array(
            'status' => $status
        );
        $where = 'order_id = ' . (int)$orderId;
      	$result = Db::getInstance()->update(StatusAndMail::TABLE_NAME, $data, $where);
      	if ($result === false) {
            throw new Exception('Order ID: ' . $orderId . ', status: ' . $status . ', something went wrong when changing status.');
        }
        return 'Order ID: ' . $orderId . ', status: ' . $status . ', status changed successfuly.';
    }

    /**
     * Delete an order from the database.
     *
     * @param int $orderId The ID of the order.
     * @return string Returns the message after deleting the order.
     * @throws Exception if something went wrong when deleting the order.
     */
    public function deleteOrder($orderId) {
        $where = 'order_id = ' . (int)$orderId;
      	$result = Db::getInstance()->delete(StatusAndMail::TABLE_NAME, $where);
      	if ($result === false) {
            throw new Exception('Order ID: ' . $orderId . ', something went wrong when deleting order.');
        }
        return 'Order ID: ' . $orderId . ', order deleted successfuly.';
    }

    /**
     * Get the count of orders in the database.
     *
     * @return string Returns the count of orders.
     * @throws Exception if something went wrong when getting the orders count.
     */
    public function getCount() {
        $sql = "SELECT COUNT(*) FROM `" . StatusAndMail::TABLE_NAME . "`";
        $result = Db::getInstance()->getValue($sql);
      	if ($result === false) {
            throw new Exception('Something went wrong when geting orders count.');
        }
        return $result;
    }

    /**
     * Send email to the customer.
     * Template of an email is the same name as status name.
     * $senderEmail = null - Uses default email
     *
     * @param int $orderId The ID of the order.
     * @param string $status The status of the order.
     * @param string $trackingNumber The tracking number.
     * @return string Returns the message after sending the email.
     * @throws Exception if something went wrong when sending the email.
     */
    public function sendMail($orderId, $status)  {
        $order = new Order((int)$orderId);
        $customer = new Customer($order->id_customer);
        $historyUrl = $this->context->link->getPageLink(
            'history',
            true,
            $this->context->language->id,
            array('id_order' => $order->reference)
        );
        $myAccountUrl = $this->context->link->getPageLink('my-account', true);
        $guestTrackingUrl = $this->context->link->getPageLink(
            'guest-tracking',
            true,
            $this->context->language->id,
            array(
                'id_order' => $orderId,
                'email' => $customer->email
            )
        );
        $followup = Tools::getShopDomainSsl(true) . __PS_BASE_URI__ . 'track.php?ref=' . $order->getUniqReference();

        $language = $customer->id_lang;
        $template = strtolower($status);
        $topic = $this->l('Test from') . ' ' . $this->context->shop->name;
        $data = array(  '{email}' => $customer->email, 
                        '{firstname}' => $customer->firstname, 
                        '{lastname}' => $customer->lastname,
                        '{shop_name}' => $this->context->shop->name,
                        '{shop_url}' => Tools::getShopDomainSsl(true) . __PS_BASE_URI__,
                        '{order_status}' => $status,
                        '{order_name}' => $order->getUniqReference(),
                        '{history_url}' => $historyUrl,
                        '{my_account_url}' => $myAccountUrl,
                        '{guest_tracking_url}' => $guestTrackingUrl,
                        '{tracking_number}' => $trackingNumber,
                        '{followup}' => $followup);
        $recipientEmail = $customer->email;
        $recipientName = $customer->firstname.' '.$customer->lastname;
        $senderEmail = null;
        $shopName = $this->context->shop->name;
        $cc = null;
        $bcc = null;
        $templatesPath = dirname(__FILE__) . '/mails/';

        $response = '';
        if (!$response = Mail::Send($language, $template, $topic, $data, $recipientEmail, 
            $recipientName, $senderEmail, $shopName, $cc, $bcc, $templatesPath)) {
                throw new Exception('Order ID: ' . $orderId . ', status: ' . $status . ', something went wrong when sending email.');
        }
        return 'Order ID: ' . $orderId . ', status: ' . $status . ', email sent successfuly.';
    }
}
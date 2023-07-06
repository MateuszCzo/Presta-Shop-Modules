<?php

include_once(dirname(__FILE__) . '/StatusNamesModel.php');

/**
 * The `StatusModel` class represents the model for the "order_status2" table in the database.
 * It provides functions for creating, deleting, and manipulating data in the table.
 */
class StatusModel {
    const TABLE_NAME = _DB_PREFIX_ . 'order_status2';

    /**
     * Creates the "order_status2" table if it doesn't exist.
     * @return bool True if the table was created or already exists, False otherwise.
     */
    public static function createTable() {
        $sql = 'CREATE TABLE IF NOT EXISTS ' . StatusModel::TABLE_NAME . ' (
            order_id INT UNSIGNED NOT NULL UNIQUE,
            status ENUM("'.implode('", "', StatusNamesModel::STATUS_NAMES).'") NOT NULL
        )';
        return Db::getInstance()->execute($sql);
    }

    /**
     * Drops the "order_status2" table if it exists.
     * @return bool True if the table was dropped, False otherwise.
     */
    public static function dropTable() {
        $sql = "DROP TABLE IF EXISTS `" . StatusModel::TABLE_NAME ."`";
        return Db::getInstance()->execute($sql);
    }
    
    /**
     * Adds an order with the specified order ID and status to the table.
     * @param int $orderId The order ID.
     * @param string $status The status of the order.
     * @return string The success message if the order is added successfully.
     * @throws Exception If the provided status is not valid.
     */
    public static function addOrder($orderId, $status) {
        if(!in_array($status, StatusNamesModel::STATUS_NAMES)) {
            throw new Exception("Wrong status name.");
        }
        $data = array(
            'order_id' => $orderId,
            'status' => $status
        );
      	$result = Db::getInstance()->insert(StatusModel::TABLE_NAME, $data);
      	if ($result === false) {
            throw new Exception("No data added to table.");
        }
        return "Data added to table.";
    }

    /**
     * Checks if an order with the specified order ID exists in the table.
     * @param int $orderId The order ID to check.
     * @return bool True if the order exists, False otherwise.
     */
    public static function checkIfExists($orderId) {
        $where = 'order_id = ' . (int)$orderId;
        $row = Db::getInstance()->getRow('SELECT * FROM ' . StatusModel::TABLE_NAME . ' WHERE ' . $where);
        if($row) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Retrieves the status of the order with the specified order ID.
     * @param int $orderId The order ID.
     * @return string The status of the order.
     * @throws Exception If no status is found for the order.
     */
    public static function getStatus($orderId) {
        $sql = "SELECT status FROM `" . StatusModel::TABLE_NAME . "` WHERE order_id = " . $orderId;
      	$result = Db::getInstance()->getValue($sql);
      	if ($result === false) {
            throw new Exception("No status found for this order.");
        }
        return $result;
    }

    /**
     * Changes the status of the order with the specified order ID.
     * @param int $orderId The order ID.
     * @param string $status The new status for the order.
     * @return string The success message if the order is deleted successfully.
     * @throws Exception If no data is updated in the table or if the status name is invalid.
     */
    public static function changeStatus($orderId, $status) {
        if(!in_array($status, StatusNamesModel::STATUS_NAMES)) {
            throw new Exception("Wrong status name.");
        }
        $data = array(
            'status' => $status
        );
        $where = 'order_id = ' . (int)$orderId;
      	$result = Db::getInstance()->update(StatusModel::TABLE_NAME, $data, $where);
      	if ($result === false) {
            throw new Exception("No data updated in table. Check status name.");
        }
        return "Status changed succesfuly.";
    }

    /**
     * Deletes the order with the specified order ID from the table.
     * @param int $orderId The order ID to delete.
     * @return string The success message if the order is deleted successfully.
     * @throws Exception If no data is deleted from the table.
     */
    public static function deleteOrder($orderId) {
        $where = 'order_id = ' . (int)$orderId;
      	$result = Db::getInstance()->delete(StatusModel::TABLE_NAME, $where);
      	if ($result === false) {
            throw new Exception("No data deleted in table.");
        }
        return "Order deleted succesfuly.";
    }

    /**
     * Retrieves the count of rows in the "order_status2" table.
     * @return int The number of rows in the table.
     * @throws Exception If an error occurs.
     */
    public static function getCount() {
        $sql = "SELECT COUNT(*) FROM `" . StatusModel::TABLE_NAME . "`";
        $result = Db::getInstance()->getValue($sql);
      	if ($result === false) {
            throw new Exception("Something went wrong.");
        }
        return $result;
    }
}

?>
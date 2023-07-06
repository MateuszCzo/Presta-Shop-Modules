<?php

class SendMailModel {
    /**
     * Sends an email to the customer based on the provided order ID and status.
     *
     * @param int $orderId The ID of the order.
     * @param string $status The status of the order.
     * @return string The result message indicating if the email was sent successfully.
     * @throws Exception If there was an error sending the email.
     */
    public static function sendMail($orderId, $status)  {
        $order = new Order((int)$orderId);
        $customer = new Customer($order->id_customer);

        $language = $customer->id_lang;
        $template = strtolower($status);
        $topic = 'Test';
        $data = array(  '{email}' => $customer->email, 
                        '{firstName}' => $customer->firstname, 
                        '{lastName}' => $customer->lastname,
                        '{shopName}' => 'shop name',
                        '{orderStatus}' => $status);
        $recipientEmail = $customer->email;
        $recipientName = $customer->firstname.' '.$customer->lastname;
        $senderEmail = null;
        $shopName = 'shop name';
        $cc = null;
        $bcc = null;
        $templatesPath = dirname(__FILE__).'/mails/';

        $response = '';
        if (!$response = Mail::Send($language, $template, $topic, $data, $recipientEmail, $recipientName, $senderEmail, $shopName, $cc, $bcc, $templatesPath)) {
            throw new Exception('Error sending email.');
        }
        return 'Email send';
    }
}

?>
<?php

class HalmarAPI {
    private $url = 'http://api.halmar.pl';
    private $token = '';

    public function __construct() {
    }

    public function login($login, $password) {
        $body = json_encode(array(
    	    'username' => $login,
            'password' => $password
		));
      	$headers = array(
            'Host: api.halmar.pl',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body)
        );
        $curl = curl_init($this->url . '/api/auth_token');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
      	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      	curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        $response = curl_exec($curl);
          
        if ($response === false) {
    		throw new Exception('500 Internal Server Error.');
	    }

		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
      	
        $responseData = json_decode($response, true);

      	curl_close($curl);

        if ($httpCode / 100 !== 2) {
            throw new Exception('Http code: '. $httpCode . '. Response: ' . $response);
        }

        $this->token = $responseData['token'];
    }

    public function postOrder($data) {
        $headers = array(
            'Host: api.halmar.pl',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
            'Authorization: Bearer ' . $this->token
        );
        $curl = curl_init($this->url . '/api/order');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
      	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        $response = curl_exec($curl);
          
        if ($response === false) {
    		throw new Exception('500 Internal Server Error.');
	    }
        
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

      	curl_close($curl);

        if ($httpCode / 100 !== 2) {
            throw new Exception('Http code: '. $httpCode . '. Response: ' . $response);
        }
    }
  
  	public function getOrders() {
    	$headers = array(
            'Host: api.halmar.pl',
            'Content-Type: application/json',
            'Content-Length: ' . strlen($body),
            'Authorization: Bearer ' . $this->token
        );
        $curl = curl_init($this->url . '/api/order');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
      	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);
          
        if ($response === false) {
    		throw new Exception('500 Internal Server Error.');
	    }
        
		$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

      	curl_close($curl);

        if ($httpCode / 100 !== 2) {
            throw new Exception('Http code: '. $httpCode . '. Response: ' . $response);
        }
      
      	return $response;
    }

    public function hasOrder($orderName, $date) {
        $headers = array(
            'Host: api.halmar.pl',
            'Authorization: Bearer ' . $this->token
        );
        $curl = curl_init($this->url . '/api/order?date=' . $date);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($curl);

        if ($response === false) {
            throw new Exception('500 Internal Server Error.');
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        $responseData = json_decode($response, true);

        curl_close($curl);

        if ($httpCode / 100 !== 2) {
            throw new Exception('Http code: '. $httpCode . '. Response: ' . $response);
        }

        foreach($responseData as $order) {
            if ($order['OrderHeader']['VendorOrderNumber'] === $orderName) {
                return true;
            }
        }

        return false;
    }
  	
    public function getToken() {
        return $this->token;
    }
}

?>
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
      
        if (Tools::isSubmit("addChatbot") && 
            Tools::getValue("botPressId") && 
            Tools::getValue("apiKey"))
        {
            Configuration::updateValue('BOT_PRESS_ID', Tools::getValue("botPressId"));
            Configuration::updateValue('BOT_PRESS_API_KEY', Tools::getValue("apiKey"));
          	$connectionStatus = $this->checkConnection();
          	if ($connectionStatus === 200) {
            	$message = "Chatbot added correctly.";
            } else {
              	$message = "Cannot connect to chatbot, check bot id and api key. Http response ".$connectionStatus;
            }
        }
      
      	if (Tools::isSubmit("sendFiles")) {
          	$connectionStatus = $this->sendDataToChatbot(json_decode(Tools::getValue("botpressData"), true));
        	if ($connectionStatus === 200) {
            	$message = "Data has been send.";
            } else {
              	$message = "Unable to send data. Http response ".$connectionStatus;
            }
        }
      
        $this->context->smarty->assign([
          'botPressId' => Configuration::get('BOT_PRESS_ID'),
          'apiKey' => Configuration::get('BOT_PRESS_API_KEY'),
          'message' => $message,
          'url' => Configuration::get('BOT_PRESS_URL'),
          'urlKey' => Configuration::get('BOT_PRESS_URL_KEY'),
          'fileName' => Configuration::get('BOT_PRESS_FILE_NAME'),
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
  
  	public function checkConnection() {
    	$url = "https://documents.botpress.cloud/api/".Configuration::get('BOT_PRESS_ID')."/documents";
      	$curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        $headers = [
            "Authorization: ".Configuration::get('BOT_PRESS_API_KEY')
        ];
      	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      	$response = curl_exec($curl);
      
      	if ($response === false) {
    		return 500;
		} else {
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        }
      
      	curl_close($curl);
      
      	if ($httpCode === 200) {
    		return 200;
		}
      	return $httpCode;
    }
  	
  	public function sendDataToChatbot($payload) {
      
      	//get file id
      	$response = $this->getFilesIds();
      	if ($response['status'] !== 200) return $response['status'];
      
      	//delete file with given id
      	$fileIds = $response['ids'];
      	$response = $this->deleteFiles($fileIds);
      	if ($response['status'] !== 200) return $response['status'];
      
      	//create file
     	$response = $this->createProductsFiles();
      	if ($response['status'] !== 200) return $response['status'];

		//this code sends only 1 file!!!
      
      	//send file to given api
      	$fileName = $response['fileNames'][0];
      	Configuration::updateValue('BOT_PRESS_URL', $payload['url']);
      	Configuration::updateValue('BOT_PRESS_URL_KEY', $payload['fields']['key']);
		Configuration::updateValue('BOT_PRESS_FILE_NAME', $fileName);
      	$response = $this->sendFileToUrl($payload, $fileName);
      
      	return $response['status'];
    }
  
  	private function getFilesIds() {
     	$url = "https://documents.botpress.cloud/api/".Configuration::get('BOT_PRESS_ID')."/documents";
      	$curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPGET, true);
        $headers = [
            "Authorization: ".Configuration::get('BOT_PRESS_API_KEY')
        ];
      	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      	$response = curl_exec($curl);
      
      	if ($response === false) {
    		return array("status" => 500);
		} else {
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        }
      
      	curl_close($curl);
      
      	if ($httpCode === 200) {
          	$ids = array();
          	$data = json_decode($response, true);
          	foreach($data as $item) {
             	array_push($ids, $item['id']);
            }
          	return array("status" => 200, "ids" => $ids);
		}
      	return array("status" => $httpCode);
    }
  
  	private function deleteFiles($fileIds) {
      	$url = "https://documents.botpress.cloud/api/".Configuration::get('BOT_PRESS_ID')."/documents/";
     
     	foreach($fileIds as $fileId) {
        	$curl = curl_init($url.$fileId);
          	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
          	$headers = [
            	"Authorization: ".Configuration::get('BOT_PRESS_API_KEY')
        	];
          	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
          	$response = curl_exec($curl);
          	curl_close($curl);	
          
          	if ($response === false) {
    			return array("status" => 500);
			} else {
				$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        	}
          
          	if ($httpCode !== 200) {
              	return array("status" => $httpCode); 
            }
        }
      	return array("status" => 200);
    }
  
  	private function createProductsFiles($path = 'files/', $numPerFile = 100) {
      	$numOfProducts = Db::getInstance()->executeS('SELECT count(*) FROM `'._DB_PREFIX_.'product` WHERE `active` = 1');
      	$numOfProducts = $numOfProducts[0]['count(*)'];
      	
      	if ($numOfProducts === 0) {
         	return array("status" => 200, "fileNames" => array());
        }
      
      	$filenames = array();
      	for ($x = 0; $x * $numPerFile < $numOfProducts; $x++) {
         	$filename = $path.'products'.$x.'.txt';
          	array_push($filenames, $filename);
        	$response = $this->createSingleFile($filename, $numPerFile, $x * $numPerFile);
          	if ($response['status'] !== 200) {
             	return $response;
            }
        }
      	return array("status" => 200, "fileNames" => $filenames);
    }
  
  	private function createSingleFile($filename, $limit, $offset) {
      	$langId = Configuration::get('PS_LANG_DEFAULT');
      	$res = Db::getInstance()->executeS('SELECT `id_product` FROM `'._DB_PREFIX_.'product` WHERE `active` = 1 LIMIT '.$limit.' OFFSET '.$offset);
      	if (!$res) {
          	return array("status" => 500);
        }
      	$data = array();
      	foreach($res as $row) {
          	$productId = $row['id_product'];
          	$product = new Product($productId);
          	$keys = array();
          	$values = array();
          
          	array_push($keys, 'name');
          	array_push($keys, $product->name[$langId]);
          	array_push($values, 'price');
          	array_push($values, $product->price[$langId]);
          
          	$features = $product->getFrontFeatures($langId);
          	foreach($features as $feature) {
              	array_push($keys, $feature['name']);
              	array_push($values, $feature['value']);
            }
          	$combindeArrays = array_combine($keys, $values);
        	array_push($data, $combindeArrays);
        }
      	$jsonData = json_encode($data);
      
      	$file = fopen($filename, 'w');
      	if ($file === false) {
        	return array("status" => 500);
        }
      	fwrite($file, $jsonData);
      	fclose($file);
    	return array("status" => 200);
    }
  
  	private function sendFileToUrl($payload, $fileName) {
     	$url = $payload['url'];
      	$body = array(
    		'acl' => $payload['fields']['acl'],
          	'Content-Type' => $payload['fields']['Content-Type'],
          	'bucket' => $payload['fields']['bucket'],
          	'X-Amz-Algorithm' => $payload['fields']['X-Amz-Algorithm'],
          	'X-Amz-Credential' => $payload['fields']['X-Amz-Credential'],
          	'X-Amz-Date' => $payload['fields']['X-Amz-Date'],
          	'key' => $payload['fields']['key'],
          	'Policy' => $payload['fields']['Policy'],
          	'X-Amz-Signature' => $payload['fields']['X-Amz-Signature'],
          	'file' => new CURLFile($fileName)
		);
      	$payload = json_encode($body);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
      	curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
      	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    		'Content-Type: application/json',
    		'Content-Length: ' . strlen($payload)
		));
        $response = curl_exec($curl);
          
        if ($response === false) {
    		return array("status" => 500);
	    } else {
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        }
      	
      	$return = array("status" => 200, "payload" => $response);
      	curl_close($curl);	
          
        if ($httpCode !== 204) {
            return array("status" => $httpCode); 
        }
      	return $return;
    }
}
<?php
// mcowkin shipping method
class ModelShippingChinavasiondhl extends Model {
	public function getQuote($address) {
		$this->load->language('shipping/chinavasiondhl');
		
		$method_data = array();
		
		$dhlShipPrice = 30.00; // defaukt shipping price
		
		$status = true; // status of shipping method
		
		$apiKey = $this->config->get('chinavasiondhl_apikey');
		
		$url = "https://secure.chinavasion.com/api/getPrice.php";
		
		$produtcsJsonArray = array();

		foreach ($this->cart->getProducts() as $product)
		{
			$produtcsJsonArray[] = array("model_code"=>$product['model'],"quantity"=>$product['quantity']);
		}

		$order_data = array ('key'=>$apiKey, 'currency'=>"USD", 'socket'=>'US', "products"=>$produtcsJsonArray, "shipping_country_iso2"=>"US");

		$jsonRequest = json_encode($order_data);
		
		$resultGetDhlShippingPrice = $this->getDhlShippingPrice($jsonRequest, $url);

		if($resultGetDhlShippingPrice !== FALSE)
		{
			$dhlShipPrice = $resultGetDhlShippingPrice;
		}

		// $status = false;
		// break;

		if($status)
		{
			$quote_data = array();
			
			$quote_data['chinavasiondhl'] = array(
				'code'         => 'chinavasiondhl.chinavasiondhl',
				'title'        => $this->language->get('text_description'),
				'cost'         => $dhlShipPrice,
				'tax_class_id' => 0,
				'text'         => $this->currency->format($dhlShipPrice)
			);

			$method_data = array(
				'code'       => 'chinavasiondhl',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('chinavasiondhl_sort_order'),
				'error'      => false
			);
		}
		return $method_data;
	}

	protected function getDhlShippingPrice($jsonRequest, $url)
	{
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Content-type: application/json"));
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonRequest);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$jsonResponse = curl_exec($curl);
		$jsonStatus = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		if($jsonStatus != 200 )
		{
			$msg = "Error: call to URL $url failed with status $jsonStatus, response $jsonResponse, curl_error ".curl_error($curl).", curl_errno ".curl_errno($curl);
			mail("scorpiolaboratory@gmail.com", "Elite-Electronix: bad order", $msg);
			mail("markiz.zelos@gmail.com", "Elite-Electronix: bad order", $msg);
			curl_close($curl);
			return false;
		}
		
		curl_close($curl);
		
		$response = json_decode($jsonResponse, true);
		if(isset($response['error']))
		{
			$msg = $response['error_message'].'<br/><br/>'.$jsonRequest;
			mail("scorpiolaboratory@gmail.com", "Elite-Electronix: bad order", $msg);
			mail("markiz.zelos@gmail.com", "Elite-Electronix: bad order", $msg);
			return false;
		}
		else
		{
			foreach($response['shipping'] as $value)
			{
				if($value['name'] == "DHL")
				{
					$dhlprice = $value['price'];
				}
			}
			return $dhlprice;
		}
	}
}
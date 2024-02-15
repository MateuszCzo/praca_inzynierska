<?php
	if (!isset($_GET['s']) || !isset($_GET['resultsPerPage']) || !is_array(json_decode($_GET['s']))) {
		header("Content-Type: application/json");
      	print_r(json_encode(array('status' => 200, 'products' => [])));
		return;
    }
	$names = json_decode($_GET['s']);
	$returnData = [];
	foreach($names as $name) {
		$url = "https://mateusz.zielonaskrzynka.pl/szukaj";
		$headers = [
        	"accept: application/json"
    	];
		$data = array(
    		's' => $name,
      		'resultsPerPage' => $_GET['resultsPerPage']
    	);
   		$curl = curl_init($url);
      	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
      	curl_setopt($curl, CURLOPT_POST, true);
      	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
      	curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
      	$response = curl_exec($curl);
     	if ($response === false || curl_getinfo($curl, CURLINFO_HTTP_CODE)/100 !== 2) {
        	continue;
     	}
      	curl_close($curl);
      	$products = json_decode($response)->products;
      	foreach($products as $product) {
        	array_push($returnData, $product);
        }
    }
	header("Content-Type: application/json");
	print_r(json_encode(array('status' => 200, 'products' => $returnData)));
	return;

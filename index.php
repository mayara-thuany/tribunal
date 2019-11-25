<?php

error_reporting(E_ERROR);
date_default_timezone_set('america/sao_paulo');


function get_header($url){
	
	$curl = curl_init();
	
	$header = array("Cookie: JSESSIONID=15FF52F72CACB7F4AC0B127A8600949B.cpopg1");

	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
	curl_setopt($curl, CURLOPT_AUTOREFERER, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_VERBOSE, 1);
	curl_setopt($curl, CURLOPT_HEADER, 1);

	$response = curl_exec($curl);

	$headers = array();

	$header_text = substr($response, 0, strpos($response, "\r\n\r\n"));

	foreach (explode("\r\n", $header_text) as $i => $line){
		if ($i === 0)
			$headers['http_code'] = $line;
		else{
			list ($key, $value) = explode(': ', $line);
			$headers[$key] = $value;
		}
	}

	curl_close($curl);

	return $headers;
}

function get_body($url){
	
	$curl = curl_init();
	
	$header = array("Cookie: JSESSIONID=15FF52F72CACB7F4AC0B127A8600949B.cpopg1");

	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
	curl_setopt($curl, CURLOPT_ENCODING, 'gzip,deflate');
	curl_setopt($curl, CURLOPT_AUTOREFERER, true);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_VERBOSE, 1);

	$response = curl_exec($curl);

	curl_close($curl);

	return $response;
}

function trata_texto($data,$breakline) {
	if ($breakline) {
		$data = nl2br($data);
		$data = preg_replace("/(<br\s*\/>\s*)+/", "<br />", $data);
	}

	$data = preg_replace('/\s+/', ' ', $data);
	return trim($data);
}

function extract_elements($data) {
	
	$response = array();
	$dom      = new DOMDocument;
	
	@$dom->loadHTML(mb_convert_encoding($data, 'HTML-ENTITIES', 'UTF-8'));  

	$jsonResult = [];

	// ----------------------------
	// Dados do processo
	// ----------------------------

    $finder = new DomXPath($dom);
    $spaner = $finder->query("//table[contains(@class, 'secaoFormBody')]");

	$dadosProcesso = $spaner->item(1)->getElementsByTagName("tr");

	foreach ( $dadosProcesso as $tr )  {
		
		$tds = $tr->getElementsByTagName("td");

		$key = trata_texto($tds->item(0)->textContent);

		if (strpos($key, ':') !== false) {
			$value = trata_texto($tds->item(1)->textContent);
			// $jsonResult["dadosProcesso"][$key] = $value;
			$jsonResult["dadosProcesso"] .= $key.' '.$value.'<br>';
		}
	}

	// ----------------------------
	// Partes do processo
	// ----------------------------
	 
	$partesPrincipais = $dom->getElementById('tablePartesPrincipais')->getElementsByTagName("tr");
   	 	
	foreach ( $partesPrincipais as $i => $tr )  {
		
		$tds = $tr->getElementsByTagName("td");

		$key = trata_texto($tds->item(0)->textContent);
		$value = trata_texto($tds->item(1)->textContent);
		$jsonResult["partes"] .= $key.$value.'<br>';
	}

	// ----------------------------
	// Movimentações
	// ----------------------------

	$tabelaTodasMovimentacoes = $dom->getElementById('tabelaTodasMovimentacoes')->getElementsByTagName("tr");

	foreach ( $tabelaTodasMovimentacoes as $tr )  {
		
		$tds = $tr->getElementsByTagName("td");

		$mov = array(
			"data"=>trata_texto($tds->item(0)->textContent),
			"movimento"=>trata_texto($tds->item(2)->textContent,true)
		);

		$jsonResult["movimentacoes"][] = $mov;
	}

	return $jsonResult;
}
   
$url='https://www2.tjal.jus.br/cpopg/search.do?conversationId=&dadosConsulta.localPesquisa.cdLocal=-1&cbPesquisa=NUMPROC&dadosConsulta.tipoNuProcesso=SAJ&numeroDigitoAnoUnificado=&foroNumeroUnificado=&dadosConsulta.valorConsultaNuUnificado=&dadosConsulta.valorConsulta='.$_POST['numProcesso'].'&uuidCaptcha=';

$header = get_header($url);

$location = 'https://www2.tjal.jus.br'.$header["Location"];

$data = get_body($location);

$jsonResult = extract_elements($data);

$arquivo_consulta = __DIR__ . '/consultas.txt';

$jsonFile = file_get_contents($arquivo_consulta);
$jsonFile = json_decode($jsonFile, true);

$file = fopen($arquivo_consulta,'w');

$key = preg_replace('/\D/', '', $_POST['numProcesso']);

$jsonFile[ $key ] = array(
	"dataAtualizacao"=> date('Y-m-d H:i:s'),
	"dados"=>$jsonResult
);

fwrite($file, json_encode($jsonFile,JSON_UNESCAPED_UNICODE));
fclose($file);

$jsonResult = json_encode($jsonResult,JSON_UNESCAPED_UNICODE);
echo $jsonResult;

?>
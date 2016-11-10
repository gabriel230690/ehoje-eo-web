<?php

class instagram {

	// API do Instagram
	const INSTAGRAM_API = 'https://api.instagram.com/v1';
	
	const CLIENT_ID     = '1c2dea2787c94dc9bb5e873782325991';

    const CLIENT_SECRET = 'c254231b8c71402c97315fbe689e14ee';

    const REDIRECT_URI = 'https://www.facebook.com/ehojepp?fref=ts';

    const ACCESS_TOKEN = '12336689.1c2dea2.49f414c80f824f18b4cb648a5ba2394a';


	function instagram() {


	}

	function buscaID($pr_id_fanpage) {
	
		// Criamos um novo recurso do tipo Curl
		$vr_ch = curl_init();
		
		// Informar URL e outras funções ao CURL
		curl_setopt($vr_ch, CURLOPT_URL, self::INSTAGRAM_API . '/locations/search?access_token=' . self::ACCESS_TOKEN .
															   '&facebook_places_id=' . $pr_id_fanpage);
		
		// Evitar problemas com requisições HTTPS
		curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false); 
				
		// Retornar saida ao inves de imprimir
		curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);
		
		// Acessar a URL e retornar a saída
		$vr_saida = curl_exec($vr_ch);
				
		// Converter para JSON
		$vr_json_local = json_decode($vr_saida);

		return $vr_json_local->data[0]->id;
	
	}

}

?>
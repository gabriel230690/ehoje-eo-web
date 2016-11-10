<?

class foursquare {

	// Link da API 
	const FOURSQUARE_API = 'https://api.foursquare.com/';

	// ID para identificacao com o Foursquare
	const CLIENT_ID = '4BLXBGUWUOUWQ3T3RMT4OXCBG3AIQ5ZLSWBOLKCF3EPCYM3H';
	
	// Secret cleint para identificacao com o Foursquare
	const CLIENT_SECRET = 'TA3TVJHWIUOS51212B5G3LMR1BST5VLIU40XQIUUTHFBVBXM';
	
	// Data para validar as requisicoes
	const VERSAO_DATA = '20150101';
	
	
	// Construtor principal da classe
	function foursquare() {

	}
	
	function buscaInfos ($pr_ds_local, $pr_ds_cidade, $pr_bd) {
	
		// Criamos um novo recurso do tipo Curl
		$vr_ch = curl_init();
		
		// Informar URL e outras funções ao CURL
		curl_setopt($vr_ch, CURLOPT_URL, self::FOURSQUARE_API . 'v2/venues/search?v=' . self::VERSAO_DATA . 
		               										    '&client_id='     . self::CLIENT_ID . 
															    '&client_secret=' . self::CLIENT_SECRET   .
															    '&intent=browse'  .
															    '&query='         . str_replace(" ","%20",$pr_ds_local) . 
															    '&near='          . str_replace(" ","%20",$pr_ds_cidade)  .
															    '&radius=50000');	
		// Evitar problemas com requisições HTTPS
		curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false); 
				
		// Retornar saida ao inves de imprimir
		curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);
		
		// Acessar a URL e retornar a saída
		$vr_saida = curl_exec($vr_ch);
				
		// Converter para JSON
		$vr_json_local = json_decode($vr_saida);
		
		// Liberar recurso
		curl_close($vr_ch);
				
		// Percorrer todos os locais retornados
		for ($i=0; $i < count($vr_json_local->response->venues); $i++) {
		
			// Obter o ID, endereco e telefone	
			$vr_obj_local = $vr_json_local->response->venues[$i];
			 		
			$vr_id_foursquare = $vr_obj_local->id;
			$vr_ds_endereco   = $vr_obj_local->location->address;
			$vr_ds_telefone   = substr($vr_obj_local->contact->formattedPhone,4);
			$vr_ds_local      = $vr_obj_local->name;
			$vr_ds_site		  = $vr_obj_local->url;
			$vr_nr_latitude   = $vr_obj_local->location->lat;
			$vr_nr_longitude  = $vr_obj_local->location->lng;
		
			// Se bateu o nome, retornar os dados do mesmo 
			if  (strcasecmp(trim($vr_ds_local),trim($pr_ds_local)) == 0)  { 
						
				return array('idFoursquare' => $vr_id_foursquare, 
			 				 'dsEndereco'   => $vr_ds_endereco,
							 'dsTelefone'   => $vr_ds_telefone,
							 'dsSite' 		=> $vr_ds_site,
							 'latitude'     => $vr_nr_latitude,
							 'longitude'    => $vr_nr_longitude);
			
			}		
			
			// Verificar o local com os nomes alternativos		
			$local = new local ($pr_bd);
			$vr_info = $local->verificaNomesLocal($vr_ds_local);	
			
			// Se achou o local, retornar os dados do mesmo
			if ($vr_info['idLocal'] > 0) {
					
				return array('idFoursquare' => $vr_id_foursquare, 
			 				 'dsEndereco'   => $vr_ds_endereco,
							 'dsTelefone'   => $vr_ds_telefone,
							 'dsSite'		=> $vr_ds_site,
							 'latitude'     => $vr_nr_latitude,
							 'longitude'    => $vr_nr_longitude);
			}						

		}
			
		// Se não achou nenhum local, retornar dados em branco 
		return array('idFoursquare' => '', 
			 		 'dsEndereco'   => '',
					 'dsTelefone'   => '',
					 'dsSite'		=> '',
					 'latitude'     => '',
				     'longitude'    => '');
	
	}
	
	function trazImgPageProfile ( $pr_id_foursquare ) {
	
		// Criamos um novo recurso do tipo Curl
		$vr_ch = curl_init();
		
		// Informar URL e outras funções ao CURL
		curl_setopt($vr_ch, CURLOPT_URL, self::FOURSQUARE_API . $pr_id_foursquare  . '/v2/venues/'     . $pr_id_foursquare  .
																	        	     '?v='			   . self::VERSAO_DATA  .
		               												                 '&client_id='     . self::CLIENT_ID    .
															                         '&client_secret=' . self::CLIENT_SECRET);
		// Evitar problemas com requisições HTTPS
		curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false); 
		
		// Retornar saida ao inves de imprimir
		curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);
				
		// Acessar a URL e retornar a saída
		$vr_saida = curl_exec($vr_ch);
				 		
		// Converter para JSON
		$vr_json_local = json_decode($vr_saida);
		
		// Liberar recurso
		curl_close($vr_ch);
		
		// Obter as informacoes da imagem
		$vr_obj_imagem = $vr_json_local->response->venue->photos->groups[0]->items[0];
		
		// Se não vier imagem, retornar em branco 
		if ($vr_obj_imagem->prefix == '') {
			return '';
		}
		
		// Montar link da imagem
		$vr_ds_imgProfile = $vr_obj_imagem->prefix . 
							$vr_obj_imagem->width  . 'x' . $vr_obj_imagem->height . 
		   					$vr_obj_imagem->suffix; 					
						
		return $vr_ds_imgProfile;
	
	}

    function buscaLocais($pr_nr_latitude, $pr_nr_longitude) {

        // Criamos um novo recurso do tipo Curl
        $vr_ch = curl_init();

        // Informar URL e outras funções ao CURL
        curl_setopt($vr_ch, CURLOPT_URL, self::FOURSQUARE_API  . '/v2/venues/search?v=' . self::VERSAO_DATA   .
                                                                 '&client_id='          . self::CLIENT_ID     .
                                                                 '&client_secret='      . self::CLIENT_SECRET .
                                                                 '&ll=' . $pr_nr_latitude . ',' . $pr_nr_longitude .
                                                                 '&llAcc=400000'   .
                                                                 '&intent=checkin' .
                                                                 '&categoryId=4d4b7105d754a06376d81259'); // 4d4b7105d754a06374d81259
        // Evitar problemas com requisições HTTPS
        curl_setopt($vr_ch, CURLOPT_SSL_VERIFYPEER, false);

        // Retornar saida ao inves de imprimir
        curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);

        // Acessar a URL e retornar a saída
        $vr_saida = curl_exec($vr_ch);

        // Converter para JSON
        $vr_json_local = json_decode($vr_saida);

        // Liberar recurso
        curl_close($vr_ch);

        // Retornar os locais
        return $vr_json_local;

    }

}

?>
<?php

class hlera {
	
	// Nome do site
	const HLERA_SITE = 'http://www.hleranafesta.com.br';
	
	// ID do site
	const ID_HLERA = 4;
	
	
	// Construtor da classe principal
	function hlera () {
	
	}
	
	function ObtemEventosHlera($pr_flg_gravar) {
	
		// Criamos um novo recurso do tipo Curl
		$vr_ch = curl_init();
		
		// Informar URL e outras funções ao CURL
		curl_setopt($vr_ch, CURLOPT_URL, self::HLERA_SITE . '/agenda/');
	
		// Retornar saida ao inves de imprimir
		curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);
		
		// Acessar a URL e retornar a saída
		$vr_ds_saida = curl_exec($vr_ch);
		
		//Se houver algum erro na requisição Curl então, grava log e vai para a próxima cidade
		if(curl_error($vr_ch) != ''){
		
			// Armazenar o erro
			$vr_ds_erro = 'Erro na conversão para XML ' . curl_error($vr_ch);
					
			// Instanciar a classe do log e gerar critica
			$log = new log();
			
			$log->geraLog ( self::ID_HLERA , $vr_ds_erro);

			// Liberar recurso
			curl_close($vr_ch);
			
			return;
		}
								
		// Liberar recurso
		curl_close($vr_ch);
		
        // Retornar a String com os eventos
        return json_encode($this -> trataDadosHlera($vr_ds_saida,$pr_flg_gravar));
	
	}
	
	function trataDadosHlera($pr_ds_saida,$pr_flg_gravar){
		
		$vr_nr_desconsidera = strpos($pr_ds_saida,'<div id="centro">'); 
		$vr_ds_dados        = substr($pr_ds_saida,$vr_nr_desconsidera);
		$vr_nr_limite       = strpos($vr_ds_dados,'<div class="espaco">') - 2;
		
		$vr_ds_dados = substr($vr_ds_dados,0,$vr_nr_limite);
		$vr_ds_dados = str_replace('border="0">','border = "0"> </img>',$vr_ds_dados);
		$vr_ds_dados = str_replace("<tr>","",$vr_ds_dados);
		$vr_ds_dados = str_replace("</tr>","",$vr_ds_dados);
		$vr_ds_dados = str_replace("<br>","separador",$vr_ds_dados);
		$vr_ds_dados = str_replace('itemscope','',$vr_ds_dados);
		$vr_ds_dados = str_replace('height="50">','height = "50"> </img>',$vr_ds_dados);
		$vr_ds_dados = str_replace('&','e_comercial',$vr_ds_dados);
		$vr_ds_dados = str_replace('&layout=button_count&show_faces=false&width=82&action=like&colorscheme=light&height=21&locale=pt_BR','',$vr_ds_dados);
		
		if ($vr_ds_dados != null) {
			return $this -> trataXmlHlera($vr_ds_dados,$pr_flg_gravar);
		}
		
	}		
	
	function trataXmlHlera($pr_ds_dados, $pr_flg_gravar) {
		
		// Array para devolver os eventos
		$vr_arr_eventos = array();
		
		$vr_bd = new bd(false);
		$vr_bd -> conecta();
			
		// Converter em um objeto XML				
		$vr_xml_eventos = new DomDocument;
		
		try {
			$vr_xml_eventos->loadXML(utf8_encode($pr_ds_dados));
		} catch (Exception $ex) {
			return;
		}
					
		// Filtrar as DIV		
		$vr_elementos = $vr_xml_eventos->getElementsByTagName('div');
		
		// Percorrer todas as DIV
		foreach ($vr_elementos as $vr_elemento) {
							
			// Se não for da classe especificada, desconsiderar	
			if ($vr_elemento->getAttribute('itemtype') != 'http://data-vocabulary.org/Event') {
				continue;
			}
				
			try {
			
				$vr_ds_atracoes = $vr_elemento->getElementsByTagName('strong')->item(0)->textContent;
				$vr_dt_evento = substr($vr_elemento->getElementsByTagName('time')->item(0)->textContent,0,10);
				$vr_nr_dia = substr($vr_dt_evento,0,2);
				$vr_nr_mes = substr($vr_dt_evento,3,2);
				$vr_nr_ano = substr($vr_dt_evento,6,4);
				$vr_dt_evento = $vr_nr_ano.'-'.$vr_nr_mes.'-'.$vr_nr_dia;
				$vr_ds_link = $vr_elemento->getElementsByTagName('a')->item(0)->getAttribute('href');
				$vr_ds_local = str_replace("'","",$vr_elemento->getElementsByTagName('span')->item(2)->textContent);
				$vr_ds_cidade = $vr_elemento->getElementsByTagName('span')->item(4)->textContent;
				$vr_ds_estado = $vr_elemento->getElementsByTagName('span')->item(5)->textContent;
						
				// Se tem que gravar		                         
				if ($pr_flg_gravar == 1) {
			
					// Instanciar a classe de events
					$evento = new eventos($vr_bd);
									
					// Inserir o evento e salvar o retorno
					$vr_ds_retorno = $evento->insereEvento(self::HLERA_SITE, $vr_dt_evento , $vr_ds_local, $vr_ds_link, '', $vr_ds_atracoes , $vr_ds_cidade, $vr_ds_estado, 'Brasil');
			
				} 
				
				// Salvar dados do evento no Array para retorno   
				$vr_arr_eventos[] = array('evento'  => $vr_ds_atracoes,
										  'horario' => $vr_dt_evento,
										  'lugar'   => $vr_ds_local, 
										  'retorno' => $vr_ds_retorno ); 
						
			} catch (Exception $ex) {
			
				// Armazenar o erro
				$vr_ds_erro = 'Erro na conversão para XML ' . $ex->getMessage();
			
				// Instanciar a classe do log e gerar critica
				$log = new log();
				
				$log->geraLog ( self::ID_HLERA ,  $vr_ds_erro);
					
				// gerar log de erro
				continue;
			}	
							
		}
		
		// Percorrer todas as DIV
		foreach ($vr_elementos as $vr_elemento) {
		
			// Se não for da classe especificada, desconsiderar	
			if (substr($vr_elemento->getAttribute('style'),0,13) != 'padding: 5px;') {
				continue;
			}
					
			try {
			
				$vr_ds_atracoes = $vr_elemento->getElementsByTagName('strong')->item(0)->nodeValue;
				$vr_separa_dados = split("separador",$vr_elemento->getElementsByTagName('div')->item(2)->nodeValue);
				$vr_separa_dados[2] = trim($vr_separa_dados[2]);
				$vr_separa_dados[3] = trim($vr_separa_dados[3]);
				
				$vr_dt_evento = substr($vr_separa_dados[2],0,10);
				$vr_nr_dia = substr($vr_dt_evento,0,2);
				$vr_nr_mes = substr($vr_dt_evento,3,2);
				$vr_nr_ano = substr($vr_dt_evento,6,4);				
				$vr_dt_evento = $vr_nr_ano.'-'.$vr_nr_mes.'-'.$vr_nr_dia;
				
				$vr_ds_link = $vr_elemento->getElementsByTagName('a')->item(0)->getAttribute('href');
				$vr_ds_local = str_replace("'","",trim( substr($vr_separa_dados[3],0,((strpos($vr_separa_dados[3],"(") -1)))));
				$vr_ds_cidade = split("-",trim( substr($vr_separa_dados[3],(    (strpos($vr_separa_dados[3],"(") +1)     ))));
							
				$vr_cidade = trim($vr_ds_cidade[0]);
				$vr_estado = trim(substr($vr_ds_cidade[1],1,2));
				
				// Se tem que gravar		                         
				if ($pr_flg_gravar == 1) {
			
					// Instanciar a classe de events
					$evento = new eventos($vr_bd);
									
					// Inserir o evento e salvar o retorno
					$vr_ds_retorno = $evento->insereEvento(self::HLERA_SITE, $vr_dt_evento , $vr_ds_local, $vr_ds_link, '' , $vr_ds_atracoes , $vr_cidade, $vr_estado, 'Brasil');
			
				} 
						
				// Salvar dados do evento no Array para retorno   
				$vr_arr_eventos[] = array('evento'  => $vr_ds_atracoes,
										  'horario' => $vr_dt_evento,
										  'lugar'   => $vr_ds_local, 
										  'retorno' => $vr_ds_retorno ); 
						
			} catch (Exception $ex) {
			
				// Armazenar o erro
				$vr_ds_erro = 'Erro na conversão para XML ' . $ex->getMessage();
			
				// Instanciar a classe do log e gerar critica
				$log = new log();
				
				$log->geraLog ( self::ID_HLERA , $vr_ds_erro);
					
				// gerar log de erro
				continue;
				
			}	
							
		}	

		// Desconectar
		$vr_bd -> desconecta();
			
		return $vr_arr_eventos;
 	}

}


?>


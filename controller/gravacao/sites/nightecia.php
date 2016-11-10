<?php

class nightecia {

	// Nome do site
	const NIGHTECIA_SITE = 'http://www.nightecia.com.br';
	
	// Quantidade de dias para ler os eventos
	const DIAS = 10;
	
	// ID do site
	const ID_NIGHTECIA = 3;
	
	// Array contendo os eventos por dia
	private $gb_arr_eventos = array();
	
	// Array contendo as datas dos eventos
	private $gb_arr_dias = array();
	
	
	// Construtor da classe principal
	function nightecia () {
	
	}
	
	function ObtemEventosNightecia($pr_flg_gravar) {
		
		// Obter eventos do primeiro dia (dia que estiver rodando)
		return $this->obtemEventosDia(0, $pr_flg_gravar); 
	
	}
	
	function obtemEventosDia($pr_nr_i, $pr_flg_gravar) {
			
		// Se já percorreu de todos os dias determinados
		if ($pr_nr_i >= self::DIAS) {
			return json_encode($this->percorreEventos($pr_flg_gravar));
		}

		// Obter data atual
		$vr_dt_atual = $this->somarDatas(date('Y-m-d') , $pr_nr_i);
				
		// obter dia, mes e ano
		$vr_ds_dia = substr($vr_dt_atual,8,2);
  		$vr_ds_mes = substr($vr_dt_atual,5,2);
  		$vr_ds_ano = substr($vr_dt_atual,0,4);

		// Criamos um novo recurso do tipo Curl
		$vr_ch = curl_init();
				
		// Informar URL e outras funções ao CURL
		curl_setopt($vr_ch, CURLOPT_URL, self::NIGHTECIA_SITE . '/agenda/' . $vr_ds_dia . '/' . $vr_ds_mes . '/' . $vr_ds_ano );
	
		// Retornar saida ao inves de imprimir
		curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);
		
		// Acessar a URL e retornar a saída
		$vr_ds_dados= curl_exec($vr_ch);
		
		// Se houver algum erro na requisição Curl então, grava log e vai para o próximo site
		if (curl_error($vr_ch) != ''){
		
			// Armazenar o erro
			$vr_ds_erro = 'Erro na requisição CURL ' . curl_error($vr_ch);
			
			// Instanciar a classe do log e gerar critica
			$log = new log();
			
			$log->geraLog ( self::ID_NIGHTECIA ,  $vr_ds_erro);
				
			// Liberar recurso
			curl_close($vr_ch);
			
			return;
		}
								
		// Liberar recurso
		curl_close($vr_ch);
		
		// Armazenar em um array dados de determinado dia
		$this->trataDadosNight($vr_ds_dados, $vr_dt_atual);
		
		// Aumentar a sequencia do dia
		$pr_nr_i++;
		
		// Chamada recursiva deste método para o próximo dia		
    	return $this->obtemEventosDia($pr_nr_i, $pr_flg_gravar);
    	    		
	}
	
	function somarDatas($pr_dt_base, $pr_nr_dias, $pr_nr_meses = 0, $pr_nr_ano = 0) {
  		
   		$pr_dt_base = explode("-", $pr_dt_base);
   
   		$vr_dt_nova = date("Y-m-d", mktime(0, 0, 0, $pr_dt_base[1] + $pr_nr_meses, $pr_dt_base[2] + $pr_nr_dias, $pr_dt_base[0] + $pr_nr_ano) );
   
   		return $vr_dt_nova;
	
	}
	
	function trataDadosNight ($pr_ds_dados, $pr_dt_evento) {
	
		// Posicao inicial para considerar
		$vr_int_ini =  strpos($pr_ds_dados,'bg_green p10') - 12;
			
		// Retirar o indesejado
		$vr_ds_dados = substr($pr_ds_dados,$vr_int_ini);
		
		// Posicao final para considerar
		$vr_int_fim = strpos($vr_ds_dados,'text/javascript') - 15;
	
		// Atribuir somente os dados necessarios
		$vr_ds_dados  =  '<div>' . substr($vr_ds_dados, 0, $vr_int_fim) . '</div>';
		
		// Substituir caracteres indesejados
		$vr_ds_dados  = str_replace("&", "e_comercial", $vr_ds_dados);
		$vr_ds_dados  = str_replace("nbsp;", "",$vr_ds_dados);
		$vr_ds_dados  = str_replace("nowrap", "",$vr_ds_dados);
		$vr_ds_dados  = str_replace("<A", "<a", $vr_ds_dados);
		$vr_ds_dados  = str_replace("/A", "/a", $vr_ds_dados);
		$vr_ds_dados  = str_replace(".png'>", ".png' ></img>", $vr_ds_dados);
		$vr_ds_dados  = str_replace(".gif'>", ".gif' ></img>", $vr_ds_dados);
		$vr_ds_dados  = str_replace("width='100%'", "", $vr_ds_dados);

		// Salvar os dados do evento do dia e salvar a data
		array_push($this->gb_arr_eventos, $vr_ds_dados);
		array_push($this->gb_arr_dias, $pr_dt_evento);
	
	} 
	
	function percorreEventos ($pr_flg_gravar) {
	
		// Array para devolver os eventos
		$vr_arr_eventos = array();
		
		// Percorrer os eventos de todos os dias e converter para XML
		for ($i = 0; $i < count($this->gb_arr_eventos); $i++) {
		
			try {
				// Obter a String XML
				$vr_ds_dados = $this->gb_arr_eventos[$i];
				
				// Converter em um objeto XML				
				$vr_xml_eventos = new DomDocument;
				$vr_xml_eventos->loadXML(utf8_encode($vr_ds_dados));
				
				// Tratar os dados do XML e salvar em um array para retornar os eventos
				$vr_arr_eventos[] = $this->trataXMLNight($vr_xml_eventos, $this->gb_arr_dias[$i], $pr_flg_gravar );

			} catch (Exception $ex) {
			 
			  // Armazenar o erro
			  $vr_ds_erro = 'Erro na conversão para XML ' . $ex->getMessage();
			
			  // Instanciar a classe do log e gerar critica
			  $log = new log();
			
			  $log->geraLog ( self::ID_NIGHTECIA , $vr_ds_erro);
			  
			  // Proximo XML	  
			  continue; 
			}
		}	
		return $vr_arr_eventos;		
	}
	
	
	function trataXMLNight($pr_xml_eventos, $pr_dt_evento, $pr_flg_gravar) {
	
		// Array para devolver os eventos
		$vr_arr_eventos = array();
		
		$vr_bd = new bd(false);
		$vr_bd -> conecta();
	
		// Filtrar as DIV		
		$vr_elementos = $pr_xml_eventos->getElementsByTagName('div');

		// Percorrer todas as DIV
		foreach ($vr_elementos as $vr_elemento) {
        
        	// Se não for da classe especificada, desconsiderar	
    		if ($vr_elemento->getAttribute('class') != 'bg_green p10') {
    			continue;
    		}
    		
    		// Obter o nome da cidade
    		$vr_ds_cidade = trim($vr_elemento->getElementsByTagName('h1')->item(0)->nodeValue);
    		
    		// Obter novos elementos (eventos da cidade)
    		$vr_new_elementos = $vr_elemento->getElementsByTagName('tr');
    		
    		// Percorrer todos os eventos da cidade
    		foreach($vr_new_elementos as $vr_new_elemento) {
    		
    			try {
    			
    				if ($vr_new_elemento->getAttribute('onmouseover') != "this.style.background='#FFFFFF';" ) {
    				  continue;
    				}
    				
    				// Salvar as informações do evento
    				$vr_ds_estado  = 'SC';
					$vr_ds_info    = explode("|", $vr_new_elemento->getElementsByTagName('span')->item(0)->nodeValue);
					$vr_ds_local   = trim($vr_ds_info[0]);
					$vr_ds_atracao = trim($vr_ds_info[1]);
					$vr_ds_link    = '';
					
					$vr_obj_atracao = $vr_new_elemento->getElementsByTagName('div')->item(1);
					
					if ($vr_obj_atracao->getAttribute('class') == "f12 green" && trim($vr_obj_atracao->nodeValue != "")) {
						$vr_ds_atracao = trim($vr_new_elemento->getElementsByTagName('div')->item(1)->nodeValue);
					}

					if ($vr_ds_atracao == "") {
						$vr_ds_atracao = trim($vr_new_elemento->getElementsByTagName('div')->item(2)->nodeValue);
					}
					
					// Se tem que gravar		                         
			    	if ($pr_flg_gravar == 1) {
			    
			    		// Instanciar a classe de events
						$evento = new eventos($vr_bd);
									    	
			    		// Inserir o evento e salvar o retorno
			    		$vr_ds_retorno = $evento->insereEvento(self::NIGHTECIA_SITE, $pr_dt_evento , $vr_ds_local, $vr_ds_link, '' , $vr_ds_atracao , $vr_ds_cidade, $vr_ds_estado, 'Brasil');
			    
			    	}  
			
					// Salvar dados do evento no Array para retorno   
			    	$vr_arr_eventos[] = array('evento'  => $vr_ds_atracao,
			        	   			          'horario' => $pr_dt_evento,
			            	        	      'lugar'   => $vr_ds_local, 
			                	    	      'retorno' => $vr_ds_retorno ); 
    					
    				} catch (Exception $ex) {
    					
    					// Armazenar o erro
			 			$vr_ds_erro = 'Erro em evento individual ' . $ex->getMessage();
			
			 		    // Instanciar a classe do log e gerar critica
			  		    $log = new log();
			
			 			$log->geraLog ( self::ID_NIGHTECIA , $vr_ds_erro);
    					
    					continue;
    				}	
    		} 		
		}
		
		// Desconectar
		$vr_bd -> desconecta();
		
		return $vr_arr_eventos;
 	}
 	
}

?>
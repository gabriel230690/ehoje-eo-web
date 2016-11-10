<?php

class blueticket {

	// Nome do site
	const BLUETICKET_SITE = 'http://www.blueticket.com.br';
	
	// ID do site
	const ID_BLUETICKET = 2;
		
	// Construtor da classe principal
	function blueticket () {
	
	}
	
	function obtemEventos($pr_flg_gravar) {
			
		// Criamos um novo recurso do tipo Curl
		$vr_ch = curl_init();
		
		// Informar URL e outras funções ao CURL
		curl_setopt($vr_ch, CURLOPT_URL, self::BLUETICKET_SITE . '/?secao=Eventos&tipo=6&regiao_atual=SC' );
	
		// Retornar saida ao inves de imprimir
		curl_setopt($vr_ch, CURLOPT_RETURNTRANSFER, true);
		
		// Acessar a URL e retornar a saída
		$vr_ds_dados = curl_exec($vr_ch);
		
		// Se houver algum erro na requisição Curl então, grava log e vai para o próximo site
		if (curl_error($vr_ch) != ''){
		
			// Armazenar o erro
			$vr_ds_erro = 'Erro na requisição CURL ' . curl_error($vr_ch);
			
			// Instanciar a classe do log e gerar critica
			$log = new log();
			
			$log->geraLog ( self::ID_BLUETICKET , $vr_ds_erro);
				
			// Liberar recurso
			curl_close($vr_ch);
			
			return;
		}
								
		// Liberar recurso
		curl_close($vr_ch);
		
		// Tratar os dados e retornar
		return json_encode($this->trataDadosNight($vr_ds_dados, $pr_flg_gravar));
    	    		
	}
		
	function trataDadosNight ($pr_ds_dados, $pr_flg_gravar) {
	
		// Posicao inicial para considerar
		$vr_int_ini =  strripos($pr_ds_dados,'lista_eventos') - 12;
			
		// Retirar o indesejado
		$vr_ds_dados = substr($pr_ds_dados,$vr_int_ini);
		
		// Posicao final para considerar
		$vr_int_fim = strpos($vr_ds_dados,'padding-top:20px;padding-bottom:4px') - 12;
	
		// Nao achou OUTROS EVENTOS DA REGIAO			
		if ($vr_int_fim < 0) { 
			$vr_int_fim = strpos($vr_ds_dados,"outros_eventos") - 12;
			$vr_ds_dados = substr($vr_ds_dados,0,$vr_int_fim);
		} else {
			$vr_ds_dados = substr($vr_ds_dados,0,$vr_int_fim) . "</div>";
		}
		
		// Substituir caracteres indesejados
		$vr_ds_dados = str_replace("Dj's", "Dj s", $vr_ds_dados);
		$vr_ds_dados = str_replace("fb:", "a", $vr_ds_dados);
		$vr_ds_dados = str_replace("border=0", "border='0'", $vr_ds_dados);
		$vr_ds_dados = str_replace("src=http", "src='http", $vr_ds_dados);
		$vr_ds_dados = str_replace("jpg alt", "jpg' alt", $vr_ds_dados);
		$vr_ds_dados = str_replace("280", "''", $vr_ds_dados);
		$vr_ds_dados = str_replace("false", "''", $vr_ds_dados);
		$vr_ds_dados = str_replace("button_count", "''", $vr_ds_dados);
		$vr_ds_dados = str_replace("></span>", "> </span> <a> ", $vr_ds_dados);
		$vr_ds_dados = str_replace("solid'>", "solid' />", $vr_ds_dados);
		$vr_ds_dados = str_replace("style=''>", "style='' />", $vr_ds_dados);
		$vr_ds_dados = str_replace("#", "", $vr_ds_dados);
		$vr_ds_dados = str_replace("?&var_fb=1&f=6666", "", $vr_ds_dados);
		$vr_ds_dados = str_replace("<br>", "", $vr_ds_dados);
		$vr_ds_dados = str_replace("&", "e_comercial", $vr_ds_dados);
		$vr_ds_dados = str_replace("L'arc", "L arc", $vr_ds_dados);
		
		// Converter os dados em um XML e fazer o tratamento dos mesmos
		return $this->converteDados($vr_ds_dados, $pr_flg_gravar);
		
	} 
	
	function converteDados ($pr_ds_dados, $pr_flg_gravar) {
	
		try {				
			// Converter em um objeto XML				
			$vr_xml_eventos = new DomDocument;
			$vr_xml_eventos->loadXML(utf8_encode($pr_ds_dados));
				
			// Tratar os dados do XML e salvar em um array para retornar os eventos
			return $this->trataXMLBlueTicket($vr_xml_eventos, $pr_flg_gravar );

		} catch (Exception $ex) {
			// Armazenar o erro
			$vr_ds_erro = 'Erro na conversão para XML ' . $ex->getMessage();
			
			// Instanciar a classe do log e gerar critica
			$log = new log();
			
			$log->geraLog ( self::ID_BLUETICKET ,  $vr_ds_erro);
			
			return '';
		}
					
	}
	
	
	function trataXMLBlueTicket($pr_xml_eventos, $pr_flg_gravar) {
	
		// Array para devolver os eventos
		$vr_arr_eventos = array();

		// Filtrar as DIV		
		$vr_elementos = $pr_xml_eventos->getElementsByTagName('div');
		
		// Percorrer todas as DIV
		foreach ($vr_elementos as $vr_elemento) {
        
        	// Se não for da classe especificada, desconsiderar	
    		if ($vr_elemento->getAttribute('class') != 'item_evento item_evento_1') {
    			continue;
    		}
    		
    		try {
    			
    			// Obter os elementos XML necessarios para a gravação dos dados	
    			$vr_dados_xml   = $vr_elemento->getElementsByTagName('a');
				$vr_dados_2_xml = $vr_elemento->getElementsByTagName('img');
				$vr_dados_3_xml = explode("|",$vr_elemento->getElementsByTagName('span')->item(1)->nodeValue);
				$vr_dados_4_xml = explode(",",$vr_elemento->getElementsByTagName('span')->item(2)->nodeValue);

				// Dados do evento
				$vr_ds_link      = self::BLUETICKET_SITE . $vr_dados_xml->item(0)->getAttribute("href");
				$vr_ds_atracao   = explode("-",$vr_dados_2_xml->item(0)->getAttribute('alt'));
				$vr_ds_atracao   = trim($vr_ds_atracao[0]);
				$vr_ds_local     = trim($vr_dados_3_xml[0]);
				$vr_arr_endereco = explode("-",$vr_dados_3_xml[1]);;
				$vr_ds_cidade    = trim($vr_arr_endereco[0]);
				$vr_ds_estado    = trim($vr_arr_endereco[1]);
				
				// Tratar data do evento
				$vr_ds_data  = $vr_dados_4_xml[1];
				$vr_arr_data = explode("de", $vr_ds_data);		
				$vr_nr_dia = trim($vr_arr_data[0]);
				$vr_ds_mes = trim($vr_arr_data[1]);
				$vr_nr_ano = trim($vr_arr_data[2]);
				
				// Montar a data para inserção no banco
				$data = new data();
				$vr_dt_evento = $data->montaData($vr_nr_dia, $vr_ds_mes, $vr_nr_ano );
						
				// Se tem que gravar		                         
			    if ($pr_flg_gravar == 1) {

                    // Conectaro ao BD
                    $vr_bd = new bd(false);
                    $vr_bd -> conecta();

                    // Instanciar a classe de events
					$evento = new eventos($vr_bd);
									    	
			    	// Inserir o evento e salvar o retorno
			    	$vr_ds_retorno = $evento->insereEvento(self::BLUETICKET_SITE, $vr_dt_evento , $vr_ds_local, $vr_ds_link, '', $vr_ds_atracao , $vr_ds_cidade, $vr_ds_estado, 'Brasil' , 0);

                    // Desconectar
                    $vr_bd -> desconecta();

			    }  
			
				// Salvar dados do evento no Array para retorno   
			    $vr_arr_eventos[] = array('evento'  => $vr_ds_atracao,
			           			          'horario' => $vr_dt_evento,
			                    	      'lugar'   => $vr_ds_local, 
			                    	      'retorno' => $vr_ds_retorno ); 
    					
    		} catch (Exception $ex) {
    			// Armazenar o erro
				$vr_ds_erro = 'Erro em evento individual ' . $ex->getMessage();
			
				// Instanciar a classe do log e gerar critica
				$log = new log();
			
				$log->geraLog ( self::ID_BLUETICKET ,  $vr_ds_erro);
				
    			continue;
    		}	 		
		}

		return $vr_arr_eventos;
 	}
 	
}

?>


<?php

class eventos {

	private $bd;

	function eventos($bd) {
		$this -> bd = $bd;
	}
	
	function insereEvento($pr_ds_site , $pr_dt_evento  ,     $pr_ds_local , $pr_ds_link    ,
                          $pr_ds_banda, $pr_ds_atracao ,     $pr_ds_cidade, $pr_ds_estado  ,
                          $pr_ds_pais , $pr_id_facebook = 0, $pr_hr_evento = '00:00:00'    ,
                          $pr_ds_imgcover = '') {
		
		// Instanciar a classe Padroniza para retirar caracteres não desejados
		$padroniza = new padroniza();
		
		$pr_ds_local   = $padroniza->replaceHTML($pr_ds_local);
		$pr_ds_banda   = $padroniza->replaceHTML($pr_ds_banda);
		$pr_ds_atracao = $padroniza->replaceHTML($pr_ds_atracao);
		$pr_ds_site    = $padroniza->replaceHTML($pr_ds_site);
		$pr_ds_cidade  = $padroniza->replaceHTML($pr_ds_cidade);
		
		if ($pr_ds_banda == '' && $pr_ds_atracao == '') {
			return "Sem descrição no evento.";
		}
	
		// Instanciar a classe do Site		
		$site  = new site ($this->bd);	
			
		$vr_id_site  = $site-> trazSite ($pr_ds_site);
		
		// Instanciar a classe de log
		$log = new log();
		
		// Gerar erro pois não achou o site 
		if ($vr_id_site == -1) {
		
			// Armazenar o erro
			$vr_ds_erro = "Erro na consulta do site : " . $pr_ds_site;
			
			// Concatenar erro do MySQL
			if (mysql_error() != '') {
				$vr_ds_erro .= '. Erro -> ' . mysql_error();
			}
			
			return $vr_ds_erro;
			
		}
	
		// Trazer o ID da cidade do evento
		$vr_id_cidade = $this->trazInfoRegiao ($pr_ds_cidade, $pr_ds_estado, $pr_ds_pais );
		
		// Gerar erro pois não existia a cidade		
		if ($vr_id_cidade == -1) {
			
			// Armazenar o erro
			$vr_ds_erro = "Cidade não cadastrada : " . $pr_ds_cidade;
			
			// Concatenar erro do MySQL
			if (mysql_error() != '') {
				$vr_ds_erro .= '. Erro -> ' . mysql_error();
			}   
			
			// Gerar o log para salvar o erro
			$log-> geraLog ($vr_id_site,  $vr_ds_erro);
			
			return $vr_ds_erro;
			
		}		
						
		// Instanciar classe do local
		$local = new local ($this ->bd);	
		
		// Trazer o ID do local e da cidade	
		$vr_infos = $local->trazLocal($pr_ds_local, $vr_id_cidade, $vr_id_site);
		$vr_id_local    = $vr_infos["idLocal"];
		$vr_id_cidade   = $vr_infos["idCidade"];		
	
		// Gerar erro se não achou o local
		if ($vr_id_local < 0) {
		
			$vr_ds_erro = $vr_infos["dsSituacao"]; 
						
			// Salvar a sugestão do local
			if ($vr_id_local == -1) {
				
				// Gerar o log para salvar o erro
				$log-> gravaSugestaoLocal ( $pr_ds_local );
				
			}
			
			return $vr_ds_erro;
			
		}
							
		// Verificar se ja existe este evento para esta cidade, data e local		
		$vr_flg_existe = $this->verificaEvento( $vr_id_cidade , $pr_dt_evento , $vr_id_local );
		
		// Se já existir, desconsiderar este evento
		if ($vr_flg_existe == 1) {
			
			$vr_ds_erro = "Evento já criado.";
								
			return $vr_ds_erro;
							
		}
		
		// Instanciar a classe do facebook
		$facebook = new facebook();
		
		// Trazer imagem do evento
		if  ($pr_id_facebook != 0 && $pr_ds_imgcover == '')  {
			$pr_ds_imgcover = $facebook->trazImgPageCover($pr_id_facebook);
		}	
					
		// Obter data e hora
		setlocale(LC_TIME, 'pt_BR', 'ptb');
		$vr_dt_criacao = date("Y-m-d");
		$vr_hr_criacao = date("H:i:s");
		
		// Setar data
		$pr_dt_evento = date('Y-m-d', strtotime($pr_dt_evento));
		
		// Se for funplace, obter o ID do Facebook
		if ($vr_id_site == 7 && $pr_ds_link != '') {
		
			$funplace = new funplace();
		
			$pr_id_facebook = $funplace->trazIdFacebook($pr_ds_link);
		}
	
		// Inserir registro do evento		
		$vr_tabela  = "evento";
		$vr_campos  = "dtEvento , hrEvento, dsLink, dsAtracao, idSite , dtCriacao, hrCriacao, idLocal, idFacebook, dsImgCover";

        $vr_valores = "'$pr_dt_evento' , '$pr_hr_evento' , '$pr_ds_link' , '$pr_ds_atracao' , $vr_id_site , '$vr_dt_criacao' , '$vr_hr_criacao', $vr_id_local, $pr_id_facebook, '$pr_ds_imgcover' ";

        $vr_id_evento = $this -> bd -> incluiRegistro($vr_tabela, $vr_campos, $vr_valores);

		return ($vr_id_evento > 0);
	
	}
	
	function trazInfoRegiao ($pr_ds_cidade, $pr_ds_estado, $pr_ds_pais ) {
	
		// Se foi enviada a cidade
		if ($pr_ds_cidade != "") {
		
			$pr_ds_estado = trim ($pr_ds_estado);
		
			// Instanciar classe do Endereco
			$endereco = new endereco($this -> bd);

			// Buscar pais, estado e cidade
			$vr_id_pais   = $endereco -> buscaPais($pr_ds_pais, "true");
			$vr_id_cidade = $endereco -> buscaCidade($vr_id_pais, $pr_ds_cidade, "true");
						
		} else {
		  // Se não mandou a cidade, pegar do local
		  $vr_id_cidade = 0; 
		}
		
		// Retornar o ID da cidade
		return $vr_id_cidade;
		
	}
 	
	function verificaEvento ($pr_id_cidade , $pr_dt_evento , $pr_id_local ) {
	
		try {

			// Verifica se tal evento ja esta cadastrado
			$vr_sql = "SELECT 1 " . 
				     	"FROM evento e "  .
					        ",local  l "  .
			 		   "WHERE e.idLocal  = l.idLocal " .
			 	    	 "AND l.idCidade = $pr_id_cidade " .
				   	     "AND e.dtEvento = '$pr_dt_evento' "  .
				   	     "AND l.idLocal  = $pr_id_local";
				  	
		    $vr_results = mysql_query($vr_sql);
		    
		    // Se deu erro na consulta, considerar	que o evento foi criado  	     
			if (!$vr_results) {
				return 1;
			}

			// Se encontrou o evento
			if ($vr_row = mysql_fetch_assoc($vr_results)) {
				return 1;
			}
			
		} catch (Exception $e) {
			// Se der erro, é melhor que considere como criado
			return 1;
		}

		return 0;
	}
		
	function carregaEventos($pr_ds_cidade , $pr_id_local, $pr_dt_inicio , $pr_dt_fim , $pr_nr_distancia,  $pr_flg_hoje) {

		$endereco = new endereco($this -> bd);
		$vr_id_cidade = $endereco -> buscaCidade( 1, $pr_ds_cidade, "false");
		

		// Se nao achou a cidade, verificar com o nome do local
		if ($vr_id_cidade == -1) {
		
		  // Instanciar classe do local
		  $local = new local ($this ->bd);
		
		  // Obter o Local e Cidade
		  $vr_infos = $local->trazLocal($pr_ds_cidade, -1, -1);
		  $vr_id_local = $vr_infos["idLocal"];
		  $vr_id_cidade = $vr_infos["idCidade"];
		  $vr_nm_cidade = $vr_infos["nmCidade"];
		  
		  // Se nao achou o local, considerar eventos de Blumenau
		  if ($vr_id_local == -1 || $pr_ds_cidade == "") {
		    $vr_id_cidade = 14;
		    $vr_nm_cidade = "Blumenau";
		  } else {
		    // Senao trazer somente os eventos desse local
		    $pr_id_local = $vr_id_local; 
		    $pr_nr_distancia = 0; 
		  }
		
		} else {
		  $vr_nm_cidade = $pr_ds_cidade;
		}

		
		// Se a distnacia foi informada
		if ($pr_nr_distancia > 0) {
		
			$distancia = new distancia ($this->bd);
			
			// Buscar as cidades vizinhas com base na distancia informada
			$vr_ds_cidades = $distancia->cidadeVizinhas($vr_id_cidade , $pr_nr_distancia, false);	
		
		}
		else {
			// So buscar da cidade em questao
			$vr_ds_cidades = "(" . $vr_id_cidade . ")";
		}
				
		// Trazer eventos de todas as cidades pela distancia informada		
		$vr_rows = $this->trazEventosCidades($vr_id_cidade, $vr_nm_cidade ,$pr_id_local, $vr_ds_cidades , $pr_dt_inicio, $pr_dt_fim, $pr_flg_hoje);

		return json_encode($vr_rows);
	}
	
	function trazEventosCidades ($pr_id_cidade, $pr_ds_cidade, $pr_id_local, $pr_ds_cidades, $pr_dt_inicio , $pr_dt_fim, $pr_flg_hoje ) {
	
		$sql_local = ($pr_id_local != "") ? "AND e.idLocal = $pr_id_local " : "";
		$sql_group = ($pr_flg_hoje ) ? " GROUP BY l.idLocal " : "";
		
		$sql = "SELECT e.idEvento, e.dtEvento, e.hrEvento, e.dsLink, e.dsAtracao, e.idFacebook, l.dsSite, l.dsImgProfile, " .
		          	  "l.idLocal, l.dsLocal, l.dsTelefone, l.dsEndereco, l.nrLatitude, l.nrLongitude, l.id_instagram, " .
		          	  "c.nmCidade, "                       .
		          	  "es.nmEstado, "                      .
		          	  "c.idCidade, "                       .
		          	  "e.hrCriacao, "                      .
		          	  "e.dtCriacao, "                      .
		          	  "e.idPrioridade "                    .
		         "FROM evento e "                          .
		             ",local  l "                          . 
		             ",cidade c "                          .
		             ",estado es "                         . 
		             ",distancia d "                       .
		        "WHERE e.idLocal  = l.idLocal "            .
		          			$sql_local                     .
		          "AND l.idCidade IN $pr_ds_cidades "   . 
		          "AND l.idCidade = c.idCidade "        .
		          "AND d.idCidOrigem = $pr_id_cidade "  .
		          "AND d.idCidDestino = l.idCidade "    .
		          "AND es.idEstado = c.idEstado "       .
 		          "AND e.dtEvento >= '$pr_dt_inicio' "  .
		          "AND e.dtEvento <= '$pr_dt_fim' "     .
		       			   $sql_group                   .
				"ORDER BY e.idPrioridade desc "         .
				        ",e.dtEvento "                  .
				        ",d.nrDistancia";

		$vr_results = mysql_query($sql);
		
		$vr_rows = array();
		
		$pr_ds_cidade = ($pr_ds_cidade == -1) ? "Blumenau" : $pr_ds_cidade;
		
		$vr_rows[] = array('cidade' => $pr_ds_cidade );
		
		while ($vr_row = mysql_fetch_assoc($vr_results)) {
		
			$vr_ds_imgProfile  =  $vr_row['dsImgProfile'];
			$vr_dt_evento      =  $this->diaSemana($vr_row['dtEvento']) . ", " . $vr_row['dtEvento'] ;
			$vr_ds_link        = ($vr_row['dsSite'] != "") ? $vr_row['dsSite'] : $vr_row['dsLink'];
 			 				
			$vr_rows[] = array('id'           => $vr_row['idEvento'],
			                   'local'        => $vr_row['dsLocal'], 
							   'idLocal'      => $vr_row['idLocal'],
							   'latitude'     => $vr_row['nrLatitude'],
							   'longitude'    => $vr_row['nrLongitude'],
							   'dsImgProfile' => $vr_ds_imgProfile,
							   'data'         => $vr_dt_evento, 
							   'hora'         => $vr_row['hrEvento'],
							   'link'         => $vr_ds_link,
							   'banda'        => $vr_row[''], 
							   'atracao'      => str_replace("\n", ' ', $vr_row['dsAtracao']), 
							   'cidade'       => $vr_row["nmCidade"],
                               'facebook'     => $vr_row["idFacebook"],
							   'estado'       => $vr_row["nmEstado"],
							   'telefone'     => $vr_row["dsTelefone"],
							   'endereco'     => $vr_row["dsEndereco"],
							   'instagram'    => $vr_row["id_instagram"],
							   'idCidade'     => $vr_row["idCidade"],
							   'dtCriacao'    => $vr_row["dtCriacao"],
							   'hrCriacao'    => $vr_row["hrCriacao"],
							   'idPrioridade' => $vr_row["idPrioridade"]);
		}
		
		// Se foi informado o local e não encontrou eventos do mesmo, retornar somente as informações deste
		if ($pr_id_local != "" && count($vr_rows) == 1) {
			
			$local = new local ($this->bd);
			$vr_rows[] = $local->retornaLocal($pr_id_local);
			
		}
	
		return $vr_rows;
	
	}
	
	function trazEventosFacebook () {
	
		// Obter a data atual e a maxima
		setlocale(LC_TIME, "pt_BR", "ptb");
	
		$vr_dt_atual = date("Y-m-d");
        $vr_dt_maxim = date("Y-m-d", strtotime("+7 days"));

		// Ler os eventos futuros com ID do Facebook 
		$vr_sql = "SELECT e.idEvento, e.idFacebook " .
		            "FROM evento e " .
		           "WHERE e.dtEvento >= '$vr_dt_atual' " .
                     "AND e.dtEvento <= '$vr_dt_maxim' " .
		             "AND e.idFacebook > 0 " .
		           "ORDER BY RAND() LIMIT 10";
		             
		$vr_results = mysql_query($vr_sql);
		
		$vr_rows = array();  
		
		// Percorrer os eventos e retornar o Array dos ID
		while ($vr_row = mysql_fetch_assoc($vr_results)) {

			$vr_rows[] = array('idEvento'   => $vr_row['idEvento'],
			                   'idFacebook' => $vr_row['idFacebook']);
			        
		}   
		
		return $vr_rows;        
	
	}
	
	function buscaEvento ($pr_id_evento) {
	
		$vr_sql = "SELECT e.idFacebook, e.dsAtracao, l.dsLocal, e.dtEvento, e.hrEvento, e.qtConfirmados, e.qtHomens, " .
		                 "e.qtMulheres, e.pcHomens, e.pcMulheres, e.dsImgCover dsImgCoverEve, l.dsImgCover dsImgCoverLoc " . 
		           "FROM evento e, local l " .
		          "WHERE e.idLocal = l.idLocal " .
		            "AND e.idEvento = $pr_id_evento"; 
		          
		$vr_results = mysql_query($vr_sql);
		          		
		$vr_row = mysql_fetch_assoc($vr_results);
		
		$vr_rows = array(); 
		
		$vr_dt_evento   =  $this->diaSemana($vr_row['dtEvento']) . ", " . $vr_row['dtEvento'];
		$vr_ds_imgCover = ($vr_row['dsImgCoverEve'] == null) ? $vr_row['dsImgCoverLoc'] : $vr_row['dsImgCoverEve'];
		$vr_hr_evento   = ($vr_row['hrEvento'] == '00:00:00') ? null :  substr($vr_row['hrEvento'],0,5) ;
		
		$vr_rows[] = array('idFacebook'    => $vr_row['idFacebook'], 
		                   'dsAtracao'     => $vr_row['dsAtracao'],
		                   'dsLocal'       => $vr_row['dsLocal'],
		                   'dtEvento'      => $vr_dt_evento,
		                   'hrEvento'      => $vr_hr_evento,
		                   'qtConfirmados' => $vr_row['qtConfirmados'],
		                   'qtHomens'      => $vr_row['qtHomens'],
		                   'qtMulheres'    => $vr_row['qtMulheres'],
		                   'pcHomens'      => $vr_row['pcHomens'],
		                   'pcMulheres'    => $vr_row['pcMulheres'],
		                   'dsImgCover'    => $vr_ds_imgCover
		                   );
		
		return json_encode($vr_rows);         
		          	
	}
	
	// Traz o dia da semana para qualquer data informada
	function diaSemana($pr_dt_evento) {  
		
		$vr_nr_dia = substr($pr_dt_evento,8,2); 
		$vr_nr_mes = substr($pr_dt_evento,5,2); 
		$vr_nr_ano = substr($pr_dt_evento,0,4);
		$vr_ds_dia = date("w", mktime(0,0,0,$vr_nr_mes,$vr_nr_dia,$vr_nr_ano) );
		
		switch($vr_ds_dia){  
				case "0": $vr_ds_dia = "Domingo";	    break;  
				case "1": $vr_ds_dia = "Segunda-Feira"; break;  
				case "2": $vr_ds_dia = "Terça-Feira";   break;  
				case "3": $vr_ds_dia = "Quarta-Feira";  break;  
				case "4": $vr_ds_dia = "Quinta-Feira";  break;  
				case "5": $vr_ds_dia = "Sexta-Feira";   break;  
				case "6": $vr_ds_dia = "Sábado";		break;  
		}
		return $vr_ds_dia;
	}
	
	function retiraCaracteres () {
	
		setlocale(LC_TIME, 'pt_BR', 'ptb');
	
		$vr_dt_atual = date("Y-m-d");
	
		// Ler os eventos criados nesta data
		$vr_sql = "SELECT e.idEvento, e.dsAtracao " .
		            "FROM evento e " .
		           "WHERE e.dtEvento >= '$vr_dt_atual' ";
		             
		$vr_results = mysql_query($vr_sql);

		$padroniza = new padroniza();
		
		// Percorrer os eventos e retornar o Array dos ID
		while ($vr_row = mysql_fetch_assoc($vr_results)) {
		 			 				
			$vr_id_evento  = $vr_row['idEvento'];
			$vr_ds_atracao = $padroniza->replaceHTML($vr_row['dsAtracao']);
			
			// Atualizar registro
			$vr_tabela          = "evento";
			$vr_campos_valores  = "dsAtracao = '$vr_ds_atracao' ";
			$vr_filtro          = "idEvento";
			$vr_valor           = $vr_id_evento;
							
			$this->bd-> editaRegistro($vr_tabela, $vr_campos_valores, $vr_filtro, $vr_valor);	
			        
		} 
		
	}
	
}

?>
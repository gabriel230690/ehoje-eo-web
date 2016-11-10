<?php

class local {

	private $bd;

	// Construtor principal da classe
	function local ($pr_bd) {
		$this->bd = $pr_bd;
	}
	
	/*
	 * Trazer o local pelo ID do mesmo
	 */ 
	function retornaLocal ($pr_id_local) {
	
		$vr_sql = "SELECT l.dsLocal, l.dsEndereco, l.dsTelefone, l.id_instagram, l.dsImgProfile, l.dsAbertura, l.dsSite, " . 
		               "  c.nmCidade, e.nmEstado " .
		            "FROM local  l, " . 
		                 "cidade c, " .
		                 "estado e  " .
 		           "WHERE l.idLocal  = $pr_id_local " . 
 		             "AND c.idCidade = l.idCidade "   . 
 		             "AND c.idEstado = e.idEstado";
		           
		$vr_results = mysql_query($vr_sql); 
		
		// Instanciar a classe do Facebook
		$facebook = new facebook(); 
		
		// Obter a data atual
		setlocale(LC_TIME, 'pt_BR', 'ptb');
		$vr_dt_atual = date("Y-m-d");
		
		// Instanciar a classe de eventos para obter o dia completo
		$eventos = new eventos ($this->bd);
		$vr_dt_atual = $eventos->diaSemana($vr_dt_atual) . ", " . $vr_dt_atual ;
		
		// Se encontrou o local
		if ($vr_row = mysql_fetch_assoc($vr_results)) {
			return array('local'        => $vr_row['dsLocal'],
						 'endereco'     => $vr_row['dsEndereco'],
			             'telefone'     => $vr_row['dsTelefone'],
			             'instagram'    => $vr_row['id_instagram'],
			             'dsImgProfile' => $vr_row['dsImgProfile'],
			             'data'         => $vr_dt_atual,
			             'atracao'      => 'Abre às ' . $facebook->retornaHorarioHoje($vr_row['dsAbertura']),
			             'cidade'       => $vr_row["nmCidade"], 
						 'estado'       => $vr_row["nmEstado"],
						 'link'         => $vr_row['dsSite']);
		}   

	}
	
	/* 
	 * Trazer o local pela descricão dele, assim como também as informações da cidade.
	 * Se ele não achar, incluir o mesmo
	 */
	function trazLocal ($pr_ds_local, $pr_id_cidade, $pr_id_site) {
	
		$vr_sql = "SELECT l.idLocal, l.dsImgProfile, l.idCidade, c.nmCidade " .
			      "FROM local l "  .
			          ",cidade c " .
			      "WHERE l.idCidade = c.idCidade " .
			        "AND l.dsLocal = '$pr_ds_local'"; 
			    
		$vr_results = mysql_query($vr_sql);
		
		// Se deu erro na consulta, considerar site 9
		if (!$vr_results) {
			return array('idLocal'    => -1,
						 'dsSituacao' => mysql_error(),
			             'idCidade'   => -1,
			             'nmCidade'   => -1);
		}

		// Se encontrou o local
		if ($vr_row = mysql_fetch_assoc($vr_results)) {
		
			// Verificar se a imagem esta cadastrada, senao retorna -1
			if  ($vr_row["dsImgProfile"] == '' ) {
				return array('idLocal'    => -2,
							 'dsSituacao' => 'Faltam informações no cadastro do local',
			                 'idCidade'   => -1,
			                 'nmCidade'   => -1);
			}
			
			// Retornar um array contendo o local e as informações da cidade
			return array('idLocal'    => $vr_row["idLocal"],
			             'idCidade'   => $vr_row["idCidade"],
			             'nmCidade'   => $vr_row["nmCidade"]);

		}	
		else {
			
			// Verifica se este local deve ser ignorado
			$vr_flg_ignora = $this->verificaSeIgnora($pr_ds_local);

			// Se enviou de site desconhecido ou deve ser ignorado, não incluir o local
			if ($pr_id_site == -1 || $vr_flg_ignora == 1) {
				return array('idLocal'    => -3,
							 'dsSituacao' => 'Desconsiderado este local',
			                 'idCidade'   => -1,
			                 'nmCidade'   => -1);
			}
			
			// Verifica se tem algum local com o nome alternativo
			$vr_infos = $this->verificaNomesLocal($pr_ds_local);	
			$vr_id_local  = $vr_infos["idLocal"];
			$vr_id_cidade = $vr_infos["idCidade"];
			$vr_nm_cidade = $vr_infos["nmCidade"];
					
			// Se não achou o local e foi enviada a cidade, cadastrar a sugestao do local
			if ($vr_id_local == -1 && $pr_id_cidade != 0 ) {
			
				return array('idLocal'    => -1 , 
				             'dsSituacao' => 'Inserida a sugestao do local.',
							 'idCidade'   => $pr_id_cidade );
				
			}
			
			// Se não achou o local, grava sugestao
			if ($vr_id_local == -1)  {
				// Retornar um array contendo o local e as informações da cidade
				return array('idLocal'    => -1,
							 'dsSituacao' => 'Inserida sugestão, faltava o local ou cidade.',
			        	     'idCidade'   => 999999); // Cidade desconhecida
			}
			
			// Retornar um array contendo o local e as informações da cidade
			return array('idLocal'  => $vr_id_local,
			             'idCidade' => $vr_id_cidade,
			             'nmCidade' => $vr_nm_cidade);
		
		}	
	}
	
	/*
	 * Verificar e retornar 1 se o local deve ser ignorado, 0 caso contrario
	 */
	function verificaSeIgnora ($pr_ds_local) {
	
		$vr_sql = "SELECT i.dsLocal " .
		            "FROM ignora_local i " .
		           "WHERE i.dsLocal = '$pr_ds_local'";
		        
		$vr_results = mysql_query($vr_sql);
		
		// Se deu erro na consulta, não criar
		if (!$vr_results) {
			return 1;
		}
				
		// Se achou então desconsiderar
		if ( $vr_row = mysql_fetch_assoc($vr_results) ) {
			return 1;
		}
		
		return 0;
		           	
	}
	
	/*
	 * Verifica pelo nome na tabela de nomes alternativos dos locais. Se achar retornar os dados do mesmo.
	 * Retorna tudo -1 caso contrario.
	 */	
	function verificaNomesLocal ($pr_ds_local) {
	
		// Verifica se tem algum local com o nome alternativo
		$vr_sql = "SELECT n.idLocal, l.idCidade, c.nmCidade " . 
		            "FROM nome_local as n " . 
		                ",cidade c " . 
		                ",local l " .
		           "WHERE n.idLocal = l.idLocal " .
		             "AND l.idCidade = c.idCidade " . 
		             "AND n.dsNome = '$pr_ds_local'";
		           
		$vr_results = mysql_query($vr_sql);
		
		// Se deu erro na consulta, considerar site 9
		if (!$vr_results) {
			return -1;
		}
		
		// Se encontrou o local
		if ($vr_row = mysql_fetch_assoc($vr_results)) {
		
			// Retornar um array contendo o identificador do local, e informações da cidade
			return array('idLocal'  => $vr_row["idLocal"],
			             'idCidade' => $vr_row["idCidade"],
			             'nmCidade' => $vr_row["nmCidade"]);
		
		} else {
			// Não achou o local
	        return array('idLocal'  => -1,
			             'idCidade' => -1,
			             'nmCidade' => -1);
		}
		
	}
	
	/*
 	 * Retornar os eventos do dia atual + 2 (Tela inicial do App)
	 */
	function carregaLocaisHoje ($pr_nm_cidade, $pr_dt_inicio, $pr_dt_fim, $pr_nr_distancia) {
	
		$eventos = new eventos($this->bd);
		
		$vr_rows = $eventos->carregaEventos( $pr_nm_cidade , "" , $pr_dt_inicio , $pr_dt_fim , $pr_nr_distancia, true);
		
		return $vr_rows;

	}
	
	/*
	 * Retornar 30 locais aleatorios que possuam o cadastro da fanpage
	 */ 
	function carregaFanpages () {
	
		$vr_sql = "SELECT l.idLocal, l.id_fanpage " .
			       "FROM local l "  .
			      "WHERE l.id_fanpage != 0 " .
			      "ORDER BY l.dtLeitura LIMIT 30";
			    
		$vr_results = mysql_query($vr_sql);
		
		$vr_rows = array();

		// Obter data e hora deste momento
        setlocale(LC_TIME, 'pt_BR', 'ptb');
        $vr_dt_lei = date("Y-m-d H:i:s");
				
		while ($vr_row = mysql_fetch_assoc($vr_results)) {
		
			$vr_rows[] = $vr_row['id_fanpage'];

			// Atualizar registro do local
			$vr_tabela          = "local";
			$vr_campos_valores  = "dtLeitura = '$vr_dt_lei'";
			$vr_filtro          = "idLocal";
			$vr_valor           = $vr_row['idLocal'];
				
			$this->bd-> editaRegistro($vr_tabela, $vr_campos_valores, $vr_filtro, $vr_valor);	

		
		}
		
		return $vr_rows;
	
	}
	
	/*
	 * Retornar os locais que possuem cadastro do Foursquare
	 */
	function carregaLocaisFoursquare() {
	
		$vr_sql = "SELECT l.idLocal, l.id_foursquare " .
			       "FROM local l "  .
			      "WHERE l.id_foursquare is not null";
			    
		$vr_results = mysql_query($vr_sql);
		
		$vr_rows = array();
				
		while ($vr_row = mysql_fetch_assoc($vr_results)) {
		
			$vr_rows[] = array('idLocal'      => $vr_row['idLocal'],
			                   'idFoursquare' => $vr_row['id_foursquare']);

		
		}
		
		return $vr_rows;
	
	}
	
	/*
	 * Criar um novo local com as informações recebidas.
	 */
	function criaLocalCompleto($pr_ds_nome , $pr_ds_telefone , $pr_ds_endereco, $pr_id_foursquare , $pr_id_cidade , $pr_id_instagram , $pr_id_facebook , $pr_ds_site) {
	
		$vr_sql = "SELECT 1 " .
			        "FROM local l " .
			       "WHERE l.dsLocal = '$pr_ds_nome'"; 
			    
		$vr_results = mysql_query($vr_sql);
		
		// Desconsiderar se ja existe
		if ($vr_row = mysql_fetch_assoc($vr_results)) {
			return false;
		}
		
		$vr_tabela  = "local";
		$vr_campos  = "dsLocal, dsTelefone, dsEndereco, id_foursquare, idCidade, id_instagram, id_fanpage, dsSite ";
		$vr_valores = "'$pr_ds_nome' , '$pr_ds_telefone' , '$pr_ds_endereco' , '$pr_id_foursquare' , $pr_id_cidade , $pr_id_instagram , $pr_id_facebook , '$pr_ds_site' ";
		
		// Retornar o ID do local	
		$this -> bd -> incluiRegistro($vr_tabela, $vr_campos, $vr_valores);
		
		return true;
		
	}
	
	/*
	 * Obter 30 locais aleatorios e atualizar as informações dos mesmos
	 */
	function atualizaLocais ($pr_flg_todos) {
	
		// Ler os locais e trazer 30 aleatorios
		$vr_sql = "SELECT l.dsLocal, l.idLocal, l.id_foursquare, l.id_fanpage, l.id_instagram , l.dsTelefone, " .
		                 "l.dsEndereco, l.nrLatitude , l.nrLongitude, l.dsAbertura , c.nmCidade, e.nmEstado, p.nmPais " .
         	        "FROM local l, cidade c, estado e, pais p " .
         	       "WHERE l.idCidade = c.idCidade " .
         	         "AND c.idEstado = e.idEstado " .
         	         "AND c.idPais   = p.idPais "   . 
         	         "AND e.idPais   = p.idPais "   .         	          
                	 "ORDER BY dtAtualizacao LIMIT 30";
           
		$vr_results = mysql_query($vr_sql);

        // Obter data e hora deste momento
        setlocale(LC_TIME, 'pt_BR', 'ptb');
        $vr_dt_atu = date("Y-m-d H:i:s");
				
		// Percorrer os locais retornados
		while ($vr_row = mysql_fetch_assoc($vr_results)) {
		 		
			$vr_id_fanpage    = $vr_row['id_fanpage'];
			$vr_id_foursquare = $vr_row['id_foursquare'];
			$vr_id_instagram  = $vr_row['id_instagram'];
			$vr_ds_local      = $vr_row['dsLocal'];
			$vr_ds_cidade     = $vr_row['nmCidade'];
			$vr_ds_telefone   = $vr_row['dsTelefone'];
			$vr_ds_endereco   = $vr_row['dsEndereco'];
			$vr_nr_latitude   = $vr_row['nrLatitude'];
			$vr_nr_longitude  = $vr_row['nrLongitude'];
			$vr_ds_abertura   = $vr_row['dsAbertura'];
			
			
			if ($vr_ds_cidade == "Baln. Camboriú") {
				$vr_ds_cidade = "Balneário Camboriú";
			}
		
			$vr_ds_cidade  = $vr_ds_cidade . ', ' . $vr_row['nmEstado'] . ', ' . $vr_row['nmPais'];
						
			$facebook = new facebook($this->bd);
					
			// Buscar o ID da Fanpage se não esta gravado
			if ($vr_id_fanpage == 0 || $pr_flg_todos == true) {
								
				$vr_arr_dados  = $facebook->buscaPagina($vr_ds_local ,  $vr_row['nmCidade'], $this->bd);

				$vr_id_fanpage = ($vr_id_fanpage == 0) ? $vr_arr_dados['idFanpage'] : $vr_id_fanpage;
				
				// Buscar telefone e endereçoß			
				$vr_ds_telefone  = ($vr_ds_telefone == '')    ? $vr_arr_dados['dsTelefone'] : $vr_ds_telefone;
				$vr_ds_endereco  = ($vr_ds_endereco == '')    ? $vr_arr_dados['dsEndereco'] : $vr_ds_endereco; 
				$vr_nr_latitude  = ($vr_nr_latitude == null)  ? $vr_arr_dados['latitude']   : $vr_nr_latitude;
				$vr_nr_longitude = ($vr_nr_longitude == null) ? $vr_arr_dados['longitude']  : $vr_nr_longitude;
				$vr_ds_abertura  = ($vr_ds_abertura  == null || $vr_ds_abertura  == '') ? $vr_arr_dados['dsAbertura'] : $vr_ds_abertura;
				
			}		
		
			// Obter as imagens do local (cover e profile), site e se esta aberto ou não
			if ($vr_id_fanpage != 0) {
				$vr_ds_imgCover   = $facebook->trazImgPageCover($vr_id_fanpage);
				$vr_ds_imgProfile = $facebook->trazImgPageProfile($vr_id_fanpage);
				$vr_ds_site       = 'http://facebook.com/' . $vr_id_fanpage; 
			}		
										
			// Buscar o ID do Foursquare se não esta gravado 
			if  ($vr_id_foursquare == ''  || $pr_flg_todos == true ) {
			
				// Instanciar a classe do Foursquare
				$foursquare = new foursquare();
				
				$vr_arr_dados = $foursquare->buscaInfos($vr_ds_local, $vr_ds_cidade, $this->bd);
			
				$vr_id_foursquare = ($vr_id_foursquare == "") ? $vr_arr_dados['idFoursquare'] : $vr_id_foursquare;
					
				// Se tem Foursquare
				if ($vr_id_foursquare != '') {
			
					$vr_ds_telefone = ($vr_ds_telefone == '') ? $vr_arr_dados['dsTelefone'] : $vr_ds_telefone;
					$vr_ds_endereco = ($vr_ds_endereco == '') ? $vr_arr_dados['dsEndereco'] : $vr_ds_endereco;
						
					// Se o site veio em branco do Facebook, atribuir o site que vem do Foursquare
					$vr_ds_site = ($vr_ds_site == '') ? $vr_arr_dados['dsSite'] : $vr_ds_site; 
			
					if ($vr_arr_dados['latitude'] != '') {
					
						// Armazenar a latitude e longitude de local
						$vr_nr_latitude  = $vr_arr_dados['latitude'];
						$vr_nr_longitude = $vr_arr_dados['longitude'];
					
					}

					// Instanciar classe Instagram			
					$instagram = new instagram();
				
					// Buscar o ID do Instagram
					$vr_id_instagram = $instagram->buscaID($vr_id_fanpage);
						
					// Se a imagem de perfil do local não veio do Facebook, obter a do Foursquare
					if  ($vr_ds_imgProfile == '') {				
						$vr_ds_imgProfile = $foursquare->trazImgPageProfile($vr_id_foursquare);
					}
				
				}

			}
            
			// Atualizar registro do local
			$vr_tabela          = "local";
			$vr_campos_valores  = "id_fanpage    =  $vr_id_fanpage     , dsImgCover    = '$vr_ds_imgCover' ," .
								  "dsImgProfile  = '$vr_ds_imgProfile' , dsSite        = '$vr_ds_site'     ," .
								  "id_foursquare = '$vr_id_foursquare' , dsTelefone    = '$vr_ds_telefone' ," .
								  "dsEndereco    = '$vr_ds_endereco'   , id_instagram  =  $vr_id_instagram ," .
								  "dsAbertura    = '$vr_ds_abertura'   , nrLatitude    =  $vr_nr_latitude  ," .
								  "nrLongitude   =  $vr_nr_longitude   , dtAtualizacao = '$vr_dt_atu'";
								  
			$vr_filtro          = "idLocal";
			$vr_valor           = $vr_row['idLocal'];
				
			$this->bd-> editaRegistro($vr_tabela, $vr_campos_valores, $vr_filtro, $vr_valor);
							
		}	

	}
	
	/*
	 * Retorna os locais abertos no dia de hoje em determinada região
	 */
	function trazLocaisAbertos ($pr_ds_cidade) {
	
		// Buscar o ID da Cidade
		$endereco = new endereco($this -> bd);
		$vr_id_cidade = $endereco -> buscaCidade( 1, $pr_ds_cidade, "false");
		
		// Buscar as cidades vizinhas com base na distancia de 50 KM
		$distancia = new distancia ($this->bd);			
		$vr_ds_cidades = $distancia->cidadeVizinhas($vr_id_cidade , 50 , false);

		// Ler os locais abertos 
		$vr_sql = "SELECT l.idLocal, l.dsLocal , l.dsAbertura, l.dsImgProfile, l.nrLatitude, l.nrLongitude " .
		            "FROM local l " . 
		           "WHERE l.idCidade IN $vr_ds_cidades " .
		            " AND l.dsAbertura IS NOT NULL "     .
					" AND l.dsAbertura != '' "           .
		            " AND l.nrLatitude IS NOT NULL "     .
		            " AND l.nrLongitude IS NOT NULL";
		               
		$vr_results = mysql_query($vr_sql);
		
		$vr_rows = array();
				
		$facebook = new facebook();		
				
		// Armazenar os locais
		while ($vr_row = mysql_fetch_assoc($vr_results)) {
				
			$vr_ds_abertura = $facebook->retornaHorarioHoje($vr_row['dsAbertura']);	
				
			if  ($vr_ds_abertura == '') {
				continue;
			}	
				
			$vr_rows[] = array('idLocal'    => $vr_row['idLocal']           , 
							   'dsLocal'    => $vr_row['dsLocal']           , 
							   'dsSituacao' => 'Abre às ' . $vr_ds_abertura ,
			                   'latitude'   => $vr_row['nrLatitude']        ,
			                   'longitude'  => $vr_row['nrLongitude']       , 
			                   'imagem'     => $vr_row['dsImgProfile']);
			
		}
		
		// Obter a data atual
		setlocale(LC_TIME, 'pt_BR', 'ptb');
		$vr_dt_atual = date("Y-m-d");
				
		// Trazer os eventos de hoje
		$eventos = new eventos($this->bd);
		$vr_arr_eventos = json_decode($eventos->carregaEventos($pr_ds_cidade , "" , $vr_dt_atual , $vr_dt_atual , 50, null ), true);
				
				
		// Percorrer os eventos de hoje			
		foreach ($vr_arr_eventos as $evento ) {
		
			// Desconsiderar os locais que não tem coordenadas
			if ( $evento['latitude'] == null || $evento['longitude'] == null) {
				continue;
			}

			// Desconsiderar os locais que ja estao armazenados
			$vr_flg_desconsidera = false;
			
			foreach ($vr_rows as $local) {
			
				if ($local['dsLocal'] == $evento['local']) {
					$vr_flg_desconsidera = true;
					break;
				}
			
			}
			
			if ($vr_flg_desconsidera) {			
				continue;
			}
			
			$vr_ds_situacao = ($evento['hora'] == '00:00:00') ? "Aberto hoje" : "Abre hoje às " . substr($evento['hora'],0,5);
			
			$vr_rows[] = array('idLocal'    => $evento['idLocal']  ,
							   'dsLocal'    => $evento['local']    ,  
							   'dsSituacao' => $vr_ds_situacao     ,
			                   'latitude'   => $evento['latitude'] ,
			                   'longitude'  => $evento['longitude'], 
			                   'imagem'     => $evento['dsImgProfile']);
		
		}
				
		return json_encode($vr_rows);
		
	}	

	/*
		Metodo para buscar novos locais no Foursquare
	*/

	function novosLocais ( ) {

        // Carregar 3 cidades aleatoriamente
		$endereco = new endereco($this->bd);

        $vr_arr_cidades = json_decode($endereco->carregaCidades(true));

        // Instanciar a classe do foursquare
        $foursquare = new foursquare();

        // Instanciar a classe do log
        $log = new log();

        // Percorer as cidades
        for ($i = 0; $i < count($vr_arr_cidades); $i++) {

            $vr_nr_latitude  = $vr_arr_cidades[$i]->nrLatitude;
            $vr_nr_longitude = $vr_arr_cidades[$i]->nrLongitude;

            echo "<br/> " . $vr_nr_latitude . " | " . $vr_nr_longitude;

            // Buscar os locais desta cidade
            $vr_arr_locais = $foursquare->buscaLocais($vr_nr_latitude, $vr_nr_longitude);

            // Percorre todos os locais trazidos
            foreach ($vr_arr_locais->response->venues as $vr_local) {

                $vr_ds_local = $vr_local->name;

                $vr_info = $this->trazLocal($vr_ds_local, -1 , -1);

                // Se nao existe e nao deve ser ignorado, verifica se cadastra
                if ($vr_info["idLocal"] == -1) {

                    $vr_int_ret = $log->gravaSugestaoLocal($vr_ds_local);

                    echo "  NOME " . $vr_ds_local . " | " . $vr_int_ret;
                }


            }

        }

	}
 
}

?>
<?php

class endereco {

	private $bd;

	function endereco($bd) {
		$this -> bd = $bd;
	}

	// Função encarregada de trazer as cidades, o nome do estado e do Pais
	function carregaCidades( $pr_flg_random ) {

		$vr_sql = "SELECT idCidade, nmCidade, nmEstado, nmPais, latitude, longitude " .
		            "FROM cidade c,estado e, pais p "         .
			       "WHERE c.idPais   = e.idPais   AND "       .
			             "c.idEstado = e.idEstado AND "       .
			             "e.idPais   = p.idPais ";

			// Pegar somente 5 cidades aleatoriamente (Para o Funplace)
		if ($pr_flg_random) {
			$vr_sql .= "ORDER BY RAND() LIMIT 3";
		} else {
			// Pegar todas as cidades, ordenadas por pais, estado e cidade
		    $vr_sql .= "ORDER BY p.nmPais, e.nmEstado, c.nmCidade";
		}

		$vr_results = mysql_query($vr_sql);
		$vr_rows = array();

		while ($vr_row = mysql_fetch_assoc($vr_results)) {

			$vr_id_cidade    = $vr_row['idCidade'];
			$vr_nm_cidade    = $vr_row['nmCidade'];
			$vr_nm_estado    = $vr_row['nmEstado'];
			$vr_nm_pais      = $vr_row['nmPais'];
            $vr_nr_latitude  = $vr_row['latitude'];
            $vr_nr_longitude = $vr_row["longitude"];

			$vr_rows[] = array('id'          => $vr_id_cidade  ,
                               'nmCidade'    => $vr_nm_cidade  ,
                               'nmEstado'    => $vr_nm_estado  ,
                               'nmPais'      => $vr_nm_pais    ,
                               'nrLatitude'  => $vr_nr_latitude,
                               'nrLongitude' => $vr_nr_longitude);
		}
		return json_encode($vr_rows);

	}

	// Função encarregada de trazer um pais a traves do nome dele.
	function buscaPais($pr_nm_pais, $pr_flg_criar) {

		$vr_sql = "SELECT idPais FROM pais WHERE nmPais = '$pr_nm_pais'";

		$vr_results = mysql_query($vr_sql);

		$vr_id_pais = 0;

		// Se achar atribuir o idPais e retornar o mesmo
		if ($vr_row = mysql_fetch_assoc($vr_results)) {
			$vr_id_pais = $vr_row['idPais'];
		}

		// Se nao achou nenhum Pais com este nome , entao cria
		if ($vr_id_pais == 0 && $pr_flg_criar == "true") {

			$vr_tabela  = "pais";
			$vr_campos  = "nmPais";
			$vr_valores = "'$pr_nm_pais'";

			$vr_id_pais = $this -> bd -> incluiRegistro($vr_tabela, $vr_campos, $vr_valores);
		}
		return $vr_id_pais;
	}

	// Função encarregada de trazer o estado
	function buscaEstado($pr_id_pais, $pr_nm_estado, $pr_flg_criar) {

		$vr_sql = "SELECT idEstado FROM estado WHERE idPais = $pr_id_pais AND nmEstado = '$pr_nm_estado'";

		$vr_results = mysql_query($vr_sql);

		$vr_id_estado = 0;

		// Salvar o idEstado para retornar
		if ($vr_row = mysql_fetch_assoc($vr_results)) {
			$vr_id_estado = $vr_row['idEstado'];
		}

		// Se nao achou nenhum Estado com este nome , entao cria
		if ($vr_id_estado == 0 && $pr_flg_criar == "true") {

			$vr_tabela = "estado";
			$vr_campos = "nmEstado , idPais";
			$vr_valores = "'$pr_nm_estado' , $pr_id_pais";

			$vr_id_estado = $this -> bd -> incluiRegistro($vr_tabela, $vr_campos, $vr_valores);

		}
		return $vr_id_estado;
	}

	// Buscar uma determinada cidade
	function buscaCidade($pr_id_pais, $pr_nm_cidade, $pr_flg_criar) {

		if ($pr_nm_cidade == 'Outros' || $pr_nm_cidade == 'Várias cidades') {
		  return -1;
		}

		if ($pr_nm_cidade == "Balneário Camboriú" || $pr_nm_cidade == "BALNEARIO CAMBORIU") {
			$pr_nm_cidade = "Baln. Camboriú";
		}

		$vr_sql = "SELECT idCidade "                  .
                   " FROM cidade "                    .
                   "WHERE idPais   = $pr_id_pais "    .
                     "AND nmCidade = '$pr_nm_cidade' ";

		$vr_results = mysql_query($vr_sql);

		$vr_id_cidade = -1;

		// Salvar o idCidade para retornar
		if ($vr_row = mysql_fetch_assoc($vr_results)) {
			$vr_id_cidade = $vr_row['idCidade'];
		}

		if ($pr_flg_criar == "false") {
			$this->insereBusca($pr_nm_cidade);
		}

		return $vr_id_cidade;
	}

	function insereBusca ($pr_nm_cidade) {

		setlocale(LC_TIME, 'pt_BR', 'ptb');
		$vr_dtBusca = date("Y-m-d");

		$vr_sql = "SELECT idBusca, qtBuscas FROM busca b " .
		           "WHERE b.dtBusca  = '$vr_dtBusca' "     .
		             "AND b.nmCidade = '$pr_nm_cidade'";

		$vr_results = mysql_query($vr_sql);

		// Se encontrou a busca
		if ($vr_row = mysql_fetch_assoc($vr_results)) {

			// Aumentar a quantidade de buscas
			$vr_qt_buscas = $vr_row["qtBuscas"] + 1;
			// Obter chave primaria
			$vr_id_busca  = $vr_row["idBusca"];

			// Atualizar registro
			$vr_tabela          = "busca";
			$vr_campos_valores  = "qtBuscas = $vr_qt_buscas";
			$vr_filtro          = "idBusca";
			$vr_valor           = $vr_id_busca;

			$this->bd-> editaRegistro($vr_tabela, $vr_campos_valores, $vr_filtro, $vr_valor);


		}	else {

			// Inserir registro de buscas
			$vr_tabela  = "busca";
			$vr_campos  = "dtBusca , nmCidade, qtBuscas";
			$vr_valores = "'$vr_dtBusca' , '$pr_nm_cidade' , 1";

			$this -> bd -> incluiRegistro($vr_tabela, $vr_campos, $vr_valores);

		}
	}


}
?>


<?php

class distancia {

	private $bd;

	function distancia($bd) {
		$this -> bd = $bd;
	}

	function incluiDistancia ($pr_id_cidade, $pr_nr_latitude , $pr_nr_longitude) {
	
		// Trazer todas as outras cidades
		$vr_sql = "SELECT * FROM cidade";

		$vr_results = mysql_query($vr_sql);

		// Para cada cidade
		while ($vr_row = mysql_fetch_assoc($vr_results)) {

			$vr_id_cidadeDest    = $vr_row['idCidade'];
			$vr_nr_latitudeDest  = $vr_row['latitude'];
			$vr_nr_longitudeDest = $vr_row['longitude'];
			
			$vr_nr_distancia = $this->calculaDistancia($pr_nr_latitude , $pr_nr_longitude, $vr_nr_latitudeDest , $vr_nr_longitudeDest);
			
			if  ( $vr_nr_distancia > 100 ) {
				continue;
			}
			
			// Criar origem considerando a que estou incluindo
			$vr_tabela = "distancia";
			$vr_campos = "idCidOrigem , idCidDestino , nrDistancia ";
			$vr_valores = " $pr_id_cidade , $vr_id_cidadeDest , $vr_nr_distancia";

			$this -> bd -> incluiRegistro($vr_tabela, $vr_campos, $vr_valores);
			
			// Criar destino considerando a que estou incluindo
			$vr_tabela = "distancia";
			$vr_campos = "idCidDestino , idCidOrigem , nrDistancia ";
			$vr_valores = " $pr_id_cidade , $vr_id_cidadeDest , $vr_nr_distancia";

			$this -> bd -> incluiRegistro($vr_tabela, $vr_campos, $vr_valores);

		}
	
	}
	
	function cidadeVizinhas ($pr_id_cidade , $pr_nr_distancia, $pr_json) {
	
		$vr_sql = "SELECT * "                             .
		        "FROM distancia d "                       .
		       "WHERE d.idCidOrigem  = $pr_id_cidade "    .
		         "AND d.nrDistancia <= $pr_nr_distancia " .
		       "ORDER BY d.nrDistancia";
		
		$vr_results = mysql_query($vr_sql);
		
		if (!$pr_json) {
		
			$vr_ds_cidades = "( $pr_id_cidade ";
				
			// Juntar as cidades vizinhas		
			while ($vr_row = mysql_fetch_assoc($vr_results)) {
			
				$vr_ds_cidades .=  "," . $vr_row["idCidDestino"];
				
			}
			
			$vr_ds_cidades .= ")";
			
			return $vr_ds_cidades;
		}
	  else {
		
			$vr_rows = array();
			
		    while ($vr_row = mysql_fetch_assoc($vr_results)) {
					
					$vr_id_cidadeOrigem  = $vr_row['idCidOrigem'];
					$vr_id_cidadeDestino = $vr_row['idCidDestino'];
					$vr_nr_distancia  = $vr_row['nrDistancia'];
			
				    $vr_rows[] = array('idCidOrigem' => $vr_id_cidadeOrigem , 'idCidDestino' => $vr_id_cidadeDestino , 'nrDistancia' => $vr_nr_distancia);
			}
			
			return json_encode($vr_rows);
			
		}
		
	}
	
	function calculaDistancia($lat1, $lon1, $lat2, $lon2) {

		$lat1 = deg2rad($lat1);
		$lat2 = deg2rad($lat2);
		$lon1 = deg2rad($lon1);
		$lon2 = deg2rad($lon2);

		$a = 6378137;
		$b = 6356752.3142;
		$f = 1 / 298.257223563;
		// WGS-84 ellipsoid

		$L = $lon2 - $lon1;

		$U1 = atan((1 - $f) * tan($lat1));
		$U2 = atan((1 - $f) * tan($lat2));

		$sinU1 = sin($U1);
		$cosU1 = cos($U1);
		$sinU2 = sin($U2);
		$cosU2 = cos($U2);

		$lambda = $L;
		$lambdaP = 2 * M_PI;

		$iterLimit = 20;

		while (abs($lambda - $lambdaP) > 1e-12 && --$iterLimit > 0) {
			$sinLambda = sin($lambda);
			$cosLambda = cos($lambda);
			$sinSigma = sqrt(($cosU2 * $sinLambda) * ($cosU2 * $sinLambda) + ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda) * ($cosU1 * $sinU2 - $sinU1 * $cosU2 * $cosLambda));

			if ($sinSigma == 0)
				return 0;
			// co-incident points

			$cosSigma = $sinU1 * $sinU2 + $cosU1 * $cosU2 * $cosLambda;
			$sigma = atan2($sinSigma, $cosSigma);
			// was atan2
			$alpha = asin($cosU1 * $cosU2 * $sinLambda / $sinSigma);
			$cosSqAlpha = cos($alpha) * cos($alpha);
			$cos2SigmaM = $cosSigma - 2 * $sinU1 * $sinU2 / $cosSqAlpha;
			$C = $f / 16 * $cosSqAlpha * (4 + $f * (4 - 3 * $cosSqAlpha));
			$lambdaP = $lambda;
			$lambda = $L + (1 - $C) * $f * sin($alpha) * ($sigma + $C * $sinSigma * ($cos2SigmaM + $C * $cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM)));
		}
		if ($iterLimit == 0)
			return false;
		// formula failed to converge

		$uSq = $cosSqAlpha * ($a * $a - $b * $b) / ($b * $b);
		$A = 1 + $uSq / 16384 * (4096 + $uSq * (-768 + $uSq * (320 - 175 * $uSq)));
		$B = $uSq / 1024 * (256 + $uSq * (-128 + $uSq * (74 - 47 * $uSq)));

		$deltaSigma = $B * $sinSigma * ($cos2SigmaM + $B / 4 * ($cosSigma * (-1 + 2 * $cos2SigmaM * $cos2SigmaM) - $B / 6 * $cos2SigmaM * (-3 + 4 * $sinSigma * $sinSigma) * (-3 + 4 * $cos2SigmaM * $cos2SigmaM)));

		$s = $b * $A * ($sigma - $deltaSigma);

		$s = round($s, 3);
		// round to 1mm precision
		return $s / 1000;

	}

}

?>


<?php

	function getDataLog($token){

		#Session::forget("date_session");
		$IndexColumn = "MsgUnique";
		$Table = "ipbanmod.clone_sys_logs_test";
		$Columns = [];

		//Se aplican las fechas por semana para el where
		$fecha = date('Y-m-j');
		$nuevafecha = strtotime('-7 day', strtotime($fecha));
		$nuevafecha = date('Y-m-j', $nuevafecha);

		$where1 = $nuevafecha.' 00:00:00';
		$where2 = $fecha.' 23:59:59';

		$query_where = "MsgDateTime BETWEEN '$where1' and '$where2'";
		//$query_where = "MsgDateTime LIKE '%.$fecha.%'";
		// Session::put('date_session', "MsgDateTime BETWEEN '2017-11-20 00:00:00' and '2017-11-27 23:59:59'");
		Session::put('date_session', $query_where);

		#columnas
		if(isset($_REQUEST['columns'])){
			foreach ($_REQUEST['columns'] as $column) {
				if($column['data'] <> 'src_country' || $column['data'] <> 'dst_country' ||
				$column['data'] <> 'rulename' || $column['data'] <> 'protocol' || $column['data'] <> 'sport_svc' || $column['data'] <> 'svc'){
					$Columns[] = $column['data'];
					//Log::info($column['data']);
				}
			}
		}

		#paginación
		$Limit = "";
		if(isset( $_REQUEST['start'] ) && $_REQUEST['length'] != '-1' ){
			$Limit = "LIMIT ".intval( $_REQUEST['start'] ).", ".intval( $_REQUEST['length'] );
		}

		#orden
		$Order = "";
		if(isset($_REQUEST['order'][0]['column']) && isset($Columns[$_REQUEST['order'][0]['column']]) && isset($_REQUEST['order'][0]['dir'])){

			foreach ($_REQUEST['order'] as $order) {
				$Order .= ($Order == "") ? " ORDER BY  ": " , ";
				$Order .= $Columns[$order['column']]." ".$order['dir'];
			}

			$Order = (trim($Order) == "ORDER BY " ) ? "" : $Order;
		}

		$Order = "ORDER BY MsgUnique DESC";

		#filtro
		$Where = "";

		if(isset($_REQUEST['search']) && isset($_REQUEST['search']['value']) && $_REQUEST['search']['value'] != "" ) {
			$Where .= "WHERE (";

			for ($i=0 ; $i<count($Columns) ; $i++) {
   			$Where .= $Columns[$i]." LIKE '%".addslashes( $_REQUEST['search']["value"] )."%' OR ";
			}

			$Where = substr_replace($Where, "", -3);
			$Where .= ')';

			#filtro por fecha y filtro dashboard
			if(Session::has('date_session') && Session::has('filter_dash')){
				//Session::forget('filter_dash');
				$filter = Session::get('filter_dash');
				$ip = $filter[0];
				$field = $filter[1];

				$Where .= "AND ".Session::get('date_session')." AND (".$field." = ".$ip.")";

			}elseif(Session::has('date_session') && !Session::has('filter_dash')){
				$Where .= "AND ".Session::get('date_session');

			}elseif(!Session::has('date_session') && Session::has('filter_dash')){
				$filter = Session::get('filter_dash');
				$ip = $filter[0];
				$field = $filter[1];

				$Where .= " AND (".$field." = ".$ip.")";
			}

		}else{
			#filtro por fecha y filtro dashboard
			if(Session::has('date_session') && Session::has('filter_dash')){
				$filter = Session::get('filter_dash');
				$ip = $filter[0];
				$field = $filter[1];

				$Where .= "WHERE (".Session::get('date_session').")  AND (".$field." = '".$ip."')";

			}elseif(Session::has('date_session') && !Session::has('filter_dash')){
				$Where .= "WHERE (".Session::get('date_session').")";

			}elseif(Session::has('filter_dash') && !Session::has('date_session')){
				$filter = Session::get('filter_dash');
				$ip = $filter[0];
				$field = $filter[1];

				$Where .= " WHERE (".$field." = '".$ip."')";
			}
		}

		Log::info($Where);

		#filtro
		if(isset($_REQUEST['columns'])){
			foreach ($_REQUEST['columns'] as $i => $value) {
	   		$columnft = $_REQUEST['columns'][$i];

	   		if(isset($columnft['searchable']) && $columnft['searchable'] == true &&
	      		isset($columnft['search']['value']) &&  $columnft['search']['value'] !='' ){

	       		if($Where == ""){
	           		$Where = "WHERE ";
	   			}else{
	           		$Where .= " AND ";
	       		}

					if($Columns[$i] == "rulename" || $Columns[$i] == "protocol" || $Columns[$i] == "sport_svc" || $Columns[$i] == "svc"){
						$Columns[$i] = "MsgText";
					}

	       		$Where .= $Columns[$i]." LIKE '%".addslashes($columnft['search']['value'])."%' ";
	   		}
			}
		}

		/*if(empty(Session::get('date_session'))){
			$where1 = \Carbon\Carbon::now()->firstOfMonth();
			$where2 = \Carbon\Carbon::now()->lastOfMonth();
			$Where .= "WHERE MsgDateTime BETWEEN '".$where1 ."' and '". $where2."'";

			Session::put("date_session", $Where);
		}*/

		#$Columns = array_diff($Columns, array('src_country', 'dst_country', 'rulename', 'protocol', 'sport_svc', 'svc'));
		$Columns = ['MsgUnique', 'MsgDateTime', 'MsgSrcIP', 'MsgDstIP', 'MsgHostAddress', 'MsgText', 'MsgTypeTraffic'];
		#consulta
		$Query = "SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $Columns))." FROM $Table $Where $Order LIMIT 0, 300";

		Log::info($Query);

		$Result = DB::connection('syslog')->select(DB::raw($Query));

		$Query = "SELECT FOUND_ROWS() as FilteredTotal";
		$ResultFilterTotal = DB::connection('syslog')->select(DB::raw($Query));
		$FilteredTotal = (isset($ResultFilterTotal) && count($ResultFilterTotal) > 0) ? $ResultFilterTotal[0]->FilteredTotal : 0;

		$Query = "SELECT COUNT(".$IndexColumn.") as Total FROM $Table";
		$ResultTotal = DB::connection('syslog')->select(DB::raw($Query));
		$Total = (isset($ResultTotal) && count($ResultTotal) > 0) ? $ResultTotal[0]->Total : 0;

		$last_result = json_decode(json_encode($Result), true);

		$servers = orderServers();

		$arrSrc = array_column($last_result, 'MsgSrcIP');
		$arrDst = array_column($last_result, 'MsgDstIP');
		$arreglo = array_merge($arrSrc, $arrDst);

		foreach($last_result as $key => $value){

			$country_src = @geoip_country_code_by_name($value['MsgSrcIP']);
			$country_dst = @geoip_country_code_by_name($value['MsgDstIP']);

			if(isset($country_src)){
				$last_result[$key]['src_country'] = $country_src;
			}else{
				$last_result[$key]['src_country'] = '-';
			}

			if(isset($country_dst)){
				$last_result[$key]['dst_country'] = $country_dst;
			}else{
				$last_result[$key]['dst_country'] = '-';
			}

			//COORDENADAS POR IP
			$region_src = @geoip_record_by_name($value['MsgSrcIP']);
			$region_dst = @geoip_record_by_name($value['MsgDstIP']);

			if(!empty($region_src)){
				#Log::info($key);
				#Log::info("entra al isset");
				#Log::info($region_src);
				$coord_src = ['latitude' => $region_src['latitude'], 'longitude' => $region_src['longitude']];
				$coord_dst = ['latitude' => $region_dst['latitude'], 'longitude' => $region_dst['longitude']];



				$city_src = $region_src['region'].' '.$region_src['city'];
				$city_dst = $region_dst['region'].' '.$region_dst['city'];

				$last_result[$key]['coord_src'] = isset($region_src) ? $coord_src : '-';
				$last_result[$key]['coord_dst'] = isset($region_dst) ? $coord_dst : '-';

				$last_result[$key]['region_src'] = isset($region_src) ? $city_src : '-';
				$last_result[$key]['region_dst'] = isset($region_dst) ? $city_dst : '-';

				if(isset($value['MsgHostAddress'])){
					foreach($servers as $key2 => $row){
						if($value['MsgHostAddress'] == $row['ip']){
							$last_result[$key]['MsgHostAddress'] = $row['server'];
						}
					}
				}

				if(isset($value['MsgText']) && $value['MsgHostAddress'] == "172.16.3.114"){
					$arr = explode(";", $value['MsgText']);
					$rule_name = '';
					$protocol = '';
					$sport = '';
					$svc = '';
					$new_value = '';

					foreach($arr as $row){
						$arr2 = explode(":", $row);
						$arr2 = array_map('trim', $arr2);

						if(isset($arr2[0]) && ($arr2[0] == 'rule_name' || $arr2[0] == 'proto' || $arr2[0] == 'sport_svc' || $arr2[0] == 'svc')){

							if($arr2[0] == 'rule_name'){
								$rule_name = $arr2[1];

							}elseif($arr2[0] == 'proto'){
								$protocol = $arr2[1];

							}elseif($arr2[0] == 'sport_svc'){
								$sport = $arr2[1];

							}elseif($arr2[0] == 'svc'){
								$svc = $arr2[1];
							}

							$new_value = $rule_name.'; '.$protocol.'; '.$sport.'; '.$svc.';';
						}
					}

					$last_result[$key]['rulename'] = !empty($rule_name) ? $rule_name : 'empty';
					$last_result[$key]['protocol'] = !empty($protocol) ? $protocol : 'empty';
					$last_result[$key]['sport_svc'] = !empty($sport) ? $sport : 'empty';
					$last_result[$key]['svc'] = !empty($svc) ? $svc : 'empty';

					$last_result[$key]['MsgText'] = $new_value;
				}else{

					$arr = explode(",", $value['MsgText']);

					$last_result[$key]['rulename'] = $arr[11];
					$last_result[$key]['protocol'] = $arr[29];
					$last_result[$key]['sport_svc'] = 'empty';
					$last_result[$key]['svc'] = 'empty';

					$last_result[$key]['MsgText'] = $value['MsgText'];
				}


			}else{
				unset($last_result[$key]);
			}
		}

		$output = array(
			"recordsTotal" => $Total,
			"recordsFiltered" => $FilteredTotal,
			"data" => $last_result
		);

		return $output;
	}

	function orderServers(){
		$array_server = [];

		$array_server[0]['priority'] = 1;
		$array_server[0]['server'] = 'CHECKPOINT-CLUSTER';
		$array_server[0]['ip'] = '172.16.3.114';

		$array_server[1]['priority'] = 2;
		$array_server[1]['server'] = 'PALO ALTO ';
		$array_server[1]['ip'] = '172.16.3.51';

		$array_server[2]['priority'] = 2;
		$array_server[2]['server'] = 'PALO ALTO ';
		$array_server[2]['ip'] = '172.16.3.52';

		$array_server[3]['priority'] = 3;
		$array_server[3]['server'] = 'SONICWALL-5060';
		$array_server[3]['ip'] = '172.16.3.53';

		$array_server[4]['priority'] = 4;
		$array_server[4]['server'] = 'FORTIGATE (FORTI-ANALYZER)';
		$array_server[4]['ip'] = '172.16.3.70';

		$array_server[5]['priority'] = 5;
		$array_server[5]['server'] = 'FortiDDoS-Ashburn-200B';
		$array_server[5]['ip'] = '172.16.2.139';

		return $array_server;
	}

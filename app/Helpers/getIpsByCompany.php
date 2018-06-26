<?php

use IPTools\Range;
use IPTools\Network;
use IPTools\IP;

   function getAddressIPsCompany($token, $arreglo){

      $response = [];

      $userLog = JWTAuth::toUser($token);
      $id_company = $userLog['company_id'];

      $company_data = DB::table('fw_companies')->where('id', $id_company)->get();
      $company_data = json_encode($company_data);
      $company_data2 = json_decode($company_data, true);
      $tag = $company_data2[0]['tag'];

      // $address = FwAddress::join('fw_objects', 'fw_address_objects.object_id', '=', 'fw_objects.id')
      //    ->where('fw_objects.tag', $tag)
      //    ->where('fw_objects.type_object_id', '=', 1)
      //    ->get();

      $address = DB::table('fw_address_objects')
               ->join('fw_objects', 'fw_address_objects.object_id', '=', 'fw_objects.id')
               ->where('fw_objects.tag', $tag)
               ->where('fw_objects.type_object_id', '=', 1)
               ->get();

      //Log::info(print_r($address, true));
      //$name_company = DB::table('fw_companies')->where('tag', '=', $tag_company)->pluck('name');

      foreach ($address as $item){
         $ip = $item->ip_address;
         $type_addr = $item->type_address_id;//Obtengo el tipo de address, puede ser ip-netmask o ip-range
         $ips = getHostAvailable($ip, $type_addr);

         if($ips != null || $ips != false){
            $ips_collect[] = $ips;
         }
      }

      #38.118.71.121
      $arr3 = [];
      $array_new = [];
      $array_new2 = [];
      $c = 0;

      foreach($ips_collect as $key => $value) {
         $arr3[$c]['first'] = $value['ip-first'];
         $arr3[$c]['last'] = $value['ip-last'];
         $c++;
      }

      Log::info($arreglo);
      foreach($arreglo as $key3 => $value3){
         $ip_evaluate = ip2long($value3);
         foreach($arr3 as $row2){
            $ip_ini = ip2long($row2['first']);
            $ip_fin = ip2long($row2['last']);

            // Log::info(print_r($value3, true));
            if(Range::parse($row2['first'].'-'.$row2['last'])->contains(new IP($value3))){
               if(!in_array($value3, $array_new)){
                  $array_new[] = $value3;
               }
            }
         }
      }

      #Log::info($array_new);
      $response = [
         "id_company" => $company_data2[0]['id'],
         // "name_company" => $name_company[0],
         "name_company" => $company_data2[0]['id'],
         #"ips" => $ips_collect,
         "ips" => $array_new,
      ];

      return $array_new;
   }

   function getHostAvailable($ips, $type){
      $response = [];
      $host = 0;
      $ip_last = '';

      if($ips != '' && ($type != 4)){
         $str = substr($ips, 0, 2);
         $ips2 = [];
         $i = 0;

         if($type == 1 && is_numeric($str)){
            $hosts = Network::parse($ips)->hosts;
            foreach($hosts as $ip) {
            	$ips2[$i] = ((string)$ip);

               $i++;
            }

         }elseif($type == 2 && is_int($str)){
            $range = Range::parse($ips);
            foreach($range as $ip) {
               $ips2[$i] = ((string)$ip);

               $i++;
            }
         }

         if(isset($ips2[0])){
            if(count($ips2) > 1){
               $ips_result = collect(['ip-first' => reset($ips2), 'ip-last' => end($ips2)]);
            }else{
               $ips_result = collect(['ip-first' => $ips2[0], 'ip-last' => $ips2[0]]);
            }
            return $ips_result;
         }
      }
   }

<?php

   function saveNewRules($token){

      $array_rules = array(
         array(
            "section" => "CHECKPOINT-BLOCK",
            array(
               "name" => "CHECKPOINT DROP INCOMING",
               "source" => "checkpoint-block",
               "destination" => "any",
               "vpn" => "any"
            ),
            array(
               "name" => "CHECKPOINT DROP OUTGOING",
               "source" => "any",
               "destination" => "checkpoint-block",
               "vpn" => "any"
            )
         ),
         array(
            "section" => "PALOALTO-BLOCK",
            array(
               "name" => "PALOALTO DROP INCOMING",
               "source" => "paloalto-block",
               "destination" => "any",
               "vpn" => "any"
            ),
            array(
               "name" => "PALOALTO DROP OUTGOING",
               "source" => "any",
               "destination" => "paloalto-block",
               "vpn" => "any"
            )
         ),
         array(
            "section" => "FORTINET-BLOCK",
            array(
               "name" => "FORTINET DROP INCOMING",
               "source" => "fortinet-block",
               "destination" => "any",
               "vpn" => "any"
            ),
            array(
               "name" => "FORTINET DROP OUTGOING",
               "source" => "any",
               "destination" => "fortinet-block",
               "vpn" => "any"
            )
         ),
         array(
            "section" => "SONICWALL-BLOCK",
            array(
               "name" => "SONICWALL DROP INCOMING",
               "source" => "sonicwall-block",
               "destination" => "any",
               "vpn" => "any"
            ),
            array(
               "name" => "SONICWALL DROP OUTGOING",
               "source" => "any",
               "destination" => "sonicwall-block",
               "vpn" => "any"
            )
         ),
         array(
            "section" => "SOPHOS-BLOCK",
            array(
               "name" => "SOPHOS DROP INCOMING",
               "source" => "sophos-block",
               "destination" => "any",
               "vpn" => "any"
            ),
            array(
               "name" => "SOPHOS DROP OUTGOING",
               "source" => "any",
               "destination" => "sophos-block",
               "vpn" => "any"
            )
         ),
         array(
            "section" => "BARRACUDA-EMAIL-BLOCK",
            array(
               "name" => "BARRACUDA-EMAIL DROP INCOMING",
               "source" => "barracuda-email-block",
               "destination" => "any",
               "vpn" => "any"
            ),
            array(
               "name" => "BARRACUDA-EMAIL DROP OUTGOING",
               "source" => "any",
               "destination" => "barracuda-email-block",
               "vpn" => "any"
            )
         ),
         array(
            "section" => "BARRACUDA-NGF-BLOCK",
            array(
               "name" => "BARRACUDA-NGF DROP INCOMING",
               "source" => "barracuda-ngf-block",
               "destination" => "any",
               "vpn" => "any"
            ),
            array(
               "name" => "BARRACUDA-NGF DROP OUTGOING",
               "source" => "any",
               "destination" => "barracuda-ngf-block",
               "vpn" => "any"
            )
         ),
         array(
            "section" => "RED4G-HONEYPOT-BLOCK",
            array(
               "name" => "RED4G-HONEYPOT DROP INCOMING",
               "source" => "red4g-honeypot-block",
               "destination" => "any",
               "vpn" => "any"
            ),
            array(
               "name" => "RED4G-HONEYPOT DROP OUTGOING",
               "source" => "any",
               "destination" => "red4g-honeypot-block",
               "vpn" => "any"
            )
         )
      );

		return $array_rules;
   }

   function getObjectServers(){

      $array_server = array(
         array(
            "server" => "CHECKPOINT",
            "server_id" => 1,
            "objects" => array(
               "checkpoint-block"
            )
         ),
         array(
            "server" => "PALOALTO",
            "server_id" => 2,
            "objects" => array(
               "paloalto-block"
            )
         ),
         array(
            "server" => "FORTINET",
            "server_id" => 3,
            "objects" => array(
               "fortinet-block​"
            )
         ),
         array(
            "server" => "SONICWALL",
            "server_id" => 4,
            "objects" => array(
               "sonicwall-block"
            )
         ),
         array(
            "server" => "SOPHOS",
            "server_id" => 5,
            "objects" => array(
               "sophos-block"
            )
         )
      );

		return $array_server;
   }

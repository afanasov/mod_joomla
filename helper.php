<?php
defined('_JEXEC') or die;
/**
 * Helper for mod_listnppb
 *
 * @package     Joomla.Site
 * @subpackage  mod_listnppbu
 * @since       3.0
 */
class ModNovaPoshtaPrivatBankHelper {       
    private function ConectDataBase(){
        $JConfig = new JConfig();            
        $link = new mysqli($JConfig->host, $JConfig->user, $JConfig->password, $JConfig->db);
        $link->set_charset("utf8");        
        return $link;
    }    
    public function checkTableCity(){
        $mysqli = $this->ConectDataBase();
        $mysqli->set_charset("utf8");
        $JConfig = new JConfig();
        
        $checkCity = $mysqli->query("ALTER TABLE `{$JConfig->dbprefix}cities`");
        if(!$checkCity){
            $queryCity = "CREATE TABLE `{$JConfig->db}`.`{$JConfig->dbprefix}cities` 
                            ( `id` INT NOT NULL AUTO_INCREMENT , 
                                `city_name_ru` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , 
                                `city_name_ua` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , 
                            PRIMARY KEY (`id`)) ENGINE = InnoDB;";
            $mysqli->query($queryCity);            
        } 
        $selectCity = $mysqli->query("SELECT `city_name_ru` FROM `{$JConfig->dbprefix}cities`");
        while ($row = $selectCity->fetch_array(MYSQLI_NUM)){
            $сity[] = $row;
        }       
        $сity = call_user_func_array('array_merge', $сity);        
        return $сity;        
    }    
    private function getCity(){
        $mysqli= $this->ConectDataBase();
        $JConfig = new JConfig();
                
        $query = "SELECT `id`,`city_name_ru` FROM `{$JConfig->dbprefix}cities`";
        $selectCity = $mysqli->query($query);
        
        $сity = array();
        while ($row = $selectCity->fetch_array()){
            $сity[$row['city_name_ru']] = $row['id'];
        } 
        return $сity;
    }    
    private function getTableName($type) {
        if($type == "Novaposhta"){
            $table_name = "nova_poshta";
        }elseif ($type == "PrivatBank") {
            $table_name = "privat_bank";
        }
        return $table_name;
    }
    public function getDepartments($сity, $type){
        if($type == "Novaposhta"){
            foreach ($сity as $key => $value) { 
                $urlNovaPoshta = "https://api.novaposhta.ua/v2.0/json/";
                $questNovaPoshta = "POST";
                $fieldNovaPoshta = "{
                                        \"modelName\": \"AddressGeneral\",
                                        \"calledMethod\": \"getWarehouses\",
                                        \"methodProperties\": {
                                            \"CityName\": \"$value\",
                                            \"Language\": \"ru\"
                                        },
                                        \"apiKey\": \"\"         
                }";
                $Result[$key] = $this->getAdress($value, $urlNovaPoshta, $questNovaPoshta, $fieldNovaPoshta);            
            }
        }elseif ($type == "PrivatBank") {
                foreach ($сity as $key => $value) {
                $town = urlencode($value);
                $urlPrivat = "https://api.privatbank.ua/p24api/pboffice?json&city=$town";
                $questPrivat = "GET";

                $Result[$key] = $this->getAdress($value, $urlPrivat, $questPrivat);
            }
        }
        return $Result;
    }
    public function checkUpdate($type){        
        $mysqli = $this->ConectDataBase();
        $JConfig = new JConfig();        
        $table_name = $this->getTableName($type);

        $check_nova_poshta = $mysqli->query("ALTER TABLE `{$JConfig->dbprefix}{$table_name}`");

        if($check_nova_poshta === TRUE){
            return TRUE;
        } else {
            return FALSE;
        }
    }        
    public function getAdress($adress, $url, $quest, $field) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => True,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $quest,
            CURLOPT_POSTFIELDS => $field,
            CURLOPT_HTTPHEADER => array("content-type: application/json",),
        ));
        $response_curl = curl_exec($curl);

        curl_error($curl);
        curl_close($curl);

        $response = json_decode($response_curl, true);
        return $response;
    }    
    public function checkTheTable($addToDB, $type){
        $mysqli = $this->ConectDataBase();
        $JConfig = new JConfig();
        
        if($type == "Novaposhta"){
            $table_name = "nova_poshta";
            $query = "CREATE TABLE `{$JConfig->db}`.`{$JConfig->dbprefix}{$table_name}` 
                            ( `id` INT NOT NULL AUTO_INCREMENT , 
                              `id_city` INT NOT NULL , 
                              `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , 
                              `address` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , 
                              `phone` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , 
                              `working_hours` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,  
                            PRIMARY KEY (`id`)) 
                            ENGINE = InnoDB CHARSET=utf8 COLLATE utf8_general_ci;";
        }elseif ($type == "PrivatBank") {
            $table_name = "privat_bank";
            $query = "CREATE TABLE `{$JConfig->db}`.`{$JConfig->dbprefix}{$table_name}` 
                            ( `id` INT NOT NULL AUTO_INCREMENT , 
                              `id_city` INT NOT NULL , 
                              `name` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , 
                              `address` TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL , 
                              `phone` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,                                
                            PRIMARY KEY (`id`)) 
                            ENGINE = InnoDB CHARSET=utf8 COLLATE utf8_general_ci;";            
        }
        $check = $mysqli->query("ALTER TABLE `{$JConfig->dbprefix}{$table_name}`");
        
        if(!$check){         
            $check = $mysqli->query($query);          
            if($type == "Novaposhta"){
                $this->NovaPoshtaSendToDb($addToDB);
            }
            elseif ($type == "PrivatBank") {
                 $this->PrivatBankSendToDb($addToDB);
            }
        }           
        return TRUE;
    }
    private function getAssocArrey($type) {        
        $mysqli = $this->ConectDataBase();
        $JConfig = new JConfig();
        $resultDb = array();
        
        if($type == "Novaposhta"){
            $query = "SELECT `id_city`,`name`,`address`,`phone`,`working_hours` FROM `{$JConfig->dbprefix}nova_poshta`";
        }elseif ($type == "PrivatBank") {
            $query = "SELECT `id_city`,`name`,`address`,`phone` FROM `{$JConfig->dbprefix}privat_bank`";
        }            
        $result = $mysqli->query($query);
        while ($row = $result->fetch_array(MYSQLI_ASSOC)){
            $resultDb[] = $row;
        }  
        return $resultDb;
    }    
    private function deleteNonExistingOffices($resultDb, $type){
        $mysqli = $this->ConectDataBase();
        $JConfig = new JConfig();

        if(!empty($resultDb)){
            foreach ($resultDb as $key=>$valueDel) {                
                $address = $valueDel["address"];
                $prepare .="'$address',"; 
                $prepare_query = substr($prepare, 0, -1);                
  
                $table_name = $this->getTableName($type);
                
                $delquery = "DELETE FROM `{$JConfig->dbprefix}{$table_name}` WHERE `address` IN ($prepare_query)";               
                $mysqli->query($delquery);
            }            
        }
        return TRUE;
    }    
    private function deleteRepeat($resultDb, $type) {
        $mysqli = $this->ConectDataBase(); 
        $JConfig = new JConfig();
        $table_name = $this->getTableName($type);

        $selQuery = "select * from `{$JConfig->dbprefix}{$table_name}` WHERE `address` in (select `address` from `{$JConfig->dbprefix}{$table_name}` group by `address` having count(*) > 1)";
        $select = $mysqli->query($selQuery);
        while ($row = $select->fetch_array(MYSQLI_ASSOC)){
            $selectDb[] = $row;
        }        
        $compare = $this->deleteNonExistingOffices($selectDb, $type);
        return TRUE;
    }
    private function addNewOffices($resultJson, $type) {
        $mysqli = $this->ConectDataBase(); 
        $JConfig = new JConfig();
        
        if(!empty($resultJson)){
            foreach ($resultJson as $valueInsert) {
                $id_city = htmlspecialchars($valueInsert["id_city"]);
                $name = htmlspecialchars($valueInsert["name"]);
                $address = htmlspecialchars($valueInsert["address"]);
                $phone = htmlspecialchars($valueInsert["phone"]);

                if($valueInsert["working_hours"]){
                    $working_hours = htmlspecialchars($valueInsert["working_hours"]);
                    $prepare .= "('$id_city', '$name', '$address' ,'$phone', '$working_hours'),";    
                } else{
                    $prepare .= "('$id_city', '$name', '$address' ,'$phone'),";   
                }
            }
            $prepare_query = substr($prepare, 0, -1);
            if($type == "Novaposhta"){
                $table_name = "nova_poshta";
                $insertQuery = "INSERT INTO `{$JConfig->dbprefix}{$table_name}`(`id_city`, `name`, `address`, `phone`, `working_hours`) VALUES $prepare_query";
            }elseif($type == "PrivatBank") {
                $table_name = "privat_bank";
                $insertQuery = "INSERT INTO `{$JConfig->dbprefix}{$table_name}`(`id_city`, `name`, `address`, `phone`) VALUES $prepare_query";
            }
            $mysqli->query($insertQuery);
        }        
        return TRUE;
    }        
    public function CheckUpdateTable($array, $type) {                       
        if(!empty($array)){
            $resultDb = $this->getAssocArrey($type);
            $resultJson = $this->doOneTypeArrayUpdateDBNovaPoshta($array);
            $compare = $this->deleteRepeat($resultDb,$type);
                    
            foreach ($resultJson as $a => $valueJson) {
                foreach ($resultDb as $b => $valueDb) {
                    if (   ($valueJson['name'] == $valueDb['name'])
                        && ($valueJson['address'] == $valueDb['address'])
                        && ($valueJson['phone'] == $valueDb['phone'])
                        && ($valueJson['working_hours'] == $valueDb['working_hours'])                            
                       ) {
                        unset($resultJson[$a]);
                        unset($resultDb[$b]);
                    }
                }
            }            
            $del = $this->deleteNonExistingOffices($resultDb, $type);         
            $add = $this->addNewOffices($resultJson, $type);                                   
            return TRUE;   
        }
        return FALSE;      
    }    
    private function NovaPoshtaSendToDb($sendToDb){
        $mysqli = $this->ConectDataBase();
        $JConfig = new JConfig(); 
        $city = $this->getCity();

        foreach ($sendToDb as $valueSendToDb){
            foreach ($valueSendToDb as $valueResult){
                $name = htmlspecialchars($valueResult["CityDescriptionRu"]);

                if ($name == key($city)){
                    $id_city = $city[$name];
                } else {
                    $id_city = $city[$name];
                }  
                $address = htmlspecialchars($valueResult["DescriptionRu"]);
                $phone = htmlspecialchars($valueResult["Phone"]);
                $working_hours = $valueResult["Schedule"];
                $working_hours = htmlspecialchars(implode(", ",$working_hours));
                
                $prepare .= "('$id_city', '$name', '$address' ,'$phone', '$working_hours'),";
                $prepare_query = substr($prepare, 0, -1);                
            } 
        }
        $queryNovaPoshta = "INSERT INTO `{$JConfig->dbprefix}nova_poshta`(`id_city`, `name`, `address`, `phone`, `working_hours`) VALUES $prepare_query";        
        $mysqli->query($queryNovaPoshta);
        return TRUE;
    }    
    public function NovaPoshtaFilter($response) {        
        $result = array();
        foreach ($response as $key => $value) {
            foreach ($value['data'] as $keyResult => $valueResult) { 
               if (!preg_match_all('/очтома/', $valueResult['DescriptionRu'])){                  
                    $result[$key][$keyResult]['CityDescriptionRu'] = $valueResult['CityDescriptionRu'];
                    $result[$key][$keyResult]['DescriptionRu'] = $valueResult['DescriptionRu'];
                    $result[$key][$keyResult]['Phone'] = $valueResult['Phone'];
                    $result[$key][$keyResult]['Schedule'] = $valueResult['Schedule'];   
                }  
            }
        }
        return $result;
    }            
    private function doOneTypeArrayUpdateDBNovaPoshta($array){
        $resultJson = array();
        $city = $this->getCity();
        
        foreach ($array as $value) {
            foreach ($value as $valueResult) {
                $data = array();

                $data['id_city'] = $city[$valueResult["CityDescriptionRu"]];                
                $data['name'] = $valueResult["CityDescriptionRu"];
                $data['address'] = $valueResult["DescriptionRu"];
                $data['phone'] = $valueResult["Phone"];
                $data['working_hours'] = implode(", ",$valueResult["Schedule"]); 
                array_push($resultJson, $data);  
            }
        }
        return $resultJson;
    }    
    public function checkUpdateDBNovaPoshta($array){        
        if(!empty($array)){
            $resultDb = $this->getAssocArrey("Novaposhta");
            $resultJson = $this->doOneTypeArrayUpdateDBNovaPoshta($array);
            $compare = $this->deleteRepeat($resultDb,"Novaposhta");
            
            foreach ($resultJson as $a => $valueJson) {
                foreach ($resultDb as $b => $valueDb) {
                    if (   ($valueJson['name'] == $valueDb['name'])
                        && ($valueJson['address'] == $valueDb['address'])
                        && ($valueJson['phone'] == $valueDb['phone'])
                        && ($valueJson['working_hours'] == $valueDb['working_hours'])
                       ) {
                        unset($resultJson[$a]);
                        unset($resultDb[$b]);
                    }
                }
            }            
            $del = $this->deleteNonExistingOffices($resultDb, "Novaposhta");         
            $add = $this->addNewOffices($resultJson, "Novaposhta");                                   
            return TRUE;   
        }
        return FALSE;      
    }                
    private function PrivatBankSendToDb($sendToDb){
        $mysqli = $this->ConectDataBase();
        $JConfig = new JConfig(); 
        $city = $this->getCity();
        
        foreach ($sendToDb as $key => $valueSendToDb){
            foreach ($valueSendToDb as $keys => $valueResult){
                $name = htmlspecialchars($valueResult["city"]);

                if ($name == key($city)){
                    $id_city = $city[$name];
                } else {
                    $id_city = $city[$name];
                }

                $address = htmlspecialchars($valueResult["address"]);
                $phone = htmlspecialchars(substr($valueResult["phone"], 1)) ;
                
                $prepare .= "('$id_city', '$name', '$address' ,'$phone'),";
                $prepare_query = substr($prepare, 0, -1);  
            }
        }
        $queryPrivatBank = "INSERT INTO `{$JConfig->dbprefix}privat_bank`(`id_city`, `name`, `address`, `phone`) VALUES  $prepare_query";                                
        $mysqli->query($queryPrivatBank);        
        return TRUE;
    }            
    private function doOneTypeArrayUpdateDBPrivatBank($array){
        $resultJson = array();
        $city = $this->getCity();
                        
        foreach ($array as $value) {
            foreach ($value as $valueResult) {
                $data = array();
               
                $data['id_city'] = $city[$valueResult["city"]];                
                $data['name'] = $valueResult["city"];
                $data['address'] = $valueResult["address"];
                $data['phone'] = substr($valueResult["phone"], 1); 
                array_push($resultJson, $data);                
            }
        }            
        return $resultJson;
    }        
    public function checkUpdateDBPrivatBank($array){
        if(!empty($array)){            
            $resultDb = $this->getAssocArrey("PrivatBank");
            $resultJson = $this->doOneTypeArrayUpdateDBPrivatBank($array);
            $compare = $this->deleteRepeat($resultDb,"PrivatBank");
            
            foreach ($resultJson as $a => $valueJson) {
                foreach ($resultDb as $b => $valueDb) {
                    if (   ($valueJson['name'] == $valueDb['name'])
                        && ($valueJson['address'] == $valueDb['address'])
                        && ($valueJson['phone'] == $valueDb['phone'])
                    ) {
                        unset($resultJson[$a]);
                        unset($resultDb[$b]);
                    }
                }
            }                        
            $del = $this->deleteNonExistingOffices($resultDb, "PrivatBank");            
            $add = $this->addNewOffices($resultJson, "PrivatBank");            
            return TRUE;   
        }       
        return FALSE;
    }         
}
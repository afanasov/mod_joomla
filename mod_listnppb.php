<?php
/**
 * @package     Joomla.Site
 * @subpackage  mod_listnppb
 *
 */
defined('_JEXEC') or die;
require_once __DIR__.'/helper.php';

$response = new ModNovaPoshtaPrivatBankHelper();
$responseCity = $response->checkTableCity();

$responseNovaPoshta = $response->getDepartments($responseCity,"Novaposhta");
$responsefilterNovaPoshta = $response->NovaPoshtaFilter($responseNovaPoshta); 
$CheckUpdateNovaPoshta = $response->checkUpdate("Novaposhta");

$responsePrivatBank = $response->getDepartments($responseCity,"PrivatBank");
$CheckUpdatePrivatBank = $response->checkUpdate("PrivatBank");

if($CheckUpdateNovaPoshta === TRUE){
    $updateNovaPoshta = $response->checkUpdateDBNovaPoshta($responsefilterNovaPoshta);
} elseif($CheckUpdateNovaPoshta === FALSE) {
    $queryNovaPoshta = $response->checkTheTable($responsefilterNovaPoshta, "Novaposhta");    
}

if($CheckUpdatePrivatBank === TRUE){
    $updatePrivatBank = $response->checkUpdateDBPrivatBank($responsePrivatBank);
} elseif($CheckUpdatePrivatBank === FALSE) {
    $queryPrivatBank = $response->checkTheTable($responsePrivatBank , "PrivatBank");
}
<?php
/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

include_once('../class/tcpdf/tcpdf.php');
include_once("../class/PHPJasperXML.inc.php");

include_once ('setting.php');
require_once '../class/JasperDatabase.php';
require_once '../class/JasperMysql.php';
require_once '../class/JasperExp.php';
require_once '../class/JasperJS.php';

$xml =  simplexml_load_file("sample3.jrxml");


$PHPJasperXML = new PHPJasperXML();
$PHPJasperXML->debugsql=false;
$PHPJasperXML->arrayParameter=array("parameter1"=>1);
$PHPJasperXML->xml_dismantle($xml);

$PHPJasperXML->transferDBtoArray($server,$user,$pass,$db);
$PHPJasperXML->outpage("I");    //page output method I:standard output  D:Download file


?>

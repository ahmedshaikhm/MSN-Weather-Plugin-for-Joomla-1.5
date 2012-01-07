<?php
/**
* @version $Id: mod_msnweather.php 2007-12-15 00:45:00 $
* @package Joomla
* @copyright Copyright (C) 2007 Pingvin. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* Joomla! is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/

// no direct access
defined( '_VALID_MOS' ) or die( 'Restricted access' );
global $mosConfig_offset, $mosConfig_cachepath;
$cities = $params->def( 'cities' );
$degrees = $params->def( 'degrees' );
$font_header = $params->def( 'font_header' );

function convert_data($strdata)
{
    $strdata_from = explode(" ", $strdata);
    $strdata = str_replace(":", "", $strdata_from[1]);
    $strdata_from = explode("/", $strdata_from[0]);
    $strdata = $strdata_from[0] . $strdata_from[1] . $strdata_from[2] . $strdata;

    return $strdata;
}

function retrieve_weather($accid, $degrees, $mosConfig_cachepath)
{
    //Pulling data in cache, if any
    if ( !file_exists( $mosConfig_cachepath . '/msnweather.txt') )
    {
        $file = fopen($mosConfig_cachepath . '/msnweather.txt', "w");
        $dcache[$accid]['LastUp'] = "";
        $dcache[$accid]['Temp'] = "";
        $dcache[$accid]['CIcon'] = "";
        fputs($file, serialize($dcache));
        fclose($file);
    }else
    {
        $file = fopen($mosConfig_cachepath . '/msnweather.txt', "r");
        $dcache = unserialize(fgets($file, 1000));
        fclose($file);
    }   

    $city = explode("=", $accid);
    $accid = $city[0];
    $xcity  = $city[1];
    $url ="http://weather.uk.msn.com/local.aspx?weadegreetype=".strtoupper( $degrees ) ."&wealocations=wc:$accid";

    
    // curl retrieve data
    
    $ch = curl_init();
    $timeout = 5; // set to zero for no timeout
    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    $content = curl_exec($ch);
    curl_close($ch);

    $xtemperature   = "";
    $xicon  = "";
    if ( preg_match( '/<div class="temperature boldtext">(.+?)</', $content, $aData ) ) {
        $xtemperature   = trim( $aData[1] );
    }
    if ( preg_match( '/<div class="rgtspace"><div class="centeralign"><img src="(.+?)"/si', $content, $aData ) ) {
        $xicon  = trim( $aData[1] );
    }
    

    $xdate = date("Y/m/d H:00:00");

    $MsnWeather['City'] = $xcity;
    $MsnWeather['LastUp'] = $xdate;
    $MsnWeather['Temp'] = preg_replace( '/[^\d]/', '', $xtemperature );
    $MsnWeather['CIcon'] = $xicon;  
    //var_dump( $MsnWeather );exit;
    if (count($city)>1) $MsnWeather['City'] = $city[1];
    
    //Check is there a data that is newer than cache
    $changed = false;
    if ( !isset( $dcache[$accid]['LastUp'] ) ) {
        $dcache[$accid]['LastUp'] = date("2011/m/d H:00:00");
    }
    if ( convert_data($MsnWeather['LastUp']) > convert_data($dcache[$accid]['LastUp']) )
    {
        $dcache[$accid]['LastUp'] = $MsnWeather['LastUp'];
        if ($MsnWeather['Temp']) $dcache[$accid]['Temp'] = $MsnWeather['Temp'];
        if ($MsnWeather['CIcon']) $dcache[$accid]['CIcon'] = $MsnWeather['CIcon'];
        $changed = true;
    }else
    {
        $MsnWeather['LastUp'] = $dcache[$accid]['LastUp'];
        $MsnWeather['Temp'] = $dcache[$accid]['Temp'];
        $MsnWeather['CIcon'] = $dcache[$accid]['CIcon'];
    }

    if ( $changed && file_exists( $mosConfig_cachepath . '/msnweather.txt') )
    {
        $file = fopen($mosConfig_cachepath . '/msnweather.txt', "w");
        fputs($file, serialize($dcache));
        fclose($file);
    }
    //calculates the temperature
    $temperature    = $MsnWeather['Temp'];
    if ( !($MsnWeather['Temp']) )
    { 
        $temperature = "-"; 
        $MsnWeather['CIcon'] = "44"; 
    }

    
    $varlocal = "";
    $varlocal .= "<a rel='lightbox' href='http://weather.msn.com/local.aspx?wealocations=wc:$accid&weadegreetype=".strtoupper($degrees)."' target='_blank'>".$MsnWeather['City']."<img align=\"absmiddle\" border=\"0\" src=\"". $MsnWeather['CIcon'] . "\">&nbsp;&nbsp;<strong>".$temperature."°".strtoupper($degrees)."  </strong></a>";
    return $varlocal;
}

$cities = explode(",", $cities);
$content .="<table onmouseover=\"this.style.cursor='pointer'; return true;\" onmouseout=\"return true;\">";
for($i=0; $i<count($cities); $i++)
{
    $content .="<tr><td align='left'>";
    $content .= retrieve_weather($cities[$i],$degrees, $mosConfig_cachepath);
    $content .="</td></tr>";
}   
$content .= "</table>";


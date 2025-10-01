<?php
/*
    Programm Preistracker
    findet Angebote auf dem Portal https://www.marktguru.de/  
    
    Aufruf: p-track.php 

    Übergabe in HTML            "p-track.php?p=nnnnn&w=aaaaaa"
    Übergabe in der Konsole     "p-track.php p=nnnn w=aaaaaa"    
    
*/


/* ################################################################
   # Globale Variablen                                            #
   ################################################################ */
   
$pgm_Call = 0;                          // 1=HTML, 2=Kommandozeile
$pdat_data = "p-track.dat";             // Datendatei
$han_data;                              // Handle Datendatei
$prog_sec_d = " ";                      // Section in der Datendatei
$fly_url = array();                     // Array URL-Adressen der Flyer

$plz = "12345";
$suche = "Angebot";
$web = "https://api.marktguru.de/api/v1/offers/search?as=web&limit=24&offset=0";
$RESOURCE_URL = $web."&q=".$suche."&zipCode=".$plz;

$HEADERS = [
    "x-clientkey: WU/RH+PMGDi+gkZer3WbMelt6zcYHSTytNB7VpTia90=",
    "x-apikey: 8Kk+pmbf7TgJ9nVj2cXeA7P5zBGv8iuutVVMRfOfvNE="
];


/* ################################################################
   # Funktionen                                                   #
   ################################################################ */
   
/* Funktion zum zerlegen einer Textzeile in die einzelnen Bestandteile
   und Rückgabe der Teile in einem Array, Element 0 enthält die Anzahl der Einträge */
function teile_text($text)
{
  $tok = "";
  $index = 0;
  $respond = array("0" => "0");
  $tok = strtok($text," \t");
  while ($tok !== false)
  {
    $index += 1;
    $respond[$index] = trim($tok);
    $respond[0] = $index;
    if ($index >= 8) break;
    $tok = strtok(" \t");
  }
  return $respond;
}    

/* Funktion zum ersetzen bestimmter Zeichen
   wie Umlaute und Leerzeichen UTF-8 */
function cv_text($wert)
{
    $uml = Array(
        " " => "-",
        pack("H*", "C3A4") => "ae",
        pack("H*", "C3BC") => "ue",
        pack("H*", "C3B6") => "oe",
        pack("H*", "C39F") => "ss",
        pack("H*", "C384") => "Ae",
        pack("H*", "C39C") => "Ue",
        pack("H*", "C396") => "Oe"
    );
    $wert = trim($wert);
    $resp = strtr($wert, $uml);
    return $resp;
}


/* Funktion zum Lesen der Angebote von Marktguru */
function fetch_offers($RESOURCE_URL, $HEADERS) {
    try {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $RESOURCE_URL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $HEADERS);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); 

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch))
        {
            //throw new Exception("cURL-Fehler: " . curl_error($ch));
            echo ("cURL-Fehler\r\n");
        }
        curl_close($ch);

        //if ($httpCode < 200 || $httpCode >= 300) {
        //    //throw new Exception("HTTP-Fehler: Statuscode $httpCode");
        //    echo ("HTTP-Fehler\r\n");
        //}

        $data = json_decode($response, true);
        
        if ($data === null)
        {
            throw new Exception("Ungültiges JSON");
            echo ("Ungültiges JSON");
        }

        $results = $data["results"] ?? [];
        $lines = [];

        foreach ($results as $entry)
        {
            $price = $entry["price"] ?? null;
            $refprice = $entry["referencePrice"] ?? null;
            $description = $entry["description"] ?? "";
            if (isset($entry["unit"]["shortName"]))
            {
                $refw = $entry["unit"]["shortName"];
            }
            else
            {
                $refw = "?";
            }

            $advertisers = $entry["advertisers"] ?? [];
            $name = "Unbekannt";
            if (!empty($advertisers) && isset($advertisers[0]["name"]))
            {
                $name = $advertisers[0]["name"];
            }
            $na = str_replace("Netto Marken-Discount","Netto", $name);
            
            $prodname = $entry["product"] ?? [];
            $pname = "";
            if (!empty($prodname) && isset($prodname["name"]))
            {
                $pname = $prodname["name"]; 
            }
            
            if ($refprice !== null)
            {
                $refp =  sprintf("%.02F", $refprice);
            }
            if ($price !== null)
            {
                $pr = sprintf("%.02F", $price);
            }
            $lines[] = [$na, $pname, $description, $pr, $refp, $refw];
        }
        if (count($lines) == 0) $lines[] = ["Keiner", "No Name", "Keine Daten gefunden", "0.00", "0.00", "kg"];

        //return implode("\n", $lines);
        return $lines;
    } 
    catch (Exception $e) 
    {
        return "Fehler beim Abrufen der Daten: " . $e->getMessage();
    }
}


/* ################################################################
   # Konfiguration lesen / Arrays initialisiern                   #
   ################################################################ */

/*
    Datendatei einlesen
*/
function lese_daten()
{    
    global $pdat_data, $han_data, $prog_sec_d;
    global $plz, $suche, $fly_url;
    $buffer = "";
    $teil = "";  
    $dummy = 0;
    if (file_exists($pdat_data))
    {
        $han_data = fopen($pdat_data, 'r');
        if ($han_data)
        {
            while (($buffer = fgets($han_data, 1024))!==false)
            {
                $teil = teile_text($buffer);
                // Kommentarzeile
                if (($teil[0] >= 1) AND (stripos($teil[1],"//") !== false))
                {
                    $dummy = 0;
                }
                // Neue Sektion
                elseif (($teil[0] == 1) AND (strtoupper($teil[1]) == "[PROGRAMM]"))
                {
                    $prog_sec_d = 'prog';
                }
                elseif (($teil[0] == 1) AND (strtoupper($teil[1]) == "[PROSPEKTE]"))
                {
                    $prog_sec_d = 'flyer';
                }
                // Werte in [programm]
                elseif (($teil[0]>=2) AND ($prog_sec_d == "prog") AND (strtoupper($teil[1]) == "PLZ") AND (is_numeric($teil[2])))
                {
                    $plz = $teil[2];
                }
                elseif (($teil[0]>=2) AND ($prog_sec_d == "prog") AND (strtoupper($teil[1]) == "SUCHE"))
                {
                    $suche = $teil[2];
                }
                // Werte in [prospekte]
                elseif (($teil[0]>=2) AND ($prog_sec_d == "flyer") AND (!empty($teil[1])) AND (!empty($teil[2])))
                {
                    $fly_url[$teil[1]] = $teil[2];                   
                }
            }
        }
    }      
}

/*
    Carriage return / line feed senden je nach Ausgabe
*/
function crlf($anz = 1)
{
    global $pgm_call;
    if ($pgm_call == 1)         // HTML
    {
        echo (str_repeat("<br>", $anz));
    }
    if ($pgm_call == 2)         // Kommandozeile
    {
        echo (str_repeat("\r\n", $anz));
    } 
}

/*
    Funktion für Array Sortierung
*/
function cmp($a, $b)
{
    if ($a == $b) {
        return 0;
    }
    return ($a < $b) ? -1 : 1;
}

/*
    Ergebnisse auf der Kommandozeile anzeigen
*/
function show_terminal($daten)
{
    global $plz, $suche;
    crlf(2);    
    echo ("\033[31;1;4m"."Suche in PLZ ".$plz." nach Angeboten fuer ".$suche."\033[0m");
    crlf(2);
    echo ("\033[32;1;5m"."Suchergebnisse fuer ".$suche).":\033[0m";
    crlf(); 
    if (is_array($daten))
    {  
        foreach ($daten as $zeile)
        {
            //echo ($zeile[0]." - ".$zeile[1]." - ".$zeile[2]." - ".$zeile[3]." - ".$zeile[4]." pro ".$zeile[5]);
            echo (str_pad($zeile[0], 11)." - ".$zeile[1]);
            crlf();
            echo (str_repeat(" ", 14).trim($zeile[2]));
            crlf();
            echo (str_repeat(" ", 14).$zeile[3]."  (".$zeile[4]." pro ".$zeile[5].")");        
            crlf(1);
        }
    }
    else
    {
        echo ("Es wurden keine Daten gefunden!");
        crlf(2);
    }
}

/*
    Ergebnisse auf einer HTML-Seite anzeigen
*/
function show_html($daten)
{
    global $plz, $suche;
    global $fly_url;
?>
    <!DOCTYPE HTML>
    <html lang="de">
    
      <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Preis Tracker</title>
      </head>
      
      <style>
        body              { min-width:50px; max-width:1280px; }
        div               { display:flex; flex-flow:row wrap; }
        table             { border:3px ridge #1B838A; margin:2px; padding:0px; }
        table th          { text-align:center; width:90px; height:10px; margin:0px; padding:0px; }
        table tr          { text-align:center; margin:0px; padding:0px; border:1px solid black; }
        /*table td          { text-align:center; width:90px; background-color:#F2F2F2; }*/
        table td          { text-align:left; vertical-align:top; /*background-color:#F2F2F2;*/ border:1px solid black; margin:4px; }
        table tr:nth-child(1) { height:20px; }
        table tr:nth-child(2) { height:20px; }
        button            { font:normal bold 14px Arial; margin:0px; padding:10px; vertical-align:center;
                            border:1px solid #AAAAAA; border-radius:8px; background-color: #00EE00; }
        span button       { width:120px; height:40px; background-color: #AAAAFF; }
        div iframe        { pointer-events:none; margin:0px; padding:0px; border:0px; width:60px; height:60px; }
        .zeit             { margin:0px; padding:0px; border:0px; width:300px; height:25px; }
        #rahmen           { border-top:1px solid #1683CE; }
        #titel            { font-size:24px; font-weight:bold; word-wrap:normal; white-space:normal; }

      </style>
      
      <script> 
        function st_suche()
        {
          window.location.href = 'p-track.php?p=' + plz.value + '&w=' + prod.value;
        }
        
        function st_speichern()
        {
          window.location.href = 'p-track-edit.php?p=';
        }
        
        function preisportal()
        {
          window.open("<?php echo($fly_url["*"]); ?>", "_blank");
        }
        
        function prospekt(name)
        {
          window.open(name);
        }
      
      </script> 
      
      <body>
        <form>
          Suchen nach:<br>
          <table style="border:0;">
            <tr>
              <td>
                <table style="border:0;">
                  <tr style="border:0;">
                    <td style="border:0;">Produktname&nbsp;</td>
                    <td style="border:0;"><input type="text" size="50" id="prod" name="prod" value="<?php echo($suche); ?>"></td>
                  </tr>
                  <tr style="border:0;">
                    <td style="border:0;">In PLZ&nbsp;</td>
                    <td style="border:0;"><input type="text" size="5" maxlength="5" id="plz" name="plz" value="<?php echo($plz); ?>"></td>
                  </tr>
                </table>
              </td>
              <td style="border:0;">&nbsp;&nbsp;</td>
              <td style="border:0; vertical-align:center;">
                <button name="eins" type="button" onclick="st_suche()">
                  Suche<br>starten
                </button>
              </td>
              
              <td style="border:0;">&nbsp;</td>
              <td style="border:0; vertical-align:center;">
                <button name="drei" type="button" onclick="preisportal()" style="background-color: #FFD700;">
                  Preisportal<br>aufrufen
                </button>       
              </td>  
              
              <td style="border:0;">&nbsp;</td>
              <td style="border:0; vertical-align:center;">
                <button name="zwei" type="button" onclick="st_speichern()" style="background-color: #FF5555;">
                  Vorgaben<br>editieren
                </button>       
              </td>              
              
            </tr>
          </table>
          <br><br>
        </form>
        
        Suche in PLZ <?php echo($plz); ?> nach Angeboten f&uuml;r <?php echo($suche); ?>
        <br><br>
        Suchergebnisse: <br>
        <div>
            <table>
              <tr>
                <b>
                <td> H&auml;ndler </td>
                <td> Artikel </td> 
                <td> Beschreibung </td>
                <td> &euro; </td>
                <td> &euro; / Einheit </td>
                </b>
              </tr>
          <?php
          foreach ($daten as $zeile)
          {
          ?>
              <tr>
                <td> <?php echo($zeile[0]); ?> &nbsp; </td>
                <td> <?php echo($zeile[1]); ?> &nbsp; </td>
                <td> <?php echo($zeile[2]); ?> &nbsp; </td>
                <td> <?php echo($zeile[3]); ?> &nbsp; </td>
                <td> <?php echo($zeile[4]); ?> / <?php echo($zeile[5]); ?> &nbsp; </td>
              </tr>
            
          <?php           
          } 
          ?>
            </table>          
        </div>
        <br><br>
        Marktseiten:<br>

        <tr style="border:0;">
        <div>        
        <?php
        foreach ($fly_url as $key => $value)
        {
        if ($key !== "*")
        {
        ?> 

        <table style="border:0;">
          <tr style="border:0;">
            <td style="border:0; vertical-align:center;">
              <span>
                <button name="seite" type="button" onclick="prospekt(<?php echo ("'".$value."'"); ?>)">
                  <?php echo ($key); ?>
                </button>
              </span>
              &nbsp; &nbsp;
            </td>
          </tr>
        </table>            
    
        <?php
        }
        }
        ?>   
        </div>          


      </body>
    </html>
            
    
<?php
}


/* ################################################################
   # Hauptprogramm                                                #
   ################################################################ */

global $RESOURCE_URL, $HEADERS;
$param = "";

/*  Parameterübergabe verwalten */
//Aufruf aus HTML
if (isset($_SERVER['QUERY_STRING']))
{
    $param = $_SERVER['QUERY_STRING'];
    $pgm_call = 1;
}
//Aufruf von der Kommandozeile
else
{
    if (isset($argv[1])) $param = $argv[1];
    if (isset($argv[2])) $param .= "&" . $argv[2];
    $pgm_call = 2;
}
if (is_string($param))
{
    //str_replace('"', '', $param);  ## Fehler bei Leereichen z.b. w="rote beete"
    parse_str($param, $out);
}

lese_daten();

if (isset($out["p"])) $plz = $out["p"];
if (isset($out["P"])) $plz = $out["P"];
if (isset($out["w"])) $suche = $out["w"];
if (isset($out["W"])) $suche = $out["W"];

$suche = str_replace('"', '', $suche);
$sb = cv_text($suche); 
$RESOURCE_URL = $web."&q=".$sb."&zipCode=".$plz;

$output = fetch_offers($RESOURCE_URL, $HEADERS);
if (is_array($output)) usort($output, "cmp");

if ($pgm_call == 1)
{
    show_html($output);
}
if ($pgm_call == 2)
{
    show_terminal($output);
}





?>

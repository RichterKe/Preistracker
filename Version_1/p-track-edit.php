<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
  <head>
    <title>Preistacker Editor</title>
    <meta name="author" content="Gerd">
    <meta name="editor" content="html-editor phase 5">
    <!--<meta http-equiv="refresh" content="30; URL=index.html">-->
    <style type="text/css">
      a:link { text-decoration:none; font-weight:bold; color:#000000; }
      a:visited { text-decoration:none; font-weight:bold; color:#000000; }
      a:hover { text-decoration:none; font-weight:bold; background-color:#FFFF00; }
      a:active { text-decoration:none; font-weight:bold; background-color:#FFFFFF; }
      a:focus { text-decoration:none; font-weight:bold; background-color:#FFFFFF; }
    </style>
  </head>

  <?php
    $datei = 'p-track.dat';
    $daten = '';
    function lesen()
    {
      global $datei;
      global $daten;
      if (file_exists($datei))
      {
        $handle = fopen($datei, 'r');
        if ($handle)
        {
          $daten = '';
          while (($buffer = fgets($handle, 132)) !== false)
          {
            $daten = $daten.$buffer;
          }
        }
        fclose($handle);
      }
      return $daten;
    }
    function schreiben($inhalt)
    {
      global $datei;
      $handle = fopen($datei, 'w');
      fputs($handle, $inhalt);
      fclose($handle);
    }
  ?>

  <body text="#000000" bgcolor="#FFFFFF" link="#FF0000" alink="#FF0000" vlink="#FF0000">

    <h1>Editor Preistracker</h1>
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST">
      <table border="1" cellspacing="0" cellpadding="5">
        <tr>
          <td>
            <input type="text" name="datname" size="100%" value="<?php echo $datei?>">  
          </td>
        </tr>
        <tr valign="top" bgcolor="#FFCC00">
          <td>
            <textarea name="vorgaben" cols="110" rows="22" wrap="soft"><?php
              if (isset ($_POST['vorgaben']) &&  $_POST['vorgaben'])
              {
                echo $_POST['vorgaben'];
                schreiben($_POST['vorgaben']);
              }
              if (! isset ($_POST['vorgaben']) || ! $_POST['vorgaben'])
              {
                echo lesen();
              }
            ?></textarea>
          </td>
        </tr>
        <tr>
          <td align="center">
            <br>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <input type="submit" name="senden" value="Absenden">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <a href="./p-track.php">
              <span style="font-size:22px;">
                Zur&uuml;ck
              </span>
            </a>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <br><br>
          </td>
        </tr>
      </table>
     </form>

  </body>
</html>
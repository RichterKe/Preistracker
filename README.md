<h1>Preistracker als Docker Container</h1>  
  
Das Programm sucht auf dem Preisportal Marktguru nach Angeboten in der Nähe
und gibt die Daten auf einer HTML-Seite aus.  
  
<b>Vorbereiten:</b>  
Aus dem Verzeichnis "docker" die Datei "docker-compose.yaml" herunterladen.  
  
<b>Docker Container starten:</b>  
docker compose up -d  
  
<b>Docker Container beenden:</b>  
docker compose down  
  
<b>Image wieder entfernen:</b>  
docker image rm richterke/preistracker:v3.0  
  
<b>Bedienung:</b>  
Starten im Webbrowser unter der Adresse "localhost" oder "127.0.0.1"  
  
Beim ersten Aufruf die Vorgaben einstellen mit dem Button "Vorgaben Editieren".  
Sektion [programm]  
  hinter plz schreibst Du Deine Postleitzahl  
  hinter Suche schreibst Du einen Suchbegiff  
Sektion [prospekte]  
  hier kannst Du beliebige Webseiten von Märkten eintragen  
  Format: <Händler> <url der Webseite>  
  den Eintrag hintrer "*" darfst Du nicht verändern, der ist für das Preisportal reserviert  
  
Unter Produktname trägst Du ein beliebiges Produkt ein.  
Unter PLZ kannst Du die gewünschte Postleitzahl ändern.  
Mit der Schaltfläche "Suche starten" wird die Suche gestartet.  
  
Mit der Schaltfläche "Preisportal" wird das Preisportal Marktguru aufgerufen.  
Mit den Schaltflächen unter der Liste kannst Du Deine Marktseiten aufrufen.  
  
<b>Achtung:</b>  
Auf dem Host wird ein Verzeichnis mit dem Namen "trackerdaten" angelegt  
in diesem Verzeichnis werden die Voreinstellungen gespeichert und bei erneutem Aufruf von dort geladen.  
  
                
Bei Rückfragen erreicht Ihr mich unter: https://forum.heimnetz.de/

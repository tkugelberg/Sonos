Sonos PHP Modul für IP-Symcon
===
IP-Symcon PHP Modul um Sonos Lautsprecher zu steuern

**Inhalt**

1. [Funktionsumfang](#1-funktionsumfang)
2. [Anforderungen](#2-anforderungen)
3. [Installation](#3-installation)
4. [Instanztypen](#4-instanztypen)
5. [Konfiguration](#5-konfiguration)
6. [Variablen](#6-variablen)
7. [Timer](#7-timer)
8. [Funktionen](#8-funktionen)

## 1. Funktionsumfang
Dieses Modul is dazu gedacht um allgemeine Funktionalitäten in Sonos aus IP-Symcon heraus auszulösen.  
Die folgenden Funktionalitäten sind implementiert:

- verschiedene Quellen festlegen
  - Radiosender
  - Playlisten
  - Analogen Eingang
  - SPDIF Eingang
- Gruppenhandling
- Wiedergabe/Pause/Stop/Zurück/Vor
- Lautstärke anpassen (inkl. default volume)
- Mute, Loudness, Bass, Treble
- Balance
- Sleeptimer
- Ansagen  
Audiodateien von einem Samba-Share (z.B. Synology) oder HTTP Server abspielen und danach den vorherigen zustand wieder herstellen

## 2. Anforderungen
 - IPS >= 5.3
 - Sonos audio system

## 3. Installation
Am einfachsten ist es die Sonos Lautsprecher über das "Sonos Discovery" Modul hinzuzufügen.
Hierzu muss unter Gerätesuche (die Glocke oben rechts in der Web Konsole) "Sonos Discovery" aktiviert werden.  
<kbd>![Discovery aktivieren](imgs/addDiscovery_de.png?raw=true "Discovery aktivieren")</kbd>  
Dies installiert auch direkt das Sonos Modul aus dem Store.

Alternativ kann man das Modul auch auch manuell installieren.  
Hierzu muss man im "Module Control" (Kern Instanzen->Modules) die URL https://github.com/tkugelberg/Sonos hinzufügen.
Dann kann man eine "Sonos Discovery" Instanz erstellen.  
<kbd>![Discovery Instanz erstellen](imgs/addDiscoveryInstance_de.png?raw=true "Discovery Instanz erstellen")</kbd>

Wenn man nun diese Discovery Instanz öffnet, sieht man all im Netzwerk gefundenen Lautsprecher.  
<kbd>![Discovery Instanz](imgs/discovery_de.png?raw=true "Discovery Instanz")</kbd>  
Hier kann man dann nur bestimmte oder gleich alle "Sonos Player" Instanzen anlegen lassen.

Gleichzeitig wird auch eine "Sonos Splitter" Instanz angelegt.

## 4. Instanztypen
Es gibt 3 verschiedene Typen von Instanzen.

### 1. Sonos Discovery  
Von dieser Instanz gibt es nur eine, und sie dient lediglich dazu neue Lautsprechen im Netzwerk zu finden und einfach als Instanz anlegen zu lassen.

### 2. Sonos Splitter  
Im normalfall gibt es hiervon auch nur eine. Falls man mehr als einen Sonos Verbund (also z.B: in verschiedenen VLANs) betreibt, kann man mehere Splitter konfigurieren um dies abzubilden.  
Diese Instanz liest regelmäßig die Grupierung aus dem Sonos System aus, und gruppiert die Lautsprecher in IP-Symcon dementsprechend.  
Weiterhin dient sie dazu gemeinsame konfigurationen aller Lautsprecher zu verwalten. Dazu zählen:
  - Die höhe des "Album Art" im Web Front
  - Häuftigkeit der update Funktionalitäten
  - Playlist importe
  - Konfiguration von Radiosendern   

### 3. Sonos Player  
Hierbei handelt es sich um den eigentlichen Lautsprecher.  

## 5. Konfiguration
### 1 Sonos Splitter  
<kbd>![Splitter Instanz](imgs/splitterConfig_de.png?raw=true "Splitter Instanz")</kbd>  
1. __Album Art Höhe im Webfront__  
Wenn man im Player "Detaillierte Informationen" aktiviert, wird unter anderem in einer HTML Box ein Bild (Album Art) angezeigt.  
Diese Einstellung legt fest, wie groß dieses Bild ist.
2. __Update Grouping Frequenz__  
Hierbei handelt es sich um die Zeit in Sekunden zwischen 2 updateGrouping aufrufen der Splitter Instanz.  
Default: 120
3. __Update Status Frequenz__  
Hierbei handelt es sich um die Zeit in Sekunden zwischen 2 updateStatus aufrufen jeder Player Instanz.  
Default: 5
4. __Import Playlists__  
Mit diesem Parameter kann festgelegt werden, welche Playlisten aus Sonos in dem Profil SONOS.Playlist hinzugefügt wird.  
Dies hat zur Folge, dass diese als Button im Webfront angezeigt werden.  
Mögliche Werte sind:
  - keine  
  _Es werden keine Playlisten importiert_
  - gespeicherte
  _Eigene Playlisten, die in Sonos gespeichert wurden_
  - importierte  
  _Hierbei handelt es sich um Playlisten, die zusammen mit der Bibliothek importiert wurde (z.B. eine m3u Datei)_
  - gespeicherte & importierte
  - Favoriten  
  _z.B. Spotify Playlisten oder Radiosender, die als Favorit gespeichert wurden_
  - gespeicherte & Favoriten
  - importierte & Favoriten
  - gespeicherte, importierte & Favoriten  

5. __Radiosender__  
In dieser Tabelle müssen die Radiosender eingetragen werden, die über die funktion SNS_SetRadio( ) gestartet werden können.  
Weiterhin werden alle diese Sender als Knopf im Webfront angezeigt.  

  - Name  
Hierbei handelt es sich um den Namen des Senders. Dieser muss an die Funktion SNS_SetRadio übergeben werden und wird für den Knopf im Webfront verwendet
  - URL  
Dies ist die URL unter der der Radisender zu erreichen ist. Z.B. http://mp3-live.swr3.de/swr3_m.m3u muss als "x-rincon-mp3radio://mp3-live.swr3.de/swr3_m.m3u" angegeben werden.
  - Bild URL  
Diese URL wird verwendet, um das Serderlogo in den Detailinformationen im Webfront anzuzeigen.

Falls man in Sonos unter "TuneIn Radio" -> "Meine Radiosender" favoriten gepflegt hat, kann man diese automatisch mit dem Knopf "TuneIn Favoriten auslesen" in die Tabelle ünertragen.  
### 2 Sonos Player  
<kbd>![Player Instanz](imgs/playerConfig_de.png?raw=true "Player Instanz")</kbd>  
1. __IP-Adresse/Host__  
Dies ist der Hostname oder die IP-Adresse des Players. Sofern die Insanz aus der Discovery Instanz angelegt wurde, ist dieser Wert automatisch gefüllt.
2. __RINCON__  
RINCON ist die eindeutige Bezeichnung eines Lautsprechers. Sofern die Insanz aus der Discovery Instanz angelegt wurde, ist dieser Wert automatisch gefüllt.  
Wenn die Instanz manuell angelegt wurde und die RINCON nicht bekannt ist, kann der Knopf "RINCON auslesen" (wird eingeblendet, wenn dieses Feld leer ist) verwendet werden, um die RINCON zu ermittelt. Hierfür muss allerdings "IP-Adresse/Host" gefüllt sein.
3. __Maximale Dauer bis zur Zeitüberschreitung des ping__  
Bevor ein Lautsprecher kontaktiert wird, wird versucht diesen per Ping zu erreichen. Wenn der Lautsprecher diese Zeil lang nicht antwortet, wird er als "nicht erreichbar" erachtet.  
Wenn der Parameter auf 0 gesetzt wird, wird die Erreichbarkeit nicht überprüft.
4. __Standard Lautstärke__  
Diese Lautstärke wird verwendet, wenn die Funktion SNS_SetDefaultVolume() aufgerufen wird.
5. Nach nicht Verfügbarkeit der Gruppe automatisch wieder beitreten  
Wenn dies Option aktiviert ist wird ein Lautsprecher, der zuvor als "vanished" markiert war, wieder der vor dem verschwinden zugeordneten Gruppe hinzugefügt, solbald er wieder verfügbar ist.
6. __Mute-Steuerung__  
Diese Option legt eine Variable "Mute" an und aktiviert dass diese über SNS_updateStatus() mit dem aktuellen Wert gepflegt wird. Weiterhin taucht dann auch eine Konpf auf dem WebFront auf, über den man dies Steuern kann.
7. __Loudness-Steuerung__  
Diese Option legt eine Variable "Loudness" an und aktiviert dass diese über SNS_updateStatus() mit dem aktuellen Wert gepflegt wird. Weiterhin taucht dann auch eine Konpf auf dem WebFront auf, über den man dies Steuern kann.
8. __Tiefen-Steuerung__  
Diese Option legt eine Variable "Tiefen" an und aktiviert dass diese über SNS_updateStatus() mit dem aktuellen Wert gepflegt wird. Weiterhin taucht dann auch einen Slider auf dem WebFront auf, über den man dies Steuern kann.
9. __Höhen-Steuerung__  
Diese Option legt eine Variable "Höhen" an und aktiviert dass diese über SNS_updateStatus() mit dem aktuellen Wert gepflegt wird. Weiterhin taucht dann auch einen Slider auf dem WebFront auf, über den man dies Steuern kann.
10. __Balance-Steuerung__  
Diese Option legt eine Variable "Balance" an und aktiviert dass diese über SNS_updateStatus() mit dem aktuellen Wert gepflegt wird. Weiterhin taucht dann auch einen Slider auf dem WebFront auf, über den man dies Steuern kann.
11. __Sleeptimer-Steuerung__  
Diese Option legt eine Variable "Sleeptimer" an und aktiviert dass diese über SNS_updateStatus() mit dem aktuellen Wert gepflegt wird.
12. __Playmode-Steuerung__  
Diese Option legt die Variablen "Play Mode" und "Crossfade" an und aktiviert dass diese über SNS_updateStatus() mit dem aktuellen Wert gepflegt wird.  
Für "Play Mode" tauchen dann 6 Knöpfe und für "Crossfade" "Aus"/"An" auf dem Webfront auf, mit denen diese Funktionen gesteuert werden können.
13. __Detaillierte Informationen__  
Diese Option legt die Variablen "Details", "Titel URL", "Content Stream", "Artist", "Künstler", "Titel", "Album", "Titellänge", "Position" und "Sender ID" an, die über SNS_updateStatus() gefüllt werden.  
In der Variablen "Details" wird eine HTMLBox erzeugt, die am WebFront auch zu sehen ist. Alle anderen Variablen werden versteckt.
14. __Variablensortierung erzwingen__  
Wenn diese Option gesetzt ist, wird beim Speichern die vom Modul vorgeschlagene Reihenfolge der Vaiablen wieder hergestellt.

## 6. Variablen
Lediglich Player Instanzen haben Variablen.

- __Koordinator__  
Bei dieser versteckten Variable ist hinterlegt, ob es sich bei dem Lautsprecher zu dem aktuellen Zeitpunkt um einen Koordinator handelt.  
Auf einem Koordinator können z.B. Funktionen wie Play, Pause, Next oder der Sleeptimer verwendet werden.  
Sollte es sich bei einem Lautsprecher nicht um einen Koordinator handel und der zuständige Koordinator in IPS verfügbar sein, werden diese Kommandos automatisch an den Gruppenkoordinator weitergeleitet.
- __Gruppen Mitglieder__  
Diese Variable enthält eine Liste von Sonos Instanz IDs, die diesem Gruppen Koordinator zugewiesen sind.  
Diese Variable wird automatisch durch SNS_updateGrouping gefüllt.
- __Gruppenlautstärke__  
Diese Variable wird automatisch eingeblendet, wenn ein Lautsprecher als Gruppenmenber zugeordnet ist.  
Ihr Wert wird anhand der Lautstärke der einzelnen Gruppenmitglieder (Durchnittswert) berechnet.  
Er wird durch die Funktionen
  ```php
  SNS_ChangeGroupVolume(<InstanceID>,<Increment>);
  SNS_SetDefaultGroupVolume(<InstanceID>);
  SNS_SetGroupVolume(<InstanceID>,<Volume>);
  ``` 
  und die Funktion SNS_updateStatus() aktualisiert.
- __Teil der Gruppe__  
Diese Variable wird erstellt, wenn die Option "Koordinator" __nicht__ aktiviert ist.  
Sie enthält die InstanzID des Gruppenkoordinators der die Instanz zugeordnet ist.
- __gerade läuft__  
Diese Variable wird durch die Funktion SNS_updateStatus() aktuell gehalten.  
Sie enthält Informationen über das, was momentan gespielt wird.  
Falls die Instanz Mitglied einer Gruppe ist, wird die Variable versteckt (hidden) und mit dem Wert aus dem Gruppenkoordinator befüllt.
Wenn nicht kann sich der Wert auf 2 Arten zusammensetzen:
  1. Wenn das Feld "StreamContent" gefüllt ist, wird dieser übernommen (z.B.: bei Radiosendern)
  2. Ansonsten wird sie mit "<Titel>|<Artist>" gefüllt
- __Radio__  
Diese Variable enthält den aktuell laufenden Radiosender, sofern er in der Liste im WebFront verfügbaren Radiosender auftaucht (siehe Konfiguration).  
Eine Aktualisierung erfolgt durch die Funktion SNS_updateStatus().  
Falls die Instanz Mitglied einer Gruppe ist, wird die Variable versteckt (hidden) und mit dem Wert aus dem Gruppenkoordinator befüllt.
- __Status__  
Diese Variable enthält Informationen, in welchem Zustand sich die Sonos Instanz gerade befindet und wird von der Funktion SNS_updateStatus() aktualisiert.  
Falls die Instanz Mitglied einer Gruppe ist, wird die Variable versteckt (hidden) und mit dem Wert aus dem Gruppenkoordinator befüllt.
Mögliche Werte sind:
  - 0 - Zurück
  - 1 - Wiedergabe
  - 2 - Pause
  - 3 - Stop
  - 4 - Vor
  - 5 - Übergang

  0 bis 4 werden nur dazu genutzt um über das WebFront den Player zu steuern. 5 ist ein Wert der nur kurzfristig angenommen wird, wenn die Audioquelle gewechselt wird.
- __Lautstärke__  
Diese Variable enthält die Aktuelle Lautstärke der Instanz und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Mute__  
Diese Variable wird nur erstellt, wenn die Option "Mute-Steuerung" aktiviert ist.
Sie enthält den aktuelle Zustand ob die Instanz gemuted ist und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Loudness__  
Diese Variable wird nur erstellt, wenn die Option "Loudness-Steuerung" aktiviert ist.  
Sie enthält den aktuellen Zustand ob bei der Instanz Loudness eingeschaltet ist und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Tiefen__  
Diese Variable wird nur erstellt, wenn die Option "Tiefen-Steuerung" aktiviert ist.  
Sie enthält die aktuellen Equalizer Einstellungen der Instanz und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Höhen__  
Diese Variable wird nur erstellt, wenn die Option "Höhen-Steuerung" aktiviert ist.  
Sie enthält die aktuellen Equalizer Einstellungen der Instanz und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Balance__  
Diese Variable wird nur erstellt, wenn die Option "Balance-Steuerung" aktiviert ist.  
Sie enthält die aktuellen Equalizer Einstellungen der Instanz und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Sleeptimer__  
Diese Variable wird nur erstellt, wenn die Option "Sleeptimer-Steuerung" aktiviert ist.  
Sie enthält die aktuellen Wert des Sleeptimers der Instanz und wird von der Funktion SNS_updateStatus() aktualisiert.  
Falls die Instanz Mitglied einer Gruppe ist, wird die Variable versteckt (hidden) und mit dem Wert aus dem Gruppenkoordinator befüllt.
- __Wiedergabeliste__  
Diese Variable hat normalerweise keinen Wert gepflegt. Sie dient nur dazu vom WebFront aus eine Playliste anstarten zu können.  
Lediglich direkt nach dem Drücken des Knopfes am WebFront wird die Variable für eine Sekunde auf den Gewählten Wert gesetzt. Dies soll dem Verwener ein kurzes Feedback geben.
- __Play Mode__
Diese Variable wird nur erstellt, wenn die Option "Playmode-Steuerung" aktiviert ist.  
In diese Variablen ist der aktuelle Wert des Play Mode abgelegt und wird von der Funktion SNS_updateStatus() aktualisiert. Die möglichen Werte sind:
  - 0: "NORMAL"
  - 1: "REPEAT_ALL"
  - 2: "REPEAT_ONE"
  - 3: "SHUFFLE_NOREPEAT"
  - 4: "SHUFFLE"
  - 5: "SHUFFLE_REPEAT_ONE"
- __Crossfade__  
Diese Variable wird nur erstellt, wenn die Option "Playmode-Steuerung" aktiviert ist.  
Sie enthält den aktuellen Wert der Crossfade Einstellungen und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Titel URL__  
Diese Variable wird nur erstellt, wenn die Option "Detaillierte Informationen" aktiviert ist.  
Sie enthält die URL zu dem Cover das gerade in Sonos angezeigt wird. Dies gilt aber nur für Titel, nicht für Streams.  
Die Variable wird von der Funktion SNS_updateStatus() aktualisiert.
- __Content Stream__  
Diese Variable wird nur erstellt, wenn die Option "Detaillierte Informationen" aktiviert ist.  
Sie enthält den Conten Stram bei bei gestreamten Sender (z.B. aktuelle Informationen) und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Künstler__  
Diese Variable wird nur erstellt, wenn die Option "Detaillierte Informationen" aktiviert ist.  
Sie enthält den Künster des aktuell abgespielten Titels und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Album__  
Diese Variable wird nur erstellt, wenn die Option "Detaillierte Informationen" aktiviert ist.  
Sie enthält das Album des aktuell abgespielten Titels und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Titellänge__  
Diese Variable wird nur erstellt, wenn die Option "Detaillierte Informationen" aktiviert ist.  
Sie enthält die länge des aktuell abgespielten Titels und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Position__  
Diese Variable wird nur erstellt, wenn die Option "Detaillierte Informationen" aktiviert ist.  
Sie enthält die aktuelle Position in dem aktuell abgespielten Titels und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Titel__  
Diese Variable wird nur erstellt, wenn die Option "Detaillierte Informationen" aktiviert ist.  
Sie enthält den Titel des aktuell abgespielten Titels und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Sender ID__  
Diese Variable wird nur erstellt, wenn die Option "Detaillierte Informationen" aktiviert ist.  
Sie enthält den die StationID aus TuneIn und wird von der Funktion SNS_updateStatus() aktualisiert.
- __Details__  
Diese Variable wird nur erstellt, wenn die Option "Detaillierte Informationen" aktiviert ist.  
Dies ist eine HTMLBox die das Cover, den Titel den Künster, das Album und Positionsinfos anzeigt:  
![Details Song](imgs/details_song.png?raw=true "Details song")  
Wenn gerade ein Sender gestreamt wird, sind nur ContenStram und Titel enthalten:  
![Details Radio](imgs/details_radio.png?raw=true "Details Radio")  

## 7. Timer
### 1. Sonos Discovery  
Das Discovery Modul hat einen Timer "Sonos Discovery", welcher alle 5 Minuten die Funktion SNS_Discover() aufruft.  
Hierdurch werden neue Instanzen gefunden und in der Gerätesuche angezeigt.
### 2. Sonos Splitter  
Das Splitter Modul hat einen Timer "Sonos Update Grouping", welcher ensprechend der Konfuguration regelmäßig die Funktion SNS_updateGrouping() aufruft.  
Hierbei wird von einem der Player der "Zone Group Status" abgerufen. Dieser enthält eine Liste von Koordinatoren inklusive der Lautsprecher die ihm zugeordnet sind.  
Weiterhin werden Lautsprecher als "vaished" gemeldet, die nicht mehr in Sonos verfügbar sind.  
All diese Informationen werden aufbereitet und an die Player Instanzen geschickt.  
Diese passen dementsprechend die notwendigen Variablen an und blenden diese ein oder aus. All das, was durch die Funktion SNS_SetGroup() auch passiert.  
Zusätzlich werden die Instanzen welche als vanished gemeldet werden komplett ausgeblendet und werden als vanished markiert.  
Dies hat zur Folge, dass eine Exception geraised wird, wenn versucht wird eine Funktion auf einer solchen Instanz aufzurufen.
### 3. Sonos Player
Das Player Modul hat einen Timer "Sonos Update Status", welcher ensprechend der Konfuguration regelmäßig die Funktion SNS_updateStatus() aufruft.  
Es werden zu verschiedenen Variablen die aktuellen Werte ermittelt und gespeichert.

## 8. Funktionen
### 8.1. Sonos Discovery  
- __SNS_Discover(int $InstanceID)__  
Diese Funktion wird in regelmäßigen Abständen per Timer aufgerufen. Es ist nicht notwendig diese manuell auszuführen.

### 8.2. Sonos Splitter   
- __SNS_updateGrouping(int $InstanceID)__  
Diese Funktion wird in regelmäßigen Abständen per Timer aufgerufen. Es ist nicht notwendig diese manuell auszuführen.
- __SNS_ReadTunein(int $InstanceID, string $ip)__  
Diese Funktion ist nur für das Konfigurationsformular. Endbenutzer sollten diese Funktion nicht verwenden.
- __SNSUpdatePlaylists(int $InstanceID)__  
Bei der Ausführung dieser Funktion werden entsprechend der Konfiguration "Import Playlists" die Playlisten aus dem Sonos System abgerufen und in dem Profil "SONOS.Playlists" gespeichert.

### 8.3. Sonos Player  
- __SNS_alexaResponse(int $InstanceID)__  
Diese Funktion dient dazu ein "Custom Skill für Alexa" bereitzustellen. Für den Endanwender eher uninteressant.
- __SNS_ChangeGroupVolume(int $InstanceID, int $increment)__  
Ändert die Lautstärke jedes Mitglieds einer Gruppe um den mitgelieferten Wert in $increment.  
Kann positiv oder negativ sein.  
Falls die Lautstärke 100 übersteigen oder 0 unterschreiten würde, wird die Lautstärke auf diese Werte gesetzt.  
- __SNS_ChangeVolume(int $InstanceID, int $increment)__  
Ändert die Lautstärke einer Sonos Instanz um den mitgelieferten Wert in $increment.  
Kann positiv oder negativ sein.  
Falls die Lautstärke 100 übersteigen oder 0 unterschreiten würde, wird die Lautstärke auf diese Werte gesetzt.  
- __SNS_DelegateGroupCoordinationTo(int $InstanceID, int $newGroupCoordinator, bool $rejoinGroup)__  
Macht einen anderen Lautsprecher zum Gruppenkoordinator.  
Wird auf die instanz des aktuellen Gruppenkoordinators ausgeführt. $newGroupKoordinator ist der neue.
Wenn der Lautsprecher Box in der Gruppe bleiben soll, muss $rejoinGroup "true" sein, ansonsten wird der Alte Koordinator aus der Gruppe entfernt.  
- __SNS_DeleteSleepTimer(int $InstanceID)__  
Bricht den Sleeptimer ab.  
Sollte das Kommando auf einem Gruppenmember ausgeführt werden, wird es automatisch an den zuständigen Koordinator weitergeleitet und gilt somit für die ganze Gruppe.  
- __SNS_Next(int $InstanceID)__  
Springt zum nächsten Titel.  
Sollte das Kommando auf einem Gruppenmember ausgeführt werden, wird es automatisch an den zuständigen Koordinator weitergeleitet und gilt somit für die ganze Gruppe.  
- __SNS_Pause(int $InstanceID)__  
Pausiert die Wiedergabe.  
Sollte das Kommando auf einem Gruppenmember ausgeführt werden, wird es automatisch an den zuständigen Koordinator weitergeleitet und gilt somit für die ganze Gruppe.  
- __SNS_Play(int $InstanceID)__  
Setzt die Wiedergabe fort.  
Sollte das Kommando auf einem Gruppenmember ausgeführt werden, wird es automatisch an den zuständigen Koordinator weitergeleitet und gilt somit für die ganze Gruppe.  
- __SNS_PlayFiles(int $InstanceID, string $files, int $volumeChange)__  
  - Falls gerade etwas wiedergegeben wird, wird die Wiedergabe pausiert
  - Die Lautstärke wird entsprechend $volumeChange angepasst  
   - "0" würde die Lautstärke nicht ändern  
   - "17" würde die Lautstärke auf 17 setzen  
   - "+8" würde die Lautstärke um 8 anheben  
   - "-8" würde die Lautstärke um 8 absenken
  - Alle Dateien, die in dem (als JSON encodierten) Array $files angegeben wurden, werden abgespielt.  
Entweder von einem Samba Share (CIFS) (z.B. "//server.local.domain/share/file.mp3") oder von einem HTTP Server (z.B.: "http://ipsymcon.fritz.box:3777/user/ansage/hallo.mp3")
  - Die Ausgangslautstärke wird wieder hergestellt
  - Die Audioquelle wird wieder hergestellt
  - Falls eine Wiedergabe aktiv war, wird sie wieder gestartet
   
Falls die Instanz einer Gruppe zugeordnet ist, wird sie für die Wiedergabe der Dateien aus der Gruppe genommen und danach wieder hinzugefügt.  
Mehrere Dateien abzuspielen könnte so aussehen:  
```php
SNS_PlayFiles(17265, json_encode( Array( "//ipsymcon.fritz.box/sonos/bla.mp3",
                                         "http://www.sounds.com/blubb.mp3") ), 0);
```
- __SNS_PlayFilesGrouping(int $InstanceID, string $instances, string $files, int $volumeChange)__  
Diese Funktion ruft die Funktion SNS_PlayFiles() auf. Dementsprechend ist das (als JSON encodierte) array $files gleich aufgebaut.  
Vorher werden die in $instances mitgegebenen Instanzen zu der gruppe von $InstanceID hinzugefügt.  
Das (als JSON encodierte) array $instances beinhaltet pro hinzuzufügender instanz einen Eintrag mit dem Key "&lt;instance ID&gt;" der hinzuzufügenden instanz und einem Array mit settings. Diese Array kennt derzeit lediglich einen Eintrag mit dem Key "volume" mit dem Volume Wert entsprechend dem $volumeChange aus der Funktion SNS_PlayFiles.  
Beispiel:
```php
SNS_PlayFilesGrouping(46954 , json_encode( array( 11774 => array( "volume" => 10),
                                                    27728 => array( "volume" => "+10"),
                                                    59962 => array( "volume" => 30) ) ), json_encode(array( IVNTTS_saveMP3(12748, "Dieser Text wird angesagt"))), 28 );
```
  - Die Instanzen 11774, 27728 und 59962 werden der Gruppe mit dem Koordinator 46954 hinzugefügt.  
  - Die Instanz 11774 wird auf Lautstärke 10 gesetzt.  
  - Bei der Instanz 27728 wird die Lautstärke um 10 Punkte angehoben.  
  - Die Instanz 59962 wird auf Lautstärke 30 gesetzt.  
  - Die Instanz 46954 wird Gruppen Koordinator für die Ansage(n) und wird auf Lautstärke 28 gesetzt.  
  - Der Text "Dieser Text wird angesagt" wird vom dem SymconIvona Modul (Instanz 12748) in eine MP3 umgewandelt, welche dann abgespielt wird.
- __SNS_Previous(int $InstanceID)__  
Startet den vorhergehenden Titel in der Liste.  
Sollte das Kommando auf einem Gruppenmember ausgeführt werden, wird es automatisch an den zuständigen Koordinator weitergeleitet und gilt somit für die ganze Gruppe.  
- __SNS_RampToVolume(int $InstanceID, string $rampType, int $volume)__  
Ruft die Funktion RampToVolume in Sonos auf.  
Der Parameter $rampType kann als integer oder als string übergeben werden.  
  - 1 entspricht SLEEP_TIMER_RAMP_TYPE
  - 2 entspricht ALARM_RAMP_TYPE
  - 3 entspricht AUTOPLAY_RAMP_TYPE
- __SNS_SetAnalogInput(int $InstanceID, int $input_instance)__  
Selektiert den Analogen Input einer Instanz als Audioquelle.  
Sollte die Instanz sich gerade in einer Gruppe befinden, wird sie automatisch aus der Gruppe genommen und danach die neue Audiquelle gesetzt.  
Sollte diese Funktion auf einem Gruppenkoordinator ausgeführt werden gilt die neue Audioquelle für die ganze Gruppe.  
- __SNS_SetBalance(int $InstanceID, int $balance)__  
Passt die Balance Einstellungen im Equalizer der Sonos Instanz an. Nur Sinnvoll bei Setreopaaren oder AMPS.  
Mögliche Werte liegen zwischen -100 (ganz links) und 100 (gnaz rechts).  
- __SNS_SetBass(int $InstanceID, int $bass)__  
Passt die Bass Einstellungen im Equalizer der Sonos Instanz an.  
Mögliche Werte liegen zwischen -10 und 10.  
- __SNS_SetCrossfade(int $InstanceID, bool $crossfade)__  
Schaltet den Crossfade Modus für eine Instanz ein oder aus.  
Falls die Instanz Teil einer Gruppe ist, wird das Kommano automatisch an den Gruppenkoordinator weitergeleitet.  
0,1, true und false sind gültige Werte für $crossfade.  
- __SNS_SetDefaultGroupVolume(int $InstanceID)__  
Führt die Funktion SNS_SetDefaultVolume( ) für jeden Mitglied einer Gruppe aus.  
- __SNS_SetDefaultVolume(int $InstanceID)__  
Ändert die Lautstärke einer Instanz auf die Standard Lautstärke.  
- __SNS_SetGroup(int $InstanceID, int $groupCoordinator)__  
Fügt die Instanz zu einer Gruppe hinzu oder entfernt es aus einer Gruppe.  
Wenn die InstanzID eines Gruppenkoordinators mitgegeben wird, wird die instanz dieser Gruppe hinzugefügt.  
Wenn 0 mitgegeben wird, wird die Instanz aus allen Gruppen entfernt.  
- __SNS_SetGroupVolume(int $InstanceID, int $volume)__  
Führt die Funktion SNS_ChangeGroupVolume($volume - "aktuelle Lautstärke" ) aus.  
- __SNS_SetHdmiInput(int $InstanceID, int $input_instance)__  
Selektiert den HDMI Input einer Instanz als Audioquelle.  
Sollte die Instanz sich gerade in einer Gruppe befinden, wird sie automatisch aus der Gruppe genommen und danach die neue Audiquelle gesetzt.  
Sollte diese Funktion auf einem Gruppenkoordinator ausgeführt werden gilt die neue Audioquelle für die ganze Gruppe.
Anmerkung: Da HDMI scheinbar genau wie S/PDIF behandelt wird, wird intern lediglich SetSpdifInput aufgerufen.  
- __SNS_SetLoudness(int $InstanceID, bool $loudness)__  
Setzt das Loundess Flag an einer Instanz.  
0,1, true und false sind gültige Werte für $loudness.  
- __SNS_SetMute(int $InstanceID, bool $mute)__  
Mutet or unmutet eine Instanz.
0,1, true und false sind gültige Werte für $mute.  
- __SNS_SetPlaylist(int $InstanceID, string $name)__  
Entfernt alle Titel aus einer Queue und fügt alle Titel einer Playliste hinzu.  
Der Name der Playliste muss in Sonos bekannt sein.  
Es wird zunächst nach dem Namen in den gespeicherten Playlisten gesucht. Wird er dort nciht gefunden, wird ebenfalls in den Importierten Playlisten gesucht. Dabei wird ein Unterstrich ("_") durch ein Leerzeichen (" ") ersetzt und die Endungen ".m3u" und ".M3U" werden entfernt. Somit kann z.B. die Playliste mit dem Name "3_Doors_Down.m3u" mit dem Befehl SNS_SetPlaylist(12345,"3 Doors Down"); gestartet werden.  
Wird die Playlist auch hier nicht gefunden, wird zuletzt in den Favoriten gesucht.
Sollte die Instanz sich gerade in einer Gruppe befinden, wird sie automatisch aus der Gruppe genommen und danach die neue Audiquelle gesetzt.  
Sollte diese Funktion auf einem Gruppenkoordinator ausgeführt werden gilt die neue Audioquelle für die ganze Gruppe.  
- __SNS_SetPlayMode(int $InstanceID, int $playMode)__  
Setzt den Play Mode einer Sonos Instanz.  
Falls die Instanz Mitglied einer Gruppe ist, wird das Kommando automatisch an den Gruppenkoordinator weitergeleitet.  
Mögliche Werte für den Play Mode sind:
  - 0: "NORMAL"
  - 1: "REPEAT_ALL"
  - 2: "REPEAT_ONE"
  - 3: "SHUFFLE_NOREPEAT"
  - 4: "SHUFFLE"
  - 5: "SHUFFLE_REPEAT_ONE"
- __SNS_SetRadio(int $InstanceID, string $radio)__  
Setzt die Audioquelle auf die URL des in $radio mitgegebenen Radiosenders.  
Dieser muss hierzu in der Splitter Instanz gepflegt sein.
Sollte die Instanz sich gerade in einer Gruppe befinden, wird sie automatisch aus der Gruppe genommen und danach die neue Audiquelle gesetzt.  
Sollte diese Funktion auf einem Gruppenkoordinator ausgeführt werden gilt die neue Audioquelle für die ganze Gruppe.
- __SNS_SetSleepTimer(int $InstanceID, int $minutes)__  
Setzt den Sleeptimer auf die angegebene Anzahl an Minuten.  
Sollte das Kommando auf einem Gruppenmember ausgeführt werden, wird es automatisch an den zuständigen Koordinator weitergeleitet und gilt somit für die ganze Gruppe.  
- __SNS_SetSpdifInput(int $InstanceID, int $input_instance)__  
Selektiert den SPDIF Input einer Instanz als Audioquelle.  
Sollte die Instanz sich gerade in einer Gruppe befinden, wird sie automatisch aus der Gruppe genommen und danach die neue Audiquelle gesetzt.  
Sollte diese Funktion auf einem Gruppenkoordinator ausgeführt werden gilt die neue Audioquelle für die ganze Gruppe.  
- __SNS_SetTransportURI(int $InstanceID, string $uri)__  
Setzt die Transport URI auf den angegebenen Wert.  
Sollte die Instanz sich gerade in einer Gruppe befinden, wird sie automatisch aus der Gruppe genommen und danach die neue Audiquelle gesetzt.
Sollte diese Funktion auf einem Gruppenkoordinator ausgeführt werden gilt die neue Audioquelle für die ganze Gruppe.  
- __SNS_SetTreble(int $InstanceID, int $treble)__  
Passt die Treble Einstellungen im Equalizer der Sonos Instanz an.
Mögliche Werte liegen zwischen -10 und 10.  
- __SNS_SetVolume(int $InstanceID, int $volume)__  
Passt die Lautstärke einer Instanz an.
Mögliche Werte liegen zwischen 0 and 100.  
- __SNS_Stop(int $InstanceID)__  
Hält die Wiedergabe an.  
Sollte das Kommando auf einem Gruppenmember ausgeführt werden, wird es automatisch an den zuständigen Koordinator weitergeleitet und gilt somit für die ganze Gruppe.  
- __SNS_updateStatus(int $InstanceID)__  
Diese Funktion wird in regelmäßigen Abständen per Timer aufgerufen. Es ist nicht notwendig diese manuell auszuführen.  
- __SNS_getRINCON(int $InstanceID, string $ip)__  
Diese Funktion ist nur für das Konfigurationsformular. Endbenutzer sollten diese Funktion nicht verwenden.
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![StyleCI](https://github.styleci.io/repos/89942287/shield?branch=master)](https://github.styleci.io/repos/89942287)

# IPSymconEchoRemote

Modul für IP-Symcon ab Version 5.0. Ermöglicht die Fernsteuerung mit einem Amazon Echo / Amazon Dot / Amazon Echo Show von IP-Symcon aus.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Konfiguration](#5-konfiguration)  
6. [Anhang](#6-anhang)  

## 1. Funktionsumfang

  - Steuerung von Musik:
     - Wiedergabe
     - Pause
     - Stop
     - Weiter
     - Zurück
     - Lautstärke einstellen
     - 30 Sekunden zurückspulen
     - 30 Sekunden vorspulen
     - Titel mischen
     - Titel wiederholen
     - Radio TuneIn Sender auswählen     
 - Sprachausgabe auf einem Echo (Text to Speech)
 - Uhrzeit der nächsten Weckzeit
 - Anzeige der Einkaufsliste
 - Anzeige der Aufgabenliste
 - Wettervorhersage
 - Verkehrsmeldungen
 - Flash Briefing
 - Guten Morgen
 - Singt ein Lied
 - Erzählt eine Geschichte
 - Startet eine Routine
 - Koppeln von Bluetooth Geräten
        

## 2. Voraussetzungen

 - IPS 5.0
 - Echo / Echo Dot / Echo Show

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://<IP-Symcon IP>:3777/console/_ öffnen. 


Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.1) klicken

![Store](img/store_icon.png?raw=true "open store")

Im Suchfeld nun

```
Echo Remote
```  

eingeben

![Store](img/module_store_search.png?raw=true "module search")

und schließend das Modul auswählen und auf _Installieren_

![Store](img/install.png?raw=true "install")

drücken.


#### Alternatives Installieren über Modules Instanz (IP-Symcon < 5.1)

Die Webconsole von IP-Symcon mit _http://<IP-Symcon IP>:3777/console/_ öffnen. 

Anschließend den Objektbaum _Öffnen_.

![Objektbaum](img/objektbaum.png?raw=true "Objektbaum")	

Die Instanz _'Modules'_ unterhalb von Kerninstanzen im Objektbaum von IP-Symcon (>=Ver. 5.x) mit einem Doppelklick öffnen und das  _Plus_ Zeichen drücken.

![Modules](img/Modules.png?raw=true "Modules")	

![Plus](img/plus.png?raw=true "Plus")	

![ModulURL](img/add_module.png?raw=true "Add Module")
 
Im Feld die folgende URL eintragen und mit _OK_ bestätigen:

```
https://github.com/Wolbolar/IPSymconEchoRemote 
```  
	        
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_    

Es wird im Standard der Zweig (Branch) _master_ geladen, dieser enthält aktuelle Änderungen und Anpassungen.
Nur der Zweig _master_ wird aktuell gehalten.

![Master](img/master.png?raw=true "master") 

Sollte eine ältere Version von IP-Symcon die kleiner ist als Version 5.1 eingesetzt werden, ist auf das Zahnrad rechts in der Liste zu klicken.
Es öffnet sich ein weiteres Fenster,

![SelectBranch](img/select_branch.png?raw=true "select branch") 

hier kann man auf einen anderen Zweig wechseln, für ältere Versionen kleiner als 5.1 ist hier
_Old-Version_ auszuwählen. 


### b. Gruppen und Geräte in Amazon App benennen  

In der Alexa App alle Geräte benennen.

### c. Einrichtung in IP-Symcon

In IP-Symcon nun zunächst mit einem rechten Mausklick auf _Konfigurator Instanzen_ eine neue Instanz mit _Objekt hinzufügen -> Instanz_ (_CTRL+1_ in der Legacy Konsole) hinzufügen, und _Echo_ auswählen.

![AddInstance](img/configurator_add_instance.png?raw=true "Add Instance")


Es öffnet sich das Konfigurationsformular.

![ConfigIO](img/io_config_echo.png?raw=true "Config IO")
 
Hier sind anzugeben:
 - Amazon Benutzername
 - Amazon Passwort
 - Amazon 2FA Code (falls in Amazon die Zwei-Schritt-Verifizierung eingestellt ist)
 - Sprache

Vielen Dank an dieser Stelle an ok1982, der die Unterstützung der Zwei-Schritt-Verifizierung ermöglicht hat. Eine Beschreibung, wie die Verfizierung einzurichten ist findet ihr hier:

<a href="https://www.symcon.de/forum/attachment.php?attachmentid=48519&d=1554833431">Anleitung 2FA.pdf</a>

Alternativ zu Benutzername und Passwort kann auch ein gültiger Cookie verwendet werden.
 

 
 
##### Ermittlung des Cookie
Wenn beim Amazon Konto die Zwei-Schritt-Verifizierung aktiviert ist, dann ist der Anmeldeweg über den Cookie zu wählen, da vom Modul keine Zwei-Schritt-Verifizierung unterstützt wird.

Wie kann der Cookie ermittelt werden?

Im folgenden Beispiel wird gezeigt, wie der Cookie einer aktiven Browser Session ermittelt werden kann.

In dem Beispiel nutzen wir die Entwicklertools von Chrome, andere Browser verfügen aber über ähnliche Werkzeuge. Die Bilder und Anleitung orientiert sich jetzt aber an Chrome. 

Wie öffnen also zunächst die Webseite
https://alexa.amazon.com
und melden uns dort mit dem Amazon Account an auf den auch der Echo / Dot registriert ist. 

Nun wechseln wir unter Musik, Videos und Bücher und wählen TuneIn aus. Jetzt öffnen wir die Entwicklertools mit F12: es öffnet sich auf der rechten Seite ein weiteres Fenster.

Nun wählen wir einen Radiosender bei TuneIn mit Doppelklick aus. Der Radiosender sollte nun das Abspielen auf dem Echo beginnen. Gleichzeitig sehen wir im Fenster der Entwicklertools unter Netzwerk einen Eintrag mit queue-and-play.

Wir öffnen jetzt den Eintrag queue-and-play (linke Spalte) mit einem Doppelklick und es öffnen sich ein weiters Fenster (rechte Spalte).

In dem Fenster können wir nun Cookie Eintrag finden, den wir für das Modul benötigen. 
Im Block Request Headers findet man ihn. Der Cookie ist ziemlich lang und muss komplett kopiert werden.

 ![Cookie](img/Cookie.jpg?raw=true "Cookie")

#### Benutzung des Konfigurators
Nach der Einrichtung der IO-Instanz können im Konfigurator die Geräte eingelesen werden. Es erscheint eine Liste der verfügbaren Geräte mit _Gerätenamen_, _Gerätetyp_, _Gerätefamilie_, _Gerätenummer_ und _InstanzID_.
Das Gerät ist grün, insofern es noch nicht angelegt worden ist.
  
  ![List](img/echo_device_list.png?raw=true "Config IO")
  
Dann das gewünschte Gerät markieren und auf 
 
 ![Create](img/create.png?raw=true "Config IO")
 
_Erstellen_ drücken, die Instanz wird dann erzeugt.



### Webfront Ansicht


 ![Webfront](img/webfront.png?raw=true "Config IO")

## 4. Funktionsreferenz

### Echo Remote Device:
 
**Play**
```php
ECHOREMOTE_Play(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Pause**
```php
ECHOREMOTE_Pause(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Next**
```php
ECHOREMOTE_Next(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Previous**
```php
ECHOREMOTE_Previous(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**SetVolume**
```php
ECHOREMOTE_SetVolume(int $InstanceID, int $volume)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Parameter _$volume_ Volume des Amazon Echo Dot, min 0 max 100

**Mute / Unmute**
```php
ECHOREMOTE_Mute(int $InstanceID, bool $mute)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Parameter _$mute_ true Setzt Volume des Amazon Echo Dot auf 0, min 0 max 100
Parameter _$mute_ false Setzt Volume des Amazon Echo Dot auf den letzten bekannten Lautstärke Wert

**Rewind30s**
```php
ECHOREMOTE_Rewind30s(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Forward30s**
```php
ECHOREMOTE_Forward30s(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Shuffle**
```php
ECHOREMOTE_Shuffle(int $InstanceID, bool Shuffle)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Repeat**
```php
ECHOREMOTE_Repeat(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices  

**GetQueueInformation**
```php
ECHOREMOTE_GetPlayerInformation(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 
 
Liefert eine Liste mit Informationen zum aktuell abgespielten Titel bzw. zum aktuellen Sender.  

**GetPlayerInformation**
```php
ECHOREMOTE_GetQueueInformation(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 
 
Liefert eine Liste mit den Media Einträgen der aktuellen Abspielliste.  

**GetNotifications**
```php
ECHOREMOTE_GetNotifications(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 
 
Liefert eine Liste mit den aktuellen Weckern.  

**GetToDos**
```php
ECHOREMOTE_GetToDos(int $InstanceID, string $type, bool $completed)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Parameter _$type_ gesuchter Itemtyp: 'SHOPPING_ITEM' oder 'TASK'

Parameter _$completed_ null: es werden alle Einträge geliefert, false: es werden die offenen Einträge geliefert, true: es werden die erledigten Einträge geliefert
 
Liefert eine Liste mit den Einträgen der aktuellen Einkaufsliste oder To-Do-Liste.  

**JumpToMediaId**
```php
ECHOREMOTE_JumpToMediaId(int $InstanceID, string MediaID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 
 
Parameter _$MediaID_ MediaID, die innerhalb einer Abspielliste angesprungen werden soll

Springt zu der angegebenen ID der aktuellen Abspielliste.  

**TuneIn**
```php
ECHOREMOTE_TuneIn(int $InstanceID, string $station)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Parameter _$station_ Station ID ist die guideId die entsprechend der Anleitung pro Sender einmal ausgelesen werden muss

**TuneInPreset**
```php
ECHOREMOTE_TuneInPreset(int $InstanceID, int $preset)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Parameter _$preset_ Positions ID der Radiostation im Modul    

**PlayAmazonMusic**
```php
ECHOREMOTE_PlayAmazonMusic(integer $InstanceID, string $seedid, string $stationname)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Parameter _$seedid_ Seed ID ist die _seedId_ die pro Sender einmal ermittelt werden muss  

Parameter _$stationname_ Station Name der _stationName_ der pro Sender ermittelt werden muss

**PlayAmazonPrimePlaylist**
```php
ECHOREMOTE_PlayAmazonPrimePlaylist(integer $InstanceID, string $asin)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Parameter _$asin_ Asin ist die _asin_ die entsprechend der Anleitung pro Sender einmal ausgelesen werden muss  

**Text to Speech**
```php
ECHOREMOTE_TextToSpeech(integer $InstanceID, string $text_to_speech)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Parameter _$text_to_speech_ Text der von dem Gerät vorgelesen werden soll     

**Wettervorhersage**
```php
ECHOREMOTE_Weather(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Liest den Wetterbericht auf dem Gerät vor

**Verkehrsmeldungen**
```php
ECHOREMOTE_Traffic(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Liest Verkehrsmeldungen auf dem Gerät vor

**FlashBriefing**
```php
ECHOREMOTE_FlashBriefing(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Liest ein Flash Briefing auf dem Gerät vor

**Guten Morgen**
```php
ECHOREMOTE_Goodmorning(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Spielt die _"Guten Morgen Mitteilung"_ auf dem Gerät ab

**Singt ein Lied**
```php
ECHOREMOTE_SingASong(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Spielt ein Lied auf dem Gerät ab

**Erzählt Geschichte**
```php
ECHOREMOTE_TellStory(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Spielt ein kurze Geschichte auf dem Gerät ab
     
**Ermitteln die Bluetooth Verbindungen**
```php
array ECHOREMOTE_ListPairedBluetoothDevices(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Es werden die für das Gerät angelegten Bluetooth Verbindungen ermittelt. Hinweis: die Bluetootheinrichtung selber hat mit der Amazon App oder im Dialog zu erfolgen.

Beispiel:
```php
$devices = ECHOREMOTE_ListPairedBluetoothDevices(47111);

var_dump($devices);
```

Es wird eine Liste der eingerichteten Bluetooth Verbindungen und deren Eigenschaften ausgegeben:
```php
array(1) {
  [0]=>
  array(5) {
    ["address"]=>
    string(17) "00:16:94:25:7B:93"
    ["connected"]=>
    bool(false)
    ["deviceClass"]=>
    string(5) "OTHER"
    ["friendlyName"]=>
    string(7) "PXC 550"
    ["profiles"]=>
    array(2) {
      [0]=>
      string(9) "A2DP-SINK"
      [1]=>
      string(5) "AVRCP"
    }
  }
}
```

**Verbinden eines Bluetooth Gerätes**
```php
ECHOREMOTE_ConnectBluetooth(integer $InstanceID, string $bluetooth_address)
``` 
Parameter _$InstanceID_: ObjektID des Echo Remote Devices.
 
Parameter _$bluetooth_address_: Adresse des zu verbindenden Gerätes 

Es wird der Verbindungsaufbau zu dem angegeben Gerät initiiert.

Beispiel:
```php
ECHOREMOTE_ConnectBluetooth(47111, '00:16:94:25:7B:93');
```

**Trennen einer Bluetooth Verbindung**
```php
ECHOREMOTE_DisconnectBluetooth(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Es wird eine bestehende Bluetooth Verbindung getrennt.

**Starten einer Routine**
```php
boolean ECHOREMOTE_StartAlexaRoutine(integer $InstanceID, string $utterance)
``` 
Parameter _$InstanceID_: ObjektID des Echo Remote Devices.
 
Parameter _$utterance_: 'Sprachausdruck' der zu startenden Routine. Routinen können in der Alexa App definiert, 
konfiguriert und aktiviert werden.

Es wird die zum Sprachausdruck passende Routine gestartet. Im Fehlerfall wird false zurückgegeben.

Beispiel:
```php
ECHOREMOTE_StartAlexaRoutine(47111, 'Starte meinen Tag');
```

### AmazonEchoIO:
**Anmelden**
```php
boolean ECHOIO_LogIn(integer $InstanceID)
``` 
Parameter _$InstanceID_: ObjektID der EchoIO Instanz.

**Abmelden**
```php
boolean ECHOIO_LogOff(integer $InstanceID)
``` 
Parameter _$InstanceID_: ObjektID der EchoIO Instanz.

**Anmeldestatus überprüfen**
```php
boolean ECHOIO_CheckLoginStatus(integer $InstanceID)
``` 
Parameter _$InstanceID_: ObjektID der EchoIO Instanz.




## 5. Konfiguration:


### Echo Remote:  

| Eigenschaft     | Typ     | Standardwert | Funktion                                                              |
| :-------------: | :-----: | :----------: | :-------------------------------------------------------------------: |
| Devicetype      | string  |    -          | Typ des Geräts                                                        |
| Devicenumber    | string  |    -          | Device Nummer des Geräts (Seriennummer)                               |
| TuneInStations  | array   |  Liste von ausgewählten Sendern mit den Attributen 'position', 'station' und 'station_id'| Liste der im Webfront angebotenen Sender                              |
| UpdateIntervall | integer |  0            | Intervall in Sekunden, in dem die Daten vom Gerät geholt werden und die Statusvariablen aktualisiert werden       |
| ExtendedInfo    | boolean |  false | Auswahl, ob erweiterte Statusvariablen (Titel, Subtitel_1, Subtitel_2) sowie das MediaImage 'MediaImageCover' zur Verfügung gestellt werden sollen
| AlarmInfo       | boolean |  false | Auswahl, ob Weckzeiten (nextAlarmTime, lastAlarmTime) in Statusvariablen abgebildet werden sollen

## 6. Anhang


#### Echo Remote Device:

GUID: `{496AB8B5-396A-40E4-AF41-32F4C48AC90D}` 
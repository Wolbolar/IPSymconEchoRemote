[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)

# IPSymconEchoRemote

Modul für IP-Symcon ab Version 5.0. Ermöglicht die Fernsteuerung mit einem Amazon Echo / Amazon Dot / Amazon Echo Show von IP-Symcon aus.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Anhang](#5-anhang)  

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
 - Wettervorhersage
 - Verkehrsmeldungen
 - Flash Briefing
 - Guten Morgen
 - Singt ein Lied
 - Erzählt eine Geschichte
        

## 2. Voraussetzungen

 - IPS 5.0
 - Echo / Echo Dot / Echo Show

## 3. Installation

### a. Laden des Moduls

Die IP-Symcon (min [Ver. 5.0](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar "IP-Symcon 5")) Webkonsole öffnen ( *http://<IP-SYMCON IP>:3777/console/* ). Im Objektbaum unter Kerninstanzen die Instanz __*Modules*__ durch einen doppelten Mausklick öffnen.

![Modules](img/modules.png?raw=true "Modules")

In der _Modules_ Instanz rechts unten auf den Button __*+*__ drücken.

![ModulesAdd](img/plus_add.png?raw=true "Hinzufügen")
 
In dem sich öffnenden Fenster folgende URL hinzufügen:

```	
https://github.com/Wolbolar/IPSymconEchoRemote  
```
    
und mit _OK_ bestätigen.    
    
Anschließend erscheint ein Eintrag für das Modul in der Liste der Instanz _Modules_ 

### b. Gruppen und Geräte in Amazon App benennen  



### c. Einrichtung in IP-Symcon

In IP-Symcon nun zunächst mit einem rechten Mausklick auf _Konfigurator Instanzen_ eine neue Instanz mit _Objekt hinzufügen -> Instanz_ (_CTRL+1_ in der Legacy Konsole) hinzufügen, und _Echo_ auswählen.

![AddInstance](img/configurator_add_instance.png?raw=true "Add Instance")


Es öffnet sich das Konfigurationsformular.

![ConfigIO](img/io_config_echo.png?raw=true "Config IO")
 
Hier ist anzugeben:
 - Amazon Benutzername
 - Amazon Passwort
 - Sprache
 - optional CSRF und Cookie
 
 Anschließend kann im Konfigurator die Geräte eingelesen werden. Es erscheint eine Liste der verfügbaren Geräte mit _Gerätenamen_, _Gerätetyp_, _Gerätefamilie_, _Gerätenummer_ und _InstanzID_.
 Das Gerät ist grün, insofern es noch nicht angelegt worden ist.
  
  ![List](img/echo_device_list.png?raw=true "Config IO")
  
 Dann das gewünschte Gerät markieren und auf 
 
 ![Create](img/create.png?raw=true "Config IO")
 
 _Erstellen_ drücken, die Instanz wird dann erzeugt.

### Webfront Ansicht


 ![Webfront](img/webfront.png?raw=true "Config IO")

## 4. Funktionsreferenz

### Echo Remote:
 
**Play**
```php
EchoRemote_Play(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Pause**
```php
EchoRemote_Pause(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Next**
```php
EchoRemote_Next(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Previous**
```php
EchoRemote_Previous(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**SetVolume**
```php
EchoRemote_SetVolume(int $InstanceID, int $volume)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Parameter _$volume_ Volume des Amazon Echo Dot, min 0 max 100

**Rewind30s**
```php
EchoRemote_Rewind30s(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Forward30s**
```php
EchoRemote_Forward30s(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Shuffle**
```php
EchoRemote_Shuffle(int $InstanceID, bool Shuffle)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

**Repeat**
```php
EchoRemote_Repeat(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices  

**TuneIn**
```php
EchoRemote_TuneIn(int $InstanceID, string $station)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Parameter _$station_ Station ID ist die guideId die entsprechend der Anleitung pro Sender einmal ausgelesen werden muss

**TuneInPreset**
```php
EchoRemote_TuneInPreset(int $InstanceID, int $preset)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Parameter _$preset_ Positions ID der Radiostation im Modul    

**AmazonMusic**
```php
EchoRemote_AmazonMusic(integer $InstanceID, string $seedid, string $stationname)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Parameter _$seedid_ Seed ID ist die _seedId_ die entsprechend der Anleitung pro Sender einmal ausgelesen werden muss  

Parameter _$stationname_ Station Name der _stationName_ der entsprechend der Anleitung pro Sender einmal ausgelesen werden muss

**ImportedMusic**
```php
EchoRemote_ImportedMusic(integer $InstanceID, string $trackid)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Parameter _$trackid_ trackId des importierten Files die entsprechend der Anleitung pro Track einmal ausgelesen werden muss     

**Text to Speech**
```php
EchoRemote_TextToSpeech(integer $InstanceID, string $text_to_speech)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Parameter _$text_to_speech_ Text der von dem Gerät vorgelesen werden soll     

**Wettervorhersage**
```php
EchoRemote_Weather(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Liest den Wetterbericht auf dem Gerät vor

**Verkehrsmeldungen**
```php
EchoRemote_Traffic(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Liest Verkehrsmeldungen auf dem Gerät vor

**FlashBriefing**
```php
EchoRemote_FlashBriefing(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Liest ein Flash Briefing auf dem Gerät vor

**Guten Morgen**
```php
EchoRemote_Goodmorning(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Spielt die _"Guten Morgen Mitteilung"_ auf dem Gerät ab

**Singt ein Lied**
```php
EchoRemote_SingASong(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Spielt ein Lied auf dem Gerät ab

**Erzählt Geschichte**
```php
EchoRemote_TellStory(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Spielt ein kurze Geschichte auf dem Gerät ab
     
**Ermitteln die Bluetooth Verbindungen**
```php
array EchoRemote_ListPairedBluetoothDevices(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Es werden die für das Gerät angelegten Bluetooth Verbindungen ermittelt. Hinweis: die Bluetootheinrichtung selber hat mit der Amazon App oder im Dialog zu erfolgen.

Beispiel:
```php
$devices = EchoRemote_ListPairedBluetoothDevices(47111);

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
EchoRemote_ConnectBluetooth(integer $InstanceID, string $bluetooth_address)
``` 
Parameter _$InstanceID_: ObjektID des Echo Remote Devices.
 
Parameter _$bluetooth_address_: Adresse des zu verbindenden Gerätes 

Es wird der Verbindungsaufbau zu dem angegeben Gerät initiiert.

Beispiel:
```php
EchoRemote_ConnectBluetooth(47111, '00:16:94:25:7B:93');
```

**Trennen einer Bluetooth Verbindung**
```php
EchoRemote_DisconnectBluetooth(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices 

Es wird eine bestehende Bluetooth Verbindung getrennt.

**Starten einer Routine**
```php
boolean EchoRemote_StartAlexaRoutine(integer $InstanceID, string $utterance)
``` 
Parameter _$InstanceID_: ObjektID des Echo Remote Devices.
 
Parameter _$utterance_: 'Sprachausdruck' der zu startenden Routine. Routinen können in der Alexa App definiert, 
konfiguriert und aktiviert werden. Der zu übergebene String ist der Wert der in der Alexa App hinter _Alexa,_ steht.

Es wird die zum Sprachausdruck passende Routine gestartet. Im Fehlerfall wird false zurückgegeben.

Beispiel:
```php
EchoRemote_StartAlexaRoutine(47111, 'Starte meinen Tag');
```



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
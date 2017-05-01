# IPSymconEchoRemote

Modul für IP-Symcon ab Version 4.1. Ermöglicht die Fernsteuerung mit einem Amazon Echo / Amazon Dot von Ip-Symcon aus.



## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Anhang](#5-anhang)  

## 1. Funktionsumfang

Sendet Befehle an einen Echo.
   

## 2. Voraussetzungen

 - IPS 4.1
 - Echo / Dot

## 3. Installation

### a. Laden des Moduls

Über das 'Modul Control' in IP-Symcon (Ver. 4.x) folgende URL hinzufügen:
	
    `git://github.com/Wolbolar/IPSymconEchoRemote.git`  

### b. Einrichtung in IPS

...

## 4. Funktionsreferenz

### Echo Remote:
 
Play
```php
EchoRemote_Play(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Pause
```php
EchoRemote_Pause(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Next
```php
EchoRemote_Next(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Previous
```php
EchoRemote_Previous(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

SetVolume
```php
EchoRemote_SetVolume(int $InstanceID, int $volume)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Rewind30s
```php
EchoRemote_Rewind30s(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Forward30s
```php
EchoRemote_Forward30s(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Shuffle
```php
EchoRemote_Shuffle(integer $InstanceID, bool Shuffle)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices

Repeat
```php
EchoRemote_Repeat(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices  

TuneIn
```php
EchoRemote_TuneIn(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices  

AmazonMusic
```php
EchoRemote_AmazonMusic(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjektID des Echo Remote Devices  


## 5. Konfiguration:


### Echo Remote:  

| Eigenschaft     | Typ     | Standardwert | Funktion                                                              |
| :-------------: | :-----: | :----------: | :-------------------------------------------------------------------: |
| Devicetype      | string  |              | Typ des Geräts                                                        |
| Devicenumber    | string  |              | Device Nummer des Geräts (Seriennummer)                               |
| CustomerID      | string  |              | Kunden ID                                                             |
| TuneInCSRF      | string  |              | CSRF für TuneIn                                                       |
| TuneInCookie    | string  |              | Cookie TuneIn                                                         |
| AmazonCSRF      | string  |              | CSRF für Amazon Music                                                 |
| AmazonCookie    | string  |              | Cookie für Amazon Music                                               |

## 6. Anhang


#### Echo Remote Device:

GUID: `{496AB8B5-396A-40E4-AF41-32F4C48AC90D}` 


[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)

# IPSymconEchoRemote

Module for IP Symcon version 5.0 or higher. Enables remote control with an Amazon Echo / Amazon Dot / Amazon Echo Show from IP-Symcon.

## Documentation

**Table of Contents**

1. [Features](#1-features)  
2. [Requirements](#2-requirements)  
3. [Installation](#3-installation)  
4. [Function reference](#4-function_reference)  
5. [Annex](#5-annex)  

## 1. Features

  - Control of music:
    - Play
    - Pause
    - Stop
    - Next
    - Previous
    - Adjust Volume
    - Rewind for 30 seconds
    - Fast forward for 30 seconds
    - Shuffle titles
    - Repeat title
    - Select Radio TuneIn station
 - Voice output on an echo (text to speech)
 - Weather forecast
 - Traffic News
 - Flash Briefing
 - Good Morning
 - Sing a song
 - Tell a story
       
## 2. Voraussetzungen

 - IPS 5.0
 - Echo / Echo Dot / Echo Show

## 3. Installation

### a. Loading the module

Open the IP Symcon (min [Ver. 5.0](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar "IP-Symcon 5")) web console (*http://<IP-SYMCON IP>:3777/console/*). In the object tree, under core instances, open the instance __*modules*__ with a double mouse click.

![Modules](img/modules.png?raw=true "Modules")

In the _modules_ instance, press the button __*+*__ in the lower right corner.

![ModulesAdd](img/plus_add.png?raw=true "Hinzufügen")
 
Add the following URL in the window that opens:

```	
https://github.com/Wolbolar/IPSymconEchoRemote  
```
    
and confirm with _OK_.

    
Then an entry for the module appears in the list of the instance _modules_

### b. Name groups and devices in Amazon app  



### c. Setup in IP-Symcon

In IP Symcon, first right-click _Configurator Instances_ to add a new instance with _Object -> Instance_ (_CTRL + 1_ in the Legacy Console), and select _Echo_.

![AddInstance](img/configurator_add_instance.png?raw=true "Add Instance")

The configuration form opens.

![ConfigIO](img/io_config_echo.png?raw=true "Config IO")

 
Please specify here:
 - Amazon username
 - Amazon password
 - Language
 - optional CSRF and cookie
 

Then the devices can be read in the configurator. A list of available devices appears with _equipment name, _device type_, _device family_, _device number_ and _InstanceID_.
The device is green if it has not been created yet.

  
![List](img/echo_device_list.png?raw=true "Config IO")
  

Then highlight the desired device and open

 
![Create](img/create.png?raw=true "Config IO")

 
 press _Create_ , the instance is then created.


### Webfront View


 ![Webfront](img/webfront.png?raw=true "Config IO")

## 4. Function reference

### Echo Remote:
 
**Play**
```php
EchoRemote_Play(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Pause**
```php
EchoRemote_Pause(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Next**
```php
EchoRemote_Next(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Previous**
```php
EchoRemote_Previous(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**SetVolume**
```php
EchoRemote_SetVolume(int $InstanceID, int $volume)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

Parameter _$volume_ volume of the Amazon Echo Dot, min 0 max 100

**Rewind30s**
```php
EchoRemote_Rewind30s(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Forward30s**
```php
EchoRemote_Forward30s(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Shuffle**
```php
EchoRemote_Shuffle(int $InstanceID, bool Shuffle)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Repeat**
```php
EchoRemote_Repeat(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

**TuneIn**
```php
EchoRemote_TuneIn(int $InstanceID, string $station)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

Parameter _$station_ Station ID is the guideId which has to be read once per transmitter according to the instructions

**TuneInPreset**
```php
EchoRemote_TuneInPreset(int $InstanceID, int $preset)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

Parameter _$preset_ Position ID of the radio station in the module    

**AmazonMusic**
```php
EchoRemote_AmazonMusic(integer $InstanceID, string $seedid, string $stationname)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Parameter _$seedid_ Seed ID is the _seedId_ which has to be read once per transmitter according to the instructions 

Parameter _$stationname_ Station Name, _station_name_ that must be read out once per transmitter as per the instructions


**ImportedMusic**
```php
EchoRemote_ImportedMusic(integer $InstanceID, string $trackid)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

Parameter _$trackid_ trackId of the imported file which has to be read once per track according to the instructions   

**Text to Speech**
```php
EchoRemote_TextToSpeech(integer $InstanceID, string $text_to_speech)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Parameter _$text_to_speech_ Text to be read by the device     

**Weather Forcast**
```php
EchoRemote_Weather(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Reads the weather forcast on the device

**Traffic News**
```php
EchoRemote_Traffic(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Read traffic announcements on the device

**FlashBriefing**
```php
EchoRemote_FlashBriefing(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Read a flash briefing on the device

**Good Morning**
```php
EchoRemote_Goodmorning(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Plays the _"Good morning message"_ on the device

**Singt a song**
```php
EchoRemote_SingASong(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Plays a song on the device

**Tell Story**
```php
EchoRemote_TellStory(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Plays a short story on the device
     

## 5. Configuration:


### Echo Remote:  

| Property        | Type    | Standard value | Function                                                              |
| :-------------: | :-----: | :------------: | :-------------------------------------------------------------------: |
| Devicetype      | string  |                | Type of device                                                        |
| Devicenumber    | string  |                | Device number of the device (serialnumber)                            |
| AmazonCSRF      | string  |                | CSRF from Amazon Music                                                |
| AmazonCookie    | string  |                | Cookie for Amazon Music                                               |

## 6. Annex


#### Echo Remote Device:

GUID: `{496AB8B5-396A-40E4-AF41-32F4C48AC90D}` 
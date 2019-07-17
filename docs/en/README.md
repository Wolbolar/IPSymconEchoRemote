[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)
[![StyleCI](https://github.styleci.io/repos/89942287/shield?branch=master)](https://github.styleci.io/repos/89942287)

# IPSymconEchoRemote

Module for IP Symcon version 5.0 or higher. Enables remote control with an Amazon Echo / Amazon Dot / Amazon Echo Show from IP-Symcon.

## Documentation

**Table of Contents**

1. [Features](#1-features)  
2. [Requirements](#2-requirements)  
3. [Installation](#3-installation)  
4. [Function reference](#4-function-reference)
5. [Configuration](#5-configuration)
6. [Annex](#6-annex)  

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
 - Start a Routine
 - Connect Bluetooth devices
       
## 2. Requirements

 - IPS 5.0
 - Echo / Echo Dot / Echo Show

## 3. Installation

### a. Loading the module

Open the IP Console's web console with _http://{IP-Symcon IP}:3777/console/_.

Then click on the module store icon (IP-Symcon > 5.1) in the upper right corner.

![Store](img/store_icon.png?raw=true "open store")

In the search field type

```
Echo Remote
```  


![Store](img/module_store_search_en.png?raw=true "module search")

Then select the module and click _Install_

![Store](img/install_en.png?raw=true "install")


#### Install alternative via Modules instance (IP-Symcon < 5.1)

Open the IP Console's web console with _http://{IP-Symcon IP}:3777/console/_.

_Open_ the object tree .

![Objektbaum](img/object_tree.png?raw=true "Objektbaum")	

Open the instance _'Modules'_ below core instances in the object tree of IP-Symcon (>= Ver 5.x) with a double-click and press the _Plus_ button.

![Modules](img/modules.png?raw=true "Modules")	

![Plus](img/plus.png?raw=true "Plus")	

![ModulURL](img/add_module.png?raw=true "Add Module")
 
Enter the following URL in the field and confirm with _OK_:

```
https://github.com/Wolbolar/IPSymconEchoRemote 
```  
	         
Then an entry for the module appears in the list of the instance _Modules_

By default, the branch _master_ is loaded, which contains current changes and adjustments.
Only the _master_ branch is kept current.

![Master](img/master.png?raw=true "master") 

If an older version of IP-Symcon smaller than version 5.1 is used, click on the gear on the right side of the list.
It opens another window,

![SelectBranch](img/select_branch_en.png?raw=true "select branch") 

here you can switch to another branch, for older versions smaller than 5.1 select _Old-Version_ .

### b. Name groups and devices in Amazon app  

Open the Alexa app and name your devices.

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

 
##### Determination of the cookie
If two-step verification is enabled for the Amazon account, then for the login cookie must selected, as the module does not support two-step verification.

How can the cookie be determined?

The following example shows how to detect the cookie of an active browser session.

In the example, we use Chrome's developer tools, but other browsers have similar tools. The pictures and instructions are now based on Chrome.

Open the website first
https://alexa.amazon.com
and sign up there with the Amazon account on which also the Echo / Dot is registered.

Now we switch to music, videos and books and select TuneIn. Now we open the developer tools with F12: a windows opens on the right side of the browser window.

Now we select a radio station at TuneIn with a double click. The radio station should now start playing on the echo. At the same time we see an entry with queue-and-play in the Developer Tools window under Network.

Now we open the entry queue-and-play (left column) with a double-click and it opens another window (right column).

In the window we can now find the cookie entry we need for the module.
In the block Request Headers you can find it. The cookie is quite long and needs to be completely copied.

 ![Cookie](img/Cookie.jpg?raw=true "Cookie")


### Webfront View


 ![Webfront](img/webfront.png?raw=true "Config IO")

## 4. Function Reference

### Echo Remote:
 
**Play**
```php
ECHOREMOTE_Play(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Pause**
```php
ECHOREMOTE_Pause(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Next**
```php
ECHOREMOTE_Next(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Previous**
```php
ECHOREMOTE_Previous(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**SetVolume**
```php
ECHOREMOTE_SetVolume(int $InstanceID, int $volume)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

Parameter _$volume_ volume of the Amazon Echo Dot, min 0 max 100

**Mute / Unmute**
```php
ECHOREMOTE_Mute(int $InstanceID, bool $mute)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

Parameter _$mute_ true set the volume from the Amazon Echo to 0
Parameter _$mute_ false set the volume from the Amazon Echo to the last known value

**Rewind30s**
```php
ECHOREMOTE_Rewind30s(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Forward30s**
```php
ECHOREMOTE_Forward30s(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Shuffle**
```php
ECHOREMOTE_Shuffle(int $InstanceID, bool Shuffle)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

**Repeat**
```php
ECHOREMOTE_Repeat(int $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

**TuneIn**
```php
ECHOREMOTE_TuneIn(int $InstanceID, string $station)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

Parameter _$station_ Station ID is the guideId which has to be read once per transmitter according to the instructions

**TuneInPreset**
```php
ECHOREMOTE_TuneInPreset(int $InstanceID, int $preset)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device

Parameter _$preset_ Position ID of the radio station in the module    

**AmazonMusic**
```php
ECHOREMOTE_AmazonMusic(integer $InstanceID, string $seedid, string $stationname)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Parameter _$seedid_ Seed ID is the _seedId_ which has to be read once per transmitter according to the instructions 

Parameter _$stationname_ Station Name, _station_name_ that must be read out once per transmitter as per the instructions

**Text to Speech**
```php
ECHOREMOTE_TextToSpeech(integer $InstanceID, string $text_to_speech)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Parameter _$text_to_speech_ Text to be read by the device     

**Weather Forcast**
```php
ECHOREMOTE_Weather(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Reads the weather forcast on the device

**Traffic News**
```php
ECHOREMOTE_Traffic(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Read traffic announcements on the device

**FlashBriefing**
```php
ECHOREMOTE_FlashBriefing(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Read a flash briefing on the device

**Good Morning**
```php
ECHOREMOTE_Goodmorning(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Plays the _"Good morning message"_ on the device

**Singt a song**
```php
ECHOREMOTE_SingASong(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Plays a song on the device

**Tell Story**
```php
ECHOREMOTE_TellStory(integer $InstanceID)
``` 
Parameter _$InstanceID_ ObjectID of the Echo Remote Device 

Plays a short story on the device
     

## 5. Configuration


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
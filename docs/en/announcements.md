[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)

# Set up for announcements on multiple Alexa devices

Please also note the

- [Frequently asked questions about Alexa and Alexa devices](https://www.amazon.de/gp/help/customer/display.html?nodeId=201602230 "Frequently asked questions about Alexa and Alexa devices")

### Amazon FAQ on Announcements

_The announcement function allows you to easily send announcements to other Alexa-enabled devices
in your household, comparable to a one-way intercom. For example, just say "Alexa, make an announcement"
or “Alexa, announce: dinner is ready” and “dinner is ready” will be heard in your voice by everyone
supported devices in your household. You can block announcements on any or all supported devices,
by switching on the “do not disturb” mode - just say “Alexa, please do not disturb”.
In contrast to Drop In, announcements are only possible in one direction. To respond to an announcement,
you can make a new announcement or use the drop-in function, which works like an intercom ._

### Requirement for announcements

There are several ways to be notified on an Echo device, one of which is Drop In. If you want to announce something _ several devices at the same time_, you can do so with an _Announcement_.
This means that a message can be announced in your house / apartment on several Echo devices at the same time.
In order for announcements to be used, this must first be activated for the Echo device in the Alexa app or can be deactivated for the Echo device by clicking _Do not disturb_.

When an announcement is made, a message is read out on all Echo devices in the house / apartment.
The prerequisite is that the Echo device is "Alexa built-in" certified. This is the case with an original Amazon Echo (the model does not matter) or a smart speaker that also supports the voice assistant Alexa.
An announcement can be used with IP-Symcon so that instead of outputting text to speech on one device, the entire group of all echo devices can be used to output the message.
An example would be in the case of an alarm the IP-Symcon sends off or a notification sent by IP-Symcon that should reach all Echo devices.


## Sending of announcements from IP-Symcon to Echo devices

An announcement is always sent to all supported Echo devices in the WLAN. Exceptions are the Echo devices for which _Do not disturb_ has been activated.

### Configuration of the Echo device in the Alexa app

First of all, in the Alexa app for each Echo device that is to be used with an announcement, this must be activated explicitly for each device.
To do this, open the Alexa app and then switch to the device settings of the Echo device via

_Alexa App_ -> _More_ -> _Settings_ -> _Device settings_ -> _Echo device (select from the list of available devices)_ -> _GENERAL_ -> _Communication_ -> _**Activated**_

![General](img/einstellungen_1.png?raw=true "General")

Activate communication for this device

![Communication](img/einstellungen_2.png?raw=true "Communication")

Set the announcement to activated

![Announcements](img/einstellungen_3.png?raw=true "Announcements")

If you do not want to be disturbed on the device, you can either set this to _Do not disturb_ directly on the device such as an Echo Show or in the Alexa app.

![Please do not disturb](img/einstellungen_4.png?raw=true "Please do not disturb")


### Example for an announcement by script from IP-Symcon

Create a new script in IP-Symcon.

```php
<?php
$InstanceID = 12345; // Select the object ID of the Echo Remote Instant with CTRL + O in the object tree
$text_to_speech = 'die Waschmaschine ist fertig.'; // Text that is to be output as an announcement on Echo devices, the output takes place on all Echo devices in the WLAN with the exception of devices with the Do not disturb setting
ECHOREMOTE_Announcement($InstanceID, $text_to_speech);
``` 

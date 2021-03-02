[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)

# Einrichten für Ankündigungen auf mehreren Alexa Geräten

Bitte beachte auch die 

- [Häufig gestellte Fragen zu Alexa und Alexa-Geräten](https://www.amazon.de/gp/help/customer/display.html?nodeId=201602230 "Häufig gestellte Fragen zu Alexa und Alexa-Geräten")

### Amazon FAQ zu Ankündigungen

_Die Ankündigungs-Funktion ermöglicht es Ihnen, auf einfache Weise Ankündigungen an andere Alexa-fähige Geräte
in Ihrem Haushalt zu schicken, vergleichbar einer Ein-Weg-Sprechanlage. Sagen Sie z.B. einfach „Alexa, mach eine Ankündigung“
oder „Alexa, kündige an: das Abendessen ist fertig“ und „Das Abendessen ist fertig“ wird in Ihrer Stimme von allen
unterstützten Geräten in Ihrem Haushalt angekündigt. Sie können Ankündigungen auf einem oder allen unterstützten Geräten blockieren,
indem Sie den „Bitte nicht stören“-Modus einschalten – sagen Sie hierzu einfach „Alexa, bitte nicht stören“.
Im Gegensatz zu Drop In sind Ankündigungen nur in einer Richtung möglich. Um auf eine Ankündigung zu antworten,
können Sie eine neue Ankündigung machen oder die Drop In-Funktion, die wie eine Gegensprechanlage funktioniert, nutzen._

### Voraussetzung für Ankündigungen

Es gibt verschiedene Arten sich auf einem Echo Gerät benachrichten zu lassen, eine davon ist Drop In. Wenn man _mehrere Geräte gleichzeitig_ etwas ansagen lassen will, geht das mit einer _Ankündigung_.
Dadurch lassen sich dann zeitgleich auf mehreren Echo Geräten eine Mitteilung in Deinem Haus / Wohnung ansagen.
Damit Ankündigungen genutzt werden können, muss zunächst in der Alexa App dies für das Echo Gerät aktiviert werden bzw. kann durch _Nicht stören_ für das Echo Gerät deaktiviert werden.

Bei einer Ankündigung wird auf allen Echo Geräten im Haus / Wohnung eine Nachricht vorgelesen.
Voraussetzung ist dass das Echo Gerät „Alexa built-in“-zertifiziert ist. Dies ist bei einem Original Amazon Echo (das Modell spielt keine Rolle) oder einen Smart Speaker, der ebenfalls die Sprachassistentin Alexa unterstützt gegeben.
Eine Ankündigung kann mit IP-Symcon genutzt werden, um statt Text to Speech auf einem Gerät auszugeben, gleich die gesamte Gruppe aller Echo Geräte zur Ausgabe der Nachricht zu nutzten.
Beispiel wäre im Fall eines Alarms den IP-Symcon absetzt oder einer durch IP-Symcon abgesetzten Benachrichtigung, die alle Echo Geräte erreichen soll.


## Senden von Ankündigungen aus IP-Symcon an Echo Geräte

Eine Ankündigung wird immer an alle unterstützen Echo Geräte im WLAN geschickt. Außnahme sind die Echo Geräte bei denen _Bitte nicht stören_ aktiviert wurde.

### Konfiguration des Echo Geräts in der Alexa App

Zunächst ist in der Alexa App für jedes Echo Gerät, dass mit Ankündigung genutzt werden soll, dies explizit pro Gerät zu aktivieren.
Dazu ist die Alexa App zu öffnen und dann zu den Geräte Einstellungen des Echo Geräts zu wechseln über

_Alexa App_ -> _Mehr_ -> _Einstellungen_ -> _Geräteeinstellungen_ -> _Echo Gerät (aus der Liste der verfügbaren Geräte auswählen)_ -> _ALLGEMEIN_ -> _Kommunikation_ -> _**Aktiviert**_

![Allgemein](img/einstellungen_1.png?raw=true "Allgemein")

Kommunikation _Für dieses Gerät aktivieren_ einschalten

![Kommunikation](img/einstellungen_2.png?raw=true "Kommunikation")

Ankündigung auf Aktiviert setzten

![Ankündigungen](img/einstellungen_3.png?raw=true "Ankündigungen")

Wenn man auf dem Gerät nicht gestört werden will, kann man dies entweder direkt am Gerät wie einem Echo Show oder aber in der Alexa App auf _Bitte nicht stören_ setzten.

![Bitte nicht stören](img/einstellungen_4.png?raw=true "Bitte nicht stören")


### Beispiel für ein Ankündigung per Skript aus IP-Symcon

In IP-Symcon ein neues Skript anlegen.

```php
<?php
$InstanceID = 12345; // Objekt ID der Echo Remote Instant mit STRG+O im Objektbaum auswählen
$text_to_speech = 'die Waschmaschine ist fertig.'; // Text der als Ankündigung auf Echo Geräten ausgegegebn werden soll, die Ausgabe erfolgt auf allen Echo Geräten im WLAN mit Ausnhame von Geräten mit der Einstellung Bitte nicht stören
ECHOREMOTE_Announcement($InstanceID, $text_to_speech);
``` 
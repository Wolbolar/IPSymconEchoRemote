[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)

# Einrichtung einer Routine in der Alexa App und starten dieser aus IP-Symcon

Mit einer Routine in der Alexa App kann eine Abfolge verschiedener Dinge mit einem Befehlsaufruf ausgeführt werden. dabei stehen einem in einer Routine eine Viuelzahl an Dingen zur Verfügung wie Textansage, das Aufrufen von Skill, das Ausführen von Smart Home Geräten oder auch das abspielen von Musik.
Alexa Routinen können nach einem Zeitplan ausgeführt werden oder per Sprache aufgerufen. Mit IP-Symcon ist es weiterhin Möglich eine in der Alexa App erstellte Routine z.B. bei einem Ereignis ausführen zu lassen.

## Konfiguration auf der Seite von Amazon im Amazon Kundenkonto

Bitte beachte auch die

- [Alexa-Routinen für Smart-Geräte erstellen](https://www.amazon.de/gp/help/customer/display.html?nodeId=202200080 "Alexa-Routinen für Smart-Geräte erstellen")

## Beispiele für Routinen

Mit IP-Symcon können grundsätzlich in der Alexa App erstelle Routinen bei einem Ereigniss aufgerufen werden. Dies kann man nutzten um z.B. Gerätezustände von Geräten, die selber keine Alexa Anbindung besitzten, deren Zustand aber IP-Symcon bekannt ist, auf Alexa ansagen bzw. abfragen zu können.

Mögliche Anwendung

 - Wiedergabe von Sound bei einem Erergnis auf einem Echo Gerät
 - Aufrufen von Geräten über eine Routine
 - Abfrage eines Zustands eines von IP-Symcon verwalteten Geräts über eine Routine
 - Ansage von dynamisch generierten Mitteilungen mit Sound auf einem Echo Gerät

## Beispiel um den Status eines Geräts abzufragen mit dynamischen generierten Ansagetext

Folgens Beispiel soll verdeutlichen wie man den Status eines Geräts, das durch IP-Symcon verwaltet wird, durch Alexa abfragen kann.

Zunächst erstellen wir ein Skript in IP-Symcon, das dann aufgerufen werden soll und einen Text auf dem Echo Gerät wiedergeben soll.

In dem Beispiel geben wird von einer Waschmaschine das laufende Programm aus und die Restlaufzeit.

```php
<?php
$washing_program = GetValueFormatted(24577);
$remaining_time = GetValueFormatted(43944);
$end_time = GetValueFormatted(30561);
$format = '<speak>
  <audio src="soundbank://soundlibrary/alarms/beeps_and_bloops/intro_02"/>
  Die Waschmachine läuft mit dem Programm %s, die verbleibende Restlaufzeit ist %s, das Programm ist um %s beendet.
</speak>';
$text = sprintf($format, $washing_program, $remaining_time, $end_time);
ECHOREMOTE_TextToSpeech(24448, $text);
``` 
In dem Skript werden zunächst 3 Werte der Waschmaschine (laufendes Progra,, Restlaufzeit und Endzeit) ausgelesen und in Variablen hinterlegt.
Zusätzlich wird vor der Ansage des Texts nach ein Sound abgespielt. Hierzu wird die Alexa Sound Library genutzt.

[Alexa Sound Library](https://developer.amazon.com/en-US/docs/alexa/custom-skills/ask-soundlibrary.html "Alexa Sound Library")

In der Sound Library kann man sich ein passenden Sound aussuchen und den Code in das Skript kopieren.

Nachdem das Skript fertig konfiguriert wurde, wird das Skript als Szene in der Alexa Instanz unter Kerninstanzen hinterlegt.

![Alexa Szene](img/routine_3.png?raw=true "Alexa Szene")

Jetzt wird die Routine in der Alexa App erstellt unter Mehr -> Routine

![Routine](img/routine_1.png?raw=true "Routine")

![Routine](img/routine_2.png?raw=true "Routine")

Unter _Aktion hinzufügen_ wählt man nun _Smart Home_ -> _Szene steuern_ und wählt dort die passende Szene aus. 

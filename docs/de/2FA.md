[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)
![Code](https://img.shields.io/badge/Code-PHP-blue.svg)

# Einrichten der Zwei Schritt Verifizierung (2SV) von Amazon

Mit der [Amazon Zwei-Schritt-Verifizierung](https://www.amazon.de/gp/help/customer/display.html?nodeId=202013000) (2SV) erhält Ihr Konto eine zusätzliche Schutzstufe. Statt der bloßen Eingabe des Passwortes erfordert die Zwei-Schritt-Verifizierung, dass Sie bei der Anmeldung zusätzlich zu Ihrem Passwort einen einmaligen Sicherheitscode eingeben.
Dadurch wird insgesamt die Sicherheit für das Amazon Konto erhöht. Wichtig ist aber das mit Einrichtung der [Amazon Zwei-Schritt-Verifizierung](https://www.amazon.de/gp/help/customer/display.html?nodeId=202013000) und des hinterlegen und absichern des Schlüssels für IP-Symcon, das IP-Symcon System selber abgesichert sein muss und vor Fremdzugriff geschützt sein muss.

_**Das Hinterlegen des Sicherheitscodes auf dem auf dem gleichen System, das eine Zwei Faktor Authentifizierung durchführt, kann bei einem Zugriff durch Fremde die Sicherheit der Zwei Faktor Authentifizierung vollständig außer Kraft setzten.**_

Daher liegt es im persönlichen Ermessen dafür Sorge zu tragen, dass das _**IP-Symcon System gegen Zugriff durch Fremde geschützt ist**_.

_**Ein Hinterlegen des Sicherheitsschlüssels in IP-Symcon erfolgt auf eigene Gefahr und Verantwortung.**_

## Konfiguration auf der Seite von Amazon im Amazon Kundenkonto

### Einrichten eines Authentikators

Im ersten Schritt ist zunächst ein Authenticator als Handy App herunter zu laden und einzurichten. Ein Option stellt hier der Google Authenticator dar:

- [Google Authenticator Android](https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=de&gl=US "Google Authenticator Android")
- [Google Authenticator iOS](https://apps.apple.com/de/app/google-authenticator/id388497605 "Google Authenticator iOS")

Zunächst ist der [Anleitung von Amazon zur Einrichtung der Zwei-Schritt-Verifizierung](https://www.amazon.de/gp/help/customer/display.html?nodeId=202013020 "Anleitung von Amazon zur Einrichtung der Zwei-Schritt-Verifizierung") zu folgen und die Zwei-Schritt-Verifizierung zu aktivieren.
Hier ist auch zunächst eine Handy App wie der Google Authenticator unbedingt als aller erstes zu registrieren, damit man sich dann in Folge mit der Handy App zusätzlich jederzeit bei Amazon authentifizieren kann.

### Einrichten von IP-Symcon als App mit Zugriff auf 2SV

Auf _Amazon.de → Mein Konto → Anmeldung u. Sicherheit → Einstellungen für die Zwei-Schritt-Verifizierung (2SV)  → Bearbeiten_ klicken.

Unter _Bevorzugte Methode_ auf _**Neue App hinzufügen**_ klicken.

![Neue App hinzufügen](img/app_anmelden.png?raw=true "Neue App hinzufügen")

Da in IP-Symcon der QR Code nicht gescannt werden kann, muss im folgenden Dialog auf „Barcode kann nicht gescannt werden?“ geklickt werden und der Schlüssel kopiert werden.

![Barcode kann nicht gescannt werden](img/app_anmelden_2.png?raw=true "Barcode kann nicht gescannt werden")

Im rot umrandeten Bereich ist der persönliche Schlüssel zu finden, dieser ist zu notieren.

![persönliche Schlüssel](img/app_anmelden_3.png?raw=true "persönliche Schlüssel")

Nun in der IP-Symcon Konsole die AmazonEchoIO Instanz-Konfiguration öffnen.

_**WICHTIG: Die Amazon Seite geöffnet lassen.**_

Die Instanz sollte auf „Inaktiv“ gestellt sein, die Felder „Amazon Benutzer Name“, „Amazon Passwort“ ausfüllen und „Benutze eigenen Cookie“ deaktivieren.
Der Schlüssel aus dem vorhergehenden Schritt muss nun im Feld „Amazon 2FA“ eingefügt werden und die Instanz-Konfiguration gespeichert werden.

![Konfiguartor IO](img/app_anmelden_4.png?raw=true "Konfiguartor IO")


Jetzt auf _Generiere OTP_ drücken.

![OTP](img/app_anmelden_5.png?raw=true "OTP")

Durch einen Klick auf _Generiere OTP_ wird ein One Time Password generiert, dieses muss auf der noch geöffneten Amazon Seite eingegeben werden und mit „Verifizieren Sie das OTP und fahren Sie fort“ bestätigt werden.

Nachdem der OTP Code erfolgreich verifiziert wurde, kann die AmazonEchoIO Instanz auf „aktiv“ gestellt werden und die Instanz-Konfiguration gespeichert werden.
Ab diesem Zeitpunkt erfolgt die Anmeldung nun automatisch. Bei jeder Anmeldung wird automatisch ein neuer 2SV OTP generiert.
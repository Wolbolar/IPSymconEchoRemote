# Einbindung in Visual Studio Code (Windows)

- Extension "php cs fixer" von junstyle installieren
- PHP und php-cs-fixer herunterladen:
  - https://windows.php.net/download/
  - https://github.com/FriendsOfPHP/PHP-CS-Fixer
- In settings.json folgende Einträge hinzufügen (Pfade anpassen):
```
"php.validate.executablePath": "Path\\To\\php.exe",
"php-cs-fixer.excecutablePath": "Path\\To\\php-cs-fixer-v2.phar",
"php-cs-fixer.config": ".style/.php_cs",
"php-cs-fixer.allowRisky": true
```

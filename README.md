# Impftermin Formular (PHP)

Mit dieser PHP Website können Sie ihre Patienten ganz einfach Online einen Impftermin vereinbaren.

Dies spart Zeit und erleichtert den Kommunikationsweg.

## Installation & Einrichtung

Um die Website zu Installieren, laden Sie einfach den Source Code herunter und legen ihn auf ihrem Webserver ab.

Die Website einzurichten ist nicht sonderlich kompliziert. Um alle nötigten Informationen einzutragen, öffnen Sie einfach die Datei `index.php`. In der Datei finden Sie einen abschnitt, in dem Sie alle benötigten Daten eintragen können. Der Abschnitt sieht in etwa so aus:

```php
$CONFIG = [
    "praxis" =>             "Deine Praxis",
    "website" =>            "https://www.deine-website.de",
    "impressum" =>          "https://www.deine-website.de/impressum",
    "datenschutz" =>        "https://www.deine-website.de/datenschutz",
    "mindestalter" =>       0,
    "wartezeit_info" =>     "Bitte bedenken Sie, dass bei spezifischer Auswahl des Impfstoffes eine längere Wartezeit anfallen könnte.",
    "mobilnummer_suffix" => "(WhatsApp)",
    "email_sender" =>       "mail@dein-server.de",
    "email_empfaenger" => [
        "mail@dein-server.de",
        "mail2@dein-server.de"
    ],
    "email_einstellungen" => [
        "host" =>       "smtp.dein-server.de",
        "username" =>   "mail@dein-server.de",
        "password" =>   "super_geheimes_passowort",
        "encryption" => "tls",
        "port" =>       587
    ],
];
```

Genauere Informationen zu den einzelnen Konfigurationspunkten finden Sie in der Datei.

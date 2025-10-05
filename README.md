# Freundlicher GitHub Repository Explorer

Diese kleine PHP-Anwendung zeigt auf einer einzigen Seite die wichtigsten Infos zu allen Repositories eines konfigurierten GitHub-Owners an. Sie eignet sich, um Kolleg:innen oder Kund:innen einen schnellen, sympathisch aufbereiteten Überblick über Projekte zu geben.

## Funktionsumfang
- 📂 Listet automatisch alle öffentlichen Repositories des hinterlegten Owners und bietet sie in einer Dropdown-Auswahl an.
- 🧭 Stellt Repository-Details wie Beschreibung, Stars, Forks, Issues und letzte Aktualisierung übersichtlich dar.
- 🙋‍♀️ Zeigt freundliche Owner-Informationen inklusive Avatar, Bio und Kontaktlinks.
- 📖 Rendert das README des ausgewählten Repositories direkt auf der Seite für einen unmittelbaren Projektüberblick.

- 📦 Bietet gut sichtbare Buttons, um den Standard-Branch des gewählten Repositories sofort als ZIP herunterzuladen – direkt neben der Auswahl und im Repository-Header.
- 🛟 Fängt ungültige Eingaben ab und sorgt dafür, dass immer ein gültiges Repository angezeigt wird.

## Konfiguration
Alle zentralen Einstellungen liegen in `config.php`:

```php
return [
    'owner' => 'vercel',              // GitHub-Owner, dessen Repositories angezeigt werden
    'default_repository' => 'next.js',// Optional: Vorauswahl eines Repositories
    'title' => 'Willkommen!',         // Seitentitel
    'welcome_message' => 'Schön, dass du da bist!' // Freundliche Begrüßung auf der Seite
];
```

> 💡 Ändere den Owner, um sofort einen anderen Account zu präsentieren. Weitere Texte lassen sich ebenso schnell anpassen.

## Voraussetzungen & Start
1. Stelle sicher, dass PHP (>= 8.0 empfohlen) installiert ist.
2. Starte einen lokalen Webserver, z. B. mit:
   ```bash
   php -S localhost:8000
   ```
3. Öffne `http://localhost:8000/index.php` im Browser und erkunde die Repositories.

Für Umgebungen ohne Webserver kannst du die Seite auch direkt per `php index.php` ausführen, um Syntaxfehler zu überprüfen (die Ausgabe selbst ist jedoch auf einen Browser ausgelegt).

## Hinweise
- Die Anwendung nutzt die öffentliche GitHub-API ohne Authentifizierung und unterliegt damit dem Rate-Limit für anonyme Anfragen.
- README-Inhalte werden Base64-dekodiert und im Original-Markdown angezeigt.

Viel Freude beim Entdecken deiner Lieblings-Repositories! 🚀

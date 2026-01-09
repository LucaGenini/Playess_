# Playess 🎵
Interactive Song Guess Webapp

## Inhaltsverzeichnis
1. [Projektübersicht](#projektübersicht)
2. [Ausgangslage und Motivation](#ausgangslage-und-motivation)
3. [Fragestellung](#fragestellung)
4. [Funktionaler Umfang](#funktionaler-umfang)
5. [Eingesetzte Technologien](#eingesetzte-technologien)
6. [Setup und Installation](#setup-und-installation)
7. [Datenbank-Struktur](#datenbank-struktur)
8. [UX- und Design-Entscheidungen](#ux--und-design-entscheidungen)
9. [Technische Herausforderungen](#technische-herausforderungen)
10. [Learnings und Erkenntnisse](#learnings-und-erkenntnisse)
11. [Arbeitsprozess](#arbeitsprozess)
12. [Weiterentwicklung](#weiterentwicklung)
13. [Figma](#figma)
14. [Autor](#autor)

---

## Projektübersicht
**Playess** ist eine interaktive Musik-Quiz-Webapplikation, bei der Nutzer:innen Songs anhand von Audio-Ausschnitten oder Album-Covern erraten.  
Die Anwendung verbindet spielerische Game-Mechaniken mit einer vollständigen technischen Umsetzung bestehend aus Frontend, Backend und Datenbank.

Das Projekt wurde im Rahmen des Moduls **Interaktive Medien 5 (IM5)** an der FH Graubünden entwickelt. Im Fokus stand dabei nicht nur das funktionale Endprodukt, sondern insbesondere der Entwicklungsprozess, technische Entscheidungen sowie die bewusste Auseinandersetzung mit Struktur, Wartbarkeit und User Experience.

---

## Ausgangslage und Motivation
Ich bin ein grosser Fan des Hitster Spiels und Vergleiche gerne mein Musik Wissen mit meinen Freunden. Ich dachte mir, wäre es nicht nice, wenn ich ein Plattform hätte auf der ich dies auch Online spielen kann?
Es gibt vergleichbare WebappSpiele, aber oftmals stecken diese hinter einer paywall bei denen man mit viel Werbung zugeschüttet wird oder monatliche Abos etc. zahlen muss. Von daher kam die Überzeugung für dieses Projekt.
Zumal bittet das Spiel die perfekte Möglichkeit eine Webapp zu programmieren, was ich zuvor noch nie gemacht hatte.
Es ist Projekt, welches eine realistische Webapp-Komplexität abbildet und gleichzeitig spielerisch zugänglich bleibt.
Zudem konnte ich mich weiterbilden bezüglich Frontend-Logik, Themen wie Authentifizierung, Datenpersistenz. Da ich im letzten Semester "Physical Computing" hatte ging der Login-Aspekt verloren, welchen ich dieses Semester gerne nachholen wollte.

---

## Fragestellung
Wie lässt sich ein Musik-Quiz entwickeln, das technisch sauber strukturiert ist, eine nachvollziehbare Game-Logik besitzt und für Nutzer:innen intuitiv und motivierend bleibt?

---

## Funktionaler Umfang
Playess verfügt über zwei Spielmodi. Im **Song Guess Mode** erraten Nutzer:innen Songs anhand kurzer Audio-Snippets.  
Im **Cover Guess Mode** werden Album-Covers nach und nach angezeigt, anhand derer Titel und Interpret erraten werden müssen.

Jede Spielrunde ist zeitlich begrenzt und folgt einem klar definierten Ablauf mit Guess-, Reveal- und Timeout-Zuständen.  
Zur Motivation und Vergleichbarkeit wird ein ELO-basiertes Bewertungssystem eingesetzt. Zusätzlich werden Statistiken sowie ein Leaderboard pro Nutzer geführt.

Die Anwendung beinhaltet ein vollständiges User-Account-System mit Registrierung, Login, Session-Verwaltung und Zugriffsbeschränkung für geschützte Bereiche.

---

## Eingesetzte Technologien

### Frontend
Das Frontend basiert auf **HTML, CSS und JavaScript**.  
Es wurden bewusst keine Frameworks eingesetzt, um ein besseres Verständnis für Struktur, State-Handling und DOM-Manipulation zu erlangen.

Die Styles sind modular aufgebaut und folgen einer klaren Trennung:

- `reset.css` – für eine konsistente Browser-Basis  
- `fonts.css` – für Typografie und Schriftarten
- `main.css` – für Layout, Komponenten und Utilities  

**CSS Custom Properties (Root Variables)** werden zentral definiert und ermöglichen eine konsistente Verwendung von Farben, Abständen und Schriftgrössen im gesamten Projekt. Zusätzlich wurden **Utility-Klassen** implementiert, die die Wiederverwendbarkeit erhöhen und den Code wartbarer machen.

### Backend
Das Backend wurde mit **PHP** umgesetzt. Die Kommunikation mit der Datenbank erfolgt über **PDO** (PHP Data Objects).  
**Sessions** werden für Authentifizierung und Zugriffskontrolle genutzt.

### APIs

**iTunes Search API**  
Für Audio-Snippets und Album-Covers beim Cover Guess Mode wird die iTunes Search API verwendet. Sie bietet frei zugängliche Audio-Preview-Endpunkte ohne API-Key oder Kosten und verfügt über eine umfangreiche Musik-Library.

**MusicBrainz API**  
Für die Auswahl eines persönlichen Favorite Songs (Personalisierung auf dem Spieler-Shirt) wird die MusicBrainz API eingesetzt. Obwohl dort teilweise Fan-Art statt offizieller Covers verwendet wird, bietet sie eine grössere und kreativere Auswahl, was für diesen Anwendungsfall bewusst in Kauf genommen wurde.

**Warum nicht Spotify?**  
Initial war geplant, die Spotify API zu verwenden. Jedoch bringt diese starke Design-Auflagen mit sich, die die kreative Freiheit einschränken. Zudem wurden vor ca. einem halben Jahr die Audio-Endpoints aus der Developer API entfernt, die für das Projekt elementar waren. Die iTunes Search API erwies sich als ideale Alternative.

---

## Setup und Installation

### Voraussetzungen
- Webserver mit PHP 8.0 oder höher (z.B. XAMPP, MAMP oder Hosting-Provider)
- MySQL oder MariaDB Datenbank
- Git (optional, für Repository-Verwaltung)

### Installation in 5 Schritten

**1. Repository klonen**
```bash
git clone [REPOSITORY-URL]
cd playess_
```

**Wichtig:** sensible-Dateien in `.gitignore` aufnehmen:
```
.DS_Store
**/.DS_Store

/vscode
/.vscode
/config.php
```

**2. Projekt in Webserver-Verzeichnis verschieben**
- Lokale Entwicklung: in `htdocs` (XAMPP) oder `www` (MAMP)
- Hosting (Hostinger): in das Root-Verzeichnis hochladen

**3. Datenbank erstellen und importieren**

Erstelle eine neue Datenbank (z.B. über phpMyAdmin):
- Name: `Mustername`
- Zeichensatz: `utf8mb4`
- Kollation: `utf8mb4_unicode_ci`

Führe folgendes SQL aus:
```sql
-- Tabelle: users
CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email VARCHAR(255) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(100) NULL,
  is_active TINYINT(1) DEFAULT 1,
  email_verified_at DATETIME NULL,
  last_login_at DATETIME NULL,
  reset_token VARCHAR(255) NULL,
  reset_expires_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  favorite_track_id VARCHAR(50) NULL,
  favorite_track_title VARCHAR(255) NULL,
  favorite_track_artist VARCHAR(255) NULL,
  favorite_track_artwork VARCHAR(255) NULL,
  sec_question_1 VARCHAR(255) NULL,
  security_q1_answer_hash VARCHAR(255) NULL,
  sec_question_2 VARCHAR(255) NULL,
  security_q2_answer_hash VARCHAR(255) NULL,
  character_choice VARCHAR(20) NULL,
  favorite_song_cover VARCHAR(255) NULL
);

-- Tabelle: leaderboard_stats
CREATE TABLE leaderboard_stats (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  game_mode ENUM('open','cover') DEFAULT 'timeline',
  category ENUM('all') DEFAULT 'all',
  elo INT DEFAULT 1200,
  games_played INT(10) UNSIGNED DEFAULT 0,
  correct_answers INT(10) UNSIGNED DEFAULT 0,
  wrong_answers INT(10) UNSIGNED DEFAULT 0,
  updated_at TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**4. Datenbank-Konfiguration anpassen**

Öffne `config.php` und passe die Zugangsdaten an:
```php
<?php
define('DB_HOST', 'localhost');      // Bei Hosting: oft 'localhost'
define('DB_NAME', 'playess');        // Dein Datenbankname
define('DB_USER', 'root');           // Dein Datenbanknutzer (lokal oft 'root')
define('DB_PASS', '');               // Dein Datenbankpasswort
?>
```

**Wichtig:** Alle PHP-Dateien mit Datenbankzugriff sollten sich im gleichen Verzeichnis wie `config.php` befinden, da eine Unterteilung in Sub-Ordner zu Include- und Pfadproblemen führen kann.

**5. Projekt im Browser öffnen**

Lokal:
```
http://localhost/playess
```

Auf Hosting:
```
https://deine-domain.com
```

### Troubleshooting

**Problem: 500 Internal Server Error**
- PHP-Version prüfen (mindestens 8.0)
- Dateipfade in `include` und `require` überprüfen
- Error-Logging in PHP aktivieren

**Problem: Datenbankverbindung fehlgeschlagen**
- `config.php` Zugangsdaten überprüfen
- Datenbanknutzer-Rechte prüfen

**Problem: CSS lädt nicht / aktualisiert nicht**
- Browser-Cache leeren (Ctrl/Cmd + Shift + R)
- **Teilweise bei Hostprovider** Server-Cache im Dashboard manuell leeren (dies ist oft die einzige effektive Lösung bei CSS-Update-Problemen)
- Dateipfade in HTML überprüfen

**Problem: Session funktioniert nicht**
- Prüfen ob `session_start()` in allen relevanten PHP-Dateien aufgerufen wird
- Schreibrechte für Session-Ordner prüfen
- Bei Hosting: Session-Konfiguration im Dashboard überprüfen

---

## Datenbank-Struktur

### Tabelle: `users`
Speichert alle nutzerbezogenen Informationen wie Login-Daten, Personalisierung und Account-Status.

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | INT (Primary Key) | Eindeutige Nutzer-ID |
| `email` | VARCHAR(255) | Login-E-Mail (unique) |
| `password_hash` | VARCHAR(255) | Verschlüsseltes Passwort |
| `name` | VARCHAR(100) | Name des Nutzers |
| `is_active` | TINYINT(1) | Account-Status (aktiv/inaktiv) |
| `email_verified_at` | DATETIME | Zeitpunkt der E-Mail-Verifizierung |
| `last_login_at` | DATETIME | Letzter Login-Zeitpunkt |
| `reset_token` | VARCHAR(255) | Token für Passwort-Reset |
| `reset_expires_at` | DATETIME | Ablaufzeit des Reset-Tokens |
| `created_at` | TIMESTAMP | Registrierungsdatum |
| `updated_at` | TIMESTAMP | Letztes Update |
| `favorite_track_id` | VARCHAR(50) | ID des Lieblingssongs |
| `favorite_track_title` | VARCHAR(255) | Titel des Lieblingssongs |
| `favorite_track_artist` | VARCHAR(255) | Interpret des Lieblingssongs |
| `favorite_track_artwork` | VARCHAR(255) | Cover-URL des Lieblingssongs |
| `sec_question_1` | VARCHAR(255) | Sicherheitsfrage 1 |
| `security_q1_answer_hash` | VARCHAR(255) | Gehashte Antwort zu Frage 1 |
| `sec_question_2` | VARCHAR(255) | Sicherheitsfrage 2 |
| `security_q2_answer_hash` | VARCHAR(255) | Gehashte Antwort zu Frage 2 |
| `character_choice` | VARCHAR(20) | Gewählter Charakter |
| `favorite_song_cover` | VARCHAR(255) | Cover-URL (MusicBrainz) |

### Tabelle: `leaderboard_stats`
Speichert Statistiken und ELO-Ratings pro Nutzer, Spielmodus und Kategorie.

| Feld | Typ | Beschreibung |
|------|-----|--------------|
| `id` | INT (Primary Key) | Eindeutige Statistik-ID |
| `user_id` | INT (Foreign Key) | Verknüpfung zu `users.id` |
| `game_mode` | ENUM | Spielmodus (timeline/open/cover) |
| `category` | ENUM | Musik-Kategorie (all/rock/hiphop/pop) |
| `elo` | INT | ELO-Rating (Standard: 1200) |
| `games_played` | INT | Anzahl gespielter Runden |
| `correct_answers` | INT | Anzahl richtiger Antworten |
| `wrong_answers` | INT | Anzahl falscher Antworten |
| `best_streak` | INT | Beste Streak (aufeinanderfolgende richtige Antworten) |
| `updated_at` | TIMESTAMP | Letztes Update der Statistik |

### Datenbank-Logik
- Leaderboard-Einträge werden automatisch beim ersten Spiel eines Nutzers erstellt
- ELO und Statistiken werden nach jeder Runde persistent gespeichert
- API-URLs (Covers für die T-Shirts werden in der Datenbank gespeichert. Die Audio-Endpunkt der iTunes API nicht)

---

## UX- und Design-Entscheidungen
Das Interface ist reduziert gehalten und fokussiert sich auf den Spielinhalt. Visuelle Zustände (Guess, Reveal, Timeout) sind klar voneinander getrennt.  
Das Layout ist **Mobile-First** konzipiert und skaliert (teilweise) responsiv für grössere Bildschirme.

Personalisierung erfolgt über die Wahl eines Lieblingssongs, der auf dem Spieler-Shirt angezeigt wird. Hierfür wird bewusst die MusicBrainz API verwendet, da sie mehr kreative Cover-Varianten (inkl. Fan-Art) bietet als die iTunes API.

### Figma
UI- und UX-Konzepte wurden in Figma initial erarbeitet und anschliessend für die Verwendung bisschen abgeändert.
Ziel war es mit dem Farbkonzept eine Vintage Look zu erzeugen.
👉 **Figma-Link:** [https://www.figma.com/design/Yt9H2wNYNEjqpoCToNwOJ1/Guessify?node-id=0-1&p=f]


---

## Technische Herausforderungen

### Projektstruktur und Sub-Ordner
Die nachträgliche Unterteilung von PHP-Dateien in Sub-Ordner führte zu massiven Konfigurationsproblemen. PHP-Dateien, die auf `config.php` zugreifen, funktionierten nicht mehr korrekt, da die Include-Pfade nicht mehr stimmten. ChatGPT konnte bei der Fehlerbehebung nicht helfen, da es das strukturelle Problem nicht erkannte und stattdessen unnötige Code-Änderungen vorschlug.

**Learning:** Alle PHP-Dateien mit Datenbankzugriff sollten im gleichen Verzeichnis wie `config.php` platziert werden.

### API-Wechsel: Spotify → iTunes
Initial sollte die Spotify API verwendet werden, da sie gut dokumentiert ist und viele ähnliche Projekte existieren. Jedoch stellte sich heraus, dass:
- Spotify strenge Design-Richtlinien vorschreibt, die die kreative Freiheit stark einschränken
- Die Audio-Endpoints vor ca. einem halben Jahr aus der Developer API entfernt wurden

Die **iTunes Search API** erwies sich als ideale Alternative: keine API-Keys nötig, kostenlos, umfangreiche Library und frei zugängliche Audio-Preview-Endpunkte.

### Cover-Quelle: MusicBrainz → iTunes → Hybridlösung
Anfangs wurden Album-Cover über die MusicBrainz API geladen und in der Datenbank gespeichert. Problem: Viele Alben hatten keine offiziellen Cover, was den Cover Guess Mode unbrauchbar machte.

**Lösung:** Umstieg auf iTunes API für den Cover Guess Mode (offizielle Cover, hohe Verfügbarkeit). Für die **Personalisierung** (Lieblingssong auf Spieler-Shirt) wird weiterhin MusicBrainz verwendet, da dort mehr kreative Cover-Varianten verfügbar sind.

### Hosting: CSS-Cache-Probleme bei Hostinger
Bei Änderungen am CSS wurden diese auf Hostinger nicht sichtbar, selbst nach Browser-Cache-Leerung. Die einzige effektive Lösung war, den **Server-Cache manuell im Hostinger-Dashboard zu leeren**.

### Git: .DS_Store im Repository
Beim ersten Commit wurden versehentlich `.DS_Store`-Dateien gepusht. Das gesamte Repository musste gelöscht und neu aufgesetzt werden.

**Learning:** `.DS_Store` immer in `.gitignore` aufnehmen.

---

## Learnings und Erkenntnisse

### Erstes vollständiges Login-System
Zum ersten Mal wurde ein komplettes User-Account-System mit Registrierung, Login, Session-Verwaltung und Zugriffsbeschränkung umgesetzt. Im letzten Semester (Physical Computing) fehlte diese Erfahrung komplett.

### CSS-Struktur und Modularität
Aufgrund der Erfahrungen aus vergangenen Semestern wurde bewusst eine sauberere CSS-Grundlage geschaffen:
- Erstmals mit `reset.css`, `fonts.css` und `main.css` gearbeitet
- **CSS Custom Properties (Root Variables)** für Farben, Abstände und Schriftgrössen entdeckt – vereinfacht die Konsistenz enorm
- **Utility-Klassen** definiert, die die Wiederverwendbarkeit und Lesbarkeit des Codes verbessern

### KI-Tools und strukturelle Probleme
ChatGPT stösst bei strukturellen Architekturproblemen (z.B. falsche Ordnerstruktur, Include-Pfade) an seine Grenzen. Es erkennt das zugrundeliegende Problem oft nicht und schlägt unnötige Code-Änderungen vor, die das Problem nicht lösen.

**Learning:** Ein solides eigenes Architekturverständnis ist unerlässlich. KI kann bei Syntax und Logik helfen, aber nicht bei grundlegenden Strukturentscheidungen.

### Projektstruktur und Include-Pfade
PHP-Dateien, die auf `config.php` zugreifen, sollten **nicht in Sub-Ordner** ausgelagert werden, da dies zu Include- und Pfadproblemen führt. Die Behebung dieser Probleme kostet viel Zeit.

**Learning:** Alle relevanten PHP-Dateien am gleichen Ort wie `config.php` platzieren.

### API-Auswahl und Abhängigkeiten
Die Wahl der richtigen API ist entscheidend. Spotify schien anfangs ideal, brachte aber zu viele Einschränkungen. iTunes API erwies sich als perfekte Alternative.

**Learning:** APIs frühzeitig testen und ihre Limitierungen verstehen, bevor man zu tief in die Implementierung geht.

### Hosting-spezifische Probleme
Jeder Hosting-Provider hat seine Eigenheiten. Bei Hostinger mussten CSS-Updates manuell im Dashboard forciert werden.

**Learning:** Hosting-spezifische Dokumentation lesen und frühzeitig testen.

---

## Arbeitsprozess
Das Projekt wurde iterativ entwickelt, regelmässig refaktoriert und kontinuierlich dokumentiert.  
**GitHub** diente als zentrales Arbeits- und Dokumentationswerkzeug. Nach einem initialen Fehler (`.DS_Store` im Repository) wurde das Projekt neu aufgesetzt und `.gitignore` korrekt konfiguriert.

Die Entwicklung erfolgte in mehreren Phasen:
1. **Konzeptphase:** UX/UI-Design in Figma, Technologie-Evaluation
2. **Implementierung:** Frontend, Backend, Datenbank-Integration
3. **API-Integration:** Spotify → iTunes/MusicBrainz
4. **Testing und Deployment:** Lokale Tests, Hosting auf Hostinger
5. **Refactoring:** Code-Cleanup, Dokumentation

---

## Weiterentwicklung
Mögliche zukünftige Features:

- **Multiplayer-Modus:** Echtzeit-Battles zwischen Spielern
- **Erweiterte Statistiken:** Detaillierte Auswertungen, Fortschrittsgraphen
- **Progressive Web App (PWA):** Offline-Fähigkeit, Installation auf Smartphone
- **Audiovisuelle Feedback-Elemente:** Animationen, Soundeffekte
- **Soziale Features:** Freundeslisten, Challenges, Achievements
- **Erweiterte Personalisierung:** Mehr Charakter-Optionen, Themes


---

## Autor
**Luca Genini**  
BSc Multimedia Production  
FH Graubünden  
Interaktive Medien 5

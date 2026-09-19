# One4You Bundle

Das One4You Bundle ergänzt Contao um redaktionelle Werkzeuge für wiederkehrende Projektlayouts. Im Mittelpunkt stehen der One4You Stylemanager für CSS-Klassen im Backend und ein SVG-InsertTag mit Icon-Auswahl für mitgelieferte Tabler Icons sowie eigene Projekt-Icons.

Das Bundle liefert keine vollständige Theme-Optik. Es stellt Auswahlfelder, Backend-Logik und Hilfsfunktionen bereit. Die eigentliche Gestaltung bleibt im Theme-CSS des Projekts, zum Beispiel in `theme-tokens.css`, `theme-base.scss` und `theme-project.scss`.

## Funktionen

- Stylemanager für Artikel, Inhaltselemente, Layouts, Seiten, Module, News, Events, Formulare und Formularfelder
- YAML-basierte Konfiguration der auswählbaren Klassen
- Projekt-Overrides über `config/one4you/styles.yaml`
- SVG-InsertTag `{{svg::...}}` für inline ausgegebene SVGs
- Backend-Picker für SVG-Icons in unterstützten Textfeldern
- Mitgelieferte Tabler Icons in den Varianten `outline` und `filled`
- Eigene Projekt-Icons aus einem konfigurierbaren Icon-Pfad
- Bereinigung von alten Stylemanager-Klassen über einen Konsolenbefehl

## Stylemanager

Der Stylemanager fügt in unterstützten Contao-Datensätzen ein eigenes Backend-Feld `one4youStyleManager` ein. Redakteurinnen und Redakteure wählen dort Layout-, Hintergrund-, Abstands- oder Grid-Optionen aus. Aus diesen Werten erzeugt das Bundle CSS-Klassen und hängt sie beim Rendern an das passende Frontend-Element an.

Die Klassen landen bewusst nicht dauerhaft in jedem normalen CSS-Klassenfeld. Manuell gepflegte Klassen bleiben dadurch getrennt von den über den Stylemanager gesetzten Klassen.

## Konfigurationsdateien

Die Standarddefinitionen liegen im Bundle:

```text
bundles/one4you/config/styles.yaml
```

Projektbezogene Anpassungen liegen im Projekt:

```text
config/one4you/styles.yaml
```

Beim Laden werden zuerst die Bundle-Defaults gelesen. Danach überschreibt `config/one4you/styles.yaml` die passenden Werte. So kann ein Projekt Labels, Optionslisten oder ganze Blöcke anpassen, ohne die Bundle-Datei zu verändern.

## Aufbau der styles.yaml

Die wichtigsten Bereiche sind:

```yaml
breakpoints: [sm, md, lg, xl]

option_sources:
  backgrounds:
    bg-1: Primär (dunkelblau)
    bg-2: Sekundär (blau)

blocks:
  article_layout:
    label: Layout
    show_in: [articles]
    tabs:
      article:
        label: Artikel
        fields:
          background:
            label: Hintergrund
            type: select
            options_source: backgrounds
```

`breakpoints` definiert die verfügbaren responsiven Stufen. `option_sources` enthält wiederverwendbare Optionslisten. `blocks` beschreibt die sichtbaren Stylemanager-Blöcke, Tabs und Felder.

## Option Sources

Wiederverwendbare Optionslisten werden unter `option_sources` gepflegt. Felder referenzieren diese Listen über `options_source`.

Beispiel:

```yaml
option_sources:
  backgrounds:
    bg-1: Primär (dunkelblau)
    bg-2: Sekundär (blau)
    bg-3: Akzent (rot)

blocks:
  content:
    tabs:
      settings:
        fields:
          background:
            label: Hintergrund
            type: select
            options_source: backgrounds
```

Für Hintergründe bedeutet das: Die YAML-Datei steuert, welche Klassen und Labels im Backend angeboten werden. Die echten Farbwerte gehören ins Theme-CSS. Eine Option `bg-1` setzt also die Klasse `bg-1`; wie diese Klasse aussieht, entscheidet das Projekt-CSS.

## Hintergrundfarben

Die Hintergründe werden aktuell über die Option Source `backgrounds` gepflegt:

```yaml
option_sources:
  backgrounds:
    bg-1: Primär (dunkelblau)
    bg-2: Sekundär (blau)
    bg-3: Akzent (rot)
    bg-4: Fläche (weiß)
    bg-5: Fläche abgesetzt (hellgrau)
    bg-6: Schwarz (schwarz)
    bg-7: Weiß (weiß)
    bg-8: Transparent 1 (transparent)
    bg-9: Transparent 2 (transparent)
    bg-10: Transparent 3 (transparent)
```

Die Bezeichnungen sind redaktionell gedacht. Sie sollen im Backend verständlich sein und dürfen projektspezifisch angepasst werden. Die Farblogik bleibt in den CSS-Tokens des Themes, zum Beispiel `--color-primary`, `--color-secondary`, `--color-accent` und `--bg-1` bis `--bg-10`.

## Sichtbarkeit von Style-Blöcken

Mit `show_in` wird festgelegt, in welchen Contao-Bereichen ein Block erscheint. Die internen Namen sind:

- `layouts`
- `pages`
- `articles`
- `modules`
- `news`
- `events`
- `forms`
- `form_fields`
- `content_elements`

Mit `show_for` kann ein Block, Tab oder Feld zusätzlich auf bestimmte Datensätze eingeschränkt werden. Beispiel:

```yaml
wrapper_layout:
  label: Wrapper
  show_in: [content_elements]
  show_for:
    tl_content:
      type: [rsce_wrapper_start]
```

Dieser Block erscheint nur bei Inhaltselementen mit dem Typ `rsce_wrapper_start`.

## Feldtypen und Klassen

Ein einfaches Select-Feld kann seine Klassen direkt aus den Optionen beziehen:

```yaml
width:
  label: Breite des Artikels
  type: select
  options:
    full-bg: Hintergrund vollflächig, Inhalt zentriert
    full-content: Inhalt vollflächig
```

Die gespeicherte Klasse ist hier `full-bg` oder `full-content`.

Für dynamische Klassen gibt es `class_pattern`:

```yaml
columns:
  label: Spalten
  type: select
  responsive: true
  class_pattern: "col-{value}"
  responsive_class_pattern: "col-{breakpoint}-{value}"
  options:
    "6": 6 / 12
    "12": 12 / 12
```

Bei Wert `6` entsteht `col-6`. Für den Breakpoint `md` entsteht `col-md-6`.

Für vierseitige Abstände gibt es den Feldtyp `trbl`:

```yaml
margin:
  label: Außen-Abstand
  type: trbl
  responsive: true
  class_patterns:
    top: "mt-{breakpoint}{value}"
    right: "mr-{breakpoint}{value}"
    bottom: "mb-{breakpoint}{value}"
    left: "ml-{breakpoint}{value}"
```

`trbl`-Felder verwenden eine konfigurierte Optionsliste. Dadurch erscheinen im
Backend Auswahlfelder statt freier Pixelwerte. Über `aliases` können frühere
Werte beim Synchronisieren kontrolliert auf die aktuelle Skala abgebildet werden:

```yaml
option_sources:
  spacing_sizes:
    s:
      label: S
      aliases: ["2"]
```

Manuell eingetragene Klassen werden nur dann aus dem normalen Contao-Klassenfeld
entfernt, wenn sie eindeutig einer sichtbaren Auswahl zugeordnet und dort
gespeichert werden können. Unbekannte oder widersprüchliche Klassen bleiben
unverändert erhalten.

Bestände lassen sich zunächst read-only prüfen und anschließend ausdrücklich
übernehmen:

```bash
php vendor/bin/contao-console one4you:stylemanager:sync-classes
php vendor/bin/contao-console one4you:stylemanager:sync-classes --apply
```

Die fokussierten Importtests laufen mit:

```bash
php vendor/c4y/one4you/tests/run.php
```

## Frontend-Ausgabe

Das Bundle hängt die erzeugten Klassen beim Rendern an die passenden Frontend-Elemente an. Unterstützt werden unter anderem:

- Artikel über `mod_article`
- Inhaltselemente über das erste HTML-Element des Elements
- Seiten und Layouts über die Klassen am `fe_page`
- Module über das erste HTML-Element des Moduls
- Formulare und Formularfelder
- News und Events

Wenn ein Datensatz zusätzlich manuelle CSS-Klassen enthält, werden diese mit den Stylemanager-Klassen zusammengeführt.

## SVG-InsertTag

Das Bundle stellt den InsertTag `{{svg::...}}` bereit. Der InsertTag gibt das SVG inline als HTML aus.

Beispiele:

```text
{{svg::outline:search}}
{{svg::filled:home}}
{{svg::custom:phone}}
{{svg::phone}}
```

Der erste Teil ist die Quelle:

- `outline`: mitgelieferte Tabler Outline Icons
- `filled`: mitgelieferte Tabler Filled Icons
- `custom`: eigene Projekt-Icons

Wird keine Quelle angegeben, sucht das Bundle zuerst in `custom`, danach in `outline` und danach in `filled`.

## SVG Icon-Pfad

Eigene Projekt-Icons werden standardmäßig aus diesem Pfad geladen:

```text
files/theme/icons
```

Der Pfad ist in `bundles/one4you/config/services.yml` so definiert:

```yaml
parameters:
    one4you.svg.default_icon_path: 'files/theme/icons'
    one4you.svg.icon_path: '%env(default:one4you.svg.default_icon_path:ONE4YOU_SVG_ICON_PATH)%'
```

`one4you.svg.default_icon_path` ist der Fallback. `one4you.svg.icon_path` nutzt den Wert der Umgebungsvariable `ONE4YOU_SVG_ICON_PATH`, falls sie gesetzt ist. Ist sie nicht gesetzt, wird automatisch `files/theme/icons` verwendet.

Relative Pfade werden relativ zum Contao-Projektverzeichnis aufgelöst. Absolute Pfade sind ebenfalls möglich.

Beispiele:

```dotenv
ONE4YOU_SVG_ICON_PATH=files/theme/icons
ONE4YOU_SVG_ICON_PATH=files/customer/icons
ONE4YOU_SVG_ICON_PATH=/var/www/shared/icons
```

Ein eigenes Icon `files/theme/icons/phone.svg` kann dann so ausgegeben werden:

```text
{{svg::custom:phone}}
```

Oder, weil `custom` zuerst durchsucht wird, kurz:

```text
{{svg::phone}}
```

## Regeln für Icon-Dateien

Eigene Icon-Dateien müssen als einzelne SVG-Dateien im konfigurierten Icon-Pfad liegen.

- Dateiendung: `.svg`
- Referenz im InsertTag ohne `.svg`
- Erlaubte Zeichen im Namen: Kleinbuchstaben, Zahlen, Unterstrich und Bindestrich
- Beispiele: `phone.svg`, `arrow-right.svg`, `social_instagram.svg`

Nicht gültig sind Namen mit Leerzeichen, Großbuchstaben oder Sonderzeichen.

Beim Ausgeben werden SVGs bereinigt. Das Bundle entfernt unter anderem Skripte, `foreignObject`, Event-Attribute, `href`, `xlink:href` und Inline-Styles. Zusätzlich erhält das SVG Klassen wie `svg-inline`, `svg--custom` und `svg--phone`.

## SVG-Picker im Backend

Im Backend lädt das Bundle zusätzliche Assets für den SVG-Picker:

```text
bundles/contaoone4you/backend/one4you-svg-picker.css
bundles/contaoone4you/backend/one4you-svg-picker.js
```

Die Icon-Liste kommt über die Backend-Route:

```text
/contao/one4you/svg-icons
```

Der Picker zeigt Icons aus allen Quellen an und fügt den passenden InsertTag ein, zum Beispiel `{{svg::outline:search}}` oder `{{svg::custom:phone}}`.

## Projekt-Overrides pflegen

Für Projekte sollte möglichst nur `config/one4you/styles.yaml` angepasst werden. Typische Änderungen sind:

- Hintergrund-Labels verständlicher machen
- Optionslisten kürzen oder erweitern
- zusätzliche Style-Blöcke für bestimmte Inhaltstypen ergänzen
- neue Klassen für vorhandene Theme-Utilities anbieten

Die CSS-Regeln zu diesen Klassen müssen im jeweiligen Theme vorhanden sein. Der Stylemanager ist keine Ersatz-CSS-Datei, sondern die redaktionelle Oberfläche für vorhandene Klassen.

## Klassen bereinigen

Wenn Stylemanager-Klassen früher manuell in normalen Contao-Klassenfeldern gespeichert wurden, können sie bereinigt werden:

```bash
php vendor/bin/contao-console one4you:stylemanager:cleanup-classes
```

Der Befehl entfernt nur Klassen, die aus der aktuellen Stylemanager-Konfiguration ableitbar sind. Manuelle Klassen bleiben erhalten.

## Cache und Prüfung

Nach Änderungen an YAML-Konfiguration, Services, DCA oder Templates sollte der Contao-Cache geleert werden:

```bash
php vendor/bin/contao-console cache:clear
```

Bei PHP-Änderungen können einzelne Dateien mit `php -l` geprüft werden:

```bash
php -l bundles/one4you/src/Svg/SvgIconProvider.php
```

Bei reinen README-Änderungen ist kein Cache-Clear nötig.

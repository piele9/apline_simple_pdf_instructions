# APLINE — instrukcje PDF dla PrestaShop 9

Przyciski pobierania załączników na stronie produktu, z własnym tekstem, ikoną i kolorem. Pomagają pokazać instrukcje PDF i inne dokumenty dostępne w standardowych załącznikach PrestaShop. Moduł ma polski panel i nie przechowuje samych PDF-ów.

## Funkcje

- Definicje przycisków przypisane do pozycji załącznika od 1 do 10.
- Własny tekst, opcjonalnie z nazwą produktu, albo nazwa załącznika.
- Ikona jako JPG, PNG, WebP lub encja HTML / kod Unicode; po lewej, prawej, obu stronach albo ukryta.
- Kolor przycisku, kolejność i widoczność.
- Duży przycisk **Zarządzaj przyciskami PDF** i duże przyciski zapisu.
- Pomijanie przycisku, gdy produkt nie ma załącznika na wybranej pozycji.

## Wymagania

PrestaShop 9, PHP 8.1+ (zgodny z wymaganiami użytej wersji PrestaShop). Do przesyłania ikon potrzebne jest prawo zapisu w `views/img/`. Ikony: JPG, PNG, WebP, do 2 MB. Dokumenty dodaje się jako natywne załączniki produktów.

## Instalacja

1. Pobierz ZIP z [Releases](https://github.com/piele9/apline_simple_pdf_instructions/releases).
2. W panelu PrestaShop wybierz **Moduły → Menedżer modułów → Prześlij moduł** i wskaż ZIP.
3. Przy instalacji ręcznej rozpakuj folder `apline_simple_pdf_instructions` do `modules/` — nazwa musi pozostać zgodna z nazwą modułu.
4. Zainstaluj i otwórz konfigurację. Moduł nie tworzy przykładowych przycisków.

## Konfiguracja

1. Dodaj dokument w **Katalog → Pliki** i przypisz go do produktu jako załącznik.
2. W konfiguracji modułu ustaw miejsce wyświetlania: domyślnie strona produktu, `displayProductAdditionalInfo`. Dostępne są także lewa/prawa kolumna i stopka produktu; ich obecność zależy od motywu.
3. Kliknij **Zarządzaj przyciskami PDF**, dodaj przycisk i wybierz pozycję załącznika 1–10.
4. Wybierz źródło etykiety. Dla własnego tekstu wpisz etykietę lub włącz **Dodaj nazwę produktu**. Dla nazwy pliku używana jest nazwa załącznika w bieżącym języku, a przy jej braku nazwa przechowywanego pliku bez rozszerzenia.
5. Ustaw ikonę (np. `1F4C4`), jej położenie, kolor `#RRGGBB`, widoczność i kolejność. Jeśli położenie nie jest ustawione na **Brak**, ikona jest wymagana.
6. Zapisz i sprawdź produkt z załącznikiem oraz produkt bez niego.

Pozycje załączników są wyznaczane według rosnącego `id_attachment`, nie kolejności definicji przycisków. Trzymaj spójną kolejność dokumentów między produktami. Moduł nie filtruje załączników według MIME: przycisk może pobrać także plik innego typu. Pobieranie obsługuje natywny kontroler PrestaShop, a link otwiera nową kartę.

Osadzanie w Smarty: `{widget name='apline_simple_pdf_instructions'}`. Przy braku produktu lub właściwego załącznika nie ma przycisku.

## Aktualizacja

Wykonaj kopię bazy i katalogu modułu wraz z ikonami. Prześlij nowy ZIP i uruchom aktualizację w menedżerze. Wersja 1.1.0 zmienia interfejs i nazwę zakładki, zachowując definicje, ikony, ustawienia i załączniki produktów. Nie odinstalowuj w celu aktualizacji. Wyczyść cache i sprawdź pobieranie.

## Odinstalowanie

Usuwane są definicje przycisków, konfiguracja i ikony przesłane przez moduł. Natywne załączniki produktów pozostają w PrestaShop.

## Historia zmian i licencja

Zmiany: [CHANGELOG.md](CHANGELOG.md). Warunki: [LICENSE.md](LICENSE.md), Custom Attribution License v1.0. Użycie komercyjne, modyfikacje i dystrybucja są dozwolone przy zachowaniu widocznego odnośnika APLINE w konfiguracji.

Autor: **APLINE Arkadiusz Pielechowski** — [apline.pl](https://apline.pl).

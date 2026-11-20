<?php

namespace App\Services\PlatformImport\Parsers;

use Carbon\CarbonImmutable;

/**
 * Tableau CSV lu avec ses en-têtes : séparateur « , » ou « ; » détecté, BOM retiré, colonnes
 * retrouvées par plusieurs noms possibles (export en français ou en anglais).
 */
class CsvTable
{
    /** @var list<string> */
    private array $headers;

    /** @var list<array{line: int, cells: list<string>}> */
    private array $rows = [];

    public function __construct(string $contents)
    {
        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents) ?? $contents;
        $lines = preg_split('/\r\n|\n|\r/', $contents) ?: [];
        $first = $lines[0] ?? '';
        $separator = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);
        $this->headers = array_map(fn ($cell) => $this->normalize((string) $cell), fgetcsv($stream, null, $separator, '"', '') ?: []);
        $line = 1;
        while (($cells = fgetcsv($stream, null, $separator, '"', '')) !== false) {
            $line++;
            if ($cells === [null] || implode('', array_map('strval', $cells)) === '') {
                continue;
            }
            $this->rows[] = ['line' => $line, 'cells' => array_map(fn ($cell) => trim((string) $cell), $cells)];
        }
        fclose($stream);
    }

    /**
     * Index de la première colonne qui porte l'un de ces noms.
     *
     * @param  list<string>  $names
     */
    public function column(array $names): ?int
    {
        foreach ($names as $name) {
            $index = array_search($this->normalize($name), $this->headers, true);
            if ($index !== false) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @return list<array{line: int, cells: list<string>}>
     */
    public function rows(): array
    {
        return $this->rows;
    }

    /** « 1 234,56 », « 1,234.56 », « 123.45 EUR », « -12 € » → centimes ; null si vide ou illisible. */
    public static function cents(?string $value): ?int
    {
        $text = preg_replace('/[^\d,.\-]/u', '', (string) $value) ?? '';
        if ($text === '' || $text === '-') {
            return null;
        }
        $lastComma = strrpos($text, ',');
        $lastDot = strrpos($text, '.');
        $decimal = $lastComma !== false && ($lastDot === false || $lastComma > $lastDot) ? ',' : '.';
        $thousands = $decimal === ',' ? '.' : ',';
        $text = str_replace([$thousands, $decimal], ['', '.'], $text);

        return is_numeric($text) ? (int) round((float) $text * 100) : null;
    }

    /**
     * Date d'un export : AAAA-MM-JJ, ou JJ/MM/AAAA (français) / MM/JJ/AAAA (anglais).
     */
    public static function date(?string $value, bool $dayFirst): ?CarbonImmutable
    {
        $value = trim((string) $value);
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})/', $value, $m)) {
            return checkdate((int) $m[2], (int) $m[3], (int) $m[1]) ? CarbonImmutable::create((int) $m[1], (int) $m[2], (int) $m[3]) : null;
        }
        if (preg_match('#^(\d{1,2})/(\d{1,2})/(\d{4})$#', $value, $m)) {
            [$day, $month] = $dayFirst ? [(int) $m[1], (int) $m[2]] : [(int) $m[2], (int) $m[1]];

            return checkdate($month, $day, (int) $m[3]) ? CarbonImmutable::create((int) $m[3], $month, $day) : null;
        }

        return null;
    }

    private function normalize(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        return mb_strtolower(trim($header));
    }
}

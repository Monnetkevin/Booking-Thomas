<?php

namespace App\Services\Export;

/**
 * Fichier CSV lisible par un tableur français : séparateur « ; », fin de ligne CRLF,
 * BOM UTF-8 (accents), cellules neutralisées contre l'injection de formules.
 */
class CsvWriter
{
    /**
     * @param  list<list<mixed>>  $rows
     */
    public function write(array $rows): string
    {
        return "\u{FEFF}".implode("\r\n", array_map(fn (array $row) => implode(';', array_map($this->cell(...), $row)), $rows))."\r\n";
    }

    /** 12345 centimes → « 123,45 ». */
    public function euros(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }

    /**
     * Cellule échappée. Un texte (nom de logement, de voyageur) qui commence par =, +, -, @,
     * une tabulation ou un retour chariot est neutralisé pour le tableur ; un nombre
     * (montant négatif compris) reste un nombre.
     */
    private function cell(mixed $value): string
    {
        $text = (string) $value;
        $isNumber = is_int($value) || preg_match('/^-?\d+(,\d+)?$/', $text) === 1;
        if (! $isNumber && $text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            $text = "'".$text;
        }

        return strpbrk($text, ";\"\n\r") !== false ? '"'.str_replace('"', '""', $text).'"' : $text;
    }
}

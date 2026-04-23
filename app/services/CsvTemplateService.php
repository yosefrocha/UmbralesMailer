<?php

declare(strict_types=1);

final class CsvTemplateService
{
    public static function recipientsFilename(): string
    {
        return 'plantilla_destinatarios.csv';
    }

    public static function recipientsContent(): string
    {
        return implode("\n", [
            'correo,nombre,apellido,inst,pais,segmento,estado,consent',
            'maria@ejemplo.com,Maria,Garcia,Instituto Umbrales,Mexico,Docentes,activo,2026-04-20 10:00:00',
            'juan@ejemplo.com,Juan,Perez,Colegio Horizonte,Colombia,Directivos,activo,2026-04-20 11:00:00',
        ]);
    }

    public static function downloadRecipientsTemplate(): void
    {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . self::recipientsFilename() . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF";
        echo self::recipientsContent();
        exit;
    }
}
<?php
declare(strict_types=1);

/**
 * Generador de PDF mínimo, sin dependencias externas.
 *
 * Escribe PDF 1.4 usando las fuentes base Helvetica (regular, negrita y
 * cursiva) con codificación WinAnsi, por lo que soporta acentos y ñ.
 * Pensado para generar reportes sencillos (historia clínica) en cualquier
 * hosting PHP, incluso sin Composer ni extensiones adicionales.
 */
class SimplePDF
{
    private const PAGE_WIDTH  = 595.28; // A4 en puntos
    private const PAGE_HEIGHT = 841.89;

    /** @var string[] Contenido de cada página ya cerrada. */
    private array $pages = [];

    /** @var string Contenido de la página en curso. */
    private string $current = '';

    private string $font = 'F1';
    private float $fontSize = 11;

    /** @var array{0: float, 1: float, 2: float} */
    private array $textColor = [0.0, 0.0, 0.0];

    /** @var array{0: float, 1: float, 2: float} */
    private array $drawColor = [0.0, 0.0, 0.0];

    private string $footerLeft = '';

    public function pageWidth(): float
    {
        return self::PAGE_WIDTH;
    }

    public function pageHeight(): float
    {
        return self::PAGE_HEIGHT;
    }

    public function setFooterLeft(string $text): void
    {
        $this->footerLeft = $text;
    }

    /**
     * Cierra la página actual y comienza una nueva.
     */
    public function addPage(): void
    {
        if ($this->current !== '') {
            $this->drawFooter(count($this->pages) + 1);
            $this->pages[] = $this->current;
        }

        $this->current = '';
        $this->textColor = [0.0, 0.0, 0.0];
    }

    public function setFont(string $style, float $size): void
    {
        $map = [
            'regular' => 'F1',
            'bold'    => 'F2',
            'italic'  => 'F3',
        ];

        $this->font = $map[$style] ?? 'F1';
        $this->fontSize = $size;
    }

    public function setTextColor(float $r, float $g, float $b): void
    {
        $this->textColor = [$r, $g, $b];
    }

    public function setDrawColor(float $r, float $g, float $b): void
    {
        $this->drawColor = [$r, $g, $b];
    }

    /**
     * Escribe texto en coordenadas desde la esquina superior izquierda.
     */
    public function text(float $x, float $y, string $text): void
    {
        $this->current .= sprintf(
            "%.3F %.3F %.3F rg BT /%s %.2F Tf %.2F %.2F Td (%s) Tj ET\n",
            $this->textColor[0],
            $this->textColor[1],
            $this->textColor[2],
            $this->font,
            $this->fontSize,
            $x,
            self::PAGE_HEIGHT - $y,
            $this->escape($text)
        );
    }

    /**
     * Dibuja una línea.
     */
    public function line(float $x1, float $y1, float $x2, float $y2, float $width = 0.5): void
    {
        $this->current .= sprintf(
            "%.3F %.3F %.3F RG %.2F w %.2F %.2F m %.2F %.2F l S\n",
            $this->drawColor[0],
            $this->drawColor[1],
            $this->drawColor[2],
            $width,
            $x1,
            self::PAGE_HEIGHT - $y1,
            $x2,
            self::PAGE_HEIGHT - $y2
        );
    }

    /**
     * Dibuja un rectángulo. $style: 'F' relleno, 'S' solo borde.
     *
     * @param array{0: float, 1: float, 2: float} $rgb Color de relleno.
     */
    public function rect(float $x, float $y, float $w, float $h, string $style = 'S', array $rgb = [0.0, 0.0, 0.0]): void
    {
        $operator = $style === 'F' ? 'f' : 'S';

        $this->current .= sprintf(
            "%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re %s\n",
            $rgb[0],
            $rgb[1],
            $rgb[2],
            $x,
            self::PAGE_HEIGHT - $y - $h,
            $w,
            $h,
            $operator
        );
    }

    /**
     * Ancho aproximado de un texto (para ajuste de líneas).
     */
    public function textWidth(string $text, float $size, bool $bold = false): float
    {
        $factor = $bold ? 0.56 : 0.50;

        return strlen($text) * $size * $factor;
    }

    /**
     * Divide un texto en líneas que caben en $maxWidth.
     *
     * @return string[]
     */
    public function wrap(string $text, float $maxWidth, float $size, bool $bold = false): array
    {
        $words = preg_split('/\s+/', trim($text)) ?: [];
        $lines = [];
        $line = '';

        foreach ($words as $word) {
            $candidate = $line === '' ? $word : $line . ' ' . $word;

            if ($this->textWidth($candidate, $size, $bold) > $maxWidth && $line !== '') {
                $lines[] = $line;
                $line = $word;
            } else {
                $line = $candidate;
            }
        }

        if ($line !== '') {
            $lines[] = $line;
        }

        return $lines;
    }

    /**
     * Devuelve el PDF como cadena binaria.
     */
    public function output(): string
    {
        if ($this->current !== '') {
            $this->drawFooter(count($this->pages) + 1);
            $this->pages[] = $this->current;
            $this->current = '';
        }

        if (empty($this->pages)) {
            $this->pages[] = '';
        }

        // Numeración de objetos:
        // 1 catálogo | 2 páginas | 3-5 fuentes | luego 2 objetos por página.
        $objects = [];

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';

        $kids = [];
        $total = count($this->pages);

        for ($i = 0; $i < $total; $i++) {
            $kids[] = (6 + $i * 2) . ' 0 R';
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $total . ' >>';

        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>';

        for ($i = 0; $i < $total; $i++) {
            $pageNumber    = 6 + $i * 2;
            $contentNumber = 7 + $i * 2;
            $content       = $this->pages[$i];

            $objects[$pageNumber] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] ' .
                '/Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R >> >> ' .
                '/Contents %d 0 R >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $contentNumber
            );

            $objects[$contentNumber] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
        }

        // Ensamblado y tabla xref.
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $body . "\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $maxObject    = max(array_keys($objects));

        $pdf .= "xref\n0 " . ($maxObject + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= $maxObject; $i++) {
            $pdf .= isset($offsets[$i])
                ? sprintf("%010d 00000 n \n", $offsets[$i])
                : "0000000000 65535 f \n";
        }

        $pdf .= "trailer\n<< /Size " . ($maxObject + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    /**
     * Pie de página con numeración.
     */
    private function drawFooter(int $pageNumber): void
    {
        $this->setFont('regular', 8);
        $this->setTextColor(0.42, 0.45, 0.47);
        $this->setDrawColor(0.80, 0.84, 0.86);

        $this->line(40, self::PAGE_HEIGHT - 45, self::PAGE_WIDTH - 40, self::PAGE_HEIGHT - 45, 0.4);
        $this->text(40, self::PAGE_HEIGHT - 34, $this->footerLeft);
        $this->text(self::PAGE_WIDTH - 95, self::PAGE_HEIGHT - 34, 'Página ' . $pageNumber);

        $this->setTextColor(0.0, 0.0, 0.0);
        $this->setDrawColor(0.0, 0.0, 0.0);
    }

    /**
     * Escapa una cadena UTF-8 para un literal PDF con WinAnsiEncoding.
     */
    private function escape(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);

        if ($converted === false) {
            $converted = $text;
        }

        $result = '';
        $length = strlen($converted);

        for ($i = 0; $i < $length; $i++) {
            $char = $converted[$i];
            $ord  = ord($char);

            if ($char === '\\') {
                $result .= '\\\\';
            } elseif ($char === '(') {
                $result .= '\\(';
            } elseif ($char === ')') {
                $result .= '\\)';
            } elseif ($ord < 32 || $ord > 126) {
                $result .= sprintf('\\%03o', $ord);
            } else {
                $result .= $char;
            }
        }

        return $result;
    }
}

<?php
/* Copyright (C) 2022-2024 EVARISK <technique@evarisk.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    core/modules/dolicar/dolicardocuments/dolicar_pdf_document.php
 * \ingroup dolicar
 * \brief   Shared native TCPDF helpers for DoliCar PDF documents (no HTML, fixed columns)
 */

if (!class_exists('DolicarPdfTcpdf')) {
    require_once DOL_DOCUMENT_ROOT . '/includes/tecnickcom/tcpdf/tcpdf.php';

    /**
     * TCPDF subclass drawing a repeated header (logo + title) and footer (legal + page number).
     */
    class DolicarPdfTcpdf extends TCPDF
    {
        /** @var string Path to company logo */
        public $logoPath = '';
        /** @var string Document title */
        public $docTitle = '';
        /** @var string Document subtitle */
        public $docSubtitle = '';
        /** @var string Footer legal line */
        public $footerLegal = '';

        /**
         * Page header
         *
         * @return void
         */
        public function Header() // phpcs:ignore PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
        {
            if (!empty($this->logoPath) && is_file($this->logoPath)) {
                $this->Image($this->logoPath, 15, 10, 0, 16, '', '', 'T', false, 300, '', false, false, 0);
            }
            $this->SetTextColor(31, 78, 110);
            $this->SetFont('helvetica', 'B', 15);
            $this->SetXY(80, 11);
            $this->Cell(115, 8, $this->docTitle, 0, 2, 'R');
            $this->SetFont('helvetica', '', 9);
            $this->SetTextColor(107, 119, 133);
            $this->Cell(115, 5, $this->docSubtitle, 0, 2, 'R');

            $this->SetDrawColor(31, 78, 110);
            $this->SetLineWidth(0.6);
            $this->Line(15, 30, 195, 30);
            $this->SetLineWidth(0.2);
        }

        /**
         * Page footer
         *
         * @return void
         */
        public function Footer() // phpcs:ignore PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
        {
            $this->SetY(-15);
            $this->SetDrawColor(203, 213, 224);
            $this->SetLineWidth(0.3);
            $this->Line(15, $this->GetY(), 195, $this->GetY());
            $this->SetY(-12);
            $this->SetFont('helvetica', '', 7);
            $this->SetTextColor(150, 150, 150);
            $this->Cell(150, 6, $this->footerLegal, 0, 0, 'L');
            $this->Cell(30, 6, $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
        }
    }
}

if (!class_exists('DolicarPdfRender')) {
    /**
     * Native TCPDF renderer: section titles, key/value grids, KPI boxes and aligned tables.
     * Every column has a fixed width in mm so headers and rows always line up.
     */
    class DolicarPdfRender
    {
        /** @var TCPDF */
        private $pdf;

        /** @var float Left margin */
        private $margeGauche;

        /** @var float Bottom margin kept free (footer area) */
        private $margeBasse = 18;

        /** @var float Usable width between margins */
        private $usableW;

        /** @var array Columns of the table currently being drawn (for header repetition) */
        private $headerCols = [];

        // Color palette
        private $navy   = [31, 78, 110];
        private $light  = [234, 242, 248];
        private $zebra  = [246, 249, 252];
        private $border = [203, 213, 224];
        private $gray   = [107, 119, 133];
        private $white  = [255, 255, 255];
        private $black  = [40, 40, 40];

        /**
         * Constructor
         *
         * @param TCPDF $pdf         PDF handler
         * @param float $margeGauche Left margin in mm
         * @param float $margeDroite Right margin in mm
         */
        public function __construct($pdf, float $margeGauche = 15, float $margeDroite = 15)
        {
            $this->pdf         = $pdf;
            $this->margeGauche = $margeGauche;
            $this->usableW     = $pdf->getPageWidth() - $margeGauche - $margeDroite;
        }

        /**
         * Add a page if the needed height does not fit before the bottom margin.
         *
         * @param  float $needed Height needed in mm
         * @return bool          True if a page break occurred
         */
        public function checkPageBreak(float $needed): bool
        {
            $limit = $this->pdf->getPageHeight() - $this->margeBasse;
            if ($this->pdf->GetY() + $needed > $limit) {
                $this->pdf->AddPage();
                return true;
            }
            return false;
        }

        /**
         * Add vertical space.
         *
         * @param  float $h Height in mm
         * @return void
         */
        public function spacer(float $h): void
        {
            $this->pdf->Ln($h);
        }

        /**
         * Bordered, filled cell shortcut (text font/colors must be set by the caller).
         *
         * @return void
         */
        private function mc(float $w, float $h, string $txt, string $align, float $x, float $y, int $border = 1, bool $fill = true, string $valign = 'M'): void
        {
            $this->pdf->MultiCell($w, $h, $txt, $border, $align, $fill, 0, $x, $y, true, 0, false, true, $h, $valign);
        }

        /**
         * Compute the height (mm) needed to render the tallest text among the given (width, text) pairs.
         *
         * @param  array $pairs List of [width, text]
         * @param  float $min   Minimum height
         * @return float        Height in mm (rounded up + padding)
         */
        private function rowHeight(array $pairs, float $min): float
        {
            $h = $min;
            foreach ($pairs as $pair) {
                if ((string) $pair[1] === '') {
                    continue;
                }
                $hh = $this->pdf->getStringHeight($pair[0] - 2, (string) $pair[1]);
                if ($hh > $h) {
                    $h = $hh;
                }
            }
            return ceil($h) + 1;
        }

        /**
         * Draw a full width colored section title band.
         *
         * @param  string $text Title text
         * @return void
         */
        public function sectionTitle(string $text): void
        {
            $h = 7;
            $this->checkPageBreak($h + 4);
            $y = $this->pdf->GetY();
            $this->pdf->SetFillColor(...$this->navy);
            $this->pdf->Rect($this->margeGauche, $y, $this->usableW, $h, 'F');
            $this->pdf->SetFont('', 'B', 10);
            $this->pdf->SetTextColor(...$this->white);
            $this->pdf->SetXY($this->margeGauche + 2, $y + 1.6);
            $this->pdf->Cell($this->usableW - 4, 4, $text, 0, 0, 'L');
            $this->pdf->SetXY($this->margeGauche, $y + $h);
            $this->pdf->Ln(1.5);
        }

        /**
         * Draw a two columns "label / value" information grid (2 pairs per line).
         *
         * @param  array $rows List of [label, value]
         * @return void
         */
        public function keyValueGrid(array $rows): void
        {
            $labelW = 35;
            $valueW = ($this->usableW / 2) - $labelW;

            for ($i = 0, $count = count($rows); $i < $count; $i += 2) {
                $pair = [$rows[$i] ?? null, $rows[$i + 1] ?? null];

                // Row height must fit both the (bold) label and the value, either of which may wrap
                $rowH = 6;
                foreach ($pair as $p) {
                    if ($p === null) {
                        continue;
                    }
                    $this->pdf->SetFont('', 'B', 9);
                    $hLabel = $this->pdf->getStringHeight($labelW - 2, (string) $p[0]);
                    $this->pdf->SetFont('', '', 9);
                    $value  = (string) $p[1] !== '' ? (string) $p[1] : '—';
                    $hValue = $this->pdf->getStringHeight($valueW - 2, $value);
                    $rowH   = max($rowH, $hLabel, $hValue);
                }
                $rowH = ceil($rowH) + 1;

                $this->checkPageBreak($rowH);
                $y = $this->pdf->GetY();
                $x = $this->margeGauche;

                foreach ($pair as $p) {
                    if ($p === null) {
                        $x += $labelW + $valueW;
                        continue;
                    }
                    $label = (string) $p[0];
                    $value = (string) $p[1] !== '' ? (string) $p[1] : '—';

                    $this->pdf->SetFont('', 'B', 9);
                    $this->pdf->SetTextColor(...$this->navy);
                    $this->pdf->SetFillColor(...$this->light);
                    $this->pdf->SetDrawColor(...$this->border);
                    $this->mc($labelW, $rowH, $label, 'L', $x, $y);

                    $this->pdf->SetFont('', '', 9);
                    $this->pdf->SetTextColor(...$this->black);
                    $this->pdf->SetFillColor(...$this->white);
                    $this->mc($valueW, $rowH, $value, 'L', $x + $labelW, $y);

                    $x += $labelW + $valueW;
                }
                $this->pdf->SetXY($this->margeGauche, $y + $rowH);
            }
        }

        /**
         * Draw a row of KPI boxes (label on a navy header cell, big value below).
         *
         * @param  array $boxes List of [label, value]
         * @return void
         */
        public function statBoxes(array $boxes): void
        {
            $count = max(1, count($boxes));
            $w     = $this->usableW / $count;
            $hL    = 6;
            $hV    = 11;

            $this->checkPageBreak($hL + $hV);
            $y = $this->pdf->GetY();

            $x = $this->margeGauche;
            $this->pdf->SetFont('', 'B', 8);
            $this->pdf->SetTextColor(...$this->white);
            $this->pdf->SetFillColor(...$this->navy);
            $this->pdf->SetDrawColor(...$this->navy);
            foreach ($boxes as $box) {
                $this->mc($w, $hL, (string) $box[0], 'C', $x, $y);
                $x += $w;
            }

            $x = $this->margeGauche;
            $this->pdf->SetFont('', 'B', 14);
            $this->pdf->SetTextColor(...$this->navy);
            $this->pdf->SetFillColor(...$this->light);
            $this->pdf->SetDrawColor(...$this->border);
            foreach ($boxes as $box) {
                $value = (string) $box[1] !== '' ? (string) $box[1] : '—';
                $this->mc($w, $hV, $value, 'C', $x, $y + $hL);
                $x += $w;
            }
            $this->pdf->SetXY($this->margeGauche, $y + $hL + $hV);
        }

        /**
         * Draw the navy header row of a table.
         *
         * @param  array $cols List of ['w' => float, 'label' => string, 'align' => string]
         * @return void
         */
        private function tableHeader(array $cols): void
        {
            $h = 7;
            $this->checkPageBreak($h);
            $y = $this->pdf->GetY();
            $x = $this->margeGauche;
            $this->pdf->SetFont('', 'B', 8);
            $this->pdf->SetTextColor(...$this->white);
            $this->pdf->SetFillColor(...$this->navy);
            $this->pdf->SetDrawColor(...$this->navy);
            foreach ($cols as $col) {
                $this->mc($col['w'], $h, (string) $col['label'], $col['align'] ?? 'L', $x, $y);
                $x += $col['w'];
            }
            $this->pdf->SetXY($this->margeGauche, $y + $h);
        }

        /**
         * Draw a data row aligned on the table columns.
         *
         * @return void
         */
        private function tableRow(array $cols, array $cells, array $fill, string $fontStyle, float $fontSize, array $textColor): void
        {
            $this->pdf->SetFont('', $fontStyle, $fontSize);
            $measure   = [];
            $hasImages = false;
            foreach ($cols as $idx => $col) {
                if (($col['type'] ?? '') === 'image') {
                    if (!empty($cells[$idx]) && is_array($cells[$idx])) {
                        $hasImages = true;
                    }
                    continue; // images do not contribute to the text height
                }
                $measure[] = [$col['w'], (string) ($cells[$idx] ?? '')];
            }
            $rowH = $this->rowHeight($measure, 5);
            if ($hasImages) {
                $rowH = max($rowH, 16); // keep signature thumbnails legible
            }

            if ($this->checkPageBreak($rowH)) {
                $this->tableHeader($this->headerCols);
                $this->pdf->SetFont('', $fontStyle, $fontSize);
            }

            $y = $this->pdf->GetY();
            $x = $this->margeGauche;
            $this->pdf->SetTextColor(...$textColor);
            $this->pdf->SetDrawColor(...$this->border);
            foreach ($cols as $idx => $col) {
                $this->pdf->SetFillColor(...$fill);
                if (($col['type'] ?? '') === 'image') {
                    $this->mc($col['w'], $rowH, '', 'C', $x, $y); // bordered/filled cell, images drawn on top
                    $images = (!empty($cells[$idx]) && is_array($cells[$idx])) ? $cells[$idx] : [];
                    $this->drawCellImages($images, $x, $y, $col['w'], $rowH);
                } else {
                    $this->mc($col['w'], $rowH, (string) ($cells[$idx] ?? ''), $col['align'] ?? 'L', $x, $y);
                }
                $x += $col['w'];
            }
            $this->pdf->SetXY($this->margeGauche, $y + $rowH);
        }

        /**
         * Draw one or several signature images side by side, fitted (aspect ratio kept) inside a table cell.
         *
         * @param  array $images List of raw PNG binary strings
         * @param  float $x      Cell left
         * @param  float $y      Cell top
         * @param  float $w      Cell width
         * @param  float $h      Cell height
         * @return void
         */
        private function drawCellImages(array $images, float $x, float $y, float $w, float $h): void
        {
            $images = array_values(array_filter($images, static function ($img) {
                return !empty($img);
            }));
            $count = count($images);
            if ($count === 0) {
                return;
            }

            $pad   = 1;
            $slotW = ($w - $pad * 2) / $count;
            $imgH  = $h - $pad * 2;
            foreach ($images as $i => $img) {
                $ix = $x + $pad + $i * $slotW;
                $this->pdf->Image('@' . $img, $ix, $y + $pad, $slotW, $imgH, 'PNG', '', '', false, 300, '', false, false, 0, 'CM');
            }
        }

        /**
         * Draw a complete aligned table (header + rows), with optional total row and empty message.
         *
         * @param  array       $cols     List of ['w' => float, 'label' => string, 'align' => string]
         * @param  array       $rows     List of cell arrays (same order as $cols)
         * @param  string      $emptyTxt Message shown when $rows is empty
         * @param  array|null  $total    ['label' => string, 'value' => string] total row (spans all but last column)
         * @return void
         */
        public function table(array $cols, array $rows, string $emptyTxt = '', ?array $total = null): void
        {
            $this->headerCols = $cols;
            $this->tableHeader($cols);

            if (empty($rows)) {
                $this->checkPageBreak(7);
                $y = $this->pdf->GetY();
                $this->pdf->SetFont('', '', 8.5);
                $this->pdf->SetTextColor(...$this->gray);
                $this->pdf->SetFillColor(...$this->white);
                $this->pdf->SetDrawColor(...$this->border);
                $this->mc($this->usableW, 7, $emptyTxt, 'C', $this->margeGauche, $y);
                $this->pdf->SetXY($this->margeGauche, $y + 7);
                return;
            }

            $i = 0;
            foreach ($rows as $row) {
                $fill = ($i % 2 === 0) ? $this->white : $this->zebra;
                $this->tableRow($cols, $row, $fill, '', 8.5, $this->black);
                $i++;
            }

            if ($total !== null) {
                $lastW  = (float) end($cols)['w'];
                $firstW = $this->usableW - $lastW;
                $h      = 7;
                $this->checkPageBreak($h);
                $y = $this->pdf->GetY();
                $this->pdf->SetFont('', 'B', 8.5);
                $this->pdf->SetTextColor(...$this->navy);
                $this->pdf->SetFillColor(...$this->light);
                $this->pdf->SetDrawColor(...$this->border);
                $this->mc($firstW, $h, (string) $total['label'], 'R', $this->margeGauche, $y);
                $this->mc($lastW, $h, (string) $total['value'], 'R', $this->margeGauche + $firstW, $y);
                $this->pdf->SetXY($this->margeGauche, $y + $h);
            }
        }

        /**
         * Draw the validation block: an "edited by" line and two signature boxes.
         *
         * @param  string $editedLine Edited-by sentence
         * @param  string $box1Label  Left box label
         * @param  string $box2Label  Right box label
         * @return void
         */
        public function validation(string $editedLine, string $box1Label, string $box2Label): void
        {
            $this->pdf->SetFont('', '', 9);
            $this->pdf->SetTextColor(...$this->black);
            $this->pdf->SetX($this->margeGauche);
            $this->pdf->MultiCell($this->usableW, 5, $editedLine, 0, 'L', false, 1);
            $this->pdf->Ln(2);

            $h   = 22;
            $gap = 4;
            $w   = ($this->usableW - $gap) / 2;
            $this->checkPageBreak($h);
            $y = $this->pdf->GetY();
            $this->pdf->SetFont('', 'B', 9);
            $this->pdf->SetTextColor(...$this->navy);
            $this->pdf->SetFillColor(...$this->light);
            $this->pdf->SetDrawColor(...$this->border);
            $this->mc($w, $h, '  ' . $box1Label, 'L', $this->margeGauche, $y, 1, true, 'T');
            $this->mc($w, $h, '  ' . $box2Label, 'L', $this->margeGauche + $w + $gap, $y, 1, true, 'T');
            $this->pdf->SetXY($this->margeGauche, $y + $h);
        }

        /**
         * Draw a "before / after" comparison block: a header (field | left | right) then one row
         * per field. A row is either textual (['label', 'left', 'right']) or an image row
         * (['label', 'leftImg', 'rightImg', 'image' => true]) used for the départ/retour signatures.
         *
         * @param  string $leftHeader  Header of the left (departure) column
         * @param  string $rightHeader Header of the right (return) column
         * @param  array  $rows        List of rows (text or image)
         * @return void
         */
        public function beforeAfterGrid(string $leftHeader, string $rightHeader, array $rows): void
        {
            $labelW = 32;
            $colW   = ($this->usableW - $labelW) / 2;

            // Header band: empty corner + the two column titles
            $h = 7;
            $this->checkPageBreak($h + 8);
            $y = $this->pdf->GetY();
            $this->pdf->SetFont('', 'B', 8.5);
            $this->pdf->SetTextColor(...$this->white);
            $this->pdf->SetFillColor(...$this->navy);
            $this->pdf->SetDrawColor(...$this->navy);
            $this->mc($labelW, $h, '', 'L', $this->margeGauche, $y);
            $this->mc($colW, $h, $leftHeader, 'C', $this->margeGauche + $labelW, $y);
            $this->mc($colW, $h, $rightHeader, 'C', $this->margeGauche + $labelW + $colW, $y);
            $this->pdf->SetXY($this->margeGauche, $y + $h);

            foreach ($rows as $row) {
                $isImage = !empty($row['image']);
                if ($isImage) {
                    $rowH = 24;
                } else {
                    $this->pdf->SetFont('', '', 9);
                    $rowH = $this->rowHeight([
                        [$colW, (string) ($row['left'] ?? '')],
                        [$colW, (string) ($row['right'] ?? '')],
                    ], 6);
                }

                $this->checkPageBreak($rowH);
                $y = $this->pdf->GetY();

                // Label cell (navy text on light background)
                $this->pdf->SetFont('', 'B', 9);
                $this->pdf->SetTextColor(...$this->navy);
                $this->pdf->SetFillColor(...$this->light);
                $this->pdf->SetDrawColor(...$this->border);
                $this->mc($labelW, $rowH, (string) ($row['label'] ?? ''), 'L', $this->margeGauche, $y);

                if ($isImage) {
                    $this->pdf->SetFillColor(...$this->white);
                    $this->mc($colW, $rowH, '', 'C', $this->margeGauche + $labelW, $y);
                    $this->mc($colW, $rowH, '', 'C', $this->margeGauche + $labelW + $colW, $y);
                    if (!empty($row['leftImg'])) {
                        $this->drawCellImages([$row['leftImg']], $this->margeGauche + $labelW, $y, $colW, $rowH);
                    }
                    if (!empty($row['rightImg'])) {
                        $this->drawCellImages([$row['rightImg']], $this->margeGauche + $labelW + $colW, $y, $colW, $rowH);
                    }
                } else {
                    $this->pdf->SetFont('', '', 9);
                    $this->pdf->SetTextColor(...$this->black);
                    $this->pdf->SetFillColor(...$this->white);
                    $this->mc($colW, $rowH, (string) ($row['left'] ?? '') !== '' ? (string) $row['left'] : '—', 'L', $this->margeGauche + $labelW, $y);
                    $this->mc($colW, $rowH, (string) ($row['right'] ?? '') !== '' ? (string) $row['right'] : '—', 'L', $this->margeGauche + $labelW + $colW, $y);
                }
                $this->pdf->SetXY($this->margeGauche, $y + $rowH);
            }
        }

        /**
         * Draw a grid of photos read from disk, each fitted (aspect ratio kept) inside a fixed cell.
         *
         * @param  array  $paths    List of absolute image file paths
         * @param  string $emptyTxt Message shown when there is no photo
         * @param  int    $perRow   Number of photos per row
         * @param  float  $cellH    Height of each photo cell in mm
         * @return void
         */
        public function photoGrid(array $paths, string $emptyTxt = '', int $perRow = 3, float $cellH = 42): void
        {
            $paths = array_values(array_filter($paths, static function ($p) {
                return !empty($p) && is_file($p);
            }));

            if (empty($paths)) {
                $this->checkPageBreak(7);
                $y = $this->pdf->GetY();
                $this->pdf->SetFont('', 'I', 8.5);
                $this->pdf->SetTextColor(...$this->gray);
                $this->pdf->SetFillColor(...$this->white);
                $this->pdf->SetDrawColor(...$this->border);
                $this->mc($this->usableW, 7, $emptyTxt, 'C', $this->margeGauche, $y);
                $this->pdf->SetXY($this->margeGauche, $y + 7);
                return;
            }

            $gap   = 3;
            $cellW = ($this->usableW - $gap * ($perRow - 1)) / $perRow;
            $pad   = 1.5;

            for ($i = 0, $count = count($paths); $i < $count; $i += $perRow) {
                $this->checkPageBreak($cellH + $gap);
                $y = $this->pdf->GetY();
                for ($c = 0; $c < $perRow; $c++) {
                    if (!isset($paths[$i + $c])) {
                        break;
                    }
                    $x = $this->margeGauche + $c * ($cellW + $gap);
                    $this->pdf->SetFillColor(...$this->zebra);
                    $this->pdf->SetDrawColor(...$this->border);
                    $this->mc($cellW, $cellH, '', 'C', $x, $y);
                    $this->pdf->Image($paths[$i + $c], $x + $pad, $y + $pad, $cellW - $pad * 2, $cellH - $pad * 2, '', '', '', false, 300, '', false, false, 0, 'CM');
                }
                $this->pdf->SetXY($this->margeGauche, $y + $cellH + $gap);
            }
        }
    }
}

if (!class_exists('DolicarPdfData')) {
    /**
     * Shared data access for DoliCar vehicle logbook PDF models: fetch the trips of a vehicle
     * with their driver info, mileages, comments, départ/retour signatures and départ/retour photos.
     */
    class DolicarPdfData
    {
        /**
         * Fetch the public-logbook trips of a vehicle (RegistrationCertificateFr).
         *
         * Each returned trip is an associative array carrying everything the PDF models need:
         * driver, mileages, comments, fuel, départ/retour signatures (raw PNG binary) and
         * départ/retour photos (absolute file paths, with a legacy fallback for trips recorded
         * before the départ/retour split of issue #457).
         *
         * @param  DoliDB $db            Database handler
         * @param  object $object        RegistrationCertificateFr source object (needs fk_lot)
         * @param  bool   $onlyCompleted Keep only finished trips (datef not empty)
         * @param  int    $tripId        Restrict to a single trip (ActionComm id); 0 = all trips
         * @return array                 List of trips as associative arrays, newest first
         */
        public static function fetchTrips($db, $object, bool $onlyCompleted = true, int $tripId = 0): array
        {
            global $conf;

            require_once DOL_DOCUMENT_ROOT . '/comm/action/class/actioncomm.class.php';
            require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

            $trips = [];
            if (empty($object->fk_lot)) {
                return $trips;
            }

            $actionCommHelper = new ActionComm($db);
            $allTrips         = $actionCommHelper->getActions(
                0,
                (int) $object->fk_lot,
                'productlot',
                ' AND fk_element = ' . (int) $object->fk_lot . ' AND code = "AC_PRODUCTLOT_ADD_PUBLIC_VEHICLE_LOG_BOOK"',
                'datep',
                'DESC'
            );
            if (!is_array($allTrips)) {
                return $trips;
            }

            $dirOutput   = !empty($conf->dolicar->multidir_output[$conf->entity]) ? $conf->dolicar->multidir_output[$conf->entity] : $conf->dolicar->dir_output;
            $imageFilter = '(\.jpg|\.jpeg|\.png|\.gif|\.webp)';

            foreach ($allTrips as $trip) {
                if ($tripId > 0 && (int) $trip->id !== $tripId) {
                    continue;
                }
                if ($onlyCompleted && empty($trip->datef)) {
                    continue;
                }
                $trip->fetch_optionals();
                $json = json_decode($trip->array_options['options_json'] ?? '{}', true);
                if (!is_array($json)) {
                    $json = [];
                }

                $tripDir      = $dirOutput . '/vehicle_trip/' . (int) $trip->id;
                $departPhotos = self::listImages($tripDir . '/depart', $imageFilter);
                $returnPhotos = self::listImages($tripDir . '/retour', $imageFilter);
                // Trips recorded before issue #457 keep their photos loose in the trip dir
                $legacyPhotos = (empty($departPhotos) && empty($returnPhotos)) ? self::listImages($tripDir, $imageFilter) : [];

                $trips[] = [
                    'id'                => (int) $trip->id,
                    'datep'             => $trip->datep,
                    'datef'             => $trip->datef,
                    'driver'            => $json['driver'] ?? '',
                    'start_comment'     => $json['start_comment'] ?? '',
                    'end_comment'       => $json['end_comment'] ?? '',
                    'fuel_level'        => $json['fuel_level'] ?? '',
                    'return_fuel_level' => $json['return_fuel_level'] ?? '',
                    'starting_mileage'  => (int) ($trip->array_options['options_starting_mileage'] ?? 0),
                    'arrival_mileage'   => (int) ($trip->array_options['options_arrival_mileage'] ?? 0),
                    'depart_photos'     => $departPhotos,
                    'return_photos'     => $returnPhotos,
                    'legacy_photos'     => $legacyPhotos,
                    'start_signature'   => null,
                    'end_signature'     => null,
                ];
            }

            self::attachSignatures($db, $trips);

            return $trips;
        }

        /**
         * Fill start_signature / end_signature for each trip from the Saturne signature table.
         * Within a trip, signatures are ordered by rowid: the first is the départure signature,
         * the second the return one. Exact duplicates (multiple submissions) are skipped.
         *
         * @param  DoliDB $db    Database handler
         * @param  array  $trips Trips (passed by reference, mutated in place)
         * @return void
         */
        private static function attachSignatures($db, array &$trips): void
        {
            if (empty($trips)) {
                return;
            }
            $tripIds = array_map(static function ($t) {
                return $t['id'];
            }, $trips);

            $sql  = 'SELECT fk_object, signature';
            $sql .= ' FROM ' . MAIN_DB_PREFIX . 'saturne_object_signature';
            $sql .= " WHERE object_type = 'actiocomm'";
            $sql .= " AND module_name = 'dolicar'";
            $sql .= ' AND status > 0';
            $sql .= " AND signature LIKE 'data:image%'";
            $sql .= ' AND fk_object IN (' . implode(',', $tripIds) . ')';
            $sql .= ' ORDER BY fk_object ASC, rowid ASC';

            $resql = $db->query($sql);
            if (!$resql) {
                return;
            }

            $byTrip = [];
            while ($sig = $db->fetch_object($resql)) {
                $parts = explode(',', $sig->signature, 2);
                if (empty($parts[1])) {
                    continue;
                }
                $binary = base64_decode($parts[1]);
                if (empty($binary)) {
                    continue;
                }
                $hash = md5($binary);
                if (isset($byTrip[$sig->fk_object]['hashes'][$hash])) {
                    continue;
                }
                $byTrip[$sig->fk_object]['hashes'][$hash] = true;
                $byTrip[$sig->fk_object]['list'][]        = $binary;
            }

            foreach ($trips as &$trip) {
                $list                   = $byTrip[$trip['id']]['list'] ?? [];
                $trip['start_signature'] = $list[0] ?? null;
                $trip['end_signature']   = $list[1] ?? null;
            }
            unset($trip);
        }

        /**
         * List image files in a directory as absolute paths (sorted by name).
         *
         * @param  string $dir    Directory to scan
         * @param  string $filter dol_dir_list name filter (regex fragment)
         * @return array          List of absolute file paths
         */
        private static function listImages(string $dir, string $filter): array
        {
            if (!is_dir($dir)) {
                return [];
            }
            $files = dol_dir_list($dir, 'files', 0, $filter, '', 'name', SORT_ASC);
            $paths = [];
            foreach ($files as $file) {
                $paths[] = $file['fullname'];
            }
            return $paths;
        }
    }
}

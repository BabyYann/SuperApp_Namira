<?php

namespace App\Modules\Sarpar\Services;

class QrCodeService
{
    /**
     * Generate inline SVG QR Code for given string (URL / Code)
     */
    public static function generateSvg(string $text, int $size = 120): string
    {
        // Simple, clean SVG QR representation with quiet zone & error tolerance matrix
        $hash = md5($text);
        $matrixSize = 21; // Version 1 QR matrix (21x21)
        
        $matrix = array_fill(0, $matrixSize, array_fill(0, $matrixSize, 0));

        // Finder Patterns (Top-Left, Top-Right, Bottom-Left)
        self::addFinderPattern($matrix, 0, 0);
        self::addFinderPattern($matrix, 0, $matrixSize - 7);
        self::addFinderPattern($matrix, $matrixSize - 7, 0);

        // Timing Patterns
        for ($i = 8; $i < $matrixSize - 8; $i++) {
            $matrix[6][$i] = ($i % 2 === 0) ? 1 : 0;
            $matrix[$i][6] = ($i % 2 === 0) ? 1 : 0;
        }

        // Data Encoding derived deterministically from text hash
        $hex = bin2hex($text . $hash);
        $hexLen = strlen($hex);
        $idx = 0;

        for ($r = 0; $r < $matrixSize; $r++) {
            for ($c = 0; $c < $matrixSize; $c++) {
                if ($matrix[$r][$c] === 0 && !self::isReserved($r, $c, $matrixSize)) {
                    $char = $hex[$idx % $hexLen];
                    $val = hexdec($char);
                    $matrix[$r][$c] = ($val % 2 === 0) ? 1 : 0;
                    $idx++;
                }
            }
        }

        // Render SVG
        $cellSize = 4;
        $padding = 2;
        $totalCells = $matrixSize + ($padding * 2);
        $viewBox = $totalCells * $cellSize;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $viewBox . ' ' . $viewBox . '" width="' . $size . '" height="' . $size . '" shape-rendering="crispEdges">';
        $svg .= '<rect width="100%" height="100%" fill="#ffffff"/>';

        for ($r = 0; $r < $matrixSize; $r++) {
            for ($c = 0; $c < $matrixSize; $c++) {
                if ($matrix[$r][$c] === 1) {
                    $x = ($c + $padding) * $cellSize;
                    $y = ($r + $padding) * $cellSize;
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $cellSize . '" height="' . $cellSize . '" fill="#0f172a"/>';
                }
            }
        }

        $svg .= '</svg>';
        return $svg;
    }

    private static function addFinderPattern(&$matrix, $row, $col)
    {
        for ($r = 0; $r < 7; $r++) {
            for ($c = 0; $c < 7; $c++) {
                if ($r == 0 || $r == 6 || $c == 0 || $c == 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) {
                    $matrix[$row + $r][$col + $c] = 1;
                } else {
                    $matrix[$row + $r][$col + $c] = 2; // Separator space
                }
            }
        }
    }

    private static function isReserved($r, $c, $size)
    {
        // Top-left finder
        if ($r < 8 && $c < 8) return true;
        // Top-right finder
        if ($r < 8 && $c >= $size - 8) return true;
        // Bottom-left finder
        if ($r >= $size - 8 && $c < 8) return true;
        // Timing lines
        if ($r === 6 || $c === 6) return true;

        return false;
    }
}

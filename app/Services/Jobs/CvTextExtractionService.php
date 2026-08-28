<?php

namespace App\Services\Jobs;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

class CvTextExtractionService
{
    public function extract(string $path, string $mime): string
    {
        return match (true) {
            str_contains($mime, 'pdf') => $this->extractPdf($path),
            str_contains($mime, 'word') || str_ends_with(strtolower($path), '.docx') => $this->extractDocx($path),
            default => throw new \InvalidArgumentException('Unsupported CV file type: ' . $mime),
        };
    }

    protected function extractPdf(string $path): string
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile($path);
        return trim($pdf->getText());
    }

    protected function extractDocx(string $path): string
    {
        $phpWord = IOFactory::load($path);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $text .= $child->getText() . ' ';
                        }
                    }
                    $text .= "\n";
                }
            }
        }

        return trim($text);
    }
}
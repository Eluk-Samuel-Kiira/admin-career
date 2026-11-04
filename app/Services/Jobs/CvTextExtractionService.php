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

    protected function extractPdf(string $path): string
    {
        // Fail fast on obviously-too-large files
        if (filesize($path) > 8 * 1024 * 1024) {
            throw new \RuntimeException('CV is too large to process (over 8MB).');
        }

        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($path);
            $pages = $pdf->getPages();

            // Scanned PDFs come through as image-only; skip if no text
            if (count($pages) > 30) {
                throw new \RuntimeException('CV has too many pages (over 30). Please upload a shorter version.');
            }

            $text = '';
            foreach ($pages as $i => $page) {
                $text .= $page->getText();
                if (strlen($text) > 100_000) break;   // bail on runaway extraction
            }

            return trim($text);
        } catch (\Throwable $e) {
            Log::warning('PDF extraction failed', [
                'path'  => $path,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Could not read the PDF. It may be scanned or password-protected.');
        }
    }

}
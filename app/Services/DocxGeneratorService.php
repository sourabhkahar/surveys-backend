<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

class DocxGeneratorService
{
    public function generate(array $data): string
    {
        $phpWord = new PhpWord();

        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);

        $section = $phpWord->addSection([
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 700,
            'marginRight' => 700,
        ]);

        /** HEADER */
        $header = $section->addHeader();
        $header->addText(
            $data['header'] ?? 'Company Name',
            ['bold' => true],
            ['alignment' => 'center']
        );

        /** FOOTER */
        $footer = $section->addFooter();
        $footer->addPreserveText(
            'Page {PAGE} of {NUMPAGES}',
            ['size' => 9],
            ['alignment' => 'right']
        );

        /** BODY */
        $section->addText(
            $data['title'] ?? 'Document Title',
            ['bold' => true, 'size' => 16],
            ['spaceAfter' => 400]
        );

        $section->addText($data['content'] ?? '');

        $fileName = 'document_' . time() . '.docx';
        $path = storage_path("app/temp/$fileName");

        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($path);

        return $path;
    }
}

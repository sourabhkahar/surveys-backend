<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSetPaper;
use App\Http\Requests\UpdatePaperRequest;
use App\Models\Paper;
use App\Models\section;
use App\Http\Resources\PaperResources;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Shared\Html;
use PhpOffice\PhpWord\TemplateProcessor;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PapersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // This method retrieves all papers and paginates them.
        // $user = $request->user();
        $response['data'] = [];
        $response['status'] = 'fail';
        $response['msg'] = 'Something went wrong!';
        try {
            $papers = Paper::orderBy('created_at', 'desc')->paginate(6);
            $result = PaperResources::collection($papers);
            if (count($result)) {
                $response['data'] = $result;
                $response['status'] = 'success';
                $response['msg'] = 'Surveys fetched successfully';
            }
            return $result;
        } catch (\Throwable $th) {
            $response['msg'] = $th->getMessage();
            return $response;
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(StoreSetPaper $request)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSetPaper $request)
    {
        try {
            \DB::beginTransaction();
            $data = $request->validated();
            $result = Paper::create(['title' => $data['title']]);
            foreach ($data['sections'] as $section) {
                $section['paper_id'] = $result->id;
                $section['section_name'] = $section['title'];
                $section['section_type'] = $section['section_type'];
                $section['total_marks'] = $section['total_marks'] ?? 0;
                $section['caption'] = $section['caption'] ?? '';
                $resSection = section::create($section);
                foreach ($section['questions'] as $question) {
                    $question['section_id'] = $resSection->id;
                    $question['question'] = $question['question'];
                    $options = $question['options'];
                    if ($section['section_type'] != 'matching') {
                        if (is_array($options) && sizeof($options) > 0) {
                            $options = array_filter($options, function ($item) {
                                return isset($item['title']) && trim($item['title']) !== '';
                            });
                        }
                    }
                    $question['options'] = isset($question['options']) ? json_encode($question['options']) : null;
                    $question['survey_id'] = 0; // Assuming you want to link
                    SurveyQuestion::create($question);
                }
            }
            \DB::commit();
            return response([
                'msg' => 'Paper created successfully',
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Exception $th) {
            \DB::rollBack();
            return response([
                'msg' => 'Error creating paper: ' . $th->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Paper $paper, Request $request)
    {
        $response['data'] = [];
        $response['status'] = 'fail';
        $response['msg'] = 'Something went wrong!';

        try {
            if (!$paper) {
                $response['msg'] = 'Paper not found';
                return $response;
            }
            return new PaperResources($paper);
        } catch (\Throwable $e) {
            return $response;
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Paper $papers)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaperRequest $request, Paper $paper)
    {
        try {
            \DB::beginTransaction();
            $data = $request->validated();
            $result = Paper::where('id', $paper->id)->update(['title' => $data['title']]);

            //Remove section
            $getSectionIds = array_map(function ($item) {
                return $item['id'] ?? '';
            }, $data['sections']);

            $idsToRemove = $paper->sections->filter(function ($item) use ($getSectionIds) {
                return !in_array($item->id, $getSectionIds);
            })->map(function ($item) {
                return $item->id;
            });

            if (count($idsToRemove) > 0) {
                Section::WhereIn('id', $idsToRemove)->delete();
            }
            foreach ($data['sections'] as $keySec => $section) {

                //Update section
                $updateSection['paper_id'] = $paper->id;
                $updateSection['section_name'] = $section['title'];
                $updateSection['section_type'] = $section['section_type'];
                $updateSection['total_marks'] = $section['total_marks'] ?? 0;
                $updateSection['caption'] = $section['caption'] ?? '';

                if (isset($section['id'])) {
                    Section::where('id', $section['id'])->update($updateSection);
                } else {
                    $section['id'] = Section::create($updateSection)->id;
                }
                //Remove questions
                $getQuestionIds = array_map(function ($item) {
                    return $item['id'] ?? '';
                }, $section['questions']);

                if (isset($section['id'])) {
                    $getSection = Section::find($section['id']);
                    if ($getSection) {
                        $questionsIdsToremove = $getSection->questions->filter(function ($item) use ($getQuestionIds) {
                            return !in_array($item->id, $getQuestionIds);
                        })->map(function ($item) {
                            return $item->id;
                        });
                        if (count($questionsIdsToremove) > 0) {
                            SurveyQuestion::WhereIn('id', $questionsIdsToremove)->delete();
                        }
                    }
                }
                foreach ($section['questions'] as $question) {
                    //Update questions
                    $updateQuestion['section_id'] = $section['id'];
                    $updateQuestion['question'] = $question['question'];
                    $options = $question['options'];
                    if ($section['section_type'] != 'matching') {
                        if (is_array($options) && sizeof($options) > 0) {
                            $options = array_filter($options, function ($item) {
                                return isset($item['title']) && trim($item['title']) !== '';
                            });
                        }
                    }
                    $updateQuestion['options'] = isset($options) ? json_encode($options) : null;
                    $updateQuestion['survey_id'] = 0;

                    if (isset($question['id'])) {
                        SurveyQuestion::where('id', $question['id'])->update($updateQuestion);
                    } else {
                        SurveyQuestion::create($updateQuestion);
                    }
                }
            }
            \DB::commit();
            return response([
                'msg' => 'Paper created successfully',
                'status' => 'success',
                'data' => $result
            ], 200);
        } catch (\Exception $th) {
            \DB::rollBack();
            return $th;
            return response([
                'msg' => $th,
                'status' => 'error'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Paper $papers)
    {
        //
    }

    public function getTemplateList(Request $request)
    {
        //get template File url from public folder
        $response['data'] = [];
        $response['status'] = 'fail';
        $response['msg'] = 'Something went wrong!';

        //get file names from public folder
        $files = \File::files(public_path('template_docs'));
        foreach ($files as $file) {
            $response['data'][] = [
                'name' => $file->getFilename(),
                'url' =>  asset('template_docs/' . $file->getFilename())
            ];
        }

        if (empty($response['data'])) {
            $response['msg'] = 'No templates found';
        } else {
            $response['status'] = 'success';
            $response['msg'] = 'Templates fetched successfully';
        }
        return response()->json($response);
    }

    function createPaperFromTemplate($id)
    {
        $response['data'] = [];
        $response['status'] = 'fail';
        $response['msg'] = 'Something went wrong!';
        try {
            $paper = Paper::with('sections.questions')->where('id', $id)->first();
            if (!$paper) {
                return response()->json(['msg' => 'Paper not found', 'status' => 'error'], 404);
            }

            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $phpWord->setDefaultFontName('Times New Roman');
            $pageWidth = 12240;
            $leftMargin = 300;
            $rightMargin = 300;
            $usableWidth = $pageWidth - $leftMargin - $rightMargin;

            $section = $phpWord->addSection([
                'marginTop' => 300,
                'marginBottom' => 300,
                'marginLeft' => $leftMargin,
                'marginRight' => $rightMargin,
                'headerHeight' => 150,
                'differentFirstPageHeader' => true,
            ]);

            $firstHeader = $section->addHeader();
            $firstHeader->firstPage(); 

            $col1 = 3440; // Logo
            $col2 = 5360; // Center
            $col3 = 3440; // Right

            $table = $firstHeader->addTable([
                'width' => $usableWidth,
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
            ]);

            $table->addRow();
            $table->addCell($usableWidth, ['gridSpan' => 3])->addText(
                'JOYOUS PRIMARY ENGLISH SCHOOL',
                ['bold' => true, 'size' => 24],
                ['alignment' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]
            );

            $table->addRow();
            $table->addCell($col1)->addImage(
                public_path('images/school-logo.png'),
                ['width' => 70, 'height' => 70]
            );

            $centerCell = $table->addCell($col2);
            $centerCell->addText(
                'Unit Test : 2026',
                ['bold' => true, 'size' => 14],
                ['alignment' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]
            );
            $centerCell->addText(
                'SUB: Environment',
                ['bold' => true, 'size' => 14],
                ['alignment' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]
            );
            $centerCell->addText(
                'STD: 3rd',
                ['bold' => true, 'size' => 14],
                ['alignment' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 0]
            );

            $rightCell = $table->addCell($col3);

            $textRun = $rightCell->addTextRun(['alignment' => 'right']);

            foreach (
                [
                    '(Run by EBAM Trust)',
                    'Fully Ac School',
                    'Varachha Road',
                    'Surat - 395006'
                ] as $line
            ) {
                $textRun->addText($line, ['size' => 8]);
                $textRun->addTextBreak();
            }

            $dateMarksTable = $section->addTable([
                'width' => $usableWidth,
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
                'cellMarginLeft' => 0,
                'cellMarginRight' => 0,
            ]);

            $dateMarksTable->addRow();
            $dateCell = $dateMarksTable->addCell($col1);
            $dateCell->addText(
                'Date : 27/03/2024',
                ['bold' => true, 'size' => 14],
                ['alignment' => 'left', 'spaceBefore' => 0, 'spaceAfter' => 0]
            );

            $centerCell = $dateMarksTable->addCell($col2);

            $totalMarkCell = $dateMarksTable->addCell($col3);
            $totalMarkCell->addText(
                'Total marks: - [80]',
                ['bold' => true, 'size' => 14],
                ['alignment' => 'right', 'spaceBefore' => 0, 'spaceAfter' => 0]
            );

            $nameAndRoleNo = $section->addTable([
                // 'borderSize' => 0,
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
            ]);

            $nameAndRoleNo->addRow();
            $nameAndRoleNo->addCell(10000)->addText(
                'Name: _____________________________________________________',
                ['bold' => true, 'size' => 14],
                ['spaceBefore' => 0, 'spaceAfter' => 0]
            );
            $nameAndRoleNo->addCell(2000)->addText(
                'Roll No. ______',
                ['bold' => true, 'size' => 14],
                ['alignment' => 'right', 'spaceBefore' => 0, 'spaceAfter' => 0]
            );


            $sections = $paper['sections'];
            $sectionChar = 'Q';
            $sectionCount = 1;

            $subsequentHeader = $section->addHeader();

            // Use a table with 100% width (5000 twips is roughly full page, but using % is safer)
            $tableStyle = [
                'width' => 100 * 50, // This represents 100% width
                'unit'  => \PhpOffice\PhpWord\SimpleType\TblWidth::PERCENT,
                'cellMarginTop' => 0,
                'cellMarginBottom' => 0,
            ];
            $restNameAndRoleNo = $subsequentHeader->addTable($tableStyle);

            $restNameAndRoleNo->addRow();

            // Cell 1: Name (Give it the most space)
            $restNameAndRoleNo->addCell(null, ['gridSpan' => 1, 'valign' => 'center', 'width' => 65 * 50, 'unit' => 'pct'])
                ->addText('Name: _________________________________________________', ['bold' => true, 'size' => 12], ['spaceAfter' => 0]);

            // Cell 2: Subject (Flexible width)
            $restNameAndRoleNo->addCell(null, ['width' => 15 * 50, 'unit' => 'pct'])
                ->addText('Sub: ENV.', ['bold' => true, 'size' => 12], ['spaceAfter' => 0]);

            // Cell 3: Standard (Smaller width)
            $restNameAndRoleNo->addCell(null, ['width' => 15 * 50, 'unit' => 'pct'])
                ->addText('Std: 1ST', ['bold' => true, 'size' => 12], ['spaceAfter' => 0]);

            // Cell 4: Roll No (Right Aligned)
            $restNameAndRoleNo->addCell(null, ['width' => 15 * 50, 'unit' => 'pct'])
                ->addText('Roll No. ______', ['bold' => true, 'size' => 12], ['alignment' => 'right', 'spaceAfter' => 0]);


            foreach ($sections as  $secVal) {

                $sectionName = $secVal['section_name'];
                $sectionCaption = $secVal['caption'];
                $sectionTable = $section->addTable();
                $sectionTable->addRow();
                $sectionTable->addCell(10000)->addText(
                    "$sectionChar.$sectionCount. $sectionName :- $sectionCaption",
                    ['bold' => true, 'size' => 12]
                );
                $sectionTable->addCell(2000)->addText(
                    '(20)',
                    ['bold' => true, 'size' => 12],
                    ['alignment' => 'right']
                );

                $questionCount = 1;
                $section->addTextBreak(1, ['size' => 4]);
                foreach ($secVal['questions'] as  $key => $question) {
                    if ($secVal['section_type'] == 'mcqs') {
                        $section->addText(
                            "$questionCount) $question->question",
                            ['size' => 12],
                            ['spaceBefore' => 50, 'spaceAfter' => 50, 'indentation' => [
                                'left' => 360
                            ],]
                        );

                        $optionBullet = 'a';
                        $mcqsOptions = [];
                        $tabStops = [
                            ['position' => 0],
                            ['position' => (int) ($usableWidth * 0.25)],
                            ['position' => (int) ($usableWidth * 0.50)],
                            ['position' => (int) ($usableWidth * 0.75)],
                        ];
                        $optionsArr = json_decode($question->options);
                        if (is_array($optionsArr) && sizeof($optionsArr) > 0) {
                            foreach ($optionsArr as $key => $value) {
                                $optionVal = $value->title;
                                array_push($mcqsOptions, "($optionBullet) $optionVal");
                            }
                            $section->addText(
                                implode('               ', $mcqsOptions),
                                ['size' => 12],
                                ['indentation' => ['left' => 720], 'tabs' => $tabStops],
                            );
                        }
                    } else if ($secVal['section_type'] == 'question_answer') {

                        $section->addText(
                            'Q.' . ($key + 1) . '. ' . $question->question,
                            ['size' => 12],
                            [
                                'spaceBefore' => 50,
                                'spaceAfter' => 50,
                            ]
                        );

                        $phpWord->addParagraphStyle('ansStyle', [
                            'tabs' => [
                                new \PhpOffice\PhpWord\Style\Tab('right', $usableWidth-1000)
                            ]
                        ]);

                        $textRun = $section->addTextRun('ansStyle');

                        $textRun->addText('Ans.', ['bold' => true, 'size' => 12], [
                            'spaceBefore' => 50,
                            'spaceAfter' => 50,
                        ]);

                      
                        $textRun->addText("\t", ['underline' => 'single']);
                        // $section->addTextBreak(1, ['size' => 4]);

                        for ($i = 0; $i < $question->options - 1 ; $i++) {
                            $section->addText("", [], [
                                'borderBottomSize'  => 6,
                                'borderBottomColor' => '000000',
                                'spaceBefore'       => 0,
                                'spaceAfter'        => 0,
                                'indentRight'       => 720, 
                            ]);
                            $section->addTextBreak(1, ['size' => 5]);
                        }
                    } else {
                        $section->addText(
                            ($key + 1) . '.' . $question->question,
                            ['size' => 12],
                            [
                                'spaceBefore' => 50,
                                'spaceAfter' => 50,
                                'indentation' => [
                                    'left' => 360
                                ]
                            ]
                        );
                        $section->addTextBreak(1, ['size' => 1]);

                    }
                    $questionCount++;
                }

                $sectionCount++;
            }


            return new StreamedResponse(function () use ($phpWord) {
                $writer = IOFactory::createWriter($phpWord, 'Word2007');
                $writer->save('php://output');
            }, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Content-Disposition' => 'attachment; filename="updated.docx"',
            ]);
        } catch (\Exception $e) {
            return response()->json(['msg' => 'Error: ' . $e->getMessage(), 'status' => 'error'], 500);
        }
    }

    public function cmToTwip($cm)
    {
        return (int) round($cm * 567);
    }
}

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
            $papers = Paper::paginate(6);
            $result = PaperResources::collection($papers);
            if(count($result)){
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
            $result = Paper::create(['title'=>$data['title']]);
            foreach ($data['sections'] as $section) {
                $section['paper_id'] = $result->id;
                $section['section_name'] = $section['title'];
                $section['section_type'] = $section['section_type'];
                $section['total_marks'] = $section['total_marks'] ?? 0;
                $section['caption'] = $section['caption']??'';
                $resSection = section::create($section);
                foreach ($section['questions'] as $question) {
                    $question['section_id'] = $resSection->id;
                    $question['question'] = $question['question'];
                    $question['type'] = $question['type'];
                    $question['meta'] = $question['meta'];
                    $question['data'] = isset($question['options']) ? json_encode($question['options']) : null;
                    unset($question['options']); // Remove options if not needed in the model
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
    public function show(Paper $paper,Request $request)
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
            $result = Paper::where('id',$paper->id)->update(['title'=>$data['title']]);

            //Remove section
            $getSectionIds = array_map(function($item){
                return $item['id'];
            },$data['sections']); 
            
            $idsToRemove = $paper->sections->filter(function($item) use($getSectionIds) {
                return !in_array($item->id,$getSectionIds);
            })->map(function ($item) {
                return $item->id;
            });

            if(count($idsToRemove) > 0){
                Section::WhereIn('id',$idsToRemove)->delete();
            }

            foreach ($data['sections'] as $section) {

                //Update section
                $updateSection['paper_id'] = $paper->id;
                $updateSection['section_name'] = $section['title'];
                $updateSection['section_type'] = $section['section_type'];
                $updateSection['total_marks'] = $section['total_marks'] ?? 0;
                $updateSection['caption'] = $section['caption']??'';
                Section::where('id',$section['id'])->update($updateSection);

                //Remove questions
                $getQuestionIds = array_map(function($item){
                    return $item['id']??'';
                },$section['questions']); 

                $getSection = Section::find($section['id']);
                if($getSection){
                    $questionsIdsToremove = $getSection->questions->filter(function($item) use($getQuestionIds) {
                        return !in_array($item->id,$getQuestionIds);
                    })->map(function ($item) {
                        return $item->id;
                    });
    
                    if(count($questionsIdsToremove) > 0){
                        SurveyQuestion::WhereIn('id',$questionsIdsToremove)->delete();
                    }
                }

                foreach ($section['questions'] as $question) {
                    //Update questions
                    $updateQuestion['section_id'] = $section['id'];
                    $updateQuestion['question'] = $question['question'];
                    $updateQuestion['type'] = $question['type'];
                    $updateQuestion['meta'] = $question['meta'];
                    $updateQuestion['description'] = $question['description'];
                    $updateQuestion['options'] = isset($question['options']) ? json_encode($question['options']) : null;
                    $updateQuestion['survey_id'] = 0; 
                    if(isset($question['id'])){
                        SurveyQuestion::where('id',$question['id'])->update($updateQuestion);
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
            \PhpOffice\PhpWord\Settings::setDefaultPaper('Letter');
            $paper = Paper::with('sections.questions')->where('id', $id)->first();
            if (!$paper) {
                return response()->json(['msg' => 'Paper not found', 'status' => 'error'], 404);
            }

            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $phpWord->setDefaultFontName('Times New Roman');
            $phpWord->setDefaultFontSize(12);
            $phpWord->getSettings()->setMirrorMargins(true);
            $sectionStyle = [
                'marginLeft'   => $this->cmToTwip(1), // 2 cm
                'marginRight'  => $this->cmToTwip(1),
            ];
         
            $sections = $paper['sections'];
            $sectionChar = 'Q';
            $sectionCount = 1;
            $section = $phpWord->addSection($sectionStyle);
            foreach ($sections as  $value) {
                $sectionTable = $section->addTable([
                            'borderSize' => 0,
                            'cellMargin' => 0,
                            'width' => 100 * 50,
                            'borderColor' => 'FFFFFF',
                        ]);
                $sectionTable->addRow();
                
                $sectionTable->addCell(500, ['align' => 'right'])->addText($sectionChar.'.'.$sectionCount, ['alignment' => 'right']);
                $sectionTable->addCell(10500, ['align' => 'left'])->addText($value['section_name'],['alignment' => 'left']);
                $sectionTable->addCell(500, ['align' => 'left'])->addText('(10)', ['alignment' => 'right']);
                $questionCount = 1;

                $lineText1 = str_repeat('_', 87);
                $lineText2 = str_repeat('_', 90);

                $textStyle = [
                    'bold' => false,
                    'color' => '000000',
                ];

                $paragraphStyle = [
                    'alignment' => 'left',
                    'spaceBefore' => 0,
                    'spaceAfter' => 0,        // Remove extra space after each line
                    'lineHeight' => 2       // Tighter vertical spacing
                ];
                
                $textStyleBold = ['bold' => true, 'color' => '000000'];

                foreach ($value['questions'] as  $value) {
                    if($value['type'] == 'text'){

                         //Line Style
                        $questionTable = $section->addTable([
                            'borderSize' => 0,
                            'cellMargin' => 0,
                            'width' => 100 * 50,
                            'borderColor' => 'FFFFFF',
                        ]);
                        
                        $questionTable->addRow();
                        $questionTable->addCell(500, ['align' => 'right'])->addText('Q.'.$questionCount.'.', ['alignment' => 'right']);
                        $questionTable->addCell(10500, ['align' => 'left'])->addText($value['question'],['alignment' => 'left']);
                        if($value['meta']){
                            for ($i = 0; $i < $value['meta']; $i++) {
                                //Add Ans. on start Of line
                                if($i == 0){
                                    $textrun = $section->addTextRun($paragraphStyle);
                                    $textrun->addText('Ans', $textStyleBold);
                                    $textrun->addText($lineText1, $textStyle, $paragraphStyle);
                                } else {
                                    $section->addText($lineText2, $textStyle, $paragraphStyle);
                                }
                            }
                        }
                    } 
                }
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

    public function cmToTwip($cm) {
        return (int) round($cm * 567);
    }

}

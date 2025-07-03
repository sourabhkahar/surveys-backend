<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSetPaper;
use App\Models\Paper;
use App\Models\section;
use App\Http\Resources\PaperResources;
use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
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
    public function show(Paper $paper)
    {   
        $response['data'] = [];
        $response['status'] = 'fail';
        $response['msg'] = 'Something went wrong!';

        try {
            //code...
            // $paper = $paper->with('sections.questions')->first();
             
            // // Here you can add logic to process the template file and create sections/questions as needed
            // if (!$paper) {
            //     return response()->json(['msg' => 'Paper not found', 'status' => 'error'], 404);
            // }

            // $response['data'] = $paper;
            // return $response;

             return $paper;
            return new PaperResources($paper);
        } catch (\Exception $e) {
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
    public function update(Request $request, Paper $papers)
    {
        //
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
            // Create a new paper from the template
            $paper = Paper::with('sections.questions')->where('id', $id)->first();
            // Here you can add logic to process the template file and create sections/questions as needed
            if (!$paper) {
                return response()->json(['msg' => 'Paper not found', 'status' => 'error'], 404);
            }

            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $sections = $paper['sections'];
            $sectionChar = 'Q';
            $sectionCount = 1;
            foreach ($sections as  $value) {
                $section = $phpWord->addSection([ 
                    'marginLeft'   => $this->cmToTwip(2),
                    'marginRight'  => $this->cmToTwip(2),
                ]);
                $section->addText($sectionChar.'.'.$sectionCount.' '.$value['section_name']);
                $questionCount = 1;
                foreach ($value['questions'] as  $value) {
                    $section->addText($questionCount.' '.$value['question']);
                }
            }
          
           
            return new StreamedResponse(function () use ($phpWord) {
                            $writer = IOFactory::createWriter($phpWord, 'Word2007');
                            $writer->save('php://output');
                        }, 200, [
                            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'Content-Disposition' => 'attachment; filename="updated.docx"',
                        ]);
            // return response()->json($response, 200);
        } catch (\Exception $e) {
            return response()->json(['msg' => 'Error: ' . $e->getMessage(), 'status' => 'error'], 500);
        }
    }

    public function cmToTwip($cm) {
        return (int) round($cm * 567);
    }

}

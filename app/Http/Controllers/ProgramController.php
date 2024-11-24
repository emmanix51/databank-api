<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProgramController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        $validator = Validator::make($request->all(), [
            'program_name' => 'required|string',  
            'college_id' => 'required|exists:colleges,id',  
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>'error',
                                    'message' => $validator->errors(),], 400);
        }

        $program = new Program();
      
        $program->program_name = $request->program_name;  
        $program->college_id = $request->college_id;  
        $program->save();

        return response()->json(['status'=>'success',
                                'message'=>'program created successfully!',
                                'data' => $program], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Program $program)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Program $program)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Program $program)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Program $program)
    {
        //
    }
}

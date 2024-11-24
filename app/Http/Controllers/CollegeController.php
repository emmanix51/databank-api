<?php

namespace App\Http\Controllers;

use App\Models\College;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class CollegeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
        return response()->json(['test custom api'=>'works my nig word']);

    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    public function test()
    {
        //
        return response()->json(['test custom api'=>'works my n word']);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
        //
        $validator = Validator::make($request->all(), [
            'college_name' => 'required|string',  
        ]);

        if ($validator->fails()) {
            return response()->json(['status'=>'error',
                                    'message' => $validator->errors(),], 400);
        }

        $college = new College();
      
        $college->college_name = $request->college_name;  
        $college->save();

        return response()->json(['status'=>'success',
                                'message'=>'college created successfully!',
                                'data' => $college], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(College $college)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(College $college)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, College $college)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(College $college)
    {
        //
    }
}

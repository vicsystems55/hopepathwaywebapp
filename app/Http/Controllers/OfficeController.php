<?php

namespace App\Http\Controllers;

use App\Models\Office;
use Illuminate\Http\Request;

class OfficeController extends Controller
{
    //

    public function index(){
        $offices = Office::with(['officer', 'parent'])->latest()->get();
        return $offices;
    }

    public function store(Request $request){

        $office = Office::create([
            'name' => $request->name,
            'desc' => $request->desc,
            'abbrev' => $request->abbrev,
            'parent_id' => $request->parent_id,
            'user_id' => $request->user_id
        ]);

        return $office;

    }

    public function update(Request $request, Office $office)
    {
        //

        $office->update($request->all());

        return $office;
    }
}

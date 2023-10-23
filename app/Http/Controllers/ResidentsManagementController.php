<?php

namespace App\Http\Controllers;

use App\Models\ResidentsManagement;
use Illuminate\Http\Request;

class ResidentsManagementController extends Controller
{
    //

    public function index()
    {
        $residentsRecords = ResidentsManagement::get();

        return $residentsRecords;
    }
}

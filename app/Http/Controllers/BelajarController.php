<?php

namespace App\Http\Controllers;

use App\Belajar;
use Illuminate\Http\Request;

use App\Http\Requests;

class BelajarController extends Controller
{
    public function index()
    {
        $belajar = Belajar::create([
            'name' => 'jono',
            'description' => 'deskripsi',
            'nomor' => 123
        ]);
    }

    public function show($id)
    {


        return \Response::json([
            'data' => Belajar::find($id),
        ], 200);
    }

    public function dependency(Request $request)
    {
        $var = Belajar::create($request->all());
$var->get()->asd()-
        dd($var);
    }
}

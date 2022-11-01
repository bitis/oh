<?php

namespace App\Http\Controllers;

use App\Models\T66yUrl;

class T66yController extends Controller
{
    public function index()
    {
        $result = T66yUrl::first();

        return view('other.t66y')->with([
            'title' => '1024 最新地址',
            't66y' => json_decode($result->result),
            'updated_at' =>$result->updated_at,
        ]);
    }
}

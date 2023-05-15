<?php

namespace App\Http\Controllers;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\Request;

class QrCodeController extends Controller
{
    public function gen(Request $request)
    {
        $opt = new QROptions([
            'version' => 2,
            'outputType' => QROutputInterface::GDIMAGE_PNG,
            'eccLevel' => EccLevel::L,
            'imageBase64' => false,
            'bgColor' => [200, 150, 200],
            "scale" => 5,
        ]);
        $file = (new QRCode($opt))->render($request->input('code'));

        header('Content-Type: image/png');
        echo $file;
    }

}

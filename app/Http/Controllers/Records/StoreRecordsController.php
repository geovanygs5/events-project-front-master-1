<?php

namespace App\Http\Controllers\Records;

use App\Http\Controllers\Controller;
use App\Mail\QRMail;
use Barryvdh\DomPDF\Facade\Pdf;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Webpatser\Uuid\Uuid;


class StoreRecordsController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = [
            "pk" => "User#".Uuid::generate(),
            "sk" => "METADATA#USER",
            "eventID" => 'Event#'.strval($request->input('event_pk')),
            "name" => strval($request->input('first_name')),
            "lastname" => strval($request->input('last_name')),
            "gender" => strval($request->input('gender')),
            "email" => strval($request->input('email')),
            "phone" => strval($request->input('phone')),
            "date" => strval($request->input('date')),
            "status" => 'not-attend'
        ];
        json_encode($data);

        $friend_email = $request->input('friend_email');

        $client = new Client();
        $response = $client->post(strval(getenv('URL_CREATE_EVENTS')), [
            'json' => $data,
        ]);

        $subject = 'Código de acceso para evento';
        $text = 'Buenos días.<br>Con este correo se ha adjuntado un código QR que le permitirá acceder al evento seleccionado.<br>Gracias por usar nuestros servicios.';

        $pk = $data['pk'];
        $path = 'PDF/QR/'.$pk.'.pdf';
        $qrCode = QrCode::format('png')->size(300)->generate($pk);
        $qrCode = base64_encode($qrCode);
        $pdf = Pdf::loadview('users.documentQR', compact('qrCode'));

        Mail::send('users.mailQR', compact('text'), function($mail) use ($pdf, $data, $subject) {
            $mail->to($data['email']);
            $mail->subject($subject);
            $mail->attachData($pdf->output(), 'QR.pdf');
        });

        if (isset($request->friend_email)) {
            $text = 'Un amigo te ha referenciado para que puedas ir a un evento,
            puedes registrarte dando click en el siguiente link: https://events.instanceshape.com/records-create/'.strval($request->input('event_pk'));
            $subject = 'Invitación a evento';

            Mail::send('users.mailQR', compact('text'), function($mail) use ($subject, $friend_email) {
                $mail->to($friend_email);
                $mail->subject($subject);
            });
        }
        //Storage::disk('public')->put($path, $pdf->output());
        //Mail::to($data['email'])->send(new QRMail($subject, $text, $pk));
        //$qrCodePath = 'qrcodes/qrcode.png';

        return redirect()->route('records.events')->with('recordRegistered', 'Ha sido registrado al evento');


    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\MailtrapService;

class EmailController extends Controller
{
    protected $mailtrap;

    public function __construct(MailtrapService $mailtrap)
    {
        $this->mailtrap = $mailtrap;
    }

    public function sendTestEmail()
    {
        $data = [
            "You have registered successfully."
        ];

        $result = $this->mailtrap->sendEmail($data);
        return response()->json(['message' => $result]);
    }
}

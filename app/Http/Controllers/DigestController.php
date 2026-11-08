<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\View\View;

/** Link de "não quero mais receber" do e-mail diário (assinado, funciona sem login). */
class DigestController extends Controller
{
    public function unsubscribe(User $user): View
    {
        $user->profile?->update(['digest_enabled' => false]);

        return view('digest.unsubscribed');
    }
}

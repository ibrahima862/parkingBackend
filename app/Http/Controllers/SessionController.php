<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SessionController extends Controller
{
    public function checkSession(Request $request) {
    return response()->json([
        'session_data' => $request->session()->all(),
        'is_logged_in' => auth()->check(),
        'user_id'      => auth()->id(),
        'user_details' => auth()->user(), // Attention: ne pas laisser ça en prod !
    ]);
}
}

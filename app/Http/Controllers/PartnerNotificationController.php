<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PartnerNotificationController extends Controller
{
    

public function index(Request $request) {
    return $request->user()->unreadNotifications;
}

public function markAsRead(Request $request, $id) {
    $request->user()->notifications()->findOrFail($id)->markAsRead();
    return response()->json(['status' => 'success']);
}

}

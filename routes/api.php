<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Http\Request;

// Auth & Profil
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordContoller;
use App\Http\Controllers\UserProfileController;
use App\Http\Controllers\SessionController;

// Métier (Parkings, Réservations, etc.)
use App\Http\Controllers\ParkingController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\VehiculeController;
use App\Http\Controllers\AvisController;
use App\Http\Controllers\ReportController;

// Paiements & Abonnements
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AbonnementController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\BillingController;

// Espace Partenaire
use App\Http\Controllers\PartnerParkingController;
use App\Http\Controllers\PartnerReservationController;
use App\Http\Controllers\Partenaire\PartnerSubscriptionsController;
use App\Http\Controllers\Proprietaire\RetraitController;

// Espace Admin
use App\Http\Controllers\Admin\AdminParkingController;
use App\Http\Controllers\Admin\AdminBookingController;
use App\Http\Controllers\Admin\AdminRetraitController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\AdminPartenaireController;
use App\Http\Controllers\Admin\AdminTransactionController;

// Middlewares
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsPartner;

/*
|--------------------------------------------------------------------------
| 1. ROUTES PUBLIQUES
|--------------------------------------------------------------------------
*/
Schedule::command('reservations:expire')->everyMinute();

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/password/request', [PasswordContoller::class, 'sendResetLink']);
Route::post('/password/reset', [PasswordContoller::class, 'resetPassword']);

Route::get('/parkings/liste', [ParkingController::class, 'index']);
Route::get('/parkings/show/{id}', [ParkingController::class, 'show']);
Route::get('/sessions', [SessionController::class, 'checkSession']);

// Webhooks & Paiements
Route::post('/paytech/ipn', [PaymentController::class, 'handlePayTechIPN'])->name('paytech.ipn');
Route::post('/paytech/webhook', [AbonnementController::class, 'handleWebhook'])->name('paytech.webhook');
Route::get('/payment-bridge', function (Request $request) {
    $id = $request->id;
    $frontendIp = "http://localhost:5173";
    if ($request->has('cancel')) return redirect($frontendIp . "/booking/cancel");
    return redirect($frontendIp . "/booking/success?id=" . $id);
});

/*
|--------------------------------------------------------------------------
| 2. ROUTES PROTÉGÉES (Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    /* --- Système de Notifications Global --- */
    Route::get('/notifications', function (Request $request) {
        return $request->user()->notifications()->orderBy('created_at', 'desc')->get();
    });

    Route::post('/notifications/{id}/read', function (Request $request, $id) {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['status' => 'success']);
    });

    /* --- Routes Communes --- */
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [UserProfileController::class, 'index']);
    Route::put('/profile/update', [UserProfileController::class, 'update']);
    Route::post('/convert-to-partner', [AuthController::class, 'convertToPartner']);

    /* --- ESPACE CLIENT --- */
    Route::prefix('client')->group(function () {
        Route::get('/reservations', [BookingController::class, 'index']);
        Route::post('/reservations', [BookingController::class, 'store']);
        Route::post('/reservations/{id}/annuler', [BookingController::class, 'destroy']);
        Route::get('/reservations/confirmation/{reservation}', [BookingController::class, 'confirmation'])->name('reservation.confirmation');
        
        Route::apiResource('vehicules', VehiculeController::class)->except(['show', 'update']);
        Route::patch('/vehicules/{vehicule}/set-main', [VehiculeController::class, 'setMain']);
        
        Route::post('/reports', [ReportController::class, 'store']);
        Route::post('/avis', [AvisController::class, 'store']);
        Route::post('/abonnements', [AbonnementController::class, 'store']);
        Route::get('/abonnements/confirmation/{abonnement}', [AbonnementController::class, 'confirmation']);
        
        // Devenir partenaire
        Route::post('/become-partner-parking', [ParkingController::class, 'addParkingToBecomePartner']);
    });

   /* --- ESPACE PROPRIÉTAIRE / PARTENAIRE --- */
Route::middleware([IsPartner::class])->prefix('partenaire')->group(function () {
    Route::get('/analytics', [PartnerParkingController::class, 'getDashboardStats']);
    Route::get('/billing', [BillingController::class, 'index']);
    Route::get('/subscriptions', [PartnerSubscriptionsController::class, 'index']);
    
    // 1. Les routes pour la ressource Parking (Gérées proprement)
    Route::get('/parkings', [PartnerParkingController::class, 'index']);
    Route::post('/parkings', [ParkingController::class, 'store']);
    Route::get('/parkings/{id}', [PartnerParkingController::class, 'show']); 
    Route::patch('/parkings/{id}', [PartnerParkingController::class, 'update']);
    // 2. Le reste de tes routes
    Route::get('/reservations', [PartnerReservationController::class, 'index']);
    Route::patch('/reservations/{id}/status', [PartnerReservationController::class, 'updateStatus']);
    Route::post('/retraits', [RetraitController::class, 'store']);
    Route::apiResource('plans', PlanController::class);
});

    /* --- ESPACE ADMIN --- */
    Route::middleware([IsAdmin::class])->prefix('admin')->group(function () {
        // Gestion Utilisateurs & Partenaires
        Route::get('/utilisateurs', [AdminUserController::class, 'index']);
        Route::patch('/utilisateurs/{id}/toggle', [AdminUserController::class, 'toggleStatus']);
        Route::get('/partenaires', [AdminPartenaireController::class, 'index']);
        Route::get('/pending-proprios', [AdminParkingController::class, 'getPendingProprios']);
        Route::patch('/users/{user}/approve', [AdminParkingController::class, 'approveUser']);
        Route::delete('/users/{id}/desapprove', [AdminParkingController::class, 'desapproveUser']);

        // Gestion Parkings
        Route::get('/parkings', [AdminParkingController::class, 'getParkings']);
        Route::get('/pending-parkings', [AdminParkingController::class, 'index']);
        Route::patch('/parkings/{parking}/approve', [AdminParkingController::class, 'approveParking']);
        Route::delete('/parkings/{id}/desapprove', [AdminParkingController::class, 'rejectParking']);
        // Finance & Stats
        Route::get('/stats-globales', [AdminParkingController::class, 'getGlobalStats']);
        Route::get('/paiements/stats', [AdminParkingController::class, 'getPaymentStats']);
        Route::get('/transactions', [AdminTransactionController::class, 'index']);
        Route::get('/retraits', [AdminRetraitController::class, 'index']);
        Route::post('/retraits/{id}/valide', [AdminRetraitController::class, 'valider']);
        Route::post('/retraits/{id}/rejete', [AdminRetraitController::class, 'rejeter']);

        // Réservations & Remboursements
        Route::get('/reservations', [AdminBookingController::class, 'index']);
        Route::get('/remboursements/en-attente', [AdminBookingController::class, 'listeRemboursements']);
        Route::post('/remboursements/{id}/valider', [AdminBookingController::class, 'validerRemboursement']);

        // Signalements (Reports)
        Route::get('/reports', [ReportController::class, 'index']);
        Route::post('/reports/{id}/action', [ReportController::class, 'adminAction']);
    });
});
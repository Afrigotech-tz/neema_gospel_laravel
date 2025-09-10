
<?php

use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Support\Facades\Route;


// Ticket routes

Route::prefix('tickets')->group(function () {


        // Ticket type management routes
    Route::get('/events/{eventId}/ticket-types', [TicketController::class, 'getTicketTypes']);
    Route::post('/events/{eventId}/ticket-types', [TicketController::class, 'createTicketType']);
    Route::put('/ticket-types/{ticketTypeId}', [TicketController::class, 'updateTicketType']);
    Route::delete('/ticket-types/{ticketTypeId}', [TicketController::class, 'deleteTicketType']);

    Route::post('/purchase', [TicketController::class, 'purchaseTickets']);
    Route::post('/orders/{orderId}/confirm-payment', [TicketController::class, 'confirmPayment']);
    Route::get('/my-orders', [TicketController::class, 'getUserTickets']);
    Route::get('/events/{eventId}/sales', [TicketController::class, 'getEventSales']);

    // Ticket payment routes
    Route::post('/process-payment', [PaymentController::class, 'processTicketPayment']);
    Route::post('/orders/{orderId}/confirm-ticket-payment', [PaymentController::class, 'confirmTicketPayment']);

    // Additional ticket management routes
    Route::get('/orders/{orderId}', [TicketController::class, 'getTicketOrder']);
    Route::post('/orders/{orderId}/cancel', [TicketController::class, 'cancelTicketOrder']);
    Route::get('/payment/{paymentRef}', [TicketController::class, 'getTicketByPaymentRef']);


    

});





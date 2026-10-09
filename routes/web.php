<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Ticket\TicketController;
use Illuminate\Support\Facades\Route;

// Default route
Route::get('/', function () {
    return redirect()->route('login');
});

// Guest Routes
Route::middleware('guest')->group(function () {

    Route::get('/register', [
        AuthController::class, 'showRegister'
    ])->name('register');

    Route::post('/register', [
        AuthController::class, 'register'
    ])->middleware('throttle:5,1');

    Route::get('/login', [
        AuthController::class, 'showLogin'
    ])->name('login');

    Route::post('/login', [
        AuthController::class, 'login'
    ])->middleware('throttle:5,1');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::post('/logout', [
        AuthController::class, 'logout'
    ])->name('logout');
});

// Ticket CRUD Routes
Route::middleware('auth')
    ->prefix('tickets')
    ->name('tickets.')
    ->group(function () {

        // Ticket List
        Route::get('/', [
            TicketController::class, 'index'
        ])->name('index');

        // Show Create Form
        Route::get('/create', [
            TicketController::class, 'create'
        ])->name('create');

        // Store Ticket
        Route::post('/', [
            TicketController::class, 'store'
        ])->name('store');

        // Show Ticket Details
        Route::get('/{ticket}', [
            TicketController::class, 'show'
        ])->name('show');

        Route::get('/{ticket}/edit', [
    TicketController::class, 'edit'
])->name('edit');

        // Update Ticket
        Route::patch('/{ticket}', [
            TicketController::class, 'update'
        ])->name('update');


// Manual Assignment
Route::patch('/{ticket}/assign', [
    TicketController::class, 'assign'
])->name('assign');

// Resolve Ticket
Route::patch('/{ticket}/resolve', [
    TicketController::class, 'resolve'
])->name('resolve');


Route::patch('/{ticket}/close', [
    TicketController::class, 'close'
])->name('close');
    });
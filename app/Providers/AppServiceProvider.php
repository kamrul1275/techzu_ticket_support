<?php

namespace App\Providers;

use App\Models\Ticket;
use App\Models\User;
use App\Policies\TicketPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

public function boot(): void
{
    // Ticket Policy
    Gate::policy(Ticket::class, TicketPolicy::class);

    // Role permissions
    Gate::define('access-admin', function (User $user) {
        return $user->isAdmin();
    });

    Gate::define('access-agent', function (User $user) {
        return $user->isAgent();
    });

    Gate::define('access-customer', function (User $user) {
        return $user->isCustomer();
    });
}
}
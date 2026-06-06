<?php

namespace App\Console\Commands;

use App\Models\Reservation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:expire-reservations')]
#[Description('Command description')]
class ExpireReservations extends Command
{
    /**
     * Execute the console command.
     */
   public function handle()
{
    $count = Reservation::whereIn('statut', ['en_attente', 'confirme'])
        ->where('date_fin', '<', now())
        ->update(['statut' => 'termine']);

    $this->info("$count réservations ont été marquées comme expirées.");
}
}

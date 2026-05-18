<?php

use App\Console\Commands\SendCourseReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 1. Envoyer les rappels de cours chaque jour à 8h (temps de Casablanca)
Schedule::command(SendCourseReminders::class)
    ->dailyAt('08:00')
    ->timezone('Africa/Casablanca')
    ->description('Envoyer les rappels de cours aux étudiants')
    ->onOneServer(); // Optionnel : pour éviter les doublons sur plusieurs serveurs[citation:7]

// 2. Nettoyer les anciennes notifications (30 jours)
Schedule::command('model:prune', [
    '--model' => [\Illuminate\Notifications\DatabaseNotification::class],
    '--hours' => 720, // 30 jours * 24 heures
])->daily()
  ->description('Nettoyer les anciennes notifications de la base de données');

// 3. Vous pouvez aussi planifier des Closures directement
Schedule::call(function () {
    // Logique pour envoyer des rappels de paiement
    \Log::info('Vérification des abonnements expirés exécutée');
})->dailyAt('09:00')
  ->timezone('Africa/Casablanca')
  ->description('Vérifier les abonnements expirés');

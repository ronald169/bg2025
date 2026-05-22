<?php

use App\Http\Middleware\Admin;
use App\Http\Middleware\Teacher;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ==================== LANGUE ====================
Route::get('/language/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'fr', 'de'])) {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }
    return redirect()->back();
})->name('language.switch');

// ==================== AUTHENTIFICATION ====================
Route::middleware('guest')->group(function () {
    // Login
    Route::livewire('/login', 'pages::auth.login')->name('login');

    // Register
    Route::livewire('/register', 'pages::auth.register')->name('register');

    // Forgot Password
    Route::livewire('/forgot-password', 'pages::auth.forgot-password')->name('password.request');

    // Reset Password (avec token)
    Route::livewire('/reset-password/{token}', 'pages::auth.reset-password')->name('password.reset');
});

// ==================== ROUTES GÉNÉRALES PUBLIQUES ====================
Route::livewire('/', 'pages::landing-page')->name('home');
Route::livewire('/course/{course:slug}', 'pages::student.course-show')->name('student.course.show');
Route::livewire('/course/{course:slug}/lesson/{lesson:slug}', 'pages::student.lesson-player')->name('student.lesson.show');
Route::livewire('/catalog', 'pages::student.course-catalog')->name('student.catalog');
Route::livewire('/quiz/{quiz}', 'pages::student.quiz')->name('student.quiz.show');



// ==================== DÉCONNEXION ====================
Route::get('/logout', function () {
    auth()->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect()->route('home');
})->name('logout');

// ==================== ROUTES PROTÉGÉES (AUTHENTIFIÉES) ====================
Route::middleware('auth')->group(function () {

    // Dashboard (redirection selon rôle)
    Route::livewire('/dashboard', 'pages::dashboard-redirect')->name('dashboard.redirect');

    // ==================== PROFIL UTILISATEUR ====================
    Route::livewire('/profile', 'pages::user-profile')->name('profile');
    Route::livewire('/profile/edit', 'pages::user-profile-edit')->name('profile.edit');

    // ==================== NOTIFICATIONS ====================
    Route::livewire('/notifications', 'pages::notification-center')->name('notifications.index');
    Route::livewire('/settings/notifications', 'pages::notification-preferences')->name('settings.notifications');

    // ==================== PARAMÈTRES ====================
    Route::livewire('/settings', 'pages::user-settings')->name('settings');
    Route::livewire('/settings/account', 'pages::user-settings-account')->name('settings.account');
    Route::livewire('/settings/security', 'pages::user-settings-security')->name('settings.security');

    // ==================== ESPACE ÉTUDIANT ====================
    Route::prefix('student')->name('student.')->group(function () {
        // Dashboard et vue d'ensemble
        Route::livewire('/dashboard', 'pages::student.dashboard')->name('dashboard');

        // Catalogue et cours
        Route::livewire('/progress', 'pages::student.progress-tracker')->name('progress');
        Route::livewire('/learning-path', 'pages::student.learning-path')->name('learning-path');
        Route::livewire('/achievements', 'pages::student.achievements')->name('achievements');

        // Quiz
        Route::livewire('/quiz/history', 'pages::student.quiz-history')->name('quiz-history');
        Route::livewire('/quiz/{attempt}/results', 'pages::student.quiz-results')->name('quiz.results');

        // Outils d'étude
        Route::livewire('/flashcards', 'pages::student.flashcards')->name('flashcards');
        Route::livewire('/flashcards/create', 'pages::student.flashcard-create')->name('flashcards.create');
        Route::livewire('/flashcards/{set}', 'pages::student.flashcard-set')->name('flashcards.set');
        Route::livewire('/notes', 'pages::student.notes')->name('notes');
        Route::livewire('/notes/create', 'pages::student.note-create')->name('notes.create');
        Route::livewire('/notes/{note}', 'pages::student.note-show')->name('notes.show');

        // Communication
        Route::livewire('/messages', 'pages::student.messages')->name('messages');
        Route::livewire('/messages/{conversation}', 'pages::student.message-conversation')->name('messages.conversation');
        Route::livewire('/calendar', 'pages::student.calendar')->name('calendar');

        // Certificats
        Route::livewire('/certificates', 'pages::student.certificates')->name('certificates');
        Route::livewire('/certificates/{certificate}', 'pages::student.certificate-show')->name('certificates.show');

        // Wishlist (favoris)
        Route::livewire('/wishlist', 'pages::student.wishlist')->name('wishlist');
    });

    // ==================== ESPACE PROFESSEUR ====================
    Route::middleware(['auth', Teacher::class])->prefix('teacher')->name('teacher.')->group(function () {
        // Dashboard
        Route::livewire('/dashboard', 'pages::teacher.dashboard')->name('dashboard');

        // Gestion des cours
        Route::livewire('/courses', 'pages::teacher.courses')->name('courses');
        Route::livewire('/courses/create', 'pages::teacher.course-create')->name('courses.create');
        Route::livewire('/courses/{course}/edit', 'pages::teacher.course-edit')->name('courses.edit');
        Route::livewire('/courses/{course}/analytics', 'pages::teacher.course-analytics')->name('courses.analytics');
        Route::livewire('/courses/{course}/preview', 'pages::teacher.course-preview')->name('courses.preview');

        // Gestion des leçons
        Route::livewire('/courses/{course}/lessons', 'pages::teacher.lesson-manager')->name('lessons.index');
        Route::livewire('/courses/{course}/lessons/create', 'pages::teacher.lesson-create')->name('lessons.create');
        Route::livewire('/courses/{course}/lessons/{lesson}/edit', 'pages::teacher.lesson-edit')->name('lessons.edit');
        Route::livewire('/courses/{course}/lessons/{lesson}/preview', 'pages::teacher.lesson-preview')->name('lessons.preview');

        // Gestion des quiz
        Route::livewire('/courses/{course}/quizzes', 'pages::teacher.quiz-builder')->name('quizzes.index');
        // Route::livewire('/courses/{course}/quizzes/create/{lesson?}', 'pages::teacher.quiz-create')->name('quizzes.create');
        // Route::livewire('/courses/{course}/quizzes/{quiz}/edit', 'pages::teacher.quiz-edit')->name('quizzes.edit');
        Route::livewire('/courses/{course}/quizzes/{quiz}/preview', 'pages::teacher.quiz-preview')->name('quizzes.preview');

        // Gestion des étudiants
        Route::livewire('/students', 'pages::teacher.students')->name('students');
        Route::livewire('/students/{user}', 'pages::teacher.student-show')->name('students.show');
        Route::livewire('/students/{user}/progress', 'pages::teacher.student-progress')->name('students.progress');

        // Analytics et calendrier
        Route::livewire('/analytics', 'pages::teacher.analytics')->name('analytics');
        Route::livewire('/schedule', 'pages::teacher.schedule')->name('schedule');

        // Communication
        Route::livewire('/messages', 'pages::teacher.messages')->name('messages');
        Route::livewire('/announcements', 'pages::teacher.announcements')->name('announcements');

        // Paramètres
        Route::livewire('/settings', 'pages::teacher.settings')->name('settings');
    });

    // ==================== ESPACE ADMINISTRATION ====================
    Route::middleware(['auth', Admin::class])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard
        Route::livewire('/dashboard', 'pages::admin.dashboard')->name('dashboard');

        // Gestion des utilisateurs
        Route::livewire('/users', 'pages::admin.users')->name('users');
        Route::livewire('/users/create', 'pages::admin.user-create')->name('users.create');
        Route::livewire('/users/{user}', 'pages::admin.user-show')->name('users.show');
        Route::livewire('/users/{user}/edit', 'pages::admin.user-edit')->name('users.edit');

        // Gestion des cours
        Route::livewire('/courses', 'pages::admin.courses')->name('courses');
        Route::livewire('/courses/create', 'pages::admin.course-create')->name('courses.create');
        Route::livewire('/courses/{course}/edit', 'pages::admin.course-edit')->name('courses.edit');

        // Gestion des matières
        Route::livewire('/subjects', 'pages::admin.subjects')->name('subjects');

        // Gestion des inscriptions
        Route::livewire('/enrollments', 'pages::admin.enrollments')->name('enrollments');
        // Route::livewire('/enrollments/{enrollment}', 'pages::admin.enrollment-show')->name('enrollments.show');
        // Route::livewire('/enrollments/export', 'pages::admin.enrollments-export')->name('enrollments.export');

        // Gestion des avis
        Route::livewire('/reviews', 'pages::admin.reviews')->name('reviews');

        // Rapports et analytics
        Route::livewire('/reports', 'pages::admin.reports')->name('reports');
        Route::livewire('/analytics', 'pages::admin.analytics')->name('analytics');

        // Notifications système
        Route::livewire('/notifications', 'pages::admin.notifications')->name('notifications');
        Route::livewire('/notifications/templates', 'pages::admin.notification-templates')->name('notification-templates');
        Route::livewire('/notifications/templates/create', 'pages::admin.notification-template-edit')->name('notification-templates.create');
        Route::livewire('/notifications/templates/{template}/edit', 'pages::admin.notification-template-edit')->name('notification-templates.edit');
        Route::livewire('/notifications/broadcast', 'pages::admin.notification-broadcast')->name('notification-broadcast');

        // Paramètres système
        Route::livewire('/settings', 'pages::admin.settings')->name('settings');

        // Maintenance
        Route::livewire('/maintenance', 'pages::admin.maintenance')->name('maintenance');
        Route::livewire('/logs', 'pages::admin.logs')->name('logs');
        Route::livewire('/backup', 'pages::admin.backup')->name('backup');
        Route::livewire('/cache', 'pages::admin.cache')->name('cache');

        // Contacts
        Route::livewire('/contacts', 'pages::admin.contacts')->name('contacts');
    });
});

// ==================== SYSTÈME DE PAIEMENT ====================
Route::middleware('auth')->prefix('payment')->name('payment.')->group(function () {
    // Paiement unique
    Route::livewire('/checkout/{course}', 'pages::payment.checkout')->name('checkout');
    Route::livewire('/success/{course}', 'pages::payment.success')->name('success');
    Route::livewire('/cancel/{course}', 'pages::payment.cancel')->name('cancel');

    // Abonnement
    Route::livewire('/subscription', 'pages::payment.subscription')->name('subscription');
    Route::livewire('/subscription/checkout/{plan?}', 'pages::payment.subscription-checkout')->name('subscription.checkout');
    Route::livewire('/subscription/success', 'pages::payment.subscription-success')->name('subscription.success');
    Route::livewire('/subscription/cancel', 'pages::payment.subscription-cancel')->name('subscription.cancel');

    // Historique et factures
    Route::livewire('/history', 'pages::payment.history')->name('history');
    Route::livewire('/invoice/{enrollment}', 'pages::payment.invoice')->name('invoice');
});

// Webhook Stripe (public)
Route::post('/stripe/webhook', [App\Http\Controllers\StripeWebhookController::class, 'handleWebhook'])
    ->name('stripe.webhook');

// ==================== SEO ====================
Route::get('/sitemap.xml', [App\Http\Controllers\SitemapController::class, 'index'])->name('sitemap');

// Robots.txt
Route::get('/robots.txt', function () {
    $content = "User-agent: *\n";
    $content .= "Allow: /\n";
    $content .= "Disallow: /admin/*\n";
    $content .= "Disallow: /teacher/*\n";
    $content .= "Disallow: /student/*/edit\n";
    $content .= "Disallow: /payment/*\n";
    $content .= "Disallow: /profile/*\n";
    $content .= "Disallow: /settings/*\n";
    $content .= "Disallow: /messages/*\n";
    $content .= "Disallow: /notifications/*\n";
    $content .= "Sitemap: " . url('/sitemap.xml') . "\n";
    $content .= "Crawl-delay: 10\n";

    return response($content, 200)->header('Content-Type', 'text/plain');
})->name('robots.txt');

// ==================== ROUTES DE TEST (UNIQUEMENT EN LOCAL) ====================
if (app()->environment('local')) {
    Route::get('test', function () {
        $user = App\Models\User::first();
        $course = App\Models\Course::first();

        if ($user && $course) {
            return response()->json([
                'message' => 'Test route working!',
                'user' => $user->email,
                'course' => $course->title ?? null,
            ]);
        }

        return response()->json(['error' => 'No user or course found.'], 404);
    })->name('test');

    Route::get('test/seed', function () {
        Artisan::call('db:seed');
        return response()->json(['message' => 'Database seeded successfully!']);
    })->name('test.seed');
}

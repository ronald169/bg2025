<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'status',
        'phone', 'bio', 'date_of_birth', 'level',
        'profile_photo_path', 'email_notifications', 'sms_notifications',
        'push_notifications', 'language', 'timezone',
        'german_level', 'learning_goal', 'motivation', 'study_reminders',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'email_notifications' => 'boolean',
        'sms_notifications' => 'boolean',
        'push_notifications' => 'boolean',
    ];

    // ========== RELATIONS ==========

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function coursesEnrolled()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
                    ->withTimestamps()
                    ->withPivot('status', 'progress', 'enrolled_at');
    }

    public function progress()
    {
        return $this->hasMany(Progress::class);
    }

    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function studySessions()
    {
        return $this->hasMany(StudySession::class);
    }

    public function learningStreak()
    {
        return $this->hasOne(LearningStreak::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function courses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function coursesTaught()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function notifications()
    {
        return $this->morphMany(\Illuminate\Notifications\DatabaseNotification::class, 'notifiable');
    }

    public function wishlist()
    {
        return $this->belongsToMany(Course::class, 'wishlists')
                    ->withTimestamps();
    }

    public function learningPath()
    {
        return $this->hasOne(LearningPath::class)->where('is_active', true);
    }

    public function learningPaths()
    {
        return $this->hasMany(LearningPath::class);
    }

    /**
     * Messages envoyés par l'utilisateur
     */
    public function sentMessages()
    {
        return $this->hasMany(Message::class, 'user_id');
    }

    /**
     * Messages reçus par l'utilisateur
     */
    public function receivedMessages()
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Conversations de l'utilisateur (en tant qu'étudiant)
     */
    public function conversationsAsStudent()
    {
        return $this->hasMany(Conversation::class, 'student_id');
    }

    // ========== ACCESSORS ==========


    public function getLearningStreakAttribute()
    {
        // Si la relation n'est pas chargée
        if (!$this->relationLoaded('learningStreak')) {
            // Charge la relation OU crée-la si elle n'existe pas
            $this->setRelation(
                'learningStreak',
                $this->learningStreak()->firstOrCreate(
                    ['user_id' => $this->id],
                    [
                        'current_streak' => 0,
                        'longest_streak' => 0,
                        'last_study_date' => null,
                        'total_study_days' => 0,
                    ]
                )
            );
        }

        return $this->getRelation('learningStreak');
    }

    // ========== STREAK METHODS ==========

    public function updateLearningStreak(): void
    {
        $streak = $this->learningStreak;
        $today = now()->toDateString();

        if ($streak->last_study_date?->toDateString() === $today) {
            return;
        }

        $yesterday = now()->subDay()->toDateString();

        if ($streak->last_study_date?->toDateString() === $yesterday) {
            $streak->current_streak++;
        } else {
            $streak->current_streak = 1;
        }

        if ($streak->current_streak > $streak->longest_streak) {
            $streak->longest_streak = $streak->current_streak;
        }

        $streak->last_study_date = now();
        $streak->save();
    }

    // ========== ROLE METHODS ==========

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}

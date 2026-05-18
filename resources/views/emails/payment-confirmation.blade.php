<x-mail::layout>
    <x-slot:header>
        <x-mail::header :url="config('app.url')">
            BrainGenius - Confirmation de Paiement
        </x-mail::header>
    </x-slot:header>

    <x-mail::message>
        # Paiement Confirmé ✅

        Bonjour {{ $user->name }},

        Votre paiement a été traité avec succès.

        @if($payment->course)
        **Cours :** {{ $payment->course->title }}
        **Montant :** {{ $payment->formatted_amount }}
        @else
        **Abonnement :** Plateforme BrainGenius
        **Montant :** {{ $payment->formatted_amount }}
        @endif

        **Date :** {{ $payment->paid_at->format('d/m/Y à H:i') }}
        **Référence :** {{ $payment->payment_id }}

        @if($payment->course)
        <x-mail::button :url="route('student.lesson.show', ['course' => $payment->course->slug, 'lesson' => $payment->course->lessons()->first()?->id])">
            Commencer le Cours
        </x-mail::button>
        @else
        <x-mail::button :url="route('student.dashboard')">
            Accéder à la Plateforme
        </x-mail::button>
        @endif

        Merci pour votre confiance !
        L'équipe BrainGenius
    </x-mail::message>

    <x-slot:footer>
        <x-mail::footer>
            © {{ date('Y') }} BrainGenius. Tous droits réservés.
        </x-mail::footer>
    </x-slot:footer>
</x-mail::layout>

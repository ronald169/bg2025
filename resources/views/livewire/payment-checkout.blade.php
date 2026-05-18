<?php

use App\Models\Course;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new
#[Title('Checkout')]
class extends Component {
    use Toast;

    public Course $course;
    public $paymentMethod = 'card';
    public $isProcessing = false;
    public $paymentIntent;
    public $clientSecret;

    // Plans d'abonnement plateforme
    public $selectedPlan = 'monthly';
    public $platformPlans = [
        'monthly' => ['price' => 9.99, 'name' => 'Mensuel'],
        'yearly' => ['price' => 99.99, 'name' => 'Annuel'],
    ];

    public function mount(Course $course): void
    {
        $this->course = $course;

        // Si c'est un achat de cours, initialiser le payment intent
        if ($course->price > 0) {
            $this->initializePayment();
        }
    }

    private function initializePayment(): void
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $this->paymentIntent = \Stripe\PaymentIntent::create([
                'amount' => $this->getAmount(),
                'currency' => 'eur',
                'metadata' => [
                    'course_id' => $this->course->id,
                    'user_id' => Auth::id(),
                    'type' => 'course_purchase'
                ],
            ]);

            $this->clientSecret = $this->paymentIntent->client_secret;
        } catch (\Exception $e) {
            $this->error('Erreur lors de l\'initialisation du paiement.');
        }
    }

    private function getAmount(): int
    {
        if ($this->course->price > 0) {
            return $this->course->price * 100; // Convert to cents
        }

        return $this->platformPlans[$this->selectedPlan]['price'] * 100;
    }

    public function processPayment(): void
    {
        $this->isProcessing = true;

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            if ($this->course->price > 0) {
                // Paiement pour cours individuel
                $this->processCoursePayment();
            } else {
                // Abonnement plateforme
                $this->processSubscription();
            }

        } catch (\Exception $e) {
            $this->error('Erreur de paiement: ' . $e->getMessage());
            $this->isProcessing = false;
        }
    }

    private function processCoursePayment(): void
    {
        // Confirmer le payment intent
        $paymentIntent = \Stripe\PaymentIntent::retrieve($this->paymentIntent->id);

        if ($paymentIntent->status === 'succeeded') {
            // Enregistrer le paiement
            Payment::create([
                'user_id' => Auth::id(),
                'course_id' => $this->course->id,
                'payment_id' => $paymentIntent->id,
                'amount' => $this->course->price,
                'status' => 'succeeded',
                'payment_method' => $this->paymentMethod,
                'paid_at' => now(),
            ]);

            // Inscrire l'utilisateur au cours
            Auth::user()->coursesEnrolled()->attach($this->course->id);

            $this->success('Paiement réussi ! Vous avez maintenant accès au cours.');
            $this->redirectRoute('student.lesson.show', [
                'course' => $this->course->slug,
                'lesson' => $this->course->lessons()->first()?->id
            ]);
        }
    }

    private function processSubscription(): void
    {
        // Créer ou récupérer le client Stripe
        $user = Auth::user();

        if (!$user->stripe_id) {
            $customer = \Stripe\Customer::create([
                'email' => $user->email,
                'name' => $user->name,
                'metadata' => ['user_id' => $user->id],
            ]);
            $user->update(['stripe_id' => $customer->id]);
        }

        // Créer l'abonnement
        $subscription = \Stripe\Subscription::create([
            'customer' => $user->stripe_id,
            'items' => [[
                'price' => $this->getPlanPriceId(),
            ]],
            'payment_behavior' => 'default_incomplete',
            'expand' => ['latest_invoice.payment_intent'],
        ]);

        $this->clientSecret = $subscription->latest_invoice->payment_intent->client_secret;

        // Rediriger vers la page de succès
        $this->success('Abonnement créé avec succès !');
        $this->redirectRoute('payment.success');
    }

    private function getPlanPriceId(): string
    {
        // IDs des plans Stripe - à configurer dans le dashboard Stripe
        return [
            'monthly' => 'price_monthly_plan_id',
            'yearly' => 'price_yearly_plan_id',
        ][$this->selectedPlan];
    }

    public function updatedSelectedPlan(): void
    {
        if ($this->course->price === 0) {
            $this->initializePayment();
        }
    }
}; ?>

<div>
    {{-- En-tête --}}
    <x-header
        title="{!! __('Checkout') !!}"
        subtitle="{!! __('Complete your purchase') !!}"
        separator
        progress-indicator
    >
        <x-slot:actions>
            <x-button
                label="{!! __('Back to Course') !!}"
                icon="o-arrow-left"
                link="/course/{{ $course->slug }}"
                class="btn-ghost"
            />
        </x-slot:actions>
    </x-header>

    <div class="max-w-4xl mx-auto">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            {{-- Résumé de la commande --}}
            <div>
                <x-card title="{!! __('Order Summary') !!}" shadow>
                    @if($course->price > 0)
                        {{-- Achat de cours --}}
                        <div class="flex items-center space-x-4 mb-6">
                            <img
                                src="{{ $course->image ?? '/images/course-placeholder.jpg' }}"
                                alt="{{ $course->title }}"
                                class="w-20 h-20 rounded-lg object-cover"
                            />
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-900">{{ $course->title }}</h3>
                                <p class="text-sm text-gray-600">{{ $course->teacher->name }}</p>
                                <p class="text-lg font-bold text-primary mt-2">
                                    {{ $course->price }}€
                                </p>
                            </div>
                        </div>
                    @else
                        {{-- Abonnement plateforme --}}
                        <div class="space-y-4 mb-6">
                            <h3 class="font-semibold text-gray-900">{!! __('Platform Subscription') !!}</h3>

                            <x-radio
                                name="plan"
                                value="monthly"
                                label="{!! __('Monthly Plan') !!}"
                                wire:model.live="selectedPlan"
                                class="border rounded-lg p-4"
                            >
                                <div class="flex justify-between items-center w-full">
                                    <div>
                                        <div class="font-medium">9.99€/mois</div>
                                        <div class="text-sm text-gray-600">{!! __('Access to all courses') !!}</div>
                                    </div>
                                </div>
                            </x-radio>

                            <x-radio
                                name="plan"
                                value="yearly"
                                label="{!! __('Yearly Plan') !!}"
                                wire:model.live="selectedPlan"
                                class="border rounded-lg p-4"
                            >
                                <div class="flex justify-between items-center w-full">
                                    <div>
                                        <div class="font-medium">99.99€/an</div>
                                        <div class="text-sm text-gray-600">
                                            {!! __('2 months free') !!} • {!! __('Access to all courses') !!}
                                        </div>
                                    </div>
                                    <x-badge value="{!! __('Best Value') !!}" class="badge-success" />
                                </div>
                            </x-radio>
                        </div>
                    @endif

                    {{-- Détails prix --}}
                    <div class="border-t pt-4 space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>{!! __('Subtotal') !!}</span>
                            <span>{{ $course->price > 0 ? $course->price . '€' : $platformPlans[$selectedPlan]['price'] . '€' }}</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span>{!! __('Tax') !!}</span>
                            <span>{!! __('Included') !!}</span>
                        </div>
                        <div class="flex justify-between font-semibold text-lg border-t pt-2">
                            <span>{!! __('Total') !!}</span>
                            <span class="text-primary">
                                {{ $course->price > 0 ? $course->price . '€' : $platformPlans[$selectedPlan]['price'] . '€' }}
                            </span>
                        </div>
                    </div>
                </x-card>

                {{-- Garanties --}}
                <x-card shadow class="mt-6 bg-success/10 border-success/20">
                    <div class="flex items-start space-x-3">
                        <x-icon name="o-shield-check" class="w-6 h-6 text-success mt-0.5" />
                        <div>
                            <h4 class="font-semibold text-success">{!! __('Secure Payment') !!}</h4>
                            <p class="text-sm text-success/80 mt-1">
                                {!! __('Your payment information is encrypted and secure.') !!}
                            </p>
                        </div>
                    </div>
                </x-card>
            </div>

            {{-- Formulaire de paiement --}}
            <div>
                <x-card title="{!! __('Payment Method') !!}" shadow>
                    <div class="space-y-6">
                        {{-- Sélection méthode --}}
                        <div class="grid grid-cols-2 gap-4">
                            <x-radio
                                name="paymentMethod"
                                value="card"
                                label="{!! __('Credit Card') !!}"
                                wire:model.live="paymentMethod"
                                class="border rounded-lg p-4 text-center"
                            >
                                <x-icon name="o-credit-card" class="w-8 h-8 mx-auto mb-2" />
                            </x-radio>

                            <x-radio
                                name="paymentMethod"
                                value="mobile_money"
                                label="{!! __('Mobile Money') !!}"
                                wire:model.live="paymentMethod"
                                class="border rounded-lg p-4 text-center"
                            >
                                <x-icon name="o-device-phone-mobile" class="w-8 h-8 mx-auto mb-2" />
                            </x-radio>
                        </div>

                        {{-- Formulaire Stripe --}}
                        @if($paymentMethod === 'card' && $clientSecret)
                            <div wire:ignore>
                                <div id="card-element" class="border rounded-lg p-4">
                                    <!-- Stripe Elements will create form elements here -->
                                </div>
                                <div id="card-errors" class="text-red-600 text-sm mt-2"></div>
                            </div>
                        @endif

                        {{-- Mobile Money --}}
                        @if($paymentMethod === 'mobile_money')
                            <div class="space-y-4">
                                <x-input
                                    label="{!! __('Phone Number') !!}"
                                    type="tel"
                                    placeholder="+33 6 12 34 56 78"
                                    required
                                />
                                <x-select
                                    label="{!! __('Mobile Operator') !!}"
                                    :options="[
                                        ['id' => 'orange', 'name' => 'Orange Money'],
                                        ['id' => 'mtn', 'name' => 'MTN Mobile Money'],
                                        ['id' => 'moov', 'name' => 'Moov Money'],
                                    ]"
                                    required
                                />
                            </div>
                        @endif

                        {{-- Bouton de paiement --}}
                        <x-button
                            label="{{ $isProcessing ? __('Processing...') : __('Pay Now') }}"
                            wire:click="processPayment"
                            class="btn-primary w-full btn-lg"
                            icon="o-lock-closed"
                            :disabled="$isProcessing"
                            spinner="processPayment"
                        />

                        <p class="text-xs text-gray-500 text-center">
                            {!! __('By completing your purchase, you agree to our Terms of Service and Privacy Policy.') !!}
                        </p>
                    </div>
                </x-card>
            </div>
        </div>
    </div>

    {{-- Script Stripe --}}
    @if($paymentMethod === 'card' && $clientSecret)
        <script src="https://js.stripe.com/v3/"></script>
        <script>
            const stripe = Stripe('{{ config('services.stripe.key') }}');
            const elements = stripe.elements();
            const cardElement = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#424770',
                        '::placeholder': {
                            color: '#aab7c4',
                        },
                    },
                },
            });

            cardElement.mount('#card-element');

            cardElement.on('change', function(event) {
                const displayError = document.getElementById('card-errors');
                if (event.error) {
                    displayError.textContent = event.error.message;
                } else {
                    displayError.textContent = '';
                }
            });

            // Gérer la soumission du formulaire
            Livewire.on('processStripePayment', function() {
                stripe.confirmCardPayment('{{ $clientSecret }}', {
                    payment_method: {
                        card: cardElement,
                    }
                }).then(function(result) {
                    if (result.error) {
                        // Afficher l'erreur
                        console.error(result.error.message);
                    } else {
                        // Paiement réussi
                        Livewire.dispatch('paymentSuccess');
                    }
                });
            });
        </script>
    @endif
</div>

<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new
#[Title('Math - Correction')]
#[Layout('components.layouts.mathjax')]
class extends Component {


}; ?>

<!-- Pour l'affichage -->
<div class="prose">
      <!-- EXERCICE 1 - ÉNONCÉ -->
    <div class="exercise">
        <h2 class="text-xl font-bold mb-4">Exercice 1</h2>

        <div class="question mb-6">
            <p class="font-semibold mb-2">1. Reproduire dans un repère orthonormé (O,I,J), la courbe de la fonction \( f \) définie ci-contre puis après avoir donné un programme de construction de la courbe de la fonction \( \mathcal{G} \) définie de \( ] -\infty; 2 [ \) vers \( \mathbb{R} \) par \( g(x) = f(x-2) - 1 \) construire dans ce même repère la courbe de la fonction \( g \).</p>
        </div>

        <div class="question mb-6">
            <p class="font-semibold mb-2">2. Montrer que la droite d'équation \( x = 1 \) est axe de symétrie à la courbe de la fonction \( h \) définie de \( \mathbb{R} \) vers \( \mathbb{R} \) par \( h(x) = 3x^2 - 6x - 4 \).</p>
        </div>

        <div class="question mb-6">
            <p class="font-semibold mb-2">3. Soit \( v \) la fonction définie de \( \mathbb{R} \backslash \{3\} \) vers \( \mathbb{R} \backslash \{2\} \) par \( v(x) = \frac{2x-1}{x-3} \).</p>
            <div class="ml-4">
                <p>a. Montrer que \( v \) est une application bijective.</p>
                <p>b. Définir la bijection réciproque \( v^{-1} \) de \( v \).</p>
                <p>c. Montrer que pour tout \( x \in \mathbb{R} \backslash \{3\}, v(x) = 2 + \frac{5}{x-3} \).</p>
                <p>d. Montrer que le point \( A(3;2) \) est centre de symétrie à la courbe de la fonction \( v \).</p>
            </div>
        </div>

        <div class="question mb-6">
            <p class="font-semibold mb-2">4. Étudier la parité de la fonction \( l \) définie de \( \mathbb{R} \) vers \( \mathbb{R} \) par \( l(x) = \frac{-2x^3+3x}{x^2+2} \).</p>
        </div>

        <div class="question">
            <p class="font-semibold mb-2">5. Montrer que la fonction \( r \) définie sur \( \mathbb{R} \) par \( r(x) = sin^2x - 4cosx \) est \( 2\pi \)-périodique.</p>
        </div>
    </div>

    <div class="mt-8 p-4 border-t">
        <!-- EXERCICE 1 - CORRECTION -->
        <div class="correction">
            <h2 class="text-xl font-bold mb-4 text-green-600">Correction - Exercice 1</h2>

            <div class="question-correction mb-6">
                <h3 class="font-bold text-lg mb-2">1. Construction de \( g(x) = f(x-2) - 1 \)</h3>
                <p class="mb-2"><strong>Programme de construction :</strong></p>
                <ul class="list-disc list-inside ml-4 mb-3">
                    <li>Translation horizontale de vecteur \( (2, 0) \) : \( C_{f(x-2)} \)</li>
                    <li>Translation verticale de vecteur \( (0, -1) \) : \( C_g \)</li>
                </ul>
                <p class="text-blue-600 font-semibold">✓ La courbe de \( g \) est obtenue par translation de la courbe de \( f \).</p>
            </div>

            <div class="question-correction mb-6">
                <h3 class="font-bold text-lg mb-2">2. Axe de symétrie \( x = 1 \) pour \( h(x) = 3x^2 - 6x - 4 \)</h3>
                <p class="mb-2">On vérifie que \( h(1 + t) = h(1 - t) \) :</p>
                <div class="bg-gray-50 p-4 rounded mb-2">
                    <p>\[
                    h(1 + t) = 3(1 + t)^2 - 6(1 + t) - 4 = 3(1 + 2t + t^2) - 6 - 6t - 4
                    \]</p>
                    <p>\[
                    = 3 + 6t + 3t^2 - 10 - 6t = 3t^2 - 7
                    \]</p>
                    <p>\[
                    h(1 - t) = 3(1 - t)^2 - 6(1 - t) - 4 = 3(1 - 2t + t^2) - 6 + 6t - 4
                    \]</p>
                    <p>\[
                    = 3 - 6t + 3t^2 - 10 + 6t = 3t^2 - 7
                    \]</p>
                </div>
                <p class="text-green-600 font-semibold">✓ Donc \( h(1 + t) = h(1 - t) \), la droite \( x = 1 \) est bien axe de symétrie.</p>
            </div>

            <div class="question-correction mb-6">
                <h3 class="font-bold text-lg mb-2">3. Fonction \( v(x) = \frac{2x-1}{x-3} \)</h3>

                <div class="ml-4">
                    <p class="font-semibold mb-2">a) Bijection :</p>
                    <p>Soit \( v(a) = v(b) \) :</p>
                    <div class="bg-gray-50 p-3 rounded mb-2">
                        <p>\[
                        \frac{2a-1}{a-3} = \frac{2b-1}{b-3}
                        \]</p>
                        <p>\[
                        (2a-1)(b-3) = (2b-1)(a-3)
                        \]</p>
                        <p>\[
                        2ab - 6a - b + 3 = 2ab - 6b - a + 3
                        \]</p>
                        <p>\[
                        -6a - b = -6b - a
                        \]</p>
                        <p>\[
                        -5a = -5b \Rightarrow a = b
                        \]</p>
                    </div>
                    <p class="text-green-600 font-semibold mb-4">✓ \( v \) est injective.</p>

                    <p class="font-semibold mb-2">b) Bijection réciproque :</p>
                    <p>Soit \( y = \frac{2x-1}{x-3} \) :</p>
                    <div class="bg-gray-50 p-3 rounded mb-2">
                        <p>\[
                        y(x-3) = 2x - 1
                        \]</p>
                        <p>\[
                        yx - 3y = 2x - 1
                        \]</p>
                        <p>\[
                        yx - 2x = 3y - 1
                        \]</p>
                        <p>\[
                        x(y - 2) = 3y - 1
                        \]</p>
                        <p>\[
                        x = \frac{3y - 1}{y - 2}
                        \]</p>
                    </div>
                    <p class="text-green-600 font-semibold mb-4">✓ \( v^{-1}(y) = \frac{3y - 1}{y - 2} \)</p>

                    <p class="font-semibold mb-2">c) Forme simplifiée :</p>
                    <div class="bg-gray-50 p-3 rounded mb-2">
                        <p>\[
                        v(x) = \frac{2x-1}{x-3} = \frac{2(x-3) + 5}{x-3} = 2 + \frac{5}{x-3}
                        \]</p>
                    </div>
                    <p class="text-green-600 font-semibold mb-4">✓ Forme vérifiée.</p>

                    <p class="font-semibold mb-2">d) Centre de symétrie \( A(3;2) \) :</p>
                    <p>On vérifie que \( v(3 + h) + v(3 - h) = 4 \) :</p>
                    <div class="bg-gray-50 p-3 rounded mb-2">
                        <p>\[
                        v(3 + h) = 2 + \frac{5}{h}
                        \]</p>
                        <p>\[
                        v(3 - h) = 2 + \frac{5}{-h} = 2 - \frac{5}{h}
                        \]</p>
                        <p>\[
                        v(3 + h) + v(3 - h) = 4
                        \]</p>
                    </div>
                    <p class="text-green-600 font-semibold">✓ \( A(3;2) \) est bien centre de symétrie.</p>
                </div>
            </div>

            <div class="question-correction mb-6">
                <h3 class="font-bold text-lg mb-2">4. Parité de \( l(x) = \frac{-2x^3+3x}{x^2+2} \)</h3>
                <p>On calcule \( l(-x) \) :</p>
                <div class="bg-gray-50 p-3 rounded mb-2">
                    <p>\[
                    l(-x) = \frac{-2(-x)^3 + 3(-x)}{(-x)^2 + 2} = \frac{2x^3 - 3x}{x^2 + 2}
                    \]</p>
                    <p>\[
                    = -\frac{-2x^3 + 3x}{x^2 + 2} = -l(x)
                    \]</p>
                </div>
                <p class="text-green-600 font-semibold">✓ \( l \) est impaire.</p>
            </div>

            <div class="question-correction">
                <h3 class="font-bold text-lg mb-2">5. Périodicité de \( r(x) = \sin^2x - 4\cos x \)</h3>
                <p>On vérifie la \( 2\pi \)-périodicité :</p>
                <div class="bg-gray-50 p-3 rounded mb-2">
                    <p>\[
                    r(x + 2\pi) = \sin^2(x + 2\pi) - 4\cos(x + 2\pi)
                    \]</p>
                    <p>\[
                    = \sin^2 x - 4\cos x = r(x)
                    \]</p>
                </div>
                <p class="text-green-600 font-semibold">✓ \( r \) est \( 2\pi \)-périodique.</p>
            </div>
        </div>
    </div>
</div>

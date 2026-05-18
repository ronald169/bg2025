<?php
// app/helpers.php

use Illuminate\Support\Str;

    if (!function_exists('formatDuration')) {
        function formatDuration($minutes)
        {
            if ($minutes < 1) {
                $seconds = round($minutes * 60);
                return $seconds . ' ' . ($seconds <= 1 ? 'sec' : 'sec');
            }

            $hours = floor($minutes / 60);
            $remainingMinutes = floor($minutes % 60);

            $parts = [];
            if ($hours > 0) {
                $parts[] = $hours . ' ' . ($hours <= 1 ? 'h' : 'h');
            }
            if ($remainingMinutes > 0) {
                $parts[] = $remainingMinutes . ' ' . ($remainingMinutes <= 1 ? 'min' : 'min');
            }

            return implode(' ', $parts);
        }
    }

    if (!function_exists('formatStudyTime')) {
        /**
         * Formate le temps d'étude total
         *
         * @param int $minutes
         * @return string
         */
        function formatStudyTime($minutes)
        {
            if ($minutes < 60) {
                return "{$minutes} Minute" . ($minutes > 1 ? 'n' : '');
            }

            $hours = floor($minutes / 60);
            $remainingMinutes = $minutes % 60;

            $result = "{$hours} Stunde" . ($hours > 1 ? 'n' : '');

            if ($remainingMinutes > 0) {
                $result .= " {$remainingMinutes} Minute" . ($remainingMinutes > 1 ? 'n' : '');
            }

            return $result;
        }
    }

    if (!function_exists('getProgressColor')) {
        /**
         * Retourne la couleur CSS en fonction du pourcentage de progression
         *
         * @param int $progress
         * @return string
         */
        function getProgressColor($progress)
        {
            if ($progress >= 80) return 'bg-green-500';
            if ($progress >= 50) return 'bg-blue-500';
            if ($progress >= 20) return 'bg-yellow-500';
            return 'bg-gray-400';
        }
    }

    if (!function_exists('getLevelBadgeColor')) {
        /**
         * Retourne la couleur du badge pour un niveau d'allemand
         *
         * @param string $level
         * @return string
         */
        function getLevelBadgeColor($level)
        {
            return match($level) {
                'A1', 'A2' => 'bg-green-100 text-green-700',
                'B1', 'B2' => 'bg-orange-100 text-orange-700',
                'C1', 'C2' => 'bg-red-100 text-red-700',
                default => 'bg-gray-100 text-gray-700',
            };
        }
    }

    if (!function_exists('clean_text')) {
    function clean_text($text, $limit = null)
    {
        // Supprimer les balises HTML
        $text = strip_tags($text);
        
        // Décoder les entités HTML (comme &nbsp;, &amp;, etc.)
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
        
        // Supprimer les espaces multiples
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Nettoyer les caractères invisibles
        $text = trim($text);
        
        // Limiter la longueur si nécessaire
        if ($limit) {
            $text = Str::limit($text, $limit);
        }
        
        return $text;
    }
}
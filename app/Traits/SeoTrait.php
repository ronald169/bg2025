<?php
// app/Traits/SeoTrait.php

namespace App\Traits;

use Illuminate\Support\Str;

trait SeoTrait
{
    public function getSeoMetaTags()
    {
        return [
            'title' => $this->getSeoTitle(),
            'description' => $this->getSeoDescription(),
            'keywords' => $this->getSeoKeywords(),
            'og_title' => $this->getOgTitle(),
            'og_description' => $this->getOgDescription(),
            'og_image' => $this->getOgImage(),
            'canonical_url' => $this->getCanonicalUrl(),
            'robots' => $this->getRobots(),
        ];
    }

    protected function getSeoTitle()
    {
        return $this->meta_title ?? $this->title . ' - ' . config('app.name');
    }

    protected function getSeoDescription()
    {
        if ($this->meta_description) {
            return $this->meta_description;
        }
        
        $description = $this->short_description ?? $this->description ?? '';
        return Str::limit(strip_tags($description), 160);
    }

    protected function getSeoKeywords()
    {
        if ($this->meta_keywords) {
            return $this->meta_keywords;
        }
        
        // Générer des mots-clés basés sur le titre et le niveau
        $keywords = [];
        $keywords[] = $this->title;
        $keywords[] = 'German course';
        $keywords[] = 'Learn German';
        
        if (isset($this->level)) {
            $keywords[] = $this->level . ' German';
        }
        
        return implode(', ', $keywords);
    }

    protected function getOgTitle()
    {
        return $this->og_title ?? $this->getSeoTitle();
    }

    protected function getOgDescription()
    {
        return $this->og_description ?? $this->getSeoDescription();
    }

    protected function getOgImage()
    {
        if ($this->og_image) {
            return $this->og_image;
        }
        
        if (isset($this->thumbnail) && $this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }
        
        return asset('images/default-og-image.jpg');
    }

    protected function getCanonicalUrl()
    {
        return $this->canonical_url ?? url()->current();
    }

    protected function getRobots()
    {
        return $this->robots ?? 'index,follow';
    }
}
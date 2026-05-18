<?php
// app/Traits/Seoable.php

namespace App\Traits;

use Illuminate\Support\Str;

trait Seoable
{
    public function getSeoMetaTags()
    {
        return [
            'meta_title' => $this->getMetaTitle(),
            'meta_description' => $this->getMetaDescription(),
            'meta_keywords' => $this->getMetaKeywords(),
            'og_title' => $this->getOgTitle(),
            'og_description' => $this->getOgDescription(),
            'og_image' => $this->getOgImage(),
            'og_url' => $this->getOgUrl(),
            'twitter_title' => $this->getTwitterTitle(),
            'twitter_description' => $this->getTwitterDescription(),
            'twitter_image' => $this->getTwitterImage(),
            'canonical_url' => $this->getCanonicalUrl(),
            'robots' => $this->getRobots(),
        ];
    }

    protected function getMetaTitle()
    {
        if (property_exists($this, 'meta_title') && $this->meta_title) {
            return $this->meta_title;
        }
        return $this->title . ' - ' . config('app.name');
    }

    protected function getMetaDescription()
    {
        if (property_exists($this, 'meta_description') && $this->meta_description) {
            return $this->meta_description;
        }
        
        $description = $this->short_description ?? $this->description ?? '';
        return Str::limit(strip_tags($description), 160);
    }

    protected function getMetaKeywords()
    {
        if (property_exists($this, 'meta_keywords') && $this->meta_keywords) {
            return $this->meta_keywords;
        }
        
        $keywords = [$this->title, 'German course', 'learn German'];
        if (property_exists($this, 'level') && $this->level) {
            $keywords[] = $this->level . ' German';
        }
        return implode(', ', $keywords);
    }

    protected function getOgTitle()
    {
        if (property_exists($this, 'og_title') && $this->og_title) {
            return $this->og_title;
        }
        return $this->getMetaTitle();
    }

    protected function getOgDescription()
    {
        if (property_exists($this, 'og_description') && $this->og_description) {
            return $this->og_description;
        }
        return $this->getMetaDescription();
    }

    protected function getOgImage()
    {
        if (property_exists($this, 'og_image') && $this->og_image) {
            return asset('storage/' . $this->og_image);
        }
        if (property_exists($this, 'thumbnail') && $this->thumbnail) {
            return asset('storage/' . $this->thumbnail);
        }
        return asset('images/og-image.jpg');
    }

    protected function getOgUrl()
    {
        return url()->current();
    }

    protected function getTwitterTitle()
    {
        if (property_exists($this, 'twitter_title') && $this->twitter_title) {
            return $this->twitter_title;
        }
        return $this->getOgTitle();
    }

    protected function getTwitterDescription()
    {
        if (property_exists($this, 'twitter_description') && $this->twitter_description) {
            return $this->twitter_description;
        }
        return $this->getOgDescription();
    }

    protected function getTwitterImage()
    {
        if (property_exists($this, 'twitter_image') && $this->twitter_image) {
            return asset('storage/' . $this->twitter_image);
        }
        return $this->getOgImage();
    }

    protected function getCanonicalUrl()
    {
        if (property_exists($this, 'canonical_url') && $this->canonical_url) {
            return $this->canonical_url;
        }
        return url()->current();
    }

    protected function getRobots()
    {
        if (property_exists($this, 'robots') && $this->robots) {
            return $this->robots;
        }
        return 'index,follow';
    }
}
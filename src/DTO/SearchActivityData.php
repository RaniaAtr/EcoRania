<?php

namespace App\DTO;

class SearchActivityData
{
    public ?float $minPrice = null;
    public ?float $maxPrice = null;
    public ?\DateTimeInterface $date = null;
    public ?string $tag = null;
    public ?string $adresse = null;
}
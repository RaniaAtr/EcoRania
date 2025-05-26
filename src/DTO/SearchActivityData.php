<?php

namespace App\DTO;

class SearchActivityData
{
    public ?float $minPrice = null;
    public ?float $maxPrice = null;
    public ?\DateTimeInterface $date = null;
    public ?string $categorie = null;
    public ?string $adresse = null;
}
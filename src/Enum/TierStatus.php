<?php

namespace App\Enum;

enum TierStatus: string
{
    case Nouveau = 'Ni prestataire agréé, ni prospect';
    case Prestataire = 'Prestataire agréé';
    case Prospect = 'Prospect';
    case ProspectPrestataire = 'Prospect / Prestataire agréé';
}
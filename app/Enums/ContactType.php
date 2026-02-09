<?php
declare(strict_types=1);

namespace App\Enums;

enum ContactType: string
{
    case Client      = 'C';
    case Fournisseur = 'F';
    case Livraison   = 'L';
}

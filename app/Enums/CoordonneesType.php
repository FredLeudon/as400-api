<?php
declare(strict_types=1);

namespace App\Enums;

enum CoordonneesType: int
{
    case TelephoneBureau  = 1;
    case TelephoneMobile  = 2;
    case FaxBureau        = 4;
    case MailBureau       = 62;
}
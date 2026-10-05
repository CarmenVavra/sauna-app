<?php

namespace App\Enums;

enum Dosage: string
{
    case TooWeak = 'zu schwach';
    case Right = 'passend';
    case TooStrong = 'zu stark';
}

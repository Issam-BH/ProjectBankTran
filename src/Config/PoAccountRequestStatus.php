<?php

namespace App\Config;

enum PoAccountRequestStatus: string
{
    case pending = 'pending';
    case done = 'done';
}

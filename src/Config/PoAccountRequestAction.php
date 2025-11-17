<?php

namespace App\Config;

enum PoAccountRequestAction: string
{
    case delete = 'delete';
    case create = 'create';
}

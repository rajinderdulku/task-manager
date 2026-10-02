<?php

namespace App\Enums;

enum ActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case StatusChanged = 'status_changed';
}

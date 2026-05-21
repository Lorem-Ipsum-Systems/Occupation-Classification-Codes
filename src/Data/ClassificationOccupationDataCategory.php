<?php

declare(strict_types=1);

namespace ClassificationOccupation\Data;

enum ClassificationOccupationDataCategory: string
{
    case STRUCTURE = 'structure';
    case DEFINITIONS = 'definitions';
    case SEARCH = 'search';
}

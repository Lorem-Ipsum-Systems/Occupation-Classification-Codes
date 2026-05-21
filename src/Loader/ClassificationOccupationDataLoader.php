<?php

declare(strict_types=1);

namespace ClassificationOccupation\Loader;

use ClassificationOccupation\Data\ClassificationOccupationDatasetDefinition;
use ClassificationOccupation\Model\ClassificationOccupationDataset;

interface ClassificationOccupationDataLoader
{
    public function load(ClassificationOccupationDatasetDefinition $definition): ClassificationOccupationDataset;
}

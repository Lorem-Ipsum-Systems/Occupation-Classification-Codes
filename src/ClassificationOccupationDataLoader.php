<?php

declare(strict_types=1);

namespace ClassificationOccupation;

interface ClassificationOccupationDataLoader
{
    public function load(ClassificationOccupationDatasetDefinition $definition): ClassificationOccupationDataset;
}

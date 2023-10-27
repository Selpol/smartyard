<?php declare(strict_types=1);

namespace Selpol\Task\Trait;

use ReflectionClass;
use ReflectionProperty;

/**
 * @property int $taskUniqueTtl
 * @property string[] $taskUniqueIgnore
 */
trait TaskUniqueTrait
{
    public function unique(): array
    {
        $class = new ReflectionClass($this);
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        $value = [$class->getName()];

        $exclude = ['title', 'retry', 'taskUniqueIgnore', 'taskUniqueTtl'];

        if (isset($this->taskUniqueIgnore))
            $exclude = array_merge($this->taskUniqueIgnore, $exclude);

        foreach ($properties as $property)
            if (!$property->isStatic() && !in_array($property->getName(), $exclude))
                $value[] = $property->getValue($this);

        return [implode('/', $value), $this->taskUniqueTtl ?? 3600];
    }
}
<?php declare(strict_types=1);

namespace Selpol\Task\Trait;

use ReflectionClass;
use ReflectionProperty;
use Selpol\Task\TaskUnique;

/**
 * @property int $taskUniqueTtl
 * @property string[] $taskUniqueIgnore
 */
trait TaskUniqueTrait
{
    public function unique(): TaskUnique
    {
        $class = new ReflectionClass($this);
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);

        $value = [$class->getName()];

        foreach ($properties as $property)
            if (!$property->isStatic() && !in_array($property->getName(), $this->taskUniqueIgnore ?? ['title', 'retry', 'taskUniqueIgnore', 'taskUniqueTtl']))
                $value[] = $property->getValue($this);

        return new TaskUnique($value, $this->taskUniqueTtl ?? 3600);
    }
}
<?php

declare(strict_types=1);

namespace Brick\VarExporter\Internal;

use Brick\VarExporter\ExportException;

/**
 * An exporter that handles a specific type of object.
 *
 * @internal This class is for internal use, and not part of the public API. It may change at any time without warning.
 */
abstract class ObjectExporter
{
    /**
     * @var GenericExporter
     */
    protected $exporter;

    /**
     * @param GenericExporter $exporter
     */
    public function __construct(GenericExporter $exporter)
    {
        $this->exporter = $exporter;
    }

    /**
     * Returns whether this exporter supports the given object.
     *
     * @param object            $object           The object to export.
     * @param \ReflectionObject $reflectionObject A reflection of the object.
     *
     * @return bool
     */
    abstract public function supports($object, \ReflectionObject $reflectionObject) : bool;

    /**
     * Exports the given object.
     *
     * @param object            $object           The object to export.
     * @param \ReflectionObject $reflectionObject A reflection of the object.
     *
     * @return string[] The lines of code.
     *
     * @throws ExportException
     */
    abstract public function export($object, \ReflectionObject $reflectionObject) : array;

    /**
     * Returns the code to create a new object of the given class.
     *
     * If the class has a constructor, reflection will be used to bypass it.
     *
     * @param \ReflectionClass $class
     *
     * @return string[] The lines of code.
     */
    final protected function getCreateObjectCode(\ReflectionClass $class) : array
    {
        $className = '\\' . $class->getName();

        if ($class->getConstructor() === null) {
            return ['$object = new ' . $className . ';'];
        }

        $lines = ['$class = new \ReflectionClass(' . $className . '::class);'];

        if ($this->exporter->addTypeHints) {
            $lines[] = '';
            $lines[] = '/** @var ' . $className . ' $object */';
        }

        $lines[] = '$object = $class->newInstanceWithoutConstructor();';

        return $lines;
    }

    /**
     * Wraps the given PHP code in a static closure.
     *
     * @param string[] $code The lines of code.
     *
     * @return string[] The lines of code, wrapped in a closure.
     */
    final protected function wrapInClosure(array $code) : array
    {
        return array_merge(
            ['(static function() {'],
            $this->exporter->indent($code),
            ['})()']
        );
    }

    /**
     * @param string $var
     *
     * @return string
     */
    final protected function escapePropName(string $var) : string
    {
        if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]+$/', $var) === 1) {
            return $var;
        }

        return '{' . var_export($var, true) . '}';
    }
}

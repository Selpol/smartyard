<?php declare(strict_types=1);

namespace Selpol\Cli\Document;

use ReflectionClass;
use Selpol\Cli\Document\Generate\GenerateClass;
use Selpol\Cli\Document\Generate\GenerateComment;
use Selpol\Cli\Document\Generate\GenerateMethod;
use Selpol\Cli\Document\Generate\GenerateParameter;
use Selpol\Cli\Document\Generate\GenerateProperty;
use Selpol\Cli\Document\Generate\GenerateRequest;
use Selpol\Framework\Cli\Attribute\Executable;
use Selpol\Framework\Cli\Attribute\Execute;
use Selpol\Framework\Router\Attribute\Controller;
use Selpol\Framework\Router\Attribute\Method;
use Selpol\Framework\Router\Route\RouteRequest;
use Selpol\Framework\Router\RouterConfigurator;
use Selpol\Framework\Scanner\Scanner;
use Selpol\Framework\Scanner\ScannerClass;
use Selpol\Framework\Scanner\ScannerMethod;
use Throwable;

#[Executable('document:generate', 'Генерация документации')]
class DocumentGenerateCommand
{
    private array $configs = [[true, 'admin'], [false, 'internal'], [false, 'router']];

    /**
     * @var array<string, GenerateClass[]>
     */
    private array $values = [];

    /**
     * @var array<string, GenerateRequest>
     */
    private array $requests = [];

    #[Execute]
    public function execute(): void
    {
        $this->process();
        $this->generate(path('document'));
    }

    private function generate(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, recursive: true);
        }

        $index = [
            '# Документация',
            '',
            'Документация проекта SmartYard',
            '',
            '## Группы контроллеров',
            ''
        ];

        foreach ($this->configs as $config) {
            $index[] = '- [' . strtoupper($config[1]) . '](./' . $config[1] . '/INDEX.md)';

            $subPath = $path . '/' . $config[1];

            $this->generateConfig($subPath, $config);
        }

        file_put_contents($path . '/INDEX.md', implode(PHP_EOL, $index));

        $this->generateObject($path);
    }

    private function generateConfig(string $path, array $config): void
    {
        $index = [
            '# Документация ' . strtoupper($config[1]),
            '',
            '## Контроллеры',
            ''
        ];

        if (!is_dir($path)) {
            mkdir($path, recursive: true);
        }

        foreach ($this->values[$config[1]] as $value) {
            $index[] = '- [' . $value->name . '](./' . $value->name . '.md)';

            $this->generateClass($path, $value);
        }

        file_put_contents($path . '/INDEX.md', implode(PHP_EOL, $index));
    }

    private function generateClass(string $path, GenerateClass $class): void
    {
        if (!is_dir($path)) {
            mkdir($path, recursive: true);
        }

        $value = ['# Контроллер ' . $class->name . ' `' . $class->path . '`', ''];

        if ($class->comment) {
            $value[] = $class->comment->getLine();
            $value[] = '';
        }

        $value[] = '## Методы';
        $value[] = '';

        foreach ($class->methods as $method) {
            $value[] = '### [' . $method->method . '/' . $method->name . ($method->scope ? ' `' . $method->scope . '`' : '') . '] ' . ($method->comment ? $method->comment->getLine() : '') . ' `' . $method->path . '`';
            $value[] = '';

            if (count($method->parameters) > 0) {
                $value[] = 'Параметры: ';
                $value[] = '';

                foreach ($method->parameters as $parameter) {
                    if (str_contains($parameter->type, '\\')) {
                        $segments = explode('\\', $parameter->type);

                        $value[] = '- [' . $segments[count($segments) - 1] . '](../OBJECT.md#' . $segments[count($segments) - 1] . ') ' . ($parameter->name !== 'request' ? '*' . $parameter->name . '*' : '') . ($parameter->document ? ' ' . $parameter->document->getLine() : '');
                    } else {
                        $value[] = '- `' . $parameter->type . '` *' . $parameter->name . '*' . ($parameter->document ? ' ' . $parameter->document->getLine() : '');
                    }
                }

                $value[] = '';
            }
        }

        file_put_contents($path . '/' . $class->name . '.md', implode(PHP_EOL, $value));
    }

    private function generateObject(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, recursive: true);
        }

        $object = [
            '# Объекты',
            ''
        ];

        foreach ($this->requests as $request) {
            $segments = explode('\\', $request->type);

            $object[] = '## ' . $segments[count($segments) - 1];
            $object[] = '';

            if (count($request->properties) > 0) {
                $object[] = 'Поля: ';
                $object[] = '';

                foreach ($request->properties as $property) {
                    $object[] = '- `' . $property->type . '` *' . substr($property->name, 1) . ($property->document ? '* - ' . $property->document : '*');
                }

                $object[] = '';
            }
        }

        file_put_contents($path . '/OBJECT.md', implode(PHP_EOL, $object));
    }

    private function process(): void
    {
        foreach ($this->configs as $config) {
            if (file_exists(path('config/' . $config[1] . '.php'))) {
                $this->values[$config[1]] = [];

                $this->processConfig($config);
            }
        }
    }

    private function processConfig(array $config): void
    {
        $callback = require path('config/' . $config[1] . '.php');
        $configurator = new RouterConfigurator();
        $callback($configurator);
        $paths = $configurator->getPaths();
        $psr4 = $configurator->getPsr4();

        foreach ($paths as $namespace => $path) {
            $this->processNamespace($config, $namespace, $path);
        }

        foreach ($psr4 as $namespace) {
            $this->processNamespace($config, $namespace);
        }
    }

    private function processNamespace(array $config, string $namespace, ?string $path = null): void
    {
        /** @var Scanner<Controller, Method> $scanner */
        $scanner = $path !== null && $path !== '' && $path !== '0'
            ? new Scanner($path, $namespace, Controller::class, Method::class)
            : Scanner::psr4($namespace, Controller::class, Method::class);

        foreach ($scanner->scan() as $value) {
            $this->processClass($config, $value);
        }
    }

    private function processClass(array $config, ScannerClass $value): void
    {
        /** @var Controller $instance */
        $instance = $value->getAttributeInstance();

        $comment = $value->reflectionClass->getDocComment();

        if ($comment) {
            $comment = $this->parseComment($comment);
        }

        $methods = [];

        foreach ($value->methods as $method) {
            $methods[] = $this->processMethod($config, $instance->path, $method);
        }

        $this->values[$config[1]][] = new GenerateClass($value->reflectionClass->getShortName(), $value->class, $instance->path, $methods, $comment);
    }

    private function processMethod(array $config, string $path, ScannerMethod $method): GenerateMethod
    {
        /** @var Method $instance */
        $instance = $method->getAttributeInstance();

        $comment = $method->reflectionMethod->getDocComment();

        if ($comment) {
            $comment = $this->parseComment($comment);
        }

        $parameters = [];

        foreach ($method->reflectionMethod->getParameters() as $parameter) {
            $parameterComment = !is_bool($comment) && array_key_exists($parameter->getName(), $comment->params) ? new GenerateComment([$comment->params[$parameter->getName()]], []) : false;

            if ($parameter->getType()->isBuiltin()) {
                $parameters[] = new GenerateParameter($parameter->getName(), $parameter->getType()->getName(), $parameterComment);
            } else if (is_subclass_of($parameter->getType()->getName(), RouteRequest::class)) {
                $parameters[] = new GenerateParameter($parameter->getName(), $parameter->getType()->getName(), $parameterComment);

                $this->processRequest($parameter->getType()->getName());
            }
        }

        if ($config[0]) {
            $segments = explode('/', substr($path . $instance->path, strlen($config[1]) + 1));
            $result = [];

            foreach ($segments as $segment) {
                if (str_starts_with($segment, '{') && str_ends_with($segment, '}')) {
                    continue;
                }

                if ($segment != '') {
                    $result[] = $segment;
                }
            }

            if ($result[count($result) - 1] !== $method->method) {
                $result[] = $method->method;
            }

            return new GenerateMethod($method->method, $instance->type, $path . $instance->path, implode('-', $result) . '-' . strtolower($instance->type), $parameters, $comment);
        } else {
            return new GenerateMethod($method->method, $instance->type, $path . $instance->path, '', $parameters, $comment);
        }
    }

    /**
     * @param class-string<RouteRequest> $type
     * @return void
     */
    private function processRequest(string $type): void
    {
        if (array_key_exists($type, $this->requests)) {
            return;
        }

        try {
            $reflectionClass = new ReflectionClass($type);

            $document = $reflectionClass->getDocComment();

            if (!$document) {
                return;
            }

            $lines = array_map('trim', explode(PHP_EOL, $document));

            if (count($lines) > 2) {
                $lines = array_slice($lines, 1, count($lines) - 2);
            }

            $properties = [];

            foreach ($lines as $line) {
                if (!str_starts_with($line, '* @property-read')) {
                    continue;
                }

                $segments = explode(' ', substr($line, 17), 3);

                $properties[] = new GenerateProperty($segments[1], $segments[0], array_key_exists(2, $segments) ? $segments[2] : false);
            }

            $this->requests[$type] = new GenerateRequest($type, $properties);
        } catch (Throwable) {
        }
    }

    private function parseComment(string $value): GenerateComment
    {
        $lines = array_map('trim', explode(PHP_EOL, $value));

        if (count($lines) > 2) {
            $lines = array_slice($lines, 1, count($lines) - 2);
        }

        $result = ['lines' => [], 'params' => []];

        foreach ($lines as $line) {
            if (!str_starts_with($line, '* @')) {
                $result['lines'][] = substr($line, 2);
            } else if (str_starts_with($line, '* @param')) {
                $segments = explode(' ', substr($line, 9), 3);

                if (count($segments) > 2) {
                    $result['params'][substr($segments[1], 1)] = $segments[2];
                }
            }
        }

        return new GenerateComment($result['lines'], $result['params']);
    }
}
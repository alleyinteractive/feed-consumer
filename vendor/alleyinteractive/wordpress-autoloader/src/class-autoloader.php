<?php

/**
 * Autoloader file
 *
 * @package WordPress_Autoloader
 */

declare(strict_types=1);

namespace Alley_Interactive\Autoloader;

/**
 * WordPress Autoload Generator
 *
 * @phpstan-consistent-constructor
 */
class Autoloader
{
    /**
     * Missing classes for the autoloader.
     *
     * @var array<string, bool>
     */
    protected array $missing_classes = [];

    /**
     * APCu cache key prefix.
     */
    protected ?string $apcu_prefix = null;

    /**
     * Generate an autoloader for the WordPress file naming conventions.
     *
     * @param string $namespace Namespace to autoload.
     * @param string $root_path Root path of the namespace to load from.
     */
    public static function generate(string $namespace, string $root_path): static
    {
        return new static($namespace, $root_path);
    }

    /**
     * Constructor.
     *
     * @param string $namespace Namespace to register.
     * @param string $root_path Root path of the namespace.
     */
    public function __construct(protected string $namespace, protected string $root_path)
    {
        // The trailing separator keeps "Foo\Bar" from matching "Foo\Barbaz\Class".
        $this->namespace = trim($namespace, '\\') . '\\';
        $this->root_path = rtrim($root_path, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * APCu prefix to use to cache found/not-found classes, if the extension is enabled.
     *
     * @deprecated 1.2.0 Renamed to setApcuPrefix().
     *
     * @param string|null $prefix Prefix to use.
     */
    public function set_apcu_prefix(?string $prefix): static
    {
        return $this->setApcuPrefix($prefix);
    }

    /**
     * APCu prefix to use to cache found/not-found classes, if the extension is enabled.
     *
     * @param string|null $prefix Prefix to use.
     */
    public function setApcuPrefix(?string $prefix): static
    {
        $this->apcu_prefix = function_exists('apcu_fetch') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN)
            ? $prefix
            : null;

        return $this;
    }

    /**
     * The APCu prefix in use, or null if APCu caching is not enabled.
     */
    public function getApcuPrefix(): ?string
    {
        return $this->apcu_prefix;
    }

    /**
     * Check if a class was missing from the autoloader.
     *
     * @param string $classname Class to check.
     */
    public function isMissingClass(string $classname): bool
    {
        return isset($this->missing_classes[ $classname ]);
    }

    /**
     * Register the autoloader.
     */
    public function register(): void
    {
        spl_autoload_register($this);
    }

    /**
     * Unregister the autoloader.
     */
    public function unregister(): void
    {
        spl_autoload_unregister($this);
    }

    /**
     * Invoke method of the class.
     *
     * @param string $classname Class being autoloaded.
     */
    public function __invoke(string $classname): void
    {
        if (! str_starts_with($classname, $this->namespace)) {
            return;
        }

        if (isset($this->missing_classes[ $classname ])) {
            return;
        }

        if (isset($this->apcu_prefix)) {
            $hit  = false;
            $file = apcu_fetch($this->apcu_prefix . $classname, $hit);

            if ($hit && is_string($file)) {
                require_once $file;
                return;
            }
        }

        $file = $this->findFile($classname);

        if (null === $file) {
            $this->missing_classes[ $classname ] = true;
            return;
        }

        require_once $file;

        if (isset($this->apcu_prefix)) {
            apcu_add($this->apcu_prefix . $classname, $file);
        }
    }

    /**
     * Find a file for the given class.
     *
     * @param string $classname Class to find.
     */
    protected function findFile(string $classname): ?string
    {
        $parts = explode('\\', substr($classname, strlen($this->namespace)));
        $class = strtolower(str_replace('_', '-', array_pop($parts)));

        $directory = $this->root_path;

        if ($parts) {
            $directory .= strtolower(str_replace('_', '-', implode(DIRECTORY_SEPARATOR, $parts))) . DIRECTORY_SEPARATOR;
        }

        // A class, trait, interface, or enum could share the same name, so check each prefix in order.
        foreach (['class', 'trait', 'interface', 'enum'] as $type) {
            $path = "{$directory}{$type}-{$class}.php";

            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }
}

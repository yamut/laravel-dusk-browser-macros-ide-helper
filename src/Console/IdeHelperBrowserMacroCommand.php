<?php


namespace Yamut\LaravelDuskMacroHelper\Console;


use Closure;
use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionParameter;
use RuntimeException;

class IdeHelperBrowserMacroCommand extends Command
{
    protected $signature = 'ide-helper:dusk_macros';
    protected $description = "Generates a helper file for laravel dusk browser macros";
    private $file;
    private $namespace = 'Laravel\Dusk';
    private $class = 'Browser';

    /**
     * @throws ReflectionException
     */
    public function handle()
    {
        $browserReflection = new ReflectionClass($this->namespace . '\\' . $this->class);
        if (!class_exists($this->namespace . '\\' . $this->class)) {
            throw new RuntimeException("The browser class is unavailable.");
        }
        if (!$browserReflection->hasProperty('macros')) {
            throw new RuntimeException("There are no defined macros for browser.");
        }
        $macrosProperty = $browserReflection->getProperty('macros');
        $macrosProperty->setAccessible(true);
        $macros = $macrosProperty->getValue();
        $filename = config('ide-dusk-browser-macros.filename');
        $this->file = fopen(base_path($filename), 'w');
        fwrite($this->file, "<?php\n");
        fwrite($this->file, sprintf("namespace %s {\n", $this->namespace));
        fwrite($this->file, sprintf("\tclass %s {\n", $this->class));
        foreach ($macros as $name => $closure) {
            /**
             * @var string $name
             * @var Closure $closure
             */
            $reflectionFunction = new ReflectionFunction($closure);
            $line = sprintf("\t\tpublic function %s(", $name);
            foreach ($reflectionFunction->getParameters() as $reflectionParameter) {
                /** @var ReflectionParameter $reflectionParameter */
                if ($reflectionParameter->hasType()) {
                    $line .= $reflectionParameter->getType()->allowsNull() ?
                        '?' :
                        '';
                    if (class_exists('\\' . $reflectionParameter->getType()->getName())) {
                        $line .= '\\' . $reflectionParameter->getType()->getName() . ' ';
                    } else {
                        $line .= $reflectionParameter->getType()->getName() . ' ';
                    }
                }
                $line .= '$' . $reflectionParameter->getName();
                if ($reflectionParameter->isDefaultValueAvailable()) {
                    $line .= ' = ' . (is_string($reflectionParameter->getDefaultValue()) ?
                            sprintf("'%s'", $reflectionParameter->getDefaultValue()) :
                            $reflectionParameter->getDefaultValue());
                }
                $line .= ', ';
            }
            $line = rtrim($line, ', ');
            $line .= ')';
            if ($reflectionFunction->hasReturnType()) {
                if ($reflectionFunction->getReturnType() == $this->namespace . '\\' . $this->class) {
                    $docblock = <<<DOCBLOCK
        /**
         * @return \$this
         **/
DOCBLOCK;
                    $line = $docblock . "\n" . $line;
                } else {
                    $line .= ': ' . $reflectionFunction->getReturnType();
                }
                $line .= " {}\n";
                fwrite($this->file, $line);
            }
        }
        fwrite($this->file, "\t}\n}");
        fclose($this->file);
        $this->line("Browser macros written.");
    }
}
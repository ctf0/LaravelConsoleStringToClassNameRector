<?php

declare(strict_types=1);

namespace App\Rector\Rules;

use Illuminate\Support\Facades\Artisan;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Scalar\String_;
use Rector\Contract\Rector\ConfigurableRectorInterface;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

use function in_array;

final class ConsoleStringToClassNameRector extends AbstractRector implements ConfigurableRectorInterface
{
    /**
     * @var array<string, class-string>
     */
    private array $commandMap = [];

    /**
     * @param  array<string, class-string>  $configuration
     */
    public function configure(array $commandMapFile): void
    {
        $this->commandMap = file_exists($commandMapFile['path']) ? require $commandMapFile['path'] : [];
    }

    public function getNodeTypes(): array
    {
        return [
            StaticCall::class,
            MethodCall::class,
        ];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace Artisan command string names with command class references.',
            []
        );
    }

    public function getNodeTypes(): array
    {
        return [
            StaticCall::class,
            MethodCall::class,
        ];
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace Artisan command string names with command class references.',
            []
        );
    }

    public function refactor(Node $node): ?Node
    {
        if ($node instanceof StaticCall) {
            return $this->refactorStaticCall($node);
        }

        if ($node instanceof MethodCall) {
            return $this->refactorMethodCall($node);
        }

        return null;
    }

    private function refactorMethodCall(MethodCall $methodCall): ?MethodCall
    {
        $methodName = $this->getName($methodCall->name);

        if (! in_array($methodName, ['call', 'callSilent'], true)) {
            return null;
        }

        return $this->replaceFirstArgument($methodCall);
    }

    private function refactorStaticCall(StaticCall $staticCall): ?StaticCall
    {
        $isArtisan = $this->isName($staticCall->class, Artisan::class)
            || $this->isName($staticCall->class, 'Artisan');
        $isSchedule = $this->isName($staticCall->class, Schedule::class)
            || $this->isName($staticCall->class, 'Schedule');

        if (! $isArtisan && ! $isSchedule) {
            return null;
        }

        $methodName = $this->getName($staticCall->name);

        if (! in_array($methodName, $isSchedule ? ['command'] : ['call', 'queue'], true)) {
            return null;
        }

        return $this->replaceFirstArgument($staticCall);
    }

    /**
     * @template T of StaticCall|MethodCall
     *
     * @param  T  $call
     *
     * @return T|null
     */
    private function replaceFirstArgument(StaticCall|MethodCall $call): StaticCall|MethodCall|null
    {
        $firstArg = $call->args[0] ?? null;

        if (! $firstArg instanceof Arg) {
            return null;
        }

        if (! $firstArg->value instanceof String_) {
            return null;
        }

        $commandName  = $firstArg->value->value;
        $commandClass = $this->commandMap[$commandName] ?? null;

        if ($commandClass === null) {
            return null;
        }

        $firstArg->value = new ClassConstFetch(
            new FullyQualified($commandClass),
            'class'
        );

        return $call;
    }
}

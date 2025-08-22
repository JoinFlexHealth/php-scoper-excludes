<?php

declare(strict_types=1);

namespace Snicco\PhpScoperExcludes\NodeVisitor;

use Throwable;
use PhpParser\Node;
use RuntimeException;
use PhpParser\NodeVisitorAbstract;

use function sort;

use const SORT_NATURAL;
use const SORT_FLAG_CASE;

/**
 * @internal
 */
final class Categorize extends NodeVisitorAbstract
{
    
    /**
     * @var string[]
     */
    private array $classes = [];
    
    /**
     * @var string[]
     */
    private array $interfaces = [];
    
    /**
     * @var string[]
     */
    private array $functions = [];
    
    /**
     * @var string[]
     */
    private array $traits = [];
    
    /**
     * @var string[]
     */
    private array $constants = [];
    
    public function beforeTraverse(array $nodes)
    {
        $this->reset();
        return null;
    }
    
    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Stmt\Class_) {
            $this->addClassNames($node);
            return null;
        }
        if ($node instanceof Node\Stmt\Interface_) {
            $this->addInterfaceNames($node);
            return null;
        }
        if ($node instanceof Node\Stmt\Function_) {
            $this->addFunctionNames($node);
            return null;
        }
        if ($node instanceof Node\Stmt\Trait_) {
            $this->addTraitNames($node);
            return null;
        }
        if ($node instanceof Node\Stmt\Const_) {
            $this->addConstantNames($node);
            return null;
        }
        
        if ($node instanceof Node\Stmt\Expression
            && $node->expr instanceof Node\Expr\FuncCall
            && $node->expr->name instanceof Node\Name
            && $node->expr->name->toString() === 'define'
        ) {
            $this->addDefineConstantNames($node);
        }
        
        return null;
    }
    
    public function classes() :array
    {
        $copy = $this->classes;
        sort($copy, SORT_NATURAL | SORT_FLAG_CASE);
        return $copy;
    }
    
    public function functions() :array
    {
        $copy = $this->functions;
        sort($copy, SORT_NATURAL | SORT_FLAG_CASE);
        return $copy;
    }
    
    public function traits() :array
    {
        $copy = $this->traits;
        sort($copy, SORT_NATURAL | SORT_FLAG_CASE);
        return $copy;
    }
    
    public function constants() :array
    {
        $copy = $this->constants;
        sort($copy, SORT_NATURAL | SORT_FLAG_CASE);
        return $copy;
    }
    
    public function interfaces() :array
    {
        $copy = $this->interfaces;
        sort($copy, SORT_NATURAL | SORT_FLAG_CASE);
        return $copy;
    }
    
    private function reset()
    {
        $this->classes = [];
        $this->functions = [];
        $this->traits = [];
        $this->constants = [];
    }
    
    private function addClassNames(Node $node) :void
    {
        if ( ! isset($node->namespacedName)) {
            throw new RuntimeException(
                "Class node was expected to be a namespacedName attribute."
            );
        }
        $this->classes[] = $node->namespacedName->toString();
    }
    
    private function addFunctionNames(Node $node) :void
    {
        if ( ! isset($node->namespacedName)) {
            throw new RuntimeException(
                'Function node was expected to have a namespacedName attribute.'
            );
        }
        $this->functions[] = $node->namespacedName->toString();
    }
    
    private function addTraitNames(Node $node) :void
    {
        if ( ! isset($node->namespacedName)) {
            throw new RuntimeException(
                'Trait node was expected to have a namespacedName attribute.'
            );
        }
        $this->traits[] = $node->namespacedName->toString();
    }
    
    private function addConstantNames(Node $node) :void
    {
        if (empty($node->consts)) {
            throw new RuntimeException("Constant declaration node has no constants.");
        }
        
        foreach ($node->consts as $const) {
            if ( ! isset($const->namespacedName)) {
                throw new RuntimeException(
                    'Const node was expected to have a namespacedName attribute.'
                );
            }
            
            $this->constants[] = $const->namespacedName->toString();
        }
    }
    
    private function addDefineConstantNames(Node $node) :void
    {
        if ( ! isset($node->expr->args)) {
            throw new RuntimeException("define() declaration has no constant name.");
        }
        
        try {
            $valueNode = $node->expr->args[0]->value;
            
            // Handle different types of scalar nodes
            if ($valueNode instanceof Node\Scalar\String_) {
                $constantName = $valueNode->value;
            } elseif ($valueNode instanceof Node\Scalar\Encapsed) {
                // For interpolated strings, we can't reliably extract a constant name
                // Skip these cases to avoid warnings
                return;
            } else {
                // For other node types, try to convert to string if possible
                $constantName = (string) $valueNode;
            }
            
            $this->constants[] = $constantName;
        } catch (Throwable $e) {
            throw new RuntimeException(
                "define() declaration has no constant name.\n{$e->getMessage()}",
                $e->getCode(),
                $e
            );
        }
    }
    
    private function addInterfaceNames(Node $node)
    {
        if ( ! isset($node->namespacedName)) {
            throw new RuntimeException(
                'Interface node was expected to be a namespacedName attribute.'
            );
        }
        $this->interfaces[] = $node->namespacedName->toString();
    }
    
}
<?php

namespace Bytes\UserBundle\Command;

use Symfony\Component\Console\Completion\CompletionInput;
use Symfony\Component\Console\Completion\CompletionSuggestions;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

trait UsernameCompletionTrait
{
    /**
     * @var PropertyAccessorInterface
     */
    private PropertyAccessorInterface $accessor;

    /**
     * @param PropertyAccessorInterface $accessor
     */
    public function setAccessor(PropertyAccessorInterface $accessor): void
    {
        $this->accessor = $accessor;
    }

    /**
     * Adds suggestions to $suggestions for the current completion input (e.g. option or argument).
     */
    protected function completeUsername(CompletionInput $input, CompletionSuggestions $suggestions): void
    {
        if ($input->mustSuggestArgumentValuesFor('username')) {
            $users = $this->repo->findAll();

            $suggestions->suggestValues(array_map(function ($value) {
                return $this->accessor->getValue($value, $this->userIdentifier);
            }, $users));
        }
    }
}
<?php

namespace Bytes\UserBundle\Command;

use Bytes\CommandBundle\Exception\CommandRuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\NotCompromisedPassword;
use Symfony\Component\Validator\Exception\ValidatorException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @property InputInterface $input
 */
trait PasswordValidationTrait
{
    private bool $validateNotCompromisedPassword = false;
    private bool $validatePasswordStrength = false;
    private int $validatePasswordStrengthMinScore = 2;
    private ?ValidatorInterface $validator = null;

    /**
     * @param bool $validateNotCompromisedPassword
     */
    public function setValidateNotCompromisedPassword(bool $validateNotCompromisedPassword): void
    {
        $this->validateNotCompromisedPassword = $validateNotCompromisedPassword;
    }

    /**
     * @param bool $validatePasswordStrength
     */
    public function setValidatePasswordStrength(bool $validatePasswordStrength): void
    {
        $this->validatePasswordStrength = $validatePasswordStrength;
    }

    /**
     * @param int $validatePasswordStrengthMinScore
     */
    public function setValidatePasswordStrengthMinScore(int $validatePasswordStrengthMinScore): void
    {
        $this->validatePasswordStrengthMinScore = $validatePasswordStrengthMinScore;
    }

    /**
     * @param ValidatorInterface|null $validator
     */
    public function setValidator(?ValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }

    private function validatePassword(#[\SensitiveParameter] $plainPassword): void
    {
        $validators = [
            new NotBlank()
        ];
        if ($this->validateNotCompromisedPassword) {
            $validators[] = new NotCompromisedPassword();
        }

        if ($this->validatePasswordStrength && class_exists(\Symfony\Component\Validator\Constraints\PasswordStrength::class)) {
            $validators[] = new \Symfony\Component\Validator\Constraints\PasswordStrength(minScore: $this->validatePasswordStrengthMinScore);
        }

        $errors = $this->validator->validate($plainPassword, $validators);
        if (count($errors) > 0) {
            $previous = new ValidatorException((string)$errors);
            throw new CommandRuntimeException($previous->getMessage(), displayMessage: true, code: $previous->getCode(), previous: $previous);
        }
    }
}

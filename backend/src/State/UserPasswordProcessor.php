<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $persistProcessor,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof User && $data->getPassword()) {
            // Hash password only if it is not already hashed (starting with $)
            if (!str_starts_with($data->getPassword(), '$')) {
                $hashed = $this->passwordHasher->hashPassword($data, $data->getPassword());
                $data->setPassword($hashed);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}

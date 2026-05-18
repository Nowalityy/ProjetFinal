<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\AlertComment;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @implements ProcessorInterface<object, mixed>
 */
final class ApiDoctrinePersistDecorator implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<object, mixed> $persistProcessor
     */
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private UserPasswordHasherInterface $passwordHasher,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof User) {
            $plain = $data->getPlainPassword();
            if (null !== $plain && '' !== $plain) {
                $data->setPassword($this->passwordHasher->hashPassword($data, $plain));
                $data->setPlainPassword(null);
            }
        }

        if ($data instanceof AlertComment) {
            $actor = $this->security->getUser();
            if ($actor instanceof User) {
                $data->setAuthor($actor);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}

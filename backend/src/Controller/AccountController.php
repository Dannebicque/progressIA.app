<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Lets the authenticated user update their own profile (name, email, password).
 */
#[AsController]
final class AccountController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ValidatorInterface $validator,
        private readonly UserRepository $users,
        private readonly Security $security,
    ) {
    }

    #[Route('/api/account', name: 'api_account_update', methods: ['PATCH'])]
    public function __invoke(Request $request): JsonResponse
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new UnauthorizedHttpException('Bearer', 'Authentification requise.');
        }

        $data = json_decode($request->getContent(), true) ?: [];
        $errors = [];

        if (array_key_exists('name', $data)) {
            $name = trim((string) $data['name']);
            if ('' === $name) {
                $errors['name'] = 'Le nom est requis.';
            } else {
                $user->setName($name);
            }
        }

        if (array_key_exists('email', $data)) {
            $email = trim((string) $data['email']);
            if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Email invalide.';
            } elseif ($email !== $user->getEmail()) {
                $existing = $this->users->findOneBy(['email' => $email]);
                if ($existing && $existing->getId() !== $user->getId()) {
                    $errors['email'] = 'Cet email est déjà utilisé.';
                } else {
                    $user->setEmail($email);
                }
            }
        }

        if (!empty($data['password'])) {
            $password = (string) $data['password'];
            if (strlen($password) < 6) {
                $errors['password'] = 'Le mot de passe doit faire au moins 6 caractères.';
            } else {
                $user->setPassword($this->hasher->hashPassword($user, $password));
            }
        }

        if ($errors) {
            return new JsonResponse(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            $out = [];
            foreach ($violations as $v) {
                $out[$v->getPropertyPath()] = $v->getMessage();
            }

            return new JsonResponse(['errors' => $out], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->flush();

        return new JsonResponse([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'points' => $user->getPoints(),
        ]);
    }
}

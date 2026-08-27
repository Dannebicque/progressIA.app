<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Public self-registration. Creates a ROLE_STUDENT account.
 */
#[AsController]
final class RegisterController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $hasher,
        private readonly ValidatorInterface $validator,
        private readonly UserRepository $users,
    ) {
    }

    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?: [];
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $name = trim((string) ($data['name'] ?? ''));
        $invitationCode = trim((string) ($data['invitationCode'] ?? ''));

        $errors = [];
        if ('' === $name) {
            $errors['name'] = 'Le nom est requis.';
        }
        if (!filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email invalide.';
        }
        if (strlen($password) < 6) {
            $errors['password'] = 'Le mot de passe doit faire au moins 6 caractères.';
        }
        if ($errors) {
            return new JsonResponse(['errors' => $errors], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        if ($this->users->findOneBy(['email' => $email])) {
            return new JsonResponse(['errors' => ['email' => 'Un compte existe déjà avec cet email.']], JsonResponse::HTTP_CONFLICT);
        }

        $institution = null;
        if ('' !== $invitationCode) {
            $institution = $this->em->getRepository(\App\Entity\Institution::class)->findOneBy(['invitationCode' => $invitationCode]);
            if (!$institution) {
                return new JsonResponse(['errors' => ['invitationCode' => 'Code d\'invitation invalide.']], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
        } else {
            // Auto-detect based on email domain
            $parts = explode('@', $email);
            if (count($parts) === 2) {
                $domain = strtolower($parts[1]);
                $allInsts = $this->em->getRepository(\App\Entity\Institution::class)->findAll();
                foreach ($allInsts as $inst) {
                    $domains = $inst->getEmailDomains() ?: [];
                    foreach ($domains as $d) {
                        if (strtolower(trim($d)) === $domain) {
                            $institution = $inst;
                            break 2;
                        }
                    }
                }
            }
        }

        $user = (new User())
            ->setEmail($email)
            ->setName($name)
            ->setRoles(['ROLE_STUDENT']);
        $user->setPassword($this->hasher->hashPassword($user, $password));

        if ($institution) {
            $user->setInstitution($institution);
        }

        $violations = $this->validator->validate($user);
        if (count($violations) > 0) {
            $out = [];
            foreach ($violations as $v) {
                $out[$v->getPropertyPath()] = $v->getMessage();
            }

            return new JsonResponse(['errors' => $out], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->persist($user);
        $this->em->flush();

        return new JsonResponse($this->serialize($user), JsonResponse::HTTP_CREATED);
    }

    private function serialize(User $user): array
    {
        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'points' => $user->getPoints(),
            'institution' => $user->getInstitution() ? [
                'id' => $user->getInstitution()->getId(),
                'name' => $user->getInstitution()->getName(),
            ] : null,
        ];
    }
}

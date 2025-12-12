<?php

namespace App\Service;

use App\Entity\ActivityLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

final class ActivityLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private Security $security,
        private RequestStack $requestStack
    ) {}

    public function log(string $action, ?string $context = null, ?string $description = null): void
    {
        $log = new ActivityLog();
        $user = $this->security->getUser();

        if ($user instanceof \App\Entity\User) {
            $log->setUser($user);
        }

        $log->setAction($action);
        $log->setContext($context);
        $log->setDescription($description);

        $req = $this->requestStack->getCurrentRequest();
        if ($req) {
            $log->setIpAddress($req->getClientIp());
        }

        $this->em->persist($log);
        $this->em->flush();
    }
}

<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Alert;
use App\Entity\AlertComment;
use App\Entity\AuthLog;
use App\Entity\IpReputationCache;
use App\Entity\User;
use App\Enum\AlertSeverity;
use App\Enum\AlertStatus;
use App\Enum\AlertType;
use App\Enum\AuthStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/** Données de démo avec scénarios de détection (RGPD-compatible). */
class AppFixtures extends Fixture
{
    private const DEMO_PASSWORD = 'Admin12345!';

    private const BAD_IP_BURST = '198.51.100.77';

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $users = [];
        $spec = [
            ['admin@minisoc.local', ['ROLE_ADMIN']],
            ['alice.analyste@minisoc.local', ['ROLE_ANALYSTE']],
            ['bob.analyste@minisoc.local', ['ROLE_ANALYSTE']],
            ['claire.auditrice@minisoc.local', ['ROLE_AUDITEUR']],
            ['dan.analyste@minisoc.local', ['ROLE_ANALYSTE']],
            ['eve.auditrice@minisoc.local', ['ROLE_AUDITEUR']],
            ['frank.analyste@minisoc.local', ['ROLE_ANALYSTE']],
            ['gina.admin@minisoc.local', ['ROLE_ANALYSTE', 'ROLE_ADMIN']],
            ['hector.auditeur@minisoc.local', ['ROLE_AUDITEUR']],
            ['ida.analyste@minisoc.local', ['ROLE_ANALYSTE']],
        ];

        foreach ($spec as [$mail, $roles]) {
            $u = new User();
            $u->setEmail($mail)->setRoles($roles);
            $u->setPassword($this->passwordHasher->hashPassword($u, self::DEMO_PASSWORD));
            $manager->persist($u);
            $users[] = $u;
        }
        $manager->flush();

        $authLogs = [];

        for ($idx = 0; $idx < 500; ++$idx) {
            $log = new AuthLog();

            $ipPool = random_int(1, 254).'.'.random_int(1, 254).'.'.random_int(1, 254).'.'.random_int(1, 254);
            $ip = (random_int(0, 15) === 0) ? self::BAD_IP_BURST : $ipPool;

            $rnd = random_int(0, 100);
            if ($rnd <= 82) {
                $status = AuthStatus::Success;
            } elseif ($rnd <= 95) {
                $status = AuthStatus::Failed;
            } else {
                $status = AuthStatus::Blocked;
            }

            $emailForHash = AuthStatus::Success === $status
                ? $users[$idx % \count($users)]->getEmail()
                : 'rnd'.($idx % 25).'@fuzz.invalid';

            $dt = new \DateTimeImmutable('now');
            $dt = $dt->modify(sprintf('-%d hours', random_int(1, 720)));

            $log->setIp($ip);
            $log->setUserAgent('fixture-agent-'.($idx % 80));
            $log->setEmailHash(hash('sha256', strtolower($emailForHash)));
            $log->setStatus($status);
            $log->setCreatedAt($dt);

            $manager->persist($log);
            $authLogs[] = $log;

            if (0 === $idx % 75) {
                $manager->flush();
            }
        }
        $manager->flush();

        $anchor = new \DateTimeImmutable('-3 minutes');
        foreach (range(0, 7) as $i) {
            $bl = new AuthLog();
            $bl->setIp(self::BAD_IP_BURST);
            $bl->setEmailHash(hash('sha256', 'burst'.($i % 3).'@invalid.test'));
            $bl->setStatus(AuthStatus::Failed);
            $bl->setUserAgent('bruteforce-bot');
            $bl->setCreatedAt($anchor->modify(sprintf('+%d seconds', $i * 35)));
            $manager->persist($bl);
            $authLogs[] = $bl;
        }
        $manager->flush();

        $alerts = [];
        for ($i = 0; $i < 50; ++$i) {
            $logPick = $authLogs[($i * 137) % \count($authLogs)];

            $alert = new Alert();
            $alert->setType(AlertType::cases()[$i % \count(AlertType::cases())]);
            $alert->setSeverity(AlertSeverity::cases()[$i % \count(AlertSeverity::cases())]);
            $alert->setStatus(AlertStatus::cases()[$i % \count(AlertStatus::cases())]);
            $alert->setDescription('Alerte fixture '.$i);
            $alert->setAuthLog($logPick);
            if (0 !== $i % 3) {
                $alert->setAssignedUser($users[$i % \count($users)]);
            }
            $alert->setCreatedAt((new \DateTimeImmutable())->modify(sprintf('-%d days', $i % 18)));

            if (AlertStatus::Resolved === $alert->getStatus()) {
                $alert->setResolvedAt((new \DateTimeImmutable())->modify(sprintf('-%d days', ($i % 5) + 1)));
            }

            $manager->persist($alert);
            $alerts[] = $alert;
        }
        $manager->flush();

        for ($ci = 0; $ci < 120; ++$ci) {
            $alert = $alerts[$ci % \count($alerts)];

            $c = new AlertComment();
            $c->setContent('Commentaire workflow #'.$ci.' (fixtures).');
            $c->setAlert($alert);
            $c->setAuthor($users[$ci % \count($users)]);
            $manager->persist($c);

            if (0 === $ci % 35) {
                $manager->flush();
            }
        }
        $manager->flush();

        $malicious = new IpReputationCache();
        $malicious->setIp(self::BAD_IP_BURST);
        $malicious->setScore(92);
        $malicious->setCountry('TST');
        $malicious->setIsp('Fixture ASN');
        $malicious->setTor(false);
        $malicious->setVpn(true);
        $malicious->setLastChecked(new \DateTimeImmutable());
        $manager->persist($malicious);

        $clean = new IpReputationCache();
        $clean->setIp('203.0.113.99');
        $clean->setScore(10);
        $clean->setLastChecked(new \DateTimeImmutable());
        $manager->persist($clean);

        $manager->flush();
    }
}

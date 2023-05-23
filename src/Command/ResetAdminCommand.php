<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:reset-admin',
    description: 'Reset password ou creation de compte admin',
)]
class ResetAdminCommand extends Command
{

    private $em;
    private $userPasswordHasher;

    public function __construct(EntityManagerInterface $em, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->em = $em;
        $this->userPasswordHasher = $userPasswordHasher;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'email')
            ->addArgument('nom', InputArgument::REQUIRED, 'nom')
            ->addArgument('prenom', InputArgument::REQUIRED, 'prenom')
            ->addArgument('password', InputArgument::REQUIRED, 'password')
            //->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var User $user */
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $input->getArgument('email')]);
        if(!$user){
            $user = new User();
            $user->setEmail($input->getArgument('email'));
            $user->setNom($input->getArgument('nom'));
            $user->setPrenom($input->getArgument('prenom'));
        }
        $pass = $this->userPasswordHasher->hashPassword($user, $input->getArgument('password'));
        $user->setPassword($pass);
        $user->setRoles(['ROLE_ADMIN']);
        $this->em->persist($user);
        $this->em->flush();
        $io = new SymfonyStyle($input, $output);

        $io->success('Compte mis à jour');

        return Command::SUCCESS;
    }
}

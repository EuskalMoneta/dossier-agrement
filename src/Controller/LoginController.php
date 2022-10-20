<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Csrf\TokenGenerator\TokenGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('login/login.html.twig', [
            'last_username' => $lastUsername,
            'error'         => $error,
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
    }

    #[Route('/resetting/request', name: 'fos_user_resetting_request')]
    public function forgottenPassword(Request $request,MailerInterface $mailer, TokenGeneratorInterface $tokenGenerator, EntityManagerInterface $entityManager): Response
    {
        if ($request->isMethod('POST')) {

            $email = $request->request->get('email');
            /** @var User $user */
            $user = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user === null) {
                $this->addFlash('danger', 'Email Inconnu');
                return $this->redirectToRoute('fos_user_resetting_request');
            }
            $token = $tokenGenerator->generateToken();

            try{
                $user->setConfirmationToken($token);
                $entityManager->flush();
            } catch (\Exception $e) {
                $this->addFlash('warning', $e->getMessage());
                return $this->redirectToRoute('fos_user_resetting_request');
            }

            $url = $this->generateUrl('app_reset_password', array('token' => $token), UrlGeneratorInterface::ABSOLUTE_URL);

            $body = "Bonjour,<br /><br /> Vous pouvez changer votre mot de passe en suivant le lien suivant <a href=\"" . $url. '">'.$url.'</a> <br /><br /> <br /><br />';
            $email = (new TemplatedEmail())
                ->from('contact@euskal-moneta.org')
                ->to($user->getEmail())
                ->subject('Demande de nouveau mot de passe')
                ->htmlTemplate('email/email_notification.html.twig')
                ->context(['body' => $body])
            ;

            $mailer->send($email);

            $this->addFlash('info', 'Un email permettant de réinitialiser votre mot de passe vous a été envoyé');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('login/forgotten_password.html.twig');
    }

    #[Route('/reset_password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, string $token, EntityManagerInterface $entityManager, UserPasswordHasherInterface $userPasswordHasher)
    {

        if ($request->isMethod('POST')) {

            $user = $entityManager->getRepository(User::class)->findOneBy(['confirmationToken' => $token]);
            /* @var $user User */

            if ($user === null) {
                $this->addFlash('danger', 'Token Inconnu');
                return $this->redirectToRoute('fos_user_resetting_request');
            }

            if($request->request->get('password') != $request->request->get('password2')){
                $this->addFlash('danger', 'Les deux mots de passe ne correspondent pas');
            } else {
                $user->setConfirmationToken(null);
                $user->setPassword(
                    $userPasswordHasher->hashPassword(
                        $user,
                        $request->get('password')
                    )
                );

                $entityManager->persist($user);
                $entityManager->flush();

                $this->addFlash('info', 'Mot de passe mis à jour, vous pouvez vous connecter !');

                return $this->redirectToRoute('app_login');
            }
        }
        return $this->render('login/resetting_password.html.twig', ['token' => $token]);

    }
}

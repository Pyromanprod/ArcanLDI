<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Membership;
use App\Entity\MembershipAssociation;
use App\Entity\News;
use App\Entity\Order;
use App\Entity\Presentation;
use App\Entity\User;
use App\Form\ContactType;
use App\Form\MemberAssociationType;
use App\Recaptcha\Recaptcha;
use App\Repository\GameRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(): Response
    {
        $repos = $this->getDoctrine()->getRepository(Game::class);
        $reposnews = $this->getDoctrine()->getRepository(News::class);
        $presentationRepository = $this->getDoctrine()->getRepository(Presentation::class);
        $order = $this->getDoctrine()->getRepository(Order::class);
        $member = $this->getDoctrine()->getRepository(MembershipAssociation::class);
        $membership = $this->getDoctrine()->getRepository(Membership::class);
        $orders = $order->findRefundRequestedOrder();
        $allGames = $repos->findLast();
        $news = $reposnews->findLast();
        $paid = $member->findByPlayerNotPaid($this->getUser(), $membership->findOneBylast());

        $presentation = $presentationRepository->findOneBy([], ['id' => 'DESC']);
        return $this->render('main/index.html.twig',
            [
                'paid' => $paid,
                'requestedRefund' => $orders,
                'news' => $news,
                'allGames' => $allGames,
                'presentation' => $presentation,
            ]
        );
    }


    #[Route('search', name: 'search')]
    public function search(GameRepository $gameRepository, Request $request): Response
    {
        $allGames = $gameRepository->search($request->query->get('q'));
        return $this->render("game/index.html.twig", [
            'allGames' => $allGames
        ]);
    }

    #[Route('clear', name: 'game_clear', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function clear(GameRepository $gameRepository, EntityManagerInterface $entityManager): Response
    {
        $games = $gameRepository->findOneOutdatedGame(new \DateTime());
        foreach ($games as $game) {
            foreach ($game->getRoleGroupes() as $role) {
                $entityManager->remove($role);
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('admin_jeu', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('nous-contactez', name: 'contact_us', methods: ['GET', 'POST'])]
    public function contactUs(MailerInterface $mailer, Request $request, Recaptcha $recaptcha): Response
    {

        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $recaptchaResponse = $request->request->get('g-recaptcha-response', null);

            // Si le captcha n'est pas valide, on crée une nouvelle erreur dans le formulaire (ce qui l'empêchera de créer l'article et affichera l'erreur)
// $request->server->get('REMOTE_ADDR') -----> Adresse IP de l'utilisateur dont la méthode verify() a besoin
            if ($recaptchaResponse == null || !$recaptcha->verify($recaptchaResponse, $request->server->get('REMOTE_ADDR'))) {

                // Ajout d'une nouvelle erreur manuellement dans le formulaire
                $form->addError(new FormError('Le Captcha doit être validé !'));
            }
            if ($form->isValid()) {
                $email = (new Email())
                    ->from($form->get('Email')->getData())
                    ->to('contact@arcanlesdemonsdivoire.fr')
                    ->subject($form->get('Object')->getData())
                    ->text($form->get('Content')->getData());
                $mailer->send($email);
                $this->addFlash('success', 'Message envoyé avec succès');
                return $this->redirectToRoute('home');
            }
        }
        return $this->renderForm('main/contact.html.twig', [
            'form' => $form
        ]);
    }
}

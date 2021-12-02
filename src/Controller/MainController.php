<?php

namespace App\Controller;

use App\Form\AlbumPhotoFormType;
use App\Form\ContactType;
use App\Recaptcha\Recaptcha;
use App\Repository\GameRepository;
use App\Repository\MembershipAssociationRepository;
use App\Repository\MembershipRepository;
use App\Repository\NewsRepository;
use App\Repository\OrderRepository;
use App\Repository\PictureForAdminRepository;
use App\Repository\PresentationRepository;
use App\Service\uploadGamePhoto;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{

    #[Route('/', name: 'home')]
    public function index(GameRepository                  $gameRepository,
                          OrderRepository                 $orderRepository,
                          NewsRepository                  $newsRepository,
                          PresentationRepository          $presentationRepository,
                          MembershipAssociationRepository $membershipAssociationRepository,
                          MembershipRepository            $membershipRepository,


    ): Response
    {
        $orders = $orderRepository->findRefundRequestedOrder();
        $allGames = $gameRepository->findLast();
        $news = $newsRepository->findLast();
        $paid = $membershipAssociationRepository->findByPlayerNotPaid($this->getUser(), $membershipRepository->findOneBylast());
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

    #[Route('/upload-photo-pour-nous/', name: 'upload_photo_pour_ckeditor', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function upload_photo(EntityManagerInterface $entityManager,uploadGamePhoto $uploadGamePhoto,Request $request, PaginatorInterface $paginator,PictureForAdminRepository $pictureForAdminRepository): Response
    {

        $form = $this->createForm(AlbumPhotoFormType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted()) {


            $listePhoto = $uploadGamePhoto->uploadAdminPhoto($form->get('photos')->getData());
            foreach ($listePhoto as $photo) {
                $entityManager->persist($photo);
            }
            $entityManager->flush();
            return $this->redirectToRoute('upload_photo_pour_ckeditor');

        }
        $requestedPage = $request->query->getInt('page', 1);
        if ($requestedPage < 1) {
            throw new NotFoundHttpException();
        }
        $pictures = $paginator->paginate(
            $pictureForAdminRepository->findAll(),
            $requestedPage,
            16
        );

        return $this->render('main/upload_photo.html.twig', [
            'pictures' => $pictures,
            'form' => $form->createView(),
        ]);
    }

    #[Route('recherche', name: 'search')]
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


    #[Route('contactez-nous', name: 'contact_us', methods: ['GET', 'POST'])]
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
                    ->subject('[contact] ' . $form->get('Object')->getData())
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

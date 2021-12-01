<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Order;
use App\Form\OrderType;
use App\Form\UserRefundFormType;
use App\Repository\AnswerRepository;
use App\Repository\OrderRepository;
use App\Repository\RoleGroupeRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Stripe\Checkout\Session;
use Stripe\Refund;
use Stripe\Stripe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/achat')]
class OrderController extends AbstractController
{
    // crud des order

    #[Route('/', name: 'order_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findByPaid(),
        ]);
    }

    //liste des commande pour les user
    #[Route('-utilisateur', name: 'user_order', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function userOrder(OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findByPlayer($this->getUser());

        return $this->render('order/index.html.twig', [
            'orders' => $order
        ]);


    }

    //liste des demandes de remboursement
    #[Route('-demande-de-remboursement', name: 'order_refund_requested', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function refundRequested(OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findRefundRequestedOrder();

        return $this->render('order/index_refund_requested.html.twig', [
            'orders' => $order
        ]);


    }

    //demande de remboursement
    #[Route('-demande-remboursement/{id}', name: 'user_refund', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function userRefund(Order $order,
                               OrderRepository $orderRepository,
                               Request $request,
                               EntityManagerInterface $entityManager,
                               MailerInterface $mailer): Response
    {

        //comparaison entre le mail de la commande et celui de l'utilisateur qui fait la demande
        // de remboursement redirection vers ces order sinon
        if ($order->getPlayer()->getEmail() == $this->getUser()->getUserIdentifier()) {
            $form = $this->createForm(UserRefundFormType::class, $order);
            $form->handleRequest($request);
            //verification si le formulaire est valide et bien envoyer ainsi que comparaison entre le user qui demande
            // l'order et celui a qui appartien celui ci comparaison si la date du jeu lié au ticket est superieur a
            // la date d'aujourd'hui pour demander un remboursement
            if ($form->isSubmitted() && $form->isValid() && $order->getPlayer() == $this->getUser()
                && $order->getTicket()->getGame()->getDateStart() > new \DateTime()) {

                //envoie d'un mail a l'utilisateur et un autre a l'adresse mail du site
                $email = (new Email())
                    ->from('contact@arcanlesdemonsdivoire.fr')
                    ->to($this->getUser()->getUserIdentifier())
                    ->subject('remboursement ArcanLDI')
                    ->text('votre demande de remboursement a bien été prise en compte');

                $emailNotif = (new Email())
                    ->from('contact@arcanlesdemonsdivoire.fr')
                    ->to('contact@arcanlesdemonsdivoire.fr')
                    ->subject('[remboursement] demande de remboursement par ' . $order->getPlayer()->getPseudo())
                    ->text(' demande de remboursement pour le jeu ' . $order->getTicket()->getGame()->getName()
                        . ' ticket ' . $order->getTicket()->getName() . ' par le joueur ' . $order->getPlayer()->getPseudo()
                        . ' email du joueur ' . $order->getPlayer()->getEmail())
                    ;

                $mailer->send($email);
                $mailer->send($emailNotif);
                $order->getTicket()->setStock($order->getTicket()->getStock() + 1);
                $entityManager->flush();
                $this->addFlash('success', 'Demande de remboursement effectuée');
                return $this->redirectToRoute('home');
            }
            return $this->renderForm('order/user_refund.html.twig', [
                'disclaimer' => $order->getTicket()->getGame()->getDisclaimer(),
                'form' => $form
            ]);


        } else {
            $order = $orderRepository->findByPlayer($this->getUser());
            return $this->redirectToRoute('user_order', [
                'orders' => $order
            ]);

        }


    }

    //achat des tickets
    #[IsGranted('ROLE_USER')]
    #[Route('/acheter/{slug}', name: 'order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Game $game, OrderRepository $orderRepository, EntityManagerInterface $entityManager): Response
    {
        $order = new Order();
        //creation du formulaire avec passage de paramètre
        $form = $this->createForm(OrderType::class,$order,[
            'choice'=> $game->getTickets(),
        ]);
        $form->handleRequest($request);
        $variable = false;
        foreach($game->getTickets() as $ticket){
           if($ticket->getStock() > 0){
               $variable = true;
           }
    }
        if (!$variable){
            $this->addFlash('error','Les tickets sont partis trop tôt !');
          return  $this->redirectToRoute('game_index');
        }
        if ($form->isSubmitted() && $form->isValid()) {
            if ($order->getTicket()->getGame()->getDateEnd() > new \DateTime()) {
                $reservation = $orderRepository->findOneorder($game, $this->getUser());

                if ($reservation !== null) {


                    //si la date de paiement est null on renvoie vers le questionnaire avec le message flash
                    if ($reservation->getDatePaid() == NULL) {

                        $this->addFlash('error', 'Vous avez déjà une commande en cours pour ce jeu.');

                        return $this->redirectToRoute('user_order', [], Response::HTTP_SEE_OTHER);

                    } else {

                        $this->addFlash('error', 'Vous ne pouvez acheter qu\'un seul ticket par jeu de rôle.');
                    }
                    return $this->redirectToRoute('home');
                    //si la reservation n'existe pas alors celle ci se
                    // créer et est flush dans la base de données
                } else {

                    $order->setPlayer($this->getUser());
                    $order->setTotal($order->getTicket()->getPrice());
                    $entityManager->persist($order);
                    $entityManager->flush();

                    return $this->redirectToRoute('survey_suvey_for_ticket', [
                        'id' => $order->getId(),
                    ], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', 'Ce ticket est épuisé.');
            }


        }
        return $this->renderForm('order/new.html.twig', ['order' => $order,
            'game' => $game,
            'form' => $form,]);
    }


    #[Route('/{id}/paiement', name: 'checkout', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    //$stripeSk provient de service.yaml grace au bind
    public function checkout(Order $order, $stripeSK, EntityManagerInterface $entityManager): Response
    {

        if ($order->getTicket()->getStock() > 0) {
            //déclaration de stripe avec la Stripekey
            Stripe::setApiKey($stripeSK);

            //creation de l'object paiemeent
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => $order->getTicket()->getName(),
                        ],
                        'unit_amount' => $order->getTotal() * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('success', ['id' => $order->getId()],
                        UrlGeneratorInterface::ABSOLUTE_URL)
                    . '?session_id={CHECKOUT_SESSION_ID}',

                'cancel_url' => $this->generateUrl('cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            //creation de l'url de paiement ver la quelle l'utilisateur est dirigé pour payer
            return $this->redirect($session->url, 303);
        } else {
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('error', 'Le ticket est épuisé.');

            return $this->redirectToRoute('home');
        }
    }

    #[Route('-annuler/', name: 'cancel', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancel(): Response
    {


        $this->addFlash('error', 'Achat annulé');

        return $this->redirectToRoute('home');

    }

    #[Route('annuler-un-achat/{id}', name: 'cancel_order', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function cancelOrder(Order                  $order,
                                Request                $request,
                                EntityManagerInterface $entityManager,
                                AnswerRepository       $answerRepository): Response
    {


        if ($this->isCsrfTokenValid('cancelOrder' . $order->getId(), $request->request->get('_token')) && $order->getDatePaid() == null) {
            foreach ( $answerRepository->findByUserGame($order->getTicket()->getGame(), $this->getUser()) as $answer){
                $entityManager->remove($answer);
            }
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('success', 'Achat annulé');
        } else {
            $this->addFlash('error', 'Impossible d\'annuler la commande.');
        }

        return $this->redirectToRoute('home');

    }

    #[Route('-reussite/{id}/', name: 'success', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function success(Request $request, Order $order, $stripeSK, EntityManagerInterface $entityManager, RoleGroupeRepository $groupeRepository, MailerInterface $mailer): Response
    {
        //déclaration de la clé stripe
        Stripe::setApiKey($stripeSK);
        //ouverture de la session stripe ainsi que la recuperation des informations de l'achat
        $session = Session::retrieve($request->query->get('session_id'));

        //si la commande est bien payer
        if ($session->payment_status == 'paid') {
            $this->getUser()->addRoleGroupe($groupeRepository->findOneByName('public'));
            $order->getTicket()->setStock($order->getTicket()->getStock() - 1);
            $order->setDatePaid(new \DateTime());
            $order->setReference($session->id);
            $order->setPaymentIntent($session->payment_intent);
            $entityManager->flush();
            //envoie de mail a l'utilisateur et a l'admin du site
            $emailsold = (new TemplatedEmail())
                ->from('contact@arcanlesdemonsdivoire.fr')
                ->to($order->getPlayer()->getEmail())
                ->subject('Achat d\'un ticket ArcanLDI ' . ' jeu ' . $order->getTicket()->getGame()->getName()
                    . ' ticket ' . $order->getTicket()->getName())
                ->htmlTemplate('emails/ticket.html.twig')
                ->context([
                    'ticketName' => $order->getTicket()->getName(),
                    'gameName' => $order->getTicket()->getGame()->getName(),
                    'ticketId' => $order->getTicket()->getId(),
                    'gameId' => $order->getTicket()->getGame()->getId(),
                    'playerId' => $order->getPlayer()->getId()
                ]);
            $emailNotif = (new Email())
                ->from('contact@arcanlesdemonsdivoire.fr')
                ->to('contact@arcanlesdemonsdivoire.fr')
                ->subject('[Billeterie]'.$order->getPlayer()->getPseudo() . ' a acheter un ticket pour le jeu '
                    . $order->getTicket()->getGame()->getName())

                ->text('le joueur ' . $order->getPlayer()->getPseudo() . ' à acheter un ticket '
                    . $order->getTicket()->getName() . ' pour le jeu ' . $order->getTicket()->getGame()->getName());

            $mailer->send($emailsold);
            $mailer->send($emailNotif);

            $this->addFlash('success', 'Ticket acheté avec succès.');

        }
        return $this->redirectToRoute('thank_you');

    }

    #[Route('/merci', name: 'thank_you', methods: ['GET', 'POST'])]
    public function thankYou(): Response
    {

        return $this->render('order/success.html.twig');


    }


    #[Route('-remboursement/{id}', name: 'refund', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function refund($stripeSK,
                           Order $order,
                           EntityManagerInterface $entityManager,
                           Request $request, MailerInterface $mailer,
                           AnswerRepository $answerRepository): Response
    {
        if ($this->isCsrfTokenValid('refund' . $order->getId(), $request->request->get('_token'))) {
            //declaration de la clé secrete de stripe
            Stripe::setApiKey($stripeSK);
            //creation du remboursement avec le PayementIntent
            Refund::create([
                'payment_intent' => $order->getPaymentIntent(),
            ]);
            //retrait des role de l'utilisateur
            foreach ($order->getPlayer()->getRoleGroupes() as $role) {
                $order->getPlayer()->removeRoleGroupe($role);
            }
            //supression des reponse de l'utilisateur
            $answers = $answerRepository->findByUserGame($order->getTicket()->getGame(),$order->getPlayer());
            foreach ($answers as $answer){
                $entityManager->remove($answer);
            }
            //suppression de l'achat
            $entityManager->remove($order);
            $entityManager->flush();
            //envoie de mail automatique ou avec une raison si l'admin l'a renseigné
            if ($request->request->get('reason')) {
                $email = (new Email())
                    ->from('contact@arcanlesdemonsdivoire.fr')
                    ->to($order->getPlayer()->getEmail())
                    ->subject('remboursement ArcanLDI ' . ' jeu ' . $order->getTicket()->getGame()->getName()
                        . ' ticket ' . $order->getTicket()->getName())
                    ->text($request->request->get('reason'));
            } else {
                $email = (new TemplatedEmail())
                    ->from('contact@arcanlesdemonsdivoire.fr')
                    ->to($order->getPlayer()->getEmail())
                    ->subject('remboursement ArcanLDI ' . ' jeu ' . $order->getTicket()->getGame()->getName()
                        . ' ticket ' . $order->getTicket()->getName())
                    ->htmlTemplate('emails/ticket_refund_accept.html.twig');
            }
            $mailer->send($email);

            $this->addFlash('success', 'Ticket remboursé avec succès.');
        } else {
            $this->addFlash('error', 'Une erreur s\'est produite, veuillez réessayer.');
        }
        return $this->redirectToRoute('home');


    }

    #[Route('-refus-remboursement/{id}', name: 'reject_refund', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function rejectRefund(Order $order,
                                 EntityManagerInterface $entityManager,
                                 Request $request,
                                 MailerInterface $mailer): Response
    {
        if ($this->isCsrfTokenValid('rejectRefund' . $order->getId(), $request->request->get('_token'))) {
            //remise dans le stock d'un ticket remboursé et supression de l'order
            $order->getTicket()->setStock($order->getTicket()->getStock() - 1);
            if ($request->request->get('reason')) {
                $email = (new Email())
                    ->from('contact@arcanlesdemonsdivoire.fr')
                    ->to($order->getPlayer()->getEmail())
                    ->subject('remboursement ArcanLDI ' . ' jeu ' . $order->getTicket()->getGame()->getName()
                        . ' ticket ' . $order->getTicket()->getName())
                    ->text($request->request->get('reason'));
            } else {
                $email = (new TemplatedEmail())
                    ->from('contact@arcanlesdemonsdivoire.fr')
                    ->to($order->getPlayer()->getEmail())
                    ->subject('remboursement ArcanLDI ' . ' jeu ' . $order->getTicket()->getGame()->getName()
                        . ' ticket ' . $order->getTicket()->getName())
                    ->htmlTemplate('emails/ticket_refund_reject.html.twig');
            }
            $mailer->send($email);
            $order->setRefundRequest('rejected');
            $entityManager->flush();
            $this->addFlash('success', 'La demande de remboursement a été refusée.');
        } else {
            $this->addFlash('error', 'Une erreur s\'est produite, veuillez réessayer.');
        }
        return $this->redirectToRoute('home');


    }

}

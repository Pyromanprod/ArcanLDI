<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Order;
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

#[Route('/order')]
class OrderController extends AbstractController
{
    #[Route('/', name: 'order_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('order/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('-user', name: 'user_order', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function userOrder(OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findByPlayer($this->getUser());

        return $this->render('order/index.html.twig', [
            'orders' => $order
        ]);


    }

    #[Route('-requested-refund', name: 'order_refund_requested', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function refundRequested(OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->findRefundRequestedOrder();

        return $this->render('order/index_refund_requested.html.twig', [
            'orders' => $order
        ]);


    }

    #[Route('-refund-request/{id}', name: 'user_refund', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function userRefund(Order $order, OrderRepository $orderRepository, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {

        if ($order->getPlayer()->getEmail() == $this->getUser()->getUserIdentifier()) {
            $form = $this->createForm(UserRefundFormType::class, $order);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid() && $order->getPlayer() == $this->getUser() && $order->getTicket()->getGame()->getDateStart() > new \DateTime()) {
                $email = (new Email())
                    ->from('contact@arcanlesdemondivoire.fr')
                    ->to($this->getUser()->getUserIdentifier())
                    ->subject('remboursement ArcanLDI')
                    ->text('votre demande de remboursement a bien été prise en compte');
                $emailNotif = (new Email())
                    ->from('contact@arcanlesdemondivoire.fr')
                    ->to('contact@arcanlesdemondivoire.fr')
                    ->subject('demande de remboursement par ' . $order->getPlayer()->getPseudo())
                    ->text(' demande de remboursement pour le jeu ' . $order->getTicket()->getGame()->getName() . ' ticket ' . $order->getTicket()->getName() . ' par le joueur ' . $order->getPlayer()->getPseudo() . ' email du joueur ' . $order->getPlayer()->getEmail());
                $mailer->send($email);
                $mailer->send($emailNotif);
                $order->getTicket()->setStock($order->getTicket()->getStock() + 1);
                $entityManager->flush();
                $this->addFlash('success', 'demande de remboursement effectuée');
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

    #[IsGranted('ROLE_USER')]
    #[Route('/acheter/{slug}', name: 'order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Game $game, OrderRepository $orderRepository): Response
    {

        $order = new Order();
        $form = $this->createFormBuilder($order)
            ->add('ticket', EntityType::class, [
                'class' => 'App\Entity\Ticket',
                'query_builder' => function (TicketRepository $tr) use ($game) {
                    return $tr->createQueryBuilder('u')
                        ->where('u.game = :game')
                        ->andWhere('u.stock > 0')
                        ->setParameter('game', $game);
                },
            ])->getForm();
        $form->handleRequest($request);
        $variable = false;
        foreach($game->getTickets() as $ticket){
           if($ticket->getStock() > 0){
               $variable = true;
           }
    }
        if (!$variable){
            $this->addFlash('error','les tickets sont partit trop tôt');
          return  $this->redirectToRoute('game_index');
        }
        if ($form->isSubmitted() && $form->isValid()) {
            if ($order->getTicket()->getGame()->getDateEnd() > new \DateTime() || $order->getTicket()->getGame()->getDateEnd() == NULL) {
                $reservation = $orderRepository->findOneorder($game, $this->getUser());

                if ($reservation !== null) {


                    //si la date de paiement est null on renvoie vers le questionnaire avec le message flash
                    if ($reservation->getDatePaid() == NULL) {

                        $this->addFlash('error', 'vous avez déjà une commande en cour pour ce jeu');

                        return $this->redirectToRoute('user_order', [], Response::HTTP_SEE_OTHER);

                    } else {

                        $this->addFlash('error', 'vous ne pouvez acheter qu\'un seul ticket par jeu de rôle');
                    }
                    return $this->redirectToRoute('home');
                    //si la reservation n'existe pas alors celle ci se
                    // créer et est flush dans la base de données
                } else {

                    $entityManager = $this->getDoctrine()->getManager();
                    $order->setPlayer($this->getUser());
                    $order->setTotal($order->getTicket()->getPrice());
                    $entityManager->persist($order);
                    $entityManager->flush();

                    return $this->redirectToRoute('survey_suvey_for_ticket', [
                        'id' => $order->getId(),
                    ], Response::HTTP_SEE_OTHER);
                }
            } else {
                $this->addFlash('error', 'ce ticket est épuisé');
            }


        }
        return $this->renderForm('order/new.html.twig', ['order' => $order,
            'game' => $game,
            'form' => $form,]);
    }


    #[Route('/{id}/paiement', name: 'checkout', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function checkout(Order $order, $stripeSK, EntityManagerInterface $entityManager): Response
    {

        if ($order->getTicket()->getStock() > 0) {
            Stripe::setApiKey($stripeSK);
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
                'success_url' => $this->generateUrl('success', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->generateUrl('cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            return $this->redirect($session->url, 303);
        } else {
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('error', 'le ticket est épuisé');

            return $this->redirectToRoute('home');
        }
    }

    #[Route('-cancel-url/', name: 'cancel', methods: ['GET', 'POST'])]
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
            $entityManager->remove($order);
            foreach ( $answerRepository->findByUserGame($order->getTicket()->getGame(), $this->getUser()) as $answer){
                $entityManager->remove($answer);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Achat annulé');
        } else {
            $this->addFlash('error', 'Impossible d\'annuler la commande');
        }

        return $this->redirectToRoute('home');

    }

    #[Route('-success-url/{id}/', name: 'success', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function success(Request $request, Order $order, $stripeSK, EntityManagerInterface $entityManager, RoleGroupeRepository $groupeRepository, MailerInterface $mailer): Response
    {

        Stripe::setApiKey($stripeSK);
        $session = Session::retrieve($request->query->get('session_id'));
        if ($session->payment_status == 'paid') {
            $this->getUser()->addRoleGroupe($groupeRepository->findOneByName('public'));
            $order->getTicket()->setStock($order->getTicket()->getStock() - 1);
            $order->setDatePaid(new \DateTime());
            $order->setReference($session->id);
            $order->setPaymentIntent($session->payment_intent);
            $entityManager->flush();
            $emailsold = (new TemplatedEmail())
                ->from('contact@arcanlesdemondivoire.fr')
                ->to($order->getPlayer()->getEmail())
                ->subject('Achat d\'un ticket ArcanLDI ' . ' jeu ' . $order->getTicket()->getGame()->getName() . ' ticket ' . $order->getTicket()->getName())
                ->htmlTemplate('emails/ticket.html.twig')
                ->context([
                    'ticketName' => $order->getTicket()->getName(),
                    'gameName' => $order->getTicket()->getGame()->getName(),
                    'ticketId' => $order->getTicket()->getId(),
                    'gameId' => $order->getTicket()->getGame()->getId(),
                    'playerId' => $order->getPlayer()->getId()
                ]);
            $emailNotif = (new Email())
                ->from('contact@arcanlesdemondivoire.fr')
                ->to('contact@arcanlesdemondivoire.fr')
                ->subject($order->getPlayer()->getPseudo() . ' a acheter un ticket pour le jeu ' . $order->getTicket()->getGame()->getName())
                ->text('le joueur ' . $order->getPlayer()->getPseudo() . ' à acheter un ticket ' . $order->getTicket()->getName() . ' pour le jeu ' . $order->getTicket()->getGame()->getName());
            $mailer->send($emailsold);
            $mailer->send($emailNotif);

            $this->addFlash('success', 'ticket acheté avec succès');

        }
        return $this->redirectToRoute('home');

    }

    #[Route('-refund/{id}', name: 'refund', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function refund($stripeSK, Order $order, EntityManagerInterface $entityManager, Request $request, MailerInterface $mailer): Response
    {
        if ($this->isCsrfTokenValid('refund' . $order->getId(), $request->request->get('_token'))) {
            Stripe::setApiKey($stripeSK);
            Refund::create([
                'payment_intent' => $order->getPaymentIntent(),
            ]);
            foreach ($order->getPlayer()->getRoleGroupes() as $role) {
                $order->getPlayer()->removeRoleGroupe($role);
            }
            $entityManager->remove($order);
            $entityManager->flush();
            if ($request->request->get('reason')) {
                $email = (new Email())
                    ->from('contact@arcanlesdemondivoire.fr')
                    ->to($order->getPlayer()->getEmail())
                    ->subject('remboursement ArcanLDI ' . ' jeu ' . $order->getTicket()->getGame()->getName() . ' ticket ' . $order->getTicket()->getName())
                    ->text($request->request->get('reason'));
            } else {
                $email = (new Email())
                    ->from('contact@arcanlesdemondivoire.fr')
                    ->to($order->getPlayer()->getEmail())
                    ->subject('remboursement ArcanLDI ' . ' jeu ' . $order->getTicket()->getGame()->getName() . ' ticket ' . $order->getTicket()->getName())
                    ->text('votre demande de remboursement a été acceptée');
            }
            $mailer->send($email);

            $this->addFlash('success', 'ticket remboursé avec succès');
        } else {
            $this->addFlash('error', 'une erreur s\'est produite veuillez réessayer');
        }
        return $this->redirectToRoute('home');


    }

    #[Route('-reject-refund/{id}', name: 'reject_refund', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function rejectRefund(Order $order, EntityManagerInterface $entityManager, Request $request, MailerInterface $mailer): Response
    {
        if ($this->isCsrfTokenValid('rejectRefund' . $order->getId(), $request->request->get('_token'))) {
            //remise dans le stock d'un ticket remboursé et supression de l'order
            $order->getTicket()->setStock($order->getTicket()->getStock() - 1);
            if ($request->request->get('reason')) {
                $email = (new Email())
                    ->from('contact@arcanlesdemondivoire.fr')
                    ->to($order->getPlayer()->getEmail())
                    ->subject('remboursement ArcanLDI ' . ' jeu ' . $order->getTicket()->getGame()->getName() . ' ticket ' . $order->getTicket()->getName())
                    ->text($request->request->get('reason'));
            } else {
                $email = (new Email())
                    ->from('contact@arcanlesdemondivoire.fr')
                    ->to($order->getPlayer()->getEmail())
                    ->subject('remboursement ArcanLDI')
                    ->text('Votre demande de remboursement a été refusée');
            }
            $mailer->send($email);
            $order->setRefundRequest('rejected');
            $entityManager->flush();
            $this->addFlash('success', 'la demande de remboursement a été refusée');
        } else {
            $this->addFlash('error', 'une erreur s\'est produite veuillez réessayer');
        }
        return $this->redirectToRoute('home');


    }

}

<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Order;
use App\Form\UserRefundFormType;
use App\Repository\OrderRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Stripe\Checkout\Session;
use Stripe\Refund;
use Stripe\Stripe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
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

    #[Route('-refund-request/{id}', name: 'user_refund', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function userRefund(Order $order, OrderRepository $orderRepository, Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {

        if ($order->getPlayer()->getEmail() == $this->getUser()->getUserIdentifier()) {
            $form = $this->createForm(UserRefundFormType::class, $order);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid() && $order->getPlayer() == $this->getUser()) {
                $email = (new Email())
                    ->from('jesuis@uneadresse.fr')
                    ->to($this->getUser()->getUserIdentifier())
                    ->subject('remboursement ArcanLDI')
                    ->text('votre demmande de remboursement a bien etais prise en compte');
                $mailer->send($email);
                $entityManager->flush();
                $this->addFlash('success', 'demande de remboursement effectuer');
                return $this->redirectToRoute('home');
            }
            return $this->renderForm('user_refund.html.twig', [
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
        if ($form->isSubmitted() && $form->isValid()) {
            if ($order->getTicket()->getGame()->getDateEnd() > new \DateTime() || $order->getTicket()->getGame()->getDateEnd() == NULL) {
                $reservation = $orderRepository->findOneorder($game, $this->getUser());
                dump($reservation);

                if ($reservation !== null) {


                    //si la date de paiement est null on renvoie vers le questionnaire avec le message flash
                    if ($reservation->getDatePaid() == NULL) {

                        $this->addFlash('error', 'vous devez finir le questionnaire pour acheter le ticket');

                        return $this->redirectToRoute('survey_suvey_for_ticket', [
                            'id' => $reservation->getId(),
                        ], Response::HTTP_SEE_OTHER);
                    } else {

                        $this->addFlash('error', 'vous ne pouvez acheter qu\'un seul ticket par jeu de role');
                    }
                    return $this->redirectToRoute('home');
                    //si la reservation n'existe pas alors celle ci se
                    // créer et est flush dans la base donées
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


    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }


    #[Route('/{id}/paiement', name: 'checkout', methods: ['GET', 'POST'])]
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
                'cancel_url' => $this->generateUrl('home', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            return $this->redirect($session->url, 303);
        } else {
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('error', 'le ticket est epuisé');

            return $this->redirectToRoute('home');
        }
    }

    #[Route('-success-url/{id}/', name: 'success', methods: ['GET', 'POST'])]
    public function success(Request $request, Order $order, $stripeSK, EntityManagerInterface $entityManager): Response
    {

        Stripe::setApiKey($stripeSK);
        $session = Session::retrieve($request->query->get('session_id'));
        if ($session->payment_status == 'paid') {

            $order->getTicket()->setStock($order->getTicket()->getStock() - 1);
            $order->setDatePaid(new \DateTime());
            $order->setReference($session->id);
            $order->setPaymentIntent($session->payment_intent);
            $entityManager->flush();
            $this->addFlash('success', 'ticket acheter avec succés');

        }
        return $this->redirectToRoute('home');

    }

    #[Route('-refund/{id}', name: 'refund', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function refund($stripeSK, Order $order, EntityManagerInterface $entityManager, Request $request): Response
    {
        if ($this->isCsrfTokenValid('refund' . $order->getId(), $request->request->get('_token'))) {
            Stripe::setApiKey($stripeSK);
            Refund::create([
                'payment_intent' => $order->getPaymentIntent(),
            ]);
            //remise dans le stock d'un ticket remboursé et supression de l'order
            $order->getTicket()->setStock($order->getTicket()->getStock() + 1);
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('success', 'ticket remboursé avec succés');
        } else {
            $this->addFlash('error', 'une erreur c\'est produite veuillez reessayer');
        }
        return $this->redirectToRoute('home');


    }

}

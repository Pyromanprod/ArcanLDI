<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session;
use Stripe\Customer;
use Stripe\Stripe;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGenerator;
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


    #[Route('/new/{slug}', name: 'order_new', methods: ['GET', 'POST'])]
    public function new(Request $request, Game $game, OrderRepository $orderRepository): Response
    {
        $order = new Order();
        $form = $this->createFormBuilder($order)
            ->add('ticket', EntityType::class, [
                'class' => 'App\Entity\Ticket',
                'query_builder' => function (TicketRepository $tr) use ($game) {
                    return $tr->createQueryBuilder('u')
                        ->where('u.game = :game')
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
                $this->addFlash('error', 'vous ne pouvez pas acheter un ticket pour un jeu terminé');
            }


        }
        return $this->renderForm('order/new.html.twig', ['order' => $order,
            'form' => $form,]);
    }


    #[Route('/{id}', name: 'order_show', methods: ['GET'])]
    public function show(Order $order): Response
    {
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'order_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Order $order): Response
    {
        $form = $this->createForm(OrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('order_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('order/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'order_delete', methods: ['POST'])]
    public function delete(Request $request, Order $order): Response
    {
        if ($this->isCsrfTokenValid('delete' . $order->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($order);
            $entityManager->flush();
        }

        return $this->redirectToRoute('order_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/paiement', name: 'checkout', methods: ['GET', 'POST'])]
    public function checkout(Order $order, $stripeSK): Response
    {
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
    }

    #[Route('-success-url/{id}/', name: 'success', methods: ['GET', 'POST'])]
    public function success(Request $request, Order $order, $stripeSK, EntityManagerInterface $entityManager): Response
    {

        Stripe::setApiKey($stripeSK);
        $session = Session::retrieve($request->query->get('session_id'));
        $customer = Customer::retrieve($session->customer);
        if ($session->payment_status == 'paid') {
            $order->getTicket()->setStock($order->getTicket()->getStock()-1);
            $order->setDatePaid(new \DateTime());
            $order->setReference($session->id);
            $entityManager->flush();
            $this->addFlash('success', 'ticket acheter avec succés');
            return $this->redirectToRoute('home');

        } else {
            return $this->redirectToRoute('home');
        }

    }
}

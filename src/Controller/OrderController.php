<?php

namespace App\Controller;

use App\Entity\Game;
use App\Entity\Order;
use App\Form\OrderType;
use App\Repository\OrderRepository;
use App\Repository\TicketRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
    public function new(Request $request,Game $game): Response
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
            ])->getForm()
        ;
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($order->getTicket()->getGame()->getDateEnd() > new \DateTime() || $order->getTicket()->getGame()->getDateEnd() == NULL ){
            $entityManager = $this->getDoctrine()->getManager();
            $order->setPlayer($this->getUser());
            $order->setTotal($order->getTicket()->getPrice());
            $entityManager->persist($order);
            $entityManager->flush();

            return $this->redirectToRoute('checkout', [
                'id' => $order->getId(),
            ], Response::HTTP_SEE_OTHER);
            }else{
                $this->addFlash('error', 'vous ne pouvez pas acheter un ticket pour un jeu terminé');
            }
        }

        return $this->renderForm('order/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
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
    public function checkout(Request $request, Order $order): Response
    {
        if ($this->isCsrfTokenValid('checkout' . $order->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($order);
            $entityManager->flush();
            $this->addFlash('success', 'ticket acheter');
        }

        return $this->render('order/checkout.html.twig');
    }
}

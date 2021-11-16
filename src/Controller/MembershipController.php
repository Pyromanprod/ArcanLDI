<?php

namespace App\Controller;

use App\Entity\Membership;
use App\Entity\MembershipAssociation;
use App\Entity\Order;
use App\Entity\User;
use App\Form\MemberAssociationType;
use App\Form\MembershipType;
use App\Repository\MembershipAssociationRepository;
use App\Repository\MembershipRepository;
use App\Repository\RoleGroupeRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Stripe\Checkout\Session;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/membership')]
class MembershipController extends AbstractController
{
    #[Route('/', name: 'membership_index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(MembershipRepository $membershipRepository): Response
    {
        return $this->render('membership/index.html.twig', [
            'memberships' => $membershipRepository->findAll(),
        ]);
    }
    #[Route('/{id}/{user}/paiement-manuel', name: 'manual_payment', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function manualPayment(Membership $membership,User $user,MembershipAssociationRepository $associationRepository,EntityManagerInterface $entityManager): Response
    {
        $asso = $associationRepository->findByPlayerNotPaid($user,$membership);
        $asso->setPaid(1);
        $entityManager->flush();
        return $this->redirectToRoute('membership_show',[
            'id' => $membership->getId()
        ]);


    }
    #[Route('/{id}/{user}/retrait', name: 'cancel_member', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function cancelMember(Membership $membership,User $user,MembershipAssociationRepository $associationRepository,EntityManagerInterface $entityManager): Response
    {
        $asso = $associationRepository->findByPlayerNotPaid($user,$membership);
        $entityManager->remove($asso);
        $entityManager->flush();
        return $this->redirectToRoute('membership_show',[
            'id' => $membership->getId()
        ]);


    }

    #[Route('/{id}/paiement', name: 'checkout_membership', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function checkoutMembership(Membership $membership, $stripeSK): Response
    {


            Stripe::setApiKey($stripeSK);
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'cotisation association de l\'année'. $membership->getYear(),
                        ],
                        'unit_amount' => $membership->getPrice() * 100,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => $this->generateUrl('success_membership', ['id' => $membership->getId()], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $this->generateUrl('cancel', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);
            return $this->redirect($session->url, 303);


    }

    #[Route('-success-membership-url/{id}/', name: 'success_membership', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function successMembership(Request $request, Membership $membership, $stripeSK, EntityManagerInterface $entityManager, MailerInterface $mailer,MembershipAssociationRepository $associationRepository): Response
    {

        Stripe::setApiKey($stripeSK);
        $session = Session::retrieve($request->query->get('session_id'));
        if ($session->payment_status == 'paid') {
            $asso = $associationRepository->findByPlayerNotPaid($this->getUser(),$membership);
            $asso->setPaid('1');
            $entityManager->flush();
            $email = (new Email())
                ->from('jesuis@uneadresse.fr')
                ->to($this->getUser()->getUserIdentifier())
                ->subject('cotisation payer ' . ' année ' . $membership->getYear() )
                ->text('votre cotisation a bien été payer');
            $mailer->send($email);
            $this->addFlash('success', 'cotisation payer avec succés');

        }
        return $this->redirectToRoute('home');

    }

    #[Route('/new', name: 'membership_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $membership = new Membership();
        $form = $this->createForm(MembershipType::class, $membership);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($membership);
            $entityManager->flush();

            return $this->redirectToRoute('membership_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('membership/new.html.twig', [
            'membership' => $membership,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'membership_show', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function show(Membership $membership,UserRepository $userRepository): Response
    {


        return $this->render('membership/show.html.twig', [
            'members' => $userRepository->findByPlayersNotPaid($membership),
            'membership' => $membership->getId()
        ]);
    }

    #[Route('/{id}/all', name: 'membership_showall', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function showAll(Membership $membership,UserRepository $userRepository): Response
    {


        return $this->render('membership/show.html.twig', [
            'members' => $userRepository->findPlayerIn($membership),
            'membership'=> null
        ]);
    }

    #[Route('/{id}/addMember', name: 'add_member', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function addMember(Membership $membership, Request $request,UserRepository $userRepository): Response
    {



        $form = $this->createForm(MemberAssociationType::class,[],[
            'choice'=> $userRepository->findPlayerNotIn($userRepository->findPlayerIn($membership)) ,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($form->get('member')->getData() as $member){
            $memberAssociation = new MembershipAssociation();
            $memberAssociation
                ->setMember($member)
                ->setMembership($membership)
                ->setPaid(0)
            ;
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($memberAssociation);
            }
            $entityManager->flush();
            return $this->redirectToRoute('add_member',[
                'id' => $membership->getId()
            ]);
        }

        return $this->renderForm('membership/add.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/edit', name: 'membership_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Membership $membership): Response
    {
        $form = $this->createForm(MembershipType::class, $membership);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('membership_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('membership/edit.html.twig', [
            'membership' => $membership,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'membership_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Membership $membership): Response
    {
        if ($this->isCsrfTokenValid('delete' . $membership->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($membership);
            $entityManager->flush();
        }

        return $this->redirectToRoute('membership_index', [], Response::HTTP_SEE_OTHER);
    }
}

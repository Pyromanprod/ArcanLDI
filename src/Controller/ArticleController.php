<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Coment;
use App\Form\ArticleType;
use App\Form\ComentFormType;
use App\Repository\ArticleRepository;
use App\Repository\GameRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/article')]
class ArticleController extends AbstractController
{
    #[Route('/', name: 'article_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function index(ArticleRepository $articleRepository): Response
    {
        $article = null;

        if ($this->isGranted('ROLE_ADMIN')) {
            $article = $articleRepository->findAll();
        } else {
            foreach ($this->getUser()->getRoleGroupes() as $role) {
                $article = $articleRepository->findByRoleNull('public');

            }
        }
        return $this->render('article/index.html.twig', [
            'articles' => $article,
        ]);
    }

    #[Route('/new', name: 'article_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request,): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($article);
            $entityManager->flush();

            return $this->redirectToRoute('article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article/new.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/article', name: 'article_show', methods: ['GET', 'POST'])]
    public function show(Article $article, UserRepository $userRepository, Request $request): Response
    {
        $coment = new Coment();
        $form = $this->createForm(ComentFormType::class, $coment);
        if ($article->getRoleGroupe() == NULL || $userRepository->findRoleArticle($article->getRoleGroupe()->getId(), $this->getUser()) || $this->isGranted('ROLE_ADMIN')) {
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $coment
                    ->setPlayer($this->getUser())
                    ->setArticle($article);

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($coment);
                $entityManager->flush();
                unset($coment);
                unset($form);
                $coment = new Coment();
                $form = $this->createForm(ComentFormType::class, $coment);
                return $this->redirectToRoute('article_show',[
                    'id' => $article->getId()
                ]);
            }
            return $this->renderForm('article/show.html.twig', [
                'article' => $article,
                'form' => $form
            ]);

        }
        return throw new AccessDeniedHttpException();
    }

    #[Route('/{id}/edit', name: 'article_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Article $article): Response
    {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('article_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('article/edit.html.twig', [
            'article' => $article,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete' . $article->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('article_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/comment/{id}/delete', name: 'comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deletecomment(Request $request, Coment $coment): Response
    {
        $id = $coment->getArticle()->getId();
        if ($this->isCsrfTokenValid('delete' . $coment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($coment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('article_show', [
            'id' => $id
        ], Response::HTTP_SEE_OTHER);
    }
}

<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Repository\ArticleRepository;
use App\Repository\UserRepository;
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
    public function index(ArticleRepository $articleRepository): Response
    {


        if ($this->isGranted('ROLE_ADMIN')) {
            $article = $articleRepository->findAll();
        } else {
            foreach ($this->getUser()->getRoleGroupes() as $role) {
                $article = $articleRepository->findByRoleNull($role);

            }
        }
        return $this->render('article/index.html.twig', [
            'articles' => $article,
        ]);
    }

    #[Route('/new', name: 'article_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request): Response
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $article->setAuthor($this->getUser());
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

    #[Route('/{id}', name: 'article_show', methods: ['GET'])]
    public function show(Article $article, UserRepository $userRepository): Response
    {
        if ($this->isGranted('ROLE_ADMIN')){
            return $this->render('article/show.html.twig', [
                'article' => $article,
            ]);
        }
        if (!$article->getRoleGroupe() == NULL) {
            if (!$userRepository->findRoleArticle($article->getRoleGroupe()->getId(),$this->getUser())) {
                throw  new AccessDeniedHttpException();
            }

        }
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
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

    #[Route('/{id}', name: 'article_delete', methods: ['POST'])]
    public function delete(Request $request, Article $article): Response
    {
        if ($this->isCsrfTokenValid('delete' . $article->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($article);
            $entityManager->flush();
        }

        return $this->redirectToRoute('article_index', [], Response::HTTP_SEE_OTHER);
    }
}

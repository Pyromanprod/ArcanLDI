<?php

namespace App\Controller;

use App\Entity\News;
use App\Entity\NewsComment;
use App\Form\NewsCommentType;
use App\Form\NewsType;
use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/news')]
class NewsController extends AbstractController
{
    #[Route('/', name: 'news_index', methods: ['GET'])]
    public function index(NewsRepository $newsRepository): Response
    {
        return $this->render('news/index.html.twig', [
            'news' => $newsRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'news_new', methods: ['GET','POST'])]
    public function new(Request $request): Response
    {
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news->setAuthor($this->getUser());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($news);
            $entityManager->flush();

            return $this->redirectToRoute('news_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('news/new.html.twig', [
            'news' => $news,
            'form' => $form,
        ]);
    }

    #[Route('/{slug}', name: 'news_show', methods: ['GET','POST'])]
    public function show(News $news, Request $request): Response
    {
        $newsComment = new NewsComment();
        $form = $this->createForm(NewsCommentType::class, $newsComment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newsComment->setAuthor($this->getUser())
                ->setNews($news);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($newsComment);
            $entityManager->flush();
            return $this->redirectToRoute('news_show',[
                'slug' => $news->getSlug()
                ]);

        }
        return $this->renderForm('news/show.html.twig', [
            'news' => $news,
            'form' => $form
        ]);
    }

    #[Route('/{id}/edit', name: 'news_edit', methods: ['GET','POST'])]
    public function edit(Request $request, News $news): Response
    {
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('news_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('news/edit.html.twig', [
            'news' => $news,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'news_delete', methods: ['POST'])]
    public function delete(Request $request, News $news): Response
    {
        if ($this->isCsrfTokenValid('delete'.$news->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($news);
            $entityManager->flush();
        }

        return $this->redirectToRoute('news_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/comment/{id}', name: 'news_comment_delete', methods: ['POST'])]
    public function deleteComment(Request $request, NewsComment $newsComment): Response
    {
        if ($this->isCsrfTokenValid('delete'.$newsComment->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($newsComment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('news_show', [
            'slug' => $newsComment->getNews()->getSlug()
        ], Response::HTTP_SEE_OTHER);
    }

}

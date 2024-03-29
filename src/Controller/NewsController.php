<?php

namespace App\Controller;

use App\Entity\News;
use App\Entity\NewsComment;
use App\Form\NewsCommentType;
use App\Form\NewsType;
use App\Repository\NewsCommentRepository;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/actu')]
class NewsController extends AbstractController
{
    #[Route('/', name: 'news_index', methods: ['GET'])]
    public function index(NewsRepository $newsRepository,PaginatorInterface $paginator,Request $request): Response
    {
        $requestedPage = $request->query->getInt('page', 1);
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }
        $news = $paginator->paginate(
            $newsRepository->findAll(),
            $requestedPage,
            30
        );
        return $this->render('news/index.html.twig', [
            'news' => $news,
        ]);
    }

    #[Route('/nouvelle', name: 'news_new', methods: ['GET','POST'])]
    #[IsGranted("ROLE_MODERATOR")]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $news = new News();
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news->setAuthor($this->getUser());
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
    public function show(News $news,
                         Request $request,
                         RateLimiterFactory $commentsLimiter,
                         PaginatorInterface $paginator,
                         NewsCommentRepository $commentRepository,
                         EntityManagerInterface $entityManager): Response
    {
        //déclaration limiteur de commentaire a 3/h voir ratelimiter.yaml pour modifier
        $limiter = $commentsLimiter->create($request->getClientIp());

        $newsComment = new NewsComment();
        $form = $this->createForm(NewsCommentType::class, $newsComment);
        $form->handleRequest($request);
        $requestedPage = $request->query->getInt('page', 1);
        if($requestedPage < 1){
            throw new NotFoundHttpException();
        }
        $comment = $paginator->paginate(
            $commentRepository->findByNews($news,["createdAt"=>"DESC"]),
            $requestedPage,
            50
        );

        // 1 token consumer par action si plus de token (3/h) throw exception
        if ($form->isSubmitted() && $form->isValid()) {
            if (false === $limiter->consume()->isAccepted()) {
            throw new TooManyRequestsHttpException();}


            $newsComment->setAuthor($this->getUser())
                ->setNews($news);
            $entityManager->persist($newsComment);
            $entityManager->flush();
            return $this->redirectToRoute('news_show',[
                'slug' => $news->getSlug()
                ]);

        }
        return $this->renderForm('news/show.html.twig', [
            'news' => $news,
            'comments'=> $comment,
            'form' => $form
        ]);
    }

    #[Route('/{id}/modifier', name: 'news_edit', methods: ['GET','POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function edit(EntityManagerInterface $entityManager,Request $request, News $news): Response
    {
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('news_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('news/edit.html.twig', [
            'news' => $news,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/', name: 'news_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(EntityManagerInterface $entityManager,Request $request, News $news): Response
    {
        if ($this->isCsrfTokenValid('delete'.$news->getId(), $request->request->get('_token'))) {
            $entityManager->remove($news);
            $entityManager->flush();
        }

        return $this->redirectToRoute('news_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/comment/{id}', name: 'news_comment_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function deleteComment(EntityManagerInterface $entityManager,Request $request, NewsComment $newsComment): Response
    {
        if ($this->isCsrfTokenValid('delete'.$newsComment->getId(), $request->request->get('_token'))) {
            $entityManager->remove($newsComment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('news_show', [
            'slug' => $newsComment->getNews()->getSlug()
        ], Response::HTTP_SEE_OTHER);
    }

}

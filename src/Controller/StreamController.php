<?php

namespace App\Controller;

use App\Entity\Stream;
use App\Form\StreamType;
use App\Repository\StreamRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stream')]
class StreamController extends AbstractController
{
    #[Route('/', name: 'app_stream_index', methods: ['GET'])]
    public function index(StreamRepository $streamRepository): Response
    {
        $user = $this->getUser();
        $streams = $streamRepository->findBy(['utilisateur' => $user]);

        return $this->render('stream/index.html.twig', [
            'streams' => $streams,
        ]);
    }

    #[Route('/new', name: 'app_stream_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stream = new Stream();
        $stream->setUtilisateur($this->getUser());
        $form = $this->createForm(StreamType::class, $stream);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($stream);
            $entityManager->flush();

            return $this->redirectToRoute('app_stream_index');
        }

        return $this->render('stream/new.html.twig', [
            'stream' => $stream,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/demain', name: 'app_stream_demain', methods: ['GET'])]
    public function streamsDeDemain(StreamRepository $streamRepository): Response
    {
        $demainDebut = new DateTime('tomorrow');
        $demainFin = new DateTime('tomorrow +1 day');

        $streams = $streamRepository->createQueryBuilder('s')
            ->where('s.startDate BETWEEN :demainDebut AND :demainFin')
            ->setParameter('demainDebut', $demainDebut->format('Y-m-d 00:00:00'))
            ->setParameter('demainFin', $demainFin->format('Y-m-d 00:00:00'))
            ->getQuery()
            ->getResult();

        return $this->render('stream/demain.html.twig', [
            'streams' => $streams,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stream_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stream $stream, EntityManagerInterface $entityManager): Response
    {
        if ($stream->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(StreamType::class, $stream);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_stream_index');
        }

        return $this->render('stream/edit.html.twig', [
            'stream' => $stream,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_stream_show', methods: ['GET'])]
    public function show(Stream $stream): Response
    {
        return $this->render('stream/show.html.twig', [
            'stream' => $stream,
        ]);
    }

    #[Route('/{id}', name: 'app_stream_delete', methods: ['POST'])]
    public function delete(Request $request, Stream $stream, EntityManagerInterface $entityManager): Response
    {
        if ($stream->getUtilisateur() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$stream->getId(), $request->request->get('_token'))) {
            $entityManager->remove($stream);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_stream_index');
    }


}

<?php

namespace App\Controller\Back;

use App\Entity\Mood;
use App\Form\MoodType;
use App\Repository\MoodRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("back/mood")
 */
class MoodController extends AbstractController
{
    /**
     * @Route("/", name="mood_index", methods={"GET"})
     */
    public function index(MoodRepository $moodRepository): Response
    {
        return $this->render('mood/index.html.twig', [
            'moods' => $moodRepository->findAll(),
        ]);
    }

    /**
     * @Route("/new", name="mood_new", methods={"GET","POST"})
     */
    public function new(Request $request): Response
    {
        $mood = new Mood();
        $form = $this->createForm(MoodType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($mood);
            $entityManager->flush();

            return $this->redirectToRoute('mood_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('mood/new.html.twig', [
            'mood' => $mood,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="mood_show", methods={"GET"})
     */
    public function show(Mood $mood = null): Response
    {
        if( $mood === null) {
            return $this->redirectToRoute('back_error');
        }
        return $this->render('mood/show.html.twig', [
            'mood' => $mood,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="mood_edit", methods={"GET","POST"})
     */
    public function edit(Request $request, Mood $mood = null): Response
    {
        if( $mood === null) {
            return $this->redirectToRoute('back_error');
        }
        $form = $this->createForm(MoodType::class, $mood);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $mood->setUpdatedAt(new DateTime());
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('mood_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('mood/edit.html.twig', [
            'mood' => $mood,
            'form' => $form,
        ]);
    }

    /**
     * @Route("/{id}", name="mood_delete", methods={"POST"})
     */
    public function delete(Request $request, Mood $mood = null): Response
    {
        if( $mood === null) {
            return $this->redirectToRoute('back_error');
        }
        if ($this->isCsrfTokenValid('delete'.$mood->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($mood);
            $entityManager->flush();
        }

        return $this->redirectToRoute('mood_index', [], Response::HTTP_SEE_OTHER);
    }
}

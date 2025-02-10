<?php

namespace App\Controller;

use App\Entity\Todo;
use App\Form\TodoType;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
final class TodoController extends AbstractController
{
    #[Route('/todo/{todo}', methods: ['GET'])]
    #[OA\Get(description: "Retrieve a specific Todo")]
    public function get(?Todo $todo): JsonResponse
    {
        return $this->json($todo);
    }

    #[Route('/todo/create', methods: ['POST'])]
    #[OA\Post(description: "Creates a new Todo")]
    public function store(EntityManagerInterface $entityManager, ValidatorInterface $validator, Request $request): JsonResponse
    {
        $input = json_decode($request->getContent(), true);

        $form = $this->createForm(TodoType::class);
        $form->submit($input);

        if (!$form->isValid() && count($form->getErrors()) > 0) {
            return $this->json([
                'error' => $form->getErrors()
                    ->current()
                    ->getMessage()
            ]);
        }

        $todo = $form->getData();
        $validationErrors = $validator->validate($todo);
        if (count($validationErrors) > 0) {
            $errors = [];
            foreach ($validationErrors as $error)
                $errors[$error->getPropertyPath()] = $error->getMessage();

            return $this->json(compact('errors'));
        }

        $entityManager->persist($todo);
        $entityManager->flush();

        return $this->json([
            'success' => 'Todo successfully created.',
            'todo' => $todo
        ]);
    }

    #[Route('/todo/update/{todo}', methods: ['PUT', 'PATCH'])]
    #[OA\Put(description: "Updates specified Todo")]
    #[OA\Patch(description: "Updates specified Todo")]
    public function update(EntityManagerInterface $entityManager, Request $request, ?Todo $todo): JsonResponse
    {
        if (!$todo) return $this->json(['error' => "Todo not found"], 404);

        $input = json_decode($request->getContent());
        $todo->setTitle($input->title ?? $todo->getTitle());
        $todo->setDescription($input->description ?? null);
        $todo->setFinished(isset($input->finished) ? $todo->isFinished() : $input->finished);

        $entityManager->flush();

        return $this->json([
            'success' => 'Todo successfully updated.',
            'todo' => $todo
        ]);
    }

    #[Route('/todo/delete/{id}', methods: ['DELETE'])]
    #[OA\Delete(description: "Deletes specified Todo")]
    public function destroy(EntityManagerInterface $entityManager, ?Todo $todo): JsonResponse
    {
        if (!$todo) return $this->json(['error' => "Todo not found"], 404);

        $entityManager->remove($todo);
        $entityManager->flush();

        return $this->json([
            'success' => 'Todo successfully deleted.'
        ]);
    }

    #[Route('/todos', methods: ['GET'])]
    #[OA\Get(description: "Retrieve a collection of Todos")]
    public function list(EntityManagerInterface $entityManager): JsonResponse
    {
        $todos = $entityManager->getRepository(Todo::class)
            ->findAll();

        return $this->json($todos);
    }
}

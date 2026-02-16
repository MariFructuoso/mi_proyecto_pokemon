<?php

namespace App\Controller;

use App\Entity\Pokemon;
use App\Form\PokemonType;
use App\Repository\PokemonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/pokemon')]
class PokemonController extends AbstractController
{
    // ==========================================
    // 1. GLOBAL -> Usa estilo BLOG
    // ==========================================
    #[Route('/global', name: 'app_pokemon_global', methods: ['GET'])]
    public function global(PokemonRepository $pokemonRepository): Response
    {
        return $this->render('pokemon/global.html.twig', [
            'pokemons' => $pokemonRepository->findAll(),
        ]);
    }

    // ==========================================
    // 2. MIS POKEMON -> Usa estilo SERVICE DETAILS (Con Sidebar)
    // ==========================================
    #[Route('/mis-pokemon', name: 'app_pokemon_mios', methods: ['GET'])]
    public function misPokemon(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('pokemon/mios.html.twig', [
            'pokemons' => $user->getPokemon(),
        ]);
    }

    // ==========================================
    // 3. FAVORITOS -> Usa estilo STARTER PAGE (Limpio)
    // ==========================================
    #[Route('/favoritos', name: 'app_pokemon_favoritos', methods: ['GET'])]
    public function favoritos(): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        return $this->render('pokemon/favoritos.html.twig', [
            'pokemons' => $user->getFavoritos(),
        ]);
    }

    // ==========================================
    // CREAR NUEVO (Lógica igual a ImagenController)
    // ==========================================
    #[Route('/new', name: 'app_pokemon_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $pokemon = new Pokemon();
        $form = $this->createForm(PokemonType::class, $pokemon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('imagen')->getData();

            if ($file) {
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();
                $file->move($this->getParameter('pokemon_directory'), $fileName);
                $pokemon->setImagen($fileName);
            }

            $pokemon->setFechaCreacion(new \DateTimeImmutable());

            $pokemon->setEntrenador($this->getUser());
            $entityManager->persist($pokemon);
            $entityManager->flush();

            return $this->redirectToRoute('app_pokemon_mios', [], Response::HTTP_SEE_OTHER);
        }

        // Reutilizamos la plantilla de 'mios' o una simple para el formulario
        return $this->render('pokemon/new.html.twig', [
            'pokemon' => $pokemon,
            'form' => $form,
        ]);
    }

    // ... (Mantén aquí las funciones edit, delete y toggleLike que hicimos antes) ...
    // ¿Quieres que te las pegue de nuevo o ya las tienes?
    
    // Solo por si acaso, pego el Toggle Like para que no se pierda:
    #[Route('/favorito/{id}', name: 'app_pokemon_toggle_like')]
    public function toggleLike(Pokemon $pokemon, EntityManagerInterface $entityManager, Request $request): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if ($user->getFavoritos()->contains($pokemon)) {
            $user->removeFavorito($pokemon);
        } else {
            $user->addFavorito($pokemon);
        }
        $entityManager->flush();
        
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_pokemon_global'));
    }

    // ==========================================
    // EDITAR (Adaptado de tu ImagenController)
    // ==========================================
    #[Route('/{id}/edit', name: 'app_pokemon_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pokemon $pokemon, EntityManagerInterface $entityManager): Response
    {
        // Seguridad: Solo el dueño puede editar su Pokémon
        if ($pokemon->getEntrenador() !== $this->getUser()) {
            throw $this->createAccessDeniedException('No tienes permiso para editar este Pokémon.');
        }

        $form = $this->createForm(PokemonType::class, $pokemon);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            // Usamos 'imagen' que es como se llama en tu PokemonType y DB
            $file = $form->get('imagen')->getData();

            if ($file) {
                // 1. Generar nombre único
                $fileName = md5(uniqid()) . '.' . $file->guessExtension();
                
                // 2. Mover a la carpeta configurada
                $file->move($this->getParameter('pokemon_directory'), $fileName);
                
                // 3. Actualizar el nombre en la base de datos
                $pokemon->setImagen($fileName);
            }

            // Guardar cambios
            $entityManager->flush();

            $this->addFlash('success', '¡Pokémon actualizado correctamente!');

            return $this->redirectToRoute('app_pokemon_mios', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('pokemon/edit.html.twig', [
            'pokemon' => $pokemon,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_pokemon_delete', methods: ['POST'])]
    public function delete(Request $request, Pokemon $pokemon, EntityManagerInterface $entityManager): Response
    {
        // Seguridad: Solo el dueño o un ADMIN
        if ($pokemon->getEntrenador() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$pokemon->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pokemon);
            $entityManager->flush();
            $this->addFlash('danger', 'El Pokémon ha sido liberado.');
        }

        return $this->redirectToRoute('app_pokemon_mios', [], Response::HTTP_SEE_OTHER);
    }
}
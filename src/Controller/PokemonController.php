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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;

#[Route('/pokemon')]
class PokemonController extends AbstractController
{
    #[Route('/global', name: 'app_pokemon_global', methods: ['GET'])]
    public function global(PokemonRepository $pokemonRepository, Request $request): Response
    {
        $texto = $request->query->get('q');      
        $tipo = $request->query->get('tipo');    
        $desde = $request->query->get('desde');  
        $hasta = $request->query->get('hasta');  

        $pokemons = $pokemonRepository->buscarPorFiltros($texto, $tipo, $desde, $hasta);

        return $this->render('pokemon/global.html.twig', [
            'pokemons' => $pokemons,
            'filtros' => [
                'q' => $texto,
                'tipo' => $tipo,
                'desde' => $desde,
                'hasta' => $hasta
            ]
        ]);
    }

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

        return $this->render('pokemon/new.html.twig', [
            'pokemon' => $pokemon,
            'form' => $form,
        ]);
    }

    #[Route('/favorito/{id}', name: 'app_pokemon_toggle_like')]
    public function toggleLike(Pokemon $pokemon, EntityManagerInterface $entityManager, Request $request, MailerInterface $mailer): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $dueno = $pokemon->getEntrenador();

        if ($user->getFavoritos()->contains($pokemon)) {
            $user->removeFavorito($pokemon);
        } else {
            $user->addFavorito($pokemon);

            if ($dueno && $dueno !== $user) {
                $email = (new Email())
                    ->from(new Address('no-reply@pokeapp.com', 'PokéApp Bot'))
                    ->to($dueno->getEmail())
                    ->subject('¡A alguien le gusta tu Pokémon!')
                    ->text('Hola ' . $dueno->getNombre() . ",\n\n" .
                           'El entrenador ' . $user->getNombre() . ' le ha dado Me Gusta a tu Pokémon ' . $pokemon->getNombre() . ".\n\n" .
                           '¡Sigue así!'
                    )
                    ->html('<p>Hola <strong>' . $dueno->getNombre() . '</strong>,</p>' .
                           '<p>El entrenador <strong>' . $user->getNombre() . '</strong> le ha dado Me Gusta a tu Pokémon <strong style="color:red;">' . $pokemon->getNombre() . '</strong>.</p>' .
                           '<p>¡Enhorabuena!</p>'
                    );

                try {
                    $mailer->send($email);
                } catch (\Exception $e) {
                }
            }
        }
        
        $entityManager->flush();
        
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_pokemon_global'));
    }


    #[Route('/{id}/edit', name: 'app_pokemon_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Pokemon $pokemon, EntityManagerInterface $entityManager): Response
    {
        if ($pokemon->getEntrenador() !== $this->getUser()) {
            throw $this->createAccessDeniedException('No tienes permiso para editar este Pokémon.');
        }

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
        if ($pokemon->getEntrenador() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if (!$pokemon->getFans()->isEmpty()) {
            $this->addFlash('error', 'No puedes liberar a ' . $pokemon->getNombre() . ' porque tiene fans (Me gusta) asociados.');
            return $this->redirectToRoute('app_pokemon_mios');
        }

        if ($this->isCsrfTokenValid('delete'.$pokemon->getId(), $request->request->get('_token'))) {
            $entityManager->remove($pokemon);
            $entityManager->flush();
            $this->addFlash('success', 'El Pokémon ha sido liberado correctamente.');
        }

        return $this->redirectToRoute('app_pokemon_mios', [], Response::HTTP_SEE_OTHER);
    }
}
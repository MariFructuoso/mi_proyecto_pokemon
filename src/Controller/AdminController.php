<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route; 
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/usuarios', name: 'app_admin_users')]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $filtro = $request->query->get('rol');

        if ($filtro === 'admin') {
            $users = $userRepository->createQueryBuilder('u')
                ->where('u.roles LIKE :role')
                ->setParameter('role', '%"ROLE_ADMIN"%')
                ->getQuery()
                ->getResult();
        } else {
            $users = $userRepository->findAll();
        }

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/usuario/{id}/rol', name: 'app_admin_toggle_role')]
    public function toggleRole(User $user, EntityManagerInterface $entityManager): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'No puedes cambiar tu propio rol.');
            return $this->redirectToRoute('app_admin_users');
        }

        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            $user->setRoles([]); 
        } else {
            $user->setRoles(['ROLE_ADMIN']);
        }

        $entityManager->flush();
        $this->addFlash('success', 'Rol de usuario actualizado.');

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/usuario/{id}/delete', name: 'app_admin_delete_user', methods: ['POST'])]
    public function deleteUser(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'No puedes eliminar tu propia cuenta.');
            return $this->redirectToRoute('app_admin_users');
        }

        $tienePokemon = !$user->getPokemon()->isEmpty();
        $tieneFavoritos = !$user->getFavoritos()->isEmpty();

        if ($tienePokemon || $tieneFavoritos) {
            $this->addFlash('error', 'No se puede eliminar al usuario ' . $user->getNombre() . ' porque tiene Pokémon o favoritos asociados.');
            return $this->redirectToRoute('app_admin_users');
        }

        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
            $this->addFlash('success', 'Usuario eliminado correctamente.');
        }

        return $this->redirectToRoute('app_admin_users');
    }
}
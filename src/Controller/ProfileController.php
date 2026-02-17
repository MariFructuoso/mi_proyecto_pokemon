<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AvatarType;
use App\Form\ChangePasswordType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/perfil')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class ProfileController extends AbstractController
{
    #[Route('/', name: 'app_user_profile')]
    public function index(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        // --- FORMULARIO 1: CAMBIAR FOTO ---
        $formAvatar = $this->createForm(AvatarType::class, $user);
        $formAvatar->handleRequest($request);

        if ($formAvatar->isSubmitted() && $formAvatar->isValid()) {
            // OJO: Aquí pedimos el campo 'foto'
            /** @var UploadedFile $archivo */
            $archivo = $formAvatar->get('foto')->getData();

            if ($archivo) {
                $nombreArchivo = md5(uniqid()) . '.png';
                $rutaDestino = $this->getParameter('kernel.project_dir') . '/public/uploads/avatars';

                if (!file_exists($rutaDestino)) {
                    mkdir($rutaDestino, 0777, true);
                }

                // Redimensionar a 100x100
                $this->redimensionarImagen($archivo->getPathname(), $rutaDestino . '/' . $nombreArchivo);

                // AQUÍ USAMOS TU CAMPO 'foto'
                $user->setFoto($nombreArchivo);
                $entityManager->flush();
                
                $this->addFlash('success', '¡Foto de perfil actualizada!');
                return $this->redirectToRoute('app_user_profile');
            }
        }

        // --- FORMULARIO 2: CAMBIAR PASSWORD (IGUAL QUE ANTES) ---
        $formPassword = $this->createForm(ChangePasswordType::class);
        $formPassword->handleRequest($request);

        if ($formPassword->isSubmitted() && $formPassword->isValid()) {
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $formPassword->get('plainPassword')->getData()
            );

            $user->setPassword($hashedPassword);
            $entityManager->flush();

            $this->addFlash('success', 'Contraseña cambiada correctamente.');
            return $this->redirectToRoute('app_user_profile');
        }

        return $this->render('user/profile.html.twig', [
            'user' => $user,
            'formAvatar' => $formAvatar->createView(),
            'formPassword' => $formPassword->createView(),
        ]);
    }

    private function redimensionarImagen(string $rutaOriginal, string $rutaDestino): void
    {
        list($ancho, $alto, $tipo) = getimagesize($rutaOriginal);

        switch ($tipo) {
            case IMAGETYPE_JPEG: $origen = imagecreatefromjpeg($rutaOriginal); break;
            case IMAGETYPE_PNG:  $origen = imagecreatefrompng($rutaOriginal); break;
            default: return; 
        }

        $destino = imagecreatetruecolor(100, 100);
        imagealphablending($destino, false);
        imagesavealpha($destino, true);
        imagecopyresampled($destino, $origen, 0, 0, 0, 0, 100, 100, $ancho, $alto);
        imagepng($destino, $rutaDestino);
        imagedestroy($origen);
        imagedestroy($destino);
    }
}
<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Upload sécurisé des images :
 *  - le type MIME est vérifié par le formulaire (contrainte Image)
 *  - l'extension est déduite du contenu réel (guessExtension) et non du nom fourni
 *  - le nom de fichier est "slugifié" + suffixe aléatoire (pas de path traversal,
 *    pas d'écrasement, pas de nom prévisible)
 */
class FileUploader
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public function __construct(
        private readonly SluggerInterface $slugger,
        #[Autowire('%kernel.project_dir%/public/uploads')] private readonly string $uploadRoot,
    ) {
    }

    public function upload(UploadedFile $file, string $folder, ?string $previous = null): string
    {
        $extension = $file->guessExtension();
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new \InvalidArgumentException('Format de fichier non autorisé.');
        }

        $original = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safe = strtolower($this->slugger->slug($original)->truncate(40)->toString()) ?: 'image';
        $fileName = sprintf('%s-%s.%s', $safe, bin2hex(random_bytes(6)), $extension);

        $file->move($this->dir($folder), $fileName);

        if ($previous) {
            $this->remove($previous, $folder);
        }

        return $fileName;
    }

    public function remove(?string $fileName, string $folder): void
    {
        if (!$fileName) {
            return;
        }
        // basename() : impossible de sortir du dossier d'upload
        $path = $this->dir($folder).'/'.basename($fileName);
        (new Filesystem())->remove($path);
    }

    private function dir(string $folder): string
    {
        if (!in_array($folder, ['rooms', 'services'], true)) {
            throw new \InvalidArgumentException('Dossier inconnu.');
        }
        return $this->uploadRoot.'/'.$folder;
    }
}

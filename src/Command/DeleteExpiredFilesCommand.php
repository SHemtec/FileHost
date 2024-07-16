<?php

namespace App\Command;

use App\Service\FileDeletionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:delete:files',
    description: 'Supprime les fichier en fonction de leur taille et de leur date d\'upload.',
)]
class DeleteExpiredFilesCommand extends Command
{
    private $fileDeletionService;

    public function __construct(FileDeletionService $fileDeletionService)
    {
        parent::__construct();
        $this->fileDeletionService = $fileDeletionService;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->fileDeletionService->deleteExpiredFiles();

        $io->success('Les fichiers expirés ont été supprimés.');

        return Command::SUCCESS;
    }
}
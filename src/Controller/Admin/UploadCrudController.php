<?php

namespace App\Controller\Admin;

use App\Entity\Upload;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class UploadCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Upload::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id'),
            TextField::new('fileName')->setLabel('Nom du fichier'),
            DateField::new('createdAt')->setLabel('Date de création'),
            IntegerField::new('fileSize')
                ->setLabel('Taille du fichier')
                ->formatValue(function ($value) {
                    return $this->formatFileSize($value);
                }),
            AssociationField::new('user')
                ->setLabel('Utilisateur')
                ->setTemplateName('crud/field/association')
                ->formatValue(function ($value, $entity) {
                    return $entity->getUser()->getUsername();
                }),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Action::INDEX, Action::NEW);
    }

    private function formatFileSize($size)
    {
        if ($size >= 1073741824) {
            return number_format($size / 1073741824, 2) . ' GB';
        } elseif ($size >= 1048576) {
            return number_format($size / 1048576, 2) . ' MB';
        } elseif ($size >= 1024) {
            return number_format($size / 1024, 2) . ' KB';
        } else {
            return $size . ' bytes';
        }
    }
}

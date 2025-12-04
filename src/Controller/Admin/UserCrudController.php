<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Override;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @extends AbstractCrudController<User>
 */
class UserCrudController extends AbstractCrudController
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    #[Override]
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', 'Имя'),
            TextField::new('phone', 'Телефон'),
            EmailField::new('email', 'Email'),
            ArrayField::new('roles', 'Роли'),
            TextField::new('password', 'Пароль')
                ->setFormType(PasswordType::class)
                ->onlyWhenCreating(),
        ];
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('phone'))
            ->add(TextFilter::new('email'));
    }

    #[Override]
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    #[Override]
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPassword($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPassword($user): void
    {
        if (!$user instanceof User || !$user->getPassword()) {
            return;
        }
        $hashed = $this->passwordHasher->hashPassword($user, $user->getPassword());
        $user->setPassword($hashed);
    }
}

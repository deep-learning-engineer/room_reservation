<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Booking;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Override;

/**
 * @extends AbstractCrudController<Booking>
 */
class BookingCrudController extends AbstractCrudController
{
    #[Override]
    public static function getEntityFqcn(): string
    {
        return Booking::class;
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('user', 'Клиент'),
            AssociationField::new('house', 'Дом'),
            ChoiceField::new('status', 'Статус')->setChoices([
                'Ожидает' => 'pending',
                'Подтверждено' => 'confirmed',
                'Отменено' => 'cancelled',
            ])->renderAsBadges([
                'pending' => 'warning',
                'confirmed' => 'success',
                'cancelled' => 'danger',
            ]),
            DateTimeField::new('createdAt', 'Дата создания')->hideOnForm(),
            TextareaField::new('comment', 'Комментарий'),
        ];
    }

    #[Override]
    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('status')->setChoices([
                'Pending' => 'pending',
                'Confirmed' => 'confirmed',
                'Cancelled' => 'cancelled',
            ]))
            ->add(EntityFilter::new('house'))
            ->add(DateTimeFilter::new('createdAt'));
    }
}

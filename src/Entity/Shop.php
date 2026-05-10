<?php

namespace App\Entity;

use App\Repository\ShopRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShopRepository::class)]
#[ORM\Table(name: 'shops')]
class Shop
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }
}


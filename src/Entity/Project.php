<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\Internal\TentativeType;

/**
 * @ORM\Entity(repositoryClass=ProjectRepository::class)
 */
class Project implements \JsonSerializable
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\OneToMany(targetEntity=ProjectUser::class, mappedBy="project")
     */
    private $projectUser;

    /**
     * @ORM\Column(type="boolean")
     */
    private $share;

    /**
     * @ORM\OneToMany(targetEntity=ProductBacklog::class, mappedBy="project", orphanRemoval=true)
     */
    private $productBacklogs;

    public function __construct()
    {
        $this->projectUser = new ArrayCollection();
        $this->productBacklogs = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|ProjectUser[]
     */
    public function getProjectUser(): Collection
    {
        return $this->projectUser;
    }

    public function addProjectUser(ProjectUser $projectUser): self
    {
        if (!$this->projectUser->contains($projectUser)) {
            $this->projectUser[] = $projectUser;
            $projectUser->setProject($this);
        }

        return $this;
    }

    public function removeProjectUser(ProjectUser $projectUser): self
    {
        if ($this->projectUser->removeElement($projectUser)) {
            // set the owning side to null (unless already changed)
            if ($projectUser->getProject() === $this) {
                $projectUser->setProject(null);
            }
        }

        return $this;
    }

    public function getShare(): ?bool
    {
        return $this->share;
    }

    public function setShare(bool $share): self
    {
        $this->share = $share;

        return $this;
    }

    /**
     * @return Collection|ProductBacklog[]
     */
    public function getProductBacklogs(): Collection
    {
        return $this->productBacklogs;
    }

    public function addProductBacklog(ProductBacklog $productBacklog): self
    {
        if (!$this->productBacklogs->contains($productBacklog)) {
            $this->productBacklogs[] = $productBacklog;
            $productBacklog->setProject($this);
        }

        return $this;
    }

    public function removeProductBacklog(ProductBacklog $productBacklog): self
    {
        if ($this->productBacklogs->removeElement($productBacklog)) {
            // set the owning side to null (unless already changed)
            if ($productBacklog->getProject() === $this) {
                $productBacklog->setProject(null);
            }
        }

        return $this;
    }

    public function jsonSerialize()
    {
        return [
            'name' => $this->getName(),
            'description' => $this->getDescription(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace StaffHub\Entity;

final class Department
{
    private int $departmentId = 0;
    private string $departmentName = '';
    private string $description = '';

    public static function fromRow(array $row): self
    {
        $d = new self();
        $d->departmentId = (int) ($row['department_id'] ?? 0);
        $d->departmentName = $row['department_name'] ?? '';
        $d->description = $row['description'] ?? '';
        return $d;
    }

    public function getDepartmentId(): int
    {
        return $this->departmentId;
    }

    public function setDepartmentId(int $id): self
    {
        $this->departmentId = $id;
        return $this;
    }

    public function getDepartmentName(): string
    {
        return $this->departmentName;
    }

    public function setDepartmentName(string $name): self
    {
        $this->departmentName = $name;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'department_id'   => $this->departmentId,
            'department_name' => $this->departmentName,
            'description'     => $this->description,
        ];
    }
}

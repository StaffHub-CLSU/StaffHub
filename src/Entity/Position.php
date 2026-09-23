<?php

declare(strict_types=1);

namespace StaffHub\Entity;

final class Position
{
    private int $positionId = 0;
    private string $positionName = '';
    private ?int $departmentId = null;

    public static function fromRow(array $row): self
    {
        $p = new self();
        $p->positionId = (int) ($row['position_id'] ?? 0);
        $p->positionName = $row['position_name'] ?? '';
        $p->departmentId = isset($row['department_id']) && $row['department_id'] !== null
            ? (int) $row['department_id']
            : null;
        return $p;
    }

    public function getPositionId(): int
    {
        return $this->positionId;
    }

    public function setPositionId(int $id): self
    {
        $this->positionId = $id;
        return $this;
    }

    public function getPositionName(): string
    {
        return $this->positionName;
    }

    public function setPositionName(string $name): self
    {
        $this->positionName = $name;
        return $this;
    }

    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    public function setDepartmentId(?int $departmentId): self
    {
        $this->departmentId = $departmentId;
        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'position_id'   => $this->positionId,
            'position_name' => $this->positionName,
            'department_id' => $this->departmentId,
        ];
    }
}

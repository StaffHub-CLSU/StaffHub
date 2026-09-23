<?php

declare(strict_types=1);

namespace StaffHub\Entity;

/**
 * Central employee record. Pure domain state — no SQL.
 */
final class Employee
{
    private ?int $employeeId = null;
    private string $employeeCode = '';
    private ?int $userId = null;
    private ?int $departmentId = null;
    private ?int $positionId = null;
    private string $firstName = '';
    private string $lastName = '';
    private ?string $middleName = null;
    private string $gender = 'Male';
    private string $birthdate = '';
    private string $email = '';
    private string $contactNumber = '';
    private ?string $address = null;
    private string $employmentStatus = 'Full-Time';
    private float $basicHourlyRate = 0.0;
    private string $dateHired = '';
    private ?string $profilePicture = null;
    private bool $isActive = true;

    public static function fromRow(array $row): self
    {
        $e = new self();
        $e->employeeId = isset($row['employee_id']) ? (int) $row['employee_id'] : null;
        $e->employeeCode = $row['employee_code'] ?? '';
        $e->userId = isset($row['user_id']) ? (int) $row['user_id'] : null;
        $e->departmentId = isset($row['department_id']) ? (int) $row['department_id'] : null;
        $e->positionId = isset($row['position_id']) ? (int) $row['position_id'] : null;
        $e->firstName = $row['first_name'] ?? '';
        $e->lastName = $row['last_name'] ?? '';
        $e->middleName = $row['middle_name'] ?? null;
        $e->gender = $row['gender'] ?? 'Male';
        $e->birthdate = $row['birthdate'] ?? '';
        $e->email = $row['email'] ?? '';
        $e->contactNumber = $row['contact_number'] ?? '';
        $e->address = $row['address'] ?? null;
        $e->employmentStatus = $row['employment_status'] ?? 'Full-Time';
        $e->basicHourlyRate = isset($row['basic_hourly_rate']) ? (float) $row['basic_hourly_rate'] : 0.0;
        $e->dateHired = $row['date_hired'] ?? '';
        $e->profilePicture = $row['profile_picture'] ?? null;
        $e->isActive = isset($row['is_active']) ? (bool) $row['is_active'] : true;
        return $e;
    }

    public function fullName(): string
    {
        $mi = $this->middleName !== null && $this->middleName !== ''
            ? ' ' . strtoupper(substr($this->middleName, 0, 1)) . '.'
            : '';
        return trim("{$this->firstName}{$mi} {$this->lastName}");
    }

    public function getEmployeeId(): ?int
    {
        return $this->employeeId;
    }

    public function setEmployeeId(?int $id): self
    {
        $this->employeeId = $id;
        return $this;
    }

    public function getEmployeeCode(): string
    {
        return $this->employeeCode;
    }

    public function setEmployeeCode(string $code): self
    {
        $this->employeeCode = $code;
        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(?int $userId): self
    {
        $this->userId = $userId;
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

    public function getPositionId(): ?int
    {
        return $this->positionId;
    }

    public function setPositionId(?int $positionId): self
    {
        $this->positionId = $positionId;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getMiddleName(): ?string
    {
        return $this->middleName;
    }

    public function setMiddleName(?string $middleName): self
    {
        $this->middleName = $middleName;
        return $this;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getBirthdate(): string
    {
        return $this->birthdate;
    }

    public function setBirthdate(string $birthdate): self
    {
        $this->birthdate = $birthdate;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getContactNumber(): string
    {
        return $this->contactNumber;
    }

    public function setContactNumber(string $contactNumber): self
    {
        $this->contactNumber = $contactNumber;
        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;
        return $this;
    }

    public function getEmploymentStatus(): string
    {
        return $this->employmentStatus;
    }

    public function setEmploymentStatus(string $employmentStatus): self
    {
        $this->employmentStatus = $employmentStatus;
        return $this;
    }

    public function getBasicHourlyRate(): float
    {
        return $this->basicHourlyRate;
    }

    public function setBasicHourlyRate(float $rate): self
    {
        $this->basicHourlyRate = $rate;
        return $this;
    }

    public function getDateHired(): string
    {
        return $this->dateHired;
    }

    public function setDateHired(string $dateHired): self
    {
        $this->dateHired = $dateHired;
        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profilePicture;
    }

    public function setProfilePicture(?string $profilePicture): self
    {
        $this->profilePicture = $profilePicture;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setActive(bool $active): self
    {
        $this->isActive = $active;
        return $this;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'employee_id'       => $this->employeeId,
            'employee_code'     => $this->employeeCode,
            'user_id'           => $this->userId,
            'department_id'     => $this->departmentId,
            'position_id'       => $this->positionId,
            'first_name'        => $this->firstName,
            'last_name'         => $this->lastName,
            'middle_name'       => $this->middleName,
            'gender'            => $this->gender,
            'birthdate'         => $this->birthdate,
            'email'             => $this->email,
            'contact_number'    => $this->contactNumber,
            'address'           => $this->address,
            'employment_status' => $this->employmentStatus,
            'basic_hourly_rate' => $this->basicHourlyRate,
            'date_hired'        => $this->dateHired,
            'profile_picture'   => $this->profilePicture,
            'is_active'         => $this->isActive ? 1 : 0,
        ];
    }
}

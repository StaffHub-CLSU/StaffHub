<?php
/**
 * Employee
 *
 * The central entity of the system. Encapsulates all employee fields as
 * properties, is constructed either empty (for a "new employee" form) or
 * hydrated from a DB row, and exposes reusable CRUD + search/filter methods.
 * Employee creation composes a User account (1-to-1) since every employee
 * needs login credentials.
 */
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/User.php';

class Employee
{
    private Database $db;

    // ----- Encapsulated properties (mirror the employees table) -----
    public ?int $employeeId = null;
    public string $employeeCode = '';
    public ?int $userId = null;
    public ?int $departmentId = null;
    public ?int $positionId = null;
    public string $firstName = '';
    public string $lastName = '';
    public ?string $middleName = null;
    public string $gender = 'Male';
    public string $birthdate = '';
    public string $email = '';
    public string $contactNumber = '';
    public ?string $address = null;
    public string $employmentStatus = 'Full-Time';
    public float $basicHourlyRate = 0.0;
    public string $dateHired = '';
    public ?string $profilePicture = null;
    public bool $isActive = true;

    public function __construct(?array $data = null)
    {
        $this->db = Database::getInstance();
        if ($data) {
            $this->hydrate($data);
        }
    }

    /** Fills object properties from an associative DB row. */
    private function hydrate(array $row): void
    {
        $this->employeeId       = isset($row['employee_id']) ? (int) $row['employee_id'] : null;
        $this->employeeCode     = $row['employee_code'] ?? '';
        $this->userId           = isset($row['user_id']) ? (int) $row['user_id'] : null;
        $this->departmentId     = isset($row['department_id']) ? (int) $row['department_id'] : null;
        $this->positionId       = isset($row['position_id']) ? (int) $row['position_id'] : null;
        $this->firstName        = $row['first_name'] ?? '';
        $this->lastName         = $row['last_name'] ?? '';
        $this->middleName       = $row['middle_name'] ?? null;
        $this->gender           = $row['gender'] ?? 'Male';
        $this->birthdate        = $row['birthdate'] ?? '';
        $this->email            = $row['email'] ?? '';
        $this->contactNumber    = $row['contact_number'] ?? '';
        $this->address          = $row['address'] ?? null;
        $this->employmentStatus = $row['employment_status'] ?? 'Full-Time';
        $this->basicHourlyRate  = isset($row['basic_hourly_rate']) ? (float) $row['basic_hourly_rate'] : 0.0;
        $this->dateHired        = $row['date_hired'] ?? '';
        $this->profilePicture   = $row['profile_picture'] ?? null;
        $this->isActive         = isset($row['is_active']) ? (bool) $row['is_active'] : true;
    }

    public function fullName(): string
    {
        $mi = $this->middleName ? ' ' . strtoupper(substr($this->middleName, 0, 1)) . '.' : '';
        return trim("{$this->firstName}{$mi} {$this->lastName}");
    }

    /** Generates the next sequential employee code, e.g. EMP-0003. */
    public function generateNextCode(): string
    {
        $row = $this->db->fetch("SELECT employee_code FROM employees ORDER BY employee_id DESC LIMIT 1");
        if (!$row) {
            return 'EMP-0001';
        }
        $lastNumber = (int) substr($row['employee_code'], 4);
        return 'EMP-' . str_pad((string) ($lastNumber + 1), 4, '0', STR_PAD_LEFT);
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT employee_id FROM employees WHERE email = ?";
        $params = [$email];
        if ($excludeId !== null) {
            $sql .= " AND employee_id != ?";
            $params[] = $excludeId;
        }
        return $this->db->fetch($sql, $params) !== null;
    }

    /**
     * Creates the linked User account plus the Employee record in a single
     * transaction so we never end up with an orphaned login or employee row.
     *
     * @param array $data Associative array of form fields (snake_case, matching table columns)
     * @return int The new employee_id
     * @throws Exception on failure (transaction is rolled back)
     */
    public function create(array $data): int
    {
        $this->db->beginTransaction();
        try {
            $userModel = new User();
            $userId = $userModel->create($data['username'], $data['password'], 'employee');

            $code = $this->generateNextCode();

            $sql = "INSERT INTO employees
                        (employee_code, user_id, department_id, position_id, first_name, last_name,
                         middle_name, gender, birthdate, email, contact_number, address,
                         employment_status, basic_hourly_rate, date_hired, profile_picture, is_active)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

            $employeeId = (int) $this->db->insert($sql, [
                $code,
                $userId,
                $data['department_id'] ?: null,
                $data['position_id'] ?: null,
                $data['first_name'],
                $data['last_name'],
                $data['middle_name'] ?: null,
                $data['gender'],
                $data['birthdate'],
                $data['email'],
                $data['contact_number'],
                $data['address'] ?? null,
                $data['employment_status'],
                $data['basic_hourly_rate'],
                $data['date_hired'],
                $data['profile_picture'] ?? null,
                1,
            ]);

            $this->db->commit();
            return $employeeId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Updates an existing employee's fields. Password/username changes are
     * handled separately in User so this stays focused on employee data.
     */
    public function update(int $employeeId, array $data): bool
    {
        $sql = "UPDATE employees SET
                    department_id = ?, position_id = ?, first_name = ?, last_name = ?,
                    middle_name = ?, gender = ?, birthdate = ?, email = ?, contact_number = ?,
                    address = ?, employment_status = ?, basic_hourly_rate = ?, date_hired = ?
                WHERE employee_id = ?";

        return $this->db->execute($sql, [
            $data['department_id'] ?: null,
            $data['position_id'] ?: null,
            $data['first_name'],
            $data['last_name'],
            $data['middle_name'] ?: null,
            $data['gender'],
            $data['birthdate'],
            $data['email'],
            $data['contact_number'],
            $data['address'] ?? null,
            $data['employment_status'],
            $data['basic_hourly_rate'],
            $data['date_hired'],
            $employeeId,
        ]) >= 0;
    }

    public function updateProfilePicture(int $employeeId, string $filename): bool
    {
        return $this->db->execute(
            "UPDATE employees SET profile_picture = ? WHERE employee_id = ?",
            [$filename, $employeeId]
        ) > 0;
    }

    public function setActive(int $employeeId, bool $active): bool
    {
        $employee = $this->getById($employeeId);
        if (!$employee) {
            return false;
        }
        $this->db->execute("UPDATE employees SET is_active = ? WHERE employee_id = ?", [$active ? 1 : 0, $employeeId]);
        $userModel = new User();
        return $userModel->setActive((int) $employee['user_id'], $active);
    }

    /** Deletes an employee (and cascades to user, attendance, payroll via FKs). */
    public function delete(int $employeeId): bool
    {
        $employee = $this->getById($employeeId);
        if (!$employee) {
            return false;
        }
        // Deleting the linked user cascades to the employee row (ON DELETE CASCADE).
        $userModel = new User();
        return $userModel->delete((int) $employee['user_id']);
    }

    public function getById(int $employeeId): ?array
    {
        $sql = "SELECT e.*, d.department_name, p.position_name, u.username
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                LEFT JOIN users u ON e.user_id = u.user_id
                WHERE e.employee_id = ?";
        return $this->db->fetch($sql, [$employeeId]);
    }

    public function getByUserId(int $userId): ?array
    {
        $sql = "SELECT e.*, d.department_name, p.position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                WHERE e.user_id = ?";
        return $this->db->fetch($sql, [$userId]);
    }

    /**
     * Returns a filtered, paginated, sortable list of employees for the admin table.
     *
     * @param array $filters Keys: search, department_id, employment_status, status
     * @param string $sortBy Whitelisted column key (see $sortMap below)
     * @param string $sortDir 'asc' or 'desc'
     */
    public function getAll(array $filters = [], int $page = 1, int $perPage = 10, string $sortBy = '', string $sortDir = 'desc'): array
    {
        [$where, $params] = $this->buildFilters($filters);

        $countSql = "SELECT COUNT(*) AS total FROM employees e $where";
        $total = (int) $this->db->fetch($countSql, $params)['total'];

        $offset = max(0, ($page - 1) * $perPage);

        // Whitelist sortable columns to prevent SQL injection via column name.
        $sortMap = [
            'name' => 'e.first_name',
            'code' => 'e.employee_code',
            'department' => 'd.department_name',
            'employment_status' => 'e.employment_status',
            'rate' => 'e.basic_hourly_rate',
            'date_hired' => 'e.date_hired',
        ];
        $orderColumn = $sortMap[$sortBy] ?? 'e.employee_id';
        $orderDir = strtolower($sortDir) === 'asc' ? 'ASC' : 'DESC';

        $sql = "SELECT e.*, d.department_name, p.position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN positions p ON e.position_id = p.position_id
                $where
                ORDER BY $orderColumn $orderDir
                LIMIT $perPage OFFSET $offset";

        $rows = $this->db->fetchAll($sql, $params);

        return [
            'data' => $rows,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => (int) ceil($total / $perPage),
            'sort_by' => $sortBy,
            'sort_dir' => $orderDir,
        ];
    }

    private function buildFilters(array $filters): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(e.first_name LIKE ? OR e.last_name LIKE ? OR e.employee_code LIKE ? OR e.email LIKE ?)";
            $term = '%' . $filters['search'] . '%';
            array_push($params, $term, $term, $term, $term);
        }
        if (!empty($filters['department_id'])) {
            $conditions[] = "e.department_id = ?";
            $params[] = $filters['department_id'];
        }
        if (!empty($filters['employment_status'])) {
            $conditions[] = "e.employment_status = ?";
            $params[] = $filters['employment_status'];
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $conditions[] = "e.is_active = ?";
            $params[] = $filters['status'] === 'active' ? 1 : 0;
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
        return [$where, $params];
    }

    public function countActive(): int
    {
        return (int) $this->db->fetch("SELECT COUNT(*) AS c FROM employees WHERE is_active = 1")['c'];
    }

    public function countAll(): int
    {
        return (int) $this->db->fetch("SELECT COUNT(*) AS c FROM employees")['c'];
    }
}

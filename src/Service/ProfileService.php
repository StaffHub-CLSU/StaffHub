<?php

declare(strict_types=1);

namespace StaffHub\Service;

use StaffHub\Repository\Contract\EmployeeRepositoryInterface;
use StaffHub\Repository\Contract\UserRepositoryInterface;
use StaffHub\Support\Validator;

/**
 * Profile picture upload rules + self-service profile updates.
 */
final class ProfileService
{
    private const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(
        private readonly EmployeeRepositoryInterface $employees,
        private readonly UserRepositoryInterface $users,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Validate + store an uploaded avatar. Returns array{success, message, filename?, url?}.
     *
     * @param array<string, mixed> $file $_FILES entry
     */
    public function uploadPicture(int $employeeId, array $file, ?array $existing, bool $isAdmin, ?int $actingEmployeeId): array
    {
        if (!$isAdmin && $actingEmployeeId !== $employeeId) {
            return ['success' => false, 'status' => 403, 'message' => 'You may only update your own profile picture.'];
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'No valid file uploaded.'];
        }

        $mime = mime_content_type((string) $file['tmp_name']);
        if (!isset(self::ALLOWED_MIME[$mime])) {
            return ['success' => false, 'message' => 'Only JPG, PNG, or WEBP images are allowed.'];
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            return ['success' => false, 'message' => 'Image must be smaller than 2MB.'];
        }

        if (!defined('UPLOAD_DIR')) {
            return ['success' => false, 'message' => 'Upload directory is not configured.'];
        }
        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0755, true);
        }

        $filename = 'emp_' . $employeeId . '_' . time() . '.' . self::ALLOWED_MIME[$mime];
        if (!move_uploaded_file((string) $file['tmp_name'], UPLOAD_DIR . $filename)) {
            return ['success' => false, 'message' => 'Failed to save uploaded file.'];
        }

        $old = $existing['profile_picture'] ?? null;
        if (!empty($old) && is_file(UPLOAD_DIR . $old)) {
            @unlink(UPLOAD_DIR . $old);
        }

        $this->employees->updateProfilePicture($employeeId, $filename);

        return [
            'success' => true,
            'message' => 'Profile picture updated.',
            'filename' => $filename,
            'url' => (defined('UPLOAD_URL') ? UPLOAD_URL : '') . $filename,
        ];
    }

    /** @return array{success: bool, message: string, errors?: array<string, string>} */
    public function updateContact(int $employeeId, array $employeeRow, string $contact, string $address, string $email): array
    {
        $validator = new Validator();
        $validator->required($contact, 'contact_number', 'Contact number')
            ->required($email, 'email', 'Email')
            ->email($email, 'email');

        if ($this->employees->emailExists($email, $employeeId)) {
            $validator->addError('email', 'This email is already used by another employee.');
        }

        if ($validator->fails()) {
            return [
                'success' => false,
                'message' => (string) $validator->firstError(),
                'errors' => $validator->getErrors(),
            ];
        }

        $data = $employeeRow;
        $data['contact_number'] = $contact;
        $data['address'] = $address;
        $data['email'] = $email;
        $this->employees->update($employeeId, $data);

        return ['success' => true, 'message' => 'Contact information updated successfully.'];
    }

    /** @return array{success: bool, message: string} */
    public function changePassword(int $userId, string $current, string $new, string $confirm): array
    {
        $validator = new Validator();
        $validator->required($current, 'current_password', 'Current password')
            ->required($new, 'new_password', 'New password')
            ->passwordStrength($new, 'new_password');

        $user = $this->users->findById($userId);
        if (!$user || !\StaffHub\Entity\User::verifyPassword($current, $user->getPasswordHash())) {
            $validator->addError('current_password', 'Your current password is incorrect.');
        }
        if ($new !== $confirm) {
            $validator->addError('confirm_password', 'Password confirmation does not match.');
        }

        if ($validator->fails()) {
            return ['success' => false, 'message' => (string) $validator->firstError()];
        }

        $this->users->updatePassword($userId, $new);
        $this->logger->log($userId, 'Changed password');

        return ['success' => true, 'message' => 'Password changed successfully.'];
    }
}

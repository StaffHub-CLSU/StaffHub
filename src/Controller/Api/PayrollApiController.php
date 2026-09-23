<?php

declare(strict_types=1);

namespace StaffHub\Controller\Api;

use StaffHub\Auth\AuthService;
use StaffHub\Core\Controller;
use StaffHub\Core\Request;
use StaffHub\Core\Response;
use StaffHub\Service\LoggerInterface;
use StaffHub\Service\PayrollService;
use StaffHub\Support\Validator;

/**
 * JSON API for payroll preview/process/list (formerly ajax/payroll.php).
 */
final class PayrollApiController extends Controller
{
    public function __construct(
        \StaffHub\Core\Container $container,
        private readonly PayrollService $payroll,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($container);
    }

    public function handle(Request $request): Response
    {
        $action = $request->string('action');

        return match ($action) {
            'preview' => $this->preview($request),
            'process' => $this->process($request),
            'process_batch' => $this->processBatch($request),
            'list' => $this->list($request),
            'get' => $this->get($request),
            default => Response::json(['success' => false, 'message' => 'Unknown action.'], 400),
        };
    }

    private function preview(Request $request): Response
    {
        $employeeId = (int) $request->query('employee_id', 0);
        $start = (string) $request->query('period_start', '');
        $end = (string) $request->query('period_end', '');
        $bonuses = (float) $request->query('bonuses', 0);
        $deductions = (float) $request->query('deductions', 0);

        $validator = new Validator();
        $validator->required($employeeId, 'employee_id', 'Employee')
            ->required($start, 'period_start', 'Period start')
            ->required($end, 'period_end', 'Period end');

        if ($validator->fails()) {
            return Response::json(['success' => false, 'message' => $validator->firstError()]);
        }

        return Response::json($this->payroll->preview($employeeId, $start, $end, $bonuses, $deductions));
    }

    private function process(Request $request): Response
    {
        $employeeId = (int) $request->input('employee_id', 0);
        $start = $request->string('period_start');
        $end = $request->string('period_end');
        $bonuses = $request->float('bonuses', 0);
        $deductions = $request->float('deductions', 0);

        $check = $this->payroll->validatePeriod($start, $end);
        if ($employeeId <= 0) {
            return Response::json(['success' => false, 'message' => 'Employee is required.']);
        }
        if ($check['fails']) {
            return Response::json(['success' => false, 'message' => $check['message']]);
        }

        $result = $this->payroll->process($employeeId, $start, $end, $bonuses, $deductions, (int) AuthService::getUserId());
        if ($result['success']) {
            $this->logger->log(
                AuthService::getUserId(),
                "Processed payroll for employee #{$employeeId} ({$start} to {$end})",
            );
        }

        return Response::json($result);
    }

    private function processBatch(Request $request): Response
    {
        $start = $request->string('period_start');
        $end = $request->string('period_end');

        $check = $this->payroll->validatePeriod($start, $end);
        if ($check['fails']) {
            return Response::json(['success' => false, 'message' => $check['message']]);
        }

        $result = $this->payroll->processBatch($start, $end, (int) AuthService::getUserId());
        $this->logger->log(
            AuthService::getUserId(),
            "Ran batch payroll for {$result['processed']} employee(s) ({$start} to {$end})",
        );

        return Response::json([
            'success' => true,
            'message' => "Payroll processed for {$result['processed']} employee(s). {$result['skipped']} skipped (no verified hours).",
            'result' => $result,
        ]);
    }

    private function list(Request $request): Response
    {
        $page = max(1, (int) $request->query('page', 1));
        $filters = array_filter([
            'employee_id' => (string) $request->query('employee_id', ''),
            'department_id' => (string) $request->query('department_id', ''),
            'period_start' => (string) $request->query('period_start', ''),
            'period_end' => (string) $request->query('period_end', ''),
        ]);

        $result = $this->payroll->search($filters, $page, 10);
        return Response::json(['success' => true] + $result);
    }

    private function get(Request $request): Response
    {
        $record = $this->payroll->find((int) $request->query('payroll_id', 0));
        if (!$record) {
            return Response::json(['success' => false, 'message' => 'Payroll record not found.']);
        }
        return Response::json(['success' => true, 'payroll' => $record]);
    }
}

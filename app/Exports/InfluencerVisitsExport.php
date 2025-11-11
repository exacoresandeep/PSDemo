<?php

namespace App\Exports;

use App\Models\RescheduledRoute;
use App\Models\Target;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class InfluencerVisitsExport implements FromCollection, WithMapping, WithHeadings, ShouldAutoSize
{
    protected $year, $month, $row = 1;
    protected $targetsByEmployee = [];
    protected $achievedByEmployee = [];

    public function __construct($year, $month)
    {
        $this->year = $year;
        $this->month = $month + 1;
    }

    public function collection()
    {
        $routes = RescheduledRoute::with('employee')
            ->whereYear('assign_date', $this->year)
            ->whereMonth('assign_date', $this->month)
            ->get();

        $this->targetsByEmployee = Target::where('month', $this->month)
            ->where('year', $this->year)
            ->pluck('customer_visit', 'employee_id')
            ->toArray();

        foreach ($routes as $route) {
            $employeeId = $route->employee_id;
            $customers = collect(json_decode($route->customers ?? '[]', true))
                ->where('scheduled', true)
                ->where('status', 'Completed');

            if (!isset($this->achievedByEmployee[$employeeId])) {
                $this->achievedByEmployee[$employeeId] = 0;
            }
            $this->achievedByEmployee[$employeeId] += $customers->count();
        }

        return $routes;
    }

    public function map($route): array
    {
        $employee = optional($route->employee);
        $employeeId = $employee->id ?? null;

        $customers = collect(json_decode($route->customers ?? '[]', true))
            ->where('scheduled', true)
            ->where('status', 'Completed');

        $completedCount = $customers->count();
        $completedNames = $customers->pluck('name')->implode(', ');

        $target = $this->targetsByEmployee[$employeeId] ?? 0;
        $achieved = $this->achievedByEmployee[$employeeId] ?? 0;


        return [
            $this->row++,
            optional($route->employee)->name,
            $route->route_name,
            $route->assign_date,
            $completedCount,
            $completedNames,
            $target,
            $achieved,
        ];
    }

    public function headings(): array
    {
        return [
            'S.No',
            'Employee Name',
            'Route Name',
            'Assigned Date',
            'Completed Visits Count',
            'Completed Customer Names',
            'Target',
            'Achieved',
        ];
    }
}

<?php

namespace App\Exports;
use Carbon\Carbon;
use App\Models\Attendance;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AttendanceExport implements FromQuery, WithHeadings, WithMapping
{
    protected $from_date;
    protected $to_date;
    protected $employee_type;
<<<<<<< HEAD
    protected static $rowNumber = 0;
    public function __construct($from_date, $to_date, $employee_type)
=======
    protected $employee_id;
    protected $status;
    protected static $rowNumber = 0;
    public function __construct($from_date, $to_date, $employee_type, $employee_id, $status)
>>>>>>> origin/master
    {
        $this->from_date = $from_date;
        $this->to_date = $to_date;
        $this->employee_type = $employee_type;
<<<<<<< HEAD
=======
        $this->employee_id   = $employee_id;
        $this->status        = $status;
>>>>>>> origin/master
    }

    public function query()
    {
        $query = Attendance::with('employee');

        if ($this->from_date && $this->to_date) {
            $query->whereBetween('date', [$this->from_date, $this->to_date]);
        }

        if ($this->employee_type) {
            $query->whereHas('employee', function ($q) {
                $q->where('employee_type_id', $this->employee_type);
            });
        }
<<<<<<< HEAD
=======
        if ($this->employee_id) {
            $query->where('employee_id', $this->employee_id);
        }

        // 🔹 Status filter (Present / Leave)
        if ($this->status) {
            $query->where('status', $this->status);
        }
>>>>>>> origin/master

        return $query->orderBy('date', 'desc');
    }

    public function headings(): array
    {
        return [
            'Sl No',
            'Date',
            'Employee Name',
            'Employee Code',
            'Punch In',
            'Starting Remark',
            'Starting Odometer',
            'Punch Out',
            'Ending Remark',
	    'Ending Odometer',
	    'Total Time',
            'Total Odometer',
            'Status',
	    'Leave Remark',
	    'Created At',
        ];
    }

    public function map($attendance): array
    {
	    self::$rowNumber++;
	    $totalTime = '-';
        if ($attendance->punch_in && $attendance->punch_out) {
            try {
                $start = Carbon::parse($attendance->punch_in);
                $end   = Carbon::parse($attendance->punch_out);
                $diff  = $end->diff($start);
                $totalTime = $diff->format('%H:%I:%S'); // HH:MM
            } catch (\Exception $e) {
                $totalTime = '-';
            }
        }

        // ✅ Total odometer calculation
        $totalOdometer = 0;
        if (!is_null($attendance->starting_km) && !is_null($attendance->ending_km)) {
            $totalOdometer = $attendance->ending_km - $attendance->starting_km;
	}
	$status = strtolower($attendance->status ?? '-');
	$startingRemark = '-';
        $leaveRemark = '-';

        if ($status === 'leave') {
            $leaveRemark = $attendance->starting_remarks ?? '-';
            $startingRemark = '-';
        } else {
            $startingRemark = $attendance->starting_remarks ?? '-';
        }
        return [
             self::$rowNumber,
            \Carbon\Carbon::parse($attendance->date)->format('d-m-Y'),
            $attendance->employee->name ?? 'N/A',
            $attendance->employee->employee_code ?? 'N/A',
            $attendance->punch_in ?? '-',
         $startingRemark,//   $attendance->starting_remarks ?? '-',
            $attendance->starting_km !== null && $attendance->starting_km !== '' && $attendance->starting_km !=0  ? $attendance->starting_km : '0',
    $attendance->punch_out ?? '-',
    $attendance->ending_remarks ?? '-',
    $attendance->ending_km !== null && $attendance->ending_km !== '' && $attendance->ending_km !=0 ? $attendance->ending_km : '0',
$totalTime,
 $totalOdometer > 0 ? $totalOdometer :'0',
 ucfirst($attendance->status ?? '-'),
 $leaveRemark,
            $attendance->created_at ? $attendance->created_at->format('d-m-Y H:i:s') : '-',
        ];
    }
}


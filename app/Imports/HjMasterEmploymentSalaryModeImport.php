<?php

namespace App\Imports;

use App\Entities\HousingJourney\HjMasterEmploymentSalaryMode;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class HjMasterEmploymentSalaryModeImport implements ToModel, WithHeadingRow
{
    /**
     * Import Employment Salary Mode
     */
    public function model(array $row)
    {
        if ($row['id'] != "") {
            return new HjMasterEmploymentSalaryMode([
                "name" => $row['name'],
                "handle" => $row['handle'],
                "is_active" => $row['is_active'],
            ]);
        }
    }
}

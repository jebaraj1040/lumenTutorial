<?php

namespace App\Imports;

use App\Entities\HousingJourney\HjMasterBranch;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class HjMasterBranchImport implements ToModel, WithHeadingRow
{
    /**
     * Import Branch Master
     */
    public function model(array $row)
    {
        if ($row['id'] != "") {
            return new HjMasterBranch([
                "name" => $row['name'],
                "handle" => $row['handle'],
                "code" => $row['code'],
                "is_active" => $row['is_active'],
            ]);
        }
    }
}

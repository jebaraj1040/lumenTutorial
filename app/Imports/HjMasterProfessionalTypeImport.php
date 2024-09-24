<?php

namespace App\Imports;

use App\Entities\HousingJourney\HjMasterProfessionalType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class HjMasterProfessionalTypeImport implements ToModel, WithHeadingRow
{
    /**
     * Import Professional Type
     */
    public function model(array $row)
    {
        if ($row['id'] != "") {
            return new HjMasterProfessionalType([
                "name" => $row['name'],
                "handle" => $row['handle'],
                "is_active" => $row['is_active'],
            ]);
        }
    }
}

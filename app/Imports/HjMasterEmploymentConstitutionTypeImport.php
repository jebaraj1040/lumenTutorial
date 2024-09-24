<?php

namespace App\Imports;

use App\Entities\HousingJourney\HjMasterEmploymentConstitutionType;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class HjMasterEmploymentConstitutionTypeImport implements ToModel, WithHeadingRow
{
    /**
     * Import Employment Constitution Type
     */
    public function model(array $row)
    {
        if ($row['id'] != "") {
            return new HjMasterEmploymentConstitutionType([
                "name" => $row['name'],
                "handle" => $row['handle'],
                "is_active" => $row['is_active'],
            ]);
        }
    }
}

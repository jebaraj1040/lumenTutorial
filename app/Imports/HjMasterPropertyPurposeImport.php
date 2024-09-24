<?php

namespace App\Imports;

use App\Entities\HousingJourney\HjMasterPropertyPurpose;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class HjMasterPropertyPurposeImport implements ToModel, WithHeadingRow
{
    /**
     * Import Property Purpose
     */
    public function model(array $row)
    {
        if ($row['id'] != "") {
            return new HjMasterPropertyPurpose([
                "name" => $row['name'],
                "handle" => $row['handle'],
                "is_active" => $row['is_active'],
            ]);
        }
    }
}

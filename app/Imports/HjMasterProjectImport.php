<?php

namespace App\Imports;

use App\Entities\HousingJourney\HjMasterProject;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class HjMasterProjectImport implements ToModel, WithHeadingRow
{
    /**
     * Import Project Master
     */
    public function model(array $row)
    {
        if ($row) {
            return new HjMasterProject([
                "code" => $row['code'],
                "name" => $row['name'],
                "name_handle" => $this->handlePreparation($row['name']),
                "builder" => $row['name'],
                "builder_handle" => $this->handlePreparation($row['name']),
                "pincode_id" => 1,
                "is_approved" => 1,
                "is_active" => 1,
            ]);
        }
    }

    public function handlePreparation($handleValue)
    {
        $handle = strtolower($handleValue);
        if (preg_replace('/\*-\+\*-*/', ' ', $handle)) {
            $handle = preg_replace('/[+\/]/', '', $handle);
        }
        return $handle = str_replace('--', '-', str_replace(' ', '-', $handle));
    }
}

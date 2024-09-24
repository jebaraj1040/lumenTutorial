<?php

namespace App\Imports;

use App\Entities\HousingJourney\HjMasterCompany;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class HjMasterCompanyImport implements ToModel, WithHeadingRow
{
    /**
     * Import Company Master
     */
    public function model(array $row)
    {
        if ($row) {
            return new HjMasterCompany([
                "name" => $row['name'],
                "handle" => $this->handlePreparation($row['name']),
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

<?php

namespace App\Imports;

use App\Entities\HousingJourney\HjMasterIfsc;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HjMasterIfscImport implements ToModel, WithHeadingRow
{
    /**
     * Import Pincode Master
     */
    public function model(array $row)
    {
        if ($row) {
            return new HjMasterIfsc([
                "bank_code" => $row['bankcd'],
                "bank_name" => $row['bankname'],
                "location" => $row['location'],
                "ifsc" => $row['ifsc'],
                "state" => $row['state'],
                "refpk" => $row['refpk'],
            ]);
        }
    }
}

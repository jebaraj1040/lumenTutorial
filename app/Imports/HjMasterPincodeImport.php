<?php

namespace App\Imports;

use App\Entities\HousingJourney\HjMasterPincode;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class HjMasterPincodeImport implements ToModel, WithHeadingRow
{
    /**
     * Import Pincode Master
     */
    public function model(array $row)
    {
        if ($row) {
            return new HjMasterPincode([
                "code" => $row['code'],
                "area" => $row['area'],
                "city" => $row['city'],
                "district" => $row['district'],
                "state" => $row['state'],
                "is_serviceable" => $row['is_serviceable'],
                "is_active" => $row['is_active'],
            ]);
        }
    }
}

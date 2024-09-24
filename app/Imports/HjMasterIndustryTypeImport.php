<?php

namespace App\Imports;

use App\Entities\HousingJourney\HjMasterIndustryType;
use App\Repositories\HousingJourney\MasterIndustryTypeRepository;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;


class HjMasterIndustryTypeImport implements ToModel, WithHeadingRow
{
    /**
     * Import Industry Type Master
     */
    public function model(array $row)
    {
        if ($row) {
            $indusTyRepo = new MasterIndustryTypeRepository();
            return new HjMasterIndustryType([
                "name" => $row['cat'],
                "handle" => $this->handlePreparation($row['cat']),
                "industry_segment_id" => $indusTyRepo->getIndustrySegmentId($row['instype']),
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

<?php

namespace App\Imports;

use App\Models\CompanyEmployee;
use Maatwebsite\Excel\Concerns\ToModel;

class CompnayEmployeeImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new CompanyEmployee([
            //
        ]);
    }
}

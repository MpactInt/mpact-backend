<?php

namespace App\Exports;

use App\Models\CompanyEmployee;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CompanyEmployeeExport implements FromCollection,WithHeadings
{
    protected $id;

    function __construct($id) {
        $this->id = $id;
    }

    public function headings(): array
    {
        return ["Email", "First Name", "Last Name", "Role", "Last Login"];
    }
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $user = Auth::guard('api')->user();
        $auth_id = CompanyEmployee::where('user_id', $user->id)->first()->id;
        $res = CompanyEmployee::select('users.email', 'company_employees.first_name','company_employees.last_name','company_employees.role','users.last_login')
            ->join('users', 'company_employees.user_id', 'users.id')
            ->where('company_id', $this->id)
            ->where('company_employees.id', '!=', $auth_id)
            ->get();
        return collect($res);
    }
}

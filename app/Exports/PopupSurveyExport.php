<?php

namespace App\Exports;

use App\Models\PopupSurveyQuestion;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PopupSurveyExport implements FromCollection,WithHeadings
{
    public function headings(): array
    {
        return ["Company employee id", "User id", "Question", "Answer"];
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        //return PopupSurveyQuestion::all();

        $res = PopupSurveyQuestion::select('company_employees.id', 'company_employees.user_id', 'popup_survey_questions.question','popup_survey_answers.answer')
            ->join('popup_survey_answers', 'popup_survey_answers.question_id', 'popup_survey_questions.id')
            ->join('company_employees', 'popup_survey_answers.company_employee_id', 'company_employees.id')
            ->get();
        return collect($res);
    }
}
